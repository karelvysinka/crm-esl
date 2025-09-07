<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Orders\OrderScrapeClient;
use App\Models\Order;
use App\Models\OpsActivity;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrdersReconcileRecent extends Command
{
    protected $signature = 'orders:reconcile-recent {--pages=5} {--dry-run}';
    protected $description = 'Rychlý reconciliation scan prvních N stránek pro dorovnání chybějících detailů / stavů';

    public function handle(OrderScrapeClient $client): int
    {
        $started = microtime(true);
        $activity = OpsActivity::create(['type'=>'orders.reconcile_recent','status'=>'running']);
        $dry = $this->option('dry-run');
    $pages = (int)$this->option('pages') ?: (int)config('orders.reconcile.pages',5);
    $seen=0;$details=0;$inserted=0;$hashUpdates=0;$states=0;$items=0;$errors=0;$integrityMismatches=0;
        try {
            if (config('orders.fake_mode') && !(env('ORDERS_SYNC_USER') && env('ORDERS_SYNC_PASSWORD')) && !env('ORDERS_SYNC_COOKIE')) {
                $durationMs = (int)round((microtime(true)-$started)*1000);
                $activity->update(['status'=>'success','meta'=>compact('pages','durationMs')+['mode'=>'fake_skip']]);
                $this->info('Reconcile skipped (fake mode, no credentials).');
                return self::SUCCESS;
            }
            $client->login();
            for ($p=1;$p<=$pages;$p++) {
                $listing = $client->fetchListingPage($p);
                if (empty($listing)) break;
                foreach ($listing as $row) {
                    $seen++;
                    $order = Order::where('order_number',$row->orderNumber)->first();
                    if ($order && $order->external_edit_id === null && ($row->internalId ?? null)) {
                        $order->external_edit_id = $row->internalId;
                        if (!$dry) { $order->save(); }
                    }
                    if (!$order) { continue; } // Only reconcile existing
                    // Skip if already has hash and is completed and fetched < 7d ago
                    if ($order->source_raw_hash && $order->is_completed && $order->fetched_at && $order->fetched_at->gt(now()->subDays(7))) { continue; }
                    $detail = $client->fetchDetail($row->orderNumber, $row->internalId); $details++;
                    if ($dry) { continue; }
                    DB::transaction(function() use ($order,$detail,&$hashUpdates,&$states,&$items,&$integrityMismatches) {
                        if ($order->source_raw_hash !== $detail->rawHash) { $hashUpdates++; }
                        // Diff items similar to incremental
                        $existing = $order->items()->get()->keyBy(function($i){ return md5($i->name.'|'.$i->line_type.'|'.$i->unit_price_vat_cents.'|'.$i->quantity); });
                        $incoming = collect($detail->items);
                        $seenKeys = [];
                        $incoming->each(function($it) use ($order,&$items,$existing,&$seenKeys){
                            $k = md5(($it['name']??'').'|'.($it['line_type']??'').'|'.($it['unit_price_vat_cents']??0).'|'.($it['quantity']??0));
                            $seenKeys[]=$k;
                            if (isset($existing[$k])) {
                                $model = $existing[$k]; $dirty=false;
                                foreach(['total_vat_cents','vat_rate_percent'] as $fld){ if($model->{$fld}!=$it[$fld]){ $model->{$fld}=$it[$fld]; $dirty=true; }}
                                if($dirty){ $model->save(); }
                            } else { $order->items()->create($it); $items++; }
                        });
                        $existing->each(function($model,$k) use ($seenKeys){ if(!in_array($k,$seenKeys,true)){ $model->delete(); } });
                        // States reconciliation
            $codes = $detail->row->stateCodes;
                        if (!empty($codes)) {
                            $lastStored = $order->stateChanges()->orderByDesc('changed_at')->value('new_code');
                            $tail = $codes;
                            if ($lastStored) {
                                $idx = array_search($lastStored,$codes,true);
                                if ($idx !== false) { $tail = array_slice($codes,$idx+1); }
                            }
                            $prev = $lastStored;
                            foreach ($tail as $code) {
                                $order->stateChanges()->create([
                                    'old_code'=>$prev,
                                    'new_code'=>$code,
                                    'changed_at'=>now(),
                                    'detected_at'=>now(),
                                    'source_snapshot_hash'=>$detail->rawHash,
                                ]); $prev=$code; $states++;
                $order->last_state_code = $code;
                            }
                        }
                        $order->total_vat_cents = $detail->row->totalVatCents ?: $order->total_vat_cents;
                        $order->is_completed = $detail->row->isCompleted;
                        $order->source_raw_hash = $detail->rawHash;
            $sum = $order->items()->sum('total_vat_cents');
            if ($sum !== (int)$order->total_vat_cents) { $integrityMismatches++; }
                        $order->save();
                    });
                }
            }
            $durationMs = (int)round((microtime(true)-$started)*1000);
        $activity->update(['status'=>'success','meta'=>compact('pages','seen','details','hashUpdates','states','items','durationMs','dry','integrityMismatches')]);
            $this->info("Reconcile done seen=$seen details=$details hashUpdates=$hashUpdates states=$states items=$items");
            return self::SUCCESS;
        } catch (Throwable $e) {
            $errors++; $activity->update(['status'=>'error','meta'=>['error'=>$e->getMessage()]]);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
