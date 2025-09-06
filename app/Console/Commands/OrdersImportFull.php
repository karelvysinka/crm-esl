<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Orders\OrderScrapeClient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStateChange;
use App\Models\OpsActivity;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrdersImportFull extends Command
{
    protected $signature = 'orders:import-full 
        {--dry-run : Do not persist, only simulate}
        {--pages= : Limit number of listing pages (overrides config max_pages)}
        {--timeout=600 : Max seconds before abort}
        {--sleep=0 : Extra sleep ms between pages (adds to jitter)}
        {--verbose-http : Verbose per-page debug output}';
    protected $description = 'Full paginated scrape of orders listing with detail fetch for new orders';

    public function handle(OrderScrapeClient $client): int
    {
        $started = microtime(true);
        // Early DB availability check (graceful exit if DB down) BEFORE creating OpsActivity
        try { DB::select('select 1'); } catch (\Throwable $dbEx) {
            $this->error('Database unavailable – cannot run full import: '.$dbEx->getMessage());
            return self::FAILURE;
        }
        $activity = OpsActivity::create(['type' => 'orders.full_import', 'status' => 'running']);
    $dry = $this->option('dry-run');
    $limitPagesOpt = $this->option('pages');
    $maxRuntime = (int)$this->option('timeout');
    $extraSleep = (int)$this->option('sleep');
    $verbose = (bool)$this->option('verbose-http');
    $new = 0; $seen = 0; $pages = 0; $errors = 0; $itemsInserted = 0; $statesInserted = 0; $detailsFetched = 0; $integrityMismatches = 0;
        try {
            $fake = config('orders.fake_mode');
            $haveCreds = env('ORDERS_SYNC_USER') && env('ORDERS_SYNC_PASSWORD') || env('ORDERS_SYNC_COOKIE');
            if ($fake && !$haveCreds) {
                // Seed 5 sample orders if table empty
                if (Order::count() === 0 && !$dry) {
                    for ($i=1;$i<=5;$i++) {
                        $num = 'FAKE'.date('Ymd').sprintf('%03d',$i);
                        $order = Order::create([
                            'order_number'=>$num,
                            'order_created_at'=>now()->subMinutes(5*$i),
                            'total_vat_cents'=>random_int(10000,35000),
                            'currency'=>'CZK',
                            'fetched_at'=>now(),
                            'is_completed'=>($i%2===0),
                            'source_raw_hash'=>sha1($num),
                            'last_state_code'=>'P'
                        ]);
                        $items = [
                            ['name'=>'Demo položka '.$i,'quantity'=>1,'unit_price_vat_cents'=>random_int(5000,15000),'vat_rate_percent'=>21,'discount_percent'=>null,'total_ex_vat_cents'=>null,'total_vat_cents'=>null,'line_type'=>'product','currency'=>'CZK'],
                            ['name'=>'Doprava','quantity'=>1,'unit_price_vat_cents'=>1500,'vat_rate_percent'=>21,'discount_percent'=>null,'total_ex_vat_cents'=>null,'total_vat_cents'=>1500,'line_type'=>'shipping','currency'=>'CZK']
                        ];
                        foreach($items as $it){ OrderItem::create(array_merge($it,['order_id'=>$order->id])); $itemsInserted++; }
                        OrderStateChange::create(['order_id'=>$order->id,'old_code'=>null,'new_code'=>'P','changed_at'=>now()->subMinutes(5*$i),'detected_at'=>now(),'source_snapshot_hash'=>$order->source_raw_hash]);
                        $statesInserted++;
                        $new++;
                    }
                }
                $durationMs = (int)round((microtime(true)-$started)*1000);
                $activity->update(['status'=>'success','meta'=>[
                    'pages'=>$pages,'orders_seen'=>$seen,'orders_new'=>$new,'duration_ms'=>$durationMs,'dry_run'=>$dry,'details_fetched'=>$detailsFetched,'items_inserted'=>$itemsInserted,'states_inserted'=>$statesInserted,'integrity_mismatches'=>$integrityMismatches,'mode'=>'fake'
                ]]);
                $this->info('Full import (fake mode) seeded '.$new.' sample orders.');
                return self::SUCCESS;
            }
            $client->login();
            $maxPages = $limitPagesOpt !== null ? (int)$limitPagesOpt : (int)config('orders.full_import.max_pages', 30);
            if ($verbose) { $this->line("[debug] maxPages=$maxPages startTime=".date('H:i:s')); }
            $lastPageHash = null; $repeatStreak = 0;
            for ($page = 1; $page <= $maxPages; $page++) {
                $pages++;
                if ((microtime(true)-$started) > $maxRuntime) { $this->warn('Timeout reached, aborting loop.'); break; }
                if ($verbose) { $this->line("[debug] fetching page $page..."); }
                $rows = $client->fetchListingPage($page);
                if (empty($rows)) {
                    if ($page === 1) { $this->warn('Page 1 empty - possible parse/login issue.'); }
                    break; // assume end
                }
                // Detect repeating identical listing page (no pagination advance)
                $numbers = array_map(fn($d)=>$d->orderNumber,$rows);
                $pageHash = sha1(implode('|',$numbers));
                if ($lastPageHash !== null && $pageHash === $lastPageHash) {
                    $repeatStreak++;
                    if ($repeatStreak >= 2) { $this->warn("Detected repeating listing page twice -> breaking early at page $page"); break; }
                } else {
                    $repeatStreak = 0; $lastPageHash = $pageHash;
                }
                if ($verbose) { $this->line('[debug] page '.$page.' rows='.count($rows).' first='.($numbers[0]??'n/a')); }
                foreach ($rows as $dto) {
                    $seen++;
                    $exists = Order::where('order_number', $dto->orderNumber)->first();
                    if ($exists) { continue; }
                    $new++;
                    $detail = $client->fetchDetail($dto->orderNumber, $dto->internalId); // fetch detail for new orders
                    $detailsFetched++;
                    if (!$dry) {
                        DB::transaction(function() use ($detail, $dto, &$itemsInserted, &$statesInserted, &$integrityMismatches) {
                            $order = Order::create([
                                'order_number' => $dto->orderNumber,
                                'external_edit_id' => $dto->internalId,
                                    'order_created_at' => $dto->createdAt,
                                'total_vat_cents' => $detail->row->totalVatCents ?: $dto->totalVatCents,
                                'currency' => $detail->row->currency ?: $dto->currency,
                                'fetched_at' => now(),
                                'is_completed' => $detail->row->isCompleted,
                                'source_raw_hash' => $detail->rawHash,
                                'last_state_code' => !empty($detail->row->stateCodes) ? end($detail->row->stateCodes) : null,
                            ]);
                            foreach ($detail->items as $it) {
                                OrderItem::create(array_merge($it, ['order_id'=>$order->id]));
                                $itemsInserted++;
                            }
                            // integrity mismatch check (sum items vs order total)
                            $sum = $order->items()->sum('total_vat_cents');
                            if ($sum !== (int)$order->total_vat_cents) { $integrityMismatches++; }
                            // initial state sequence -> create sequential changes from first to last
                            $codes = $detail->row->stateCodes;
                            if (count($codes) === 1) {
                                OrderStateChange::create([
                                    'order_id'=>$order->id,
                                    'old_code'=>null,
                                    'new_code'=>$codes[0],
                                    'changed_at'=>now(),
                                    'detected_at'=>now(),
                                    'source_snapshot_hash'=>$detail->rawHash,
                                ]);
                                $statesInserted++;
                            } elseif (count($codes) > 1) {
                                $prev = null; $tsBase = now();
                                foreach ($codes as $idx=>$code) {
                                    OrderStateChange::create([
                                        'order_id'=>$order->id,
                                        'old_code'=>$prev,
                                        'new_code'=>$code,
                                        'changed_at'=>$tsBase, // until real timestamps extracted
                                        'detected_at'=>now(),
                                        'source_snapshot_hash'=>$detail->rawHash,
                                    ]);
                                    $statesInserted++; $prev = $code;
                                }
                            }
                        });
                    }
                }
                // Heuristic stop: if no new found on this page and row count small -> early break
                if ($page > 1 && count($rows) < 10 && ($detailsFetched === 0 || $new === 0)) { $this->line('[debug] early break heuristic'); break; }
                $jitter = random_int(200,500);
                if ($extraSleep > 0) { $jitter += $extraSleep; }
                usleep($jitter*1000);
            }
            $durationMs = (int)round((microtime(true)-$started)*1000);
            $activity->update(['status'=>'success','meta'=>[
                'pages'=>$pages,'orders_seen'=>$seen,'orders_new'=>$new,'errors'=>$errors,'duration_ms'=>$durationMs,'dry_run'=>$dry,
                'details_fetched'=>$detailsFetched,'items_inserted'=>$itemsInserted,'states_inserted'=>$statesInserted,
                'integrity_mismatches'=>$integrityMismatches
            ]]);
            $this->info("Full import done pages=$pages seen=$seen new=$new durationMs=$durationMs");
            return self::SUCCESS;
        } catch (Throwable $e) {
            $errors++;
            $activity->update(['status'=>'error','meta'=>['error'=>$e->getMessage(),'trace_head'=>substr($e->getTraceAsString(),0,500)]]);
            $this->error('Failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
