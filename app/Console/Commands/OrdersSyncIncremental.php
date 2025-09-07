<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Orders\OrderScrapeClient;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use App\Models\OrderStateChange;
use Illuminate\Support\Facades\DB;
use App\Models\OpsActivity;
use Throwable;

class OrdersSyncIncremental extends Command
{
    protected $signature = 'orders:sync-incremental {--dry-run}';
    protected $description = 'Incremental sync – scan first page for new orders';

    public function handle(OrderScrapeClient $client): int
    {
        $started = microtime(true);
        $activity = OpsActivity::create(['type'=>'orders.incremental_sync','status'=>'running']);
        $dry = $this->option('dry-run');
    $new=0; $updated=0; $errors=0; $seen=0; $last=null; $detailsFetched=0; $itemsInserted=0; $statesInserted=0; $hashUpdates=0; $integrityMismatches=0;
        try {
            if (config('orders.fake_mode') && !(env('ORDERS_SYNC_USER') && env('ORDERS_SYNC_PASSWORD')) && !env('ORDERS_SYNC_COOKIE')) {
                $durationMs = (int)round((microtime(true)-$started)*1000);
                $activity->update(['status'=>'success','meta'=>['orders_seen'=>0,'orders_new'=>0,'orders_updated'=>0,'duration_ms'=>$durationMs,'dry_run'=>$dry,'mode'=>'fake_skip']]);
                $this->info('Incremental sync skipped (fake mode, no credentials).');
                return self::SUCCESS;
            }
            $client->login();
            $rows = $client->fetchListingPage(1);
            foreach ($rows as $dto) {
                $seen++;
                $last = $dto->orderNumber;
                $order = Order::where('order_number',$dto->orderNumber)->first();
                    // Pokud máme internalId z listingu a order ještě nemá external_edit_id, ulož ho hned (light update)
                    if ($order && $order->external_edit_id === null && ($dto->internalId ?? null)) {
                        $order->external_edit_id = $dto->internalId;
                        if (!$dry) { $order->save(); }
                    }
                    if (!$order) {
                    $new++;
                    $detail = $client->fetchDetail($dto->orderNumber, $dto->internalId); $detailsFetched++;
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
                            foreach ($detail->items as $it) { OrderItem::create(array_merge($it, ['order_id'=>$order->id])); $itemsInserted++; }
                            $codes = $detail->row->stateCodes;
                            if (count($codes) === 1) {
                                OrderStateChange::create([
                                    'order_id'=>$order->id,'old_code'=>null,'new_code'=>$codes[0],'changed_at'=>now(),'detected_at'=>now(),'source_snapshot_hash'=>$detail->rawHash
                                ]); $statesInserted++;
                            } elseif (count($codes) > 1) {
                                $prev = null; $tsBase = now();
                                foreach ($codes as $code) {
                                    OrderStateChange::create([
                                        'order_id'=>$order->id,'old_code'=>$prev,'new_code'=>$code,'changed_at'=>$tsBase,'detected_at'=>now(),'source_snapshot_hash'=>$detail->rawHash
                                    ]); $statesInserted++; $prev=$code;
                                }
                            }
                            $sum = $order->items()->sum('total_vat_cents');
                            if ($sum !== (int)$order->total_vat_cents) { $integrityMismatches++; }
                        });
                    }
                } else {
                    // Basic update if totals or completion changed
                    $changed = false;
                    if ($order->total_vat_cents !== $dto->totalVatCents || $order->is_completed !== $dto->isCompleted) {
                        $changed = true;
                        if (!$dry) {
                            $order->total_vat_cents = $dto->totalVatCents;
                            $order->is_completed = $dto->isCompleted;
                            $order->save();
                        }
                    }
                    // Detail hash/state detection for existing orders (lightweight) only if potential change
                    if (!$dry) {
                        $detail = $client->fetchDetail($dto->orderNumber, $dto->internalId); $detailsFetched++;
                        if ($order->source_raw_hash !== $detail->rawHash) {
                            $hashUpdates++;
                            DB::transaction(function() use ($order, $detail, &$itemsInserted, &$statesInserted) {
                                // Diff items by (name, line_type, unit_price_vat_cents, quantity)
                                $existing = $order->items()->get()->keyBy(function($i){ return md5($i->name.'|'.$i->line_type.'|'.$i->unit_price_vat_cents.'|'.$i->quantity); });
                                $incoming = collect($detail->items);
                                $seenKeys = [];
                                $incoming->each(function($it) use ($order,&$itemsInserted,$existing,&$seenKeys){
                                    $k = md5(($it['name']??'').'|'.($it['line_type']??'').'|'.($it['unit_price_vat_cents']??0).'|'.($it['quantity']??0));
                                    $seenKeys[] = $k;
                                    if (isset($existing[$k])) {
                                        // update totals if changed
                                        $model = $existing[$k];
                                        $dirty = false;
                                        foreach (['total_vat_cents','vat_rate_percent'] as $fld) {
                                            if ($model->{$fld} != $it[$fld]) { $model->{$fld} = $it[$fld]; $dirty=true; }
                                        }
                                        if ($dirty) { $model->save(); }
                                    } else {
                                        OrderItem::create(array_merge($it,['order_id'=>$order->id])); $itemsInserted++;
                                    }
                                });
                                // delete removed lines (hard delete for now)
                                $existing->each(function($model,$k) use ($seenKeys){ if(!in_array($k,$seenKeys,true)){ $model->delete(); } });
                                // State change detection: compare last stored state code to new last code
                                $newCodes = $detail->row->stateCodes;
                if (!empty($newCodes)) {
                                    $lastStored = $order->stateChanges()->orderByDesc('changed_at')->value('new_code');
                                    $tail = $newCodes;
                                    if ($lastStored) {
                                        // find position of lastStored and take following codes
                                        $idx = array_search($lastStored, $newCodes, true);
                                        if ($idx !== false) { $tail = array_slice($newCodes, $idx+1); }
                                    }
                                    $prev = $lastStored;
                                    foreach ($tail as $code) {
                                        OrderStateChange::create([
                                            'order_id'=>$order->id,
                                            'old_code'=>$prev,
                                            'new_code'=>$code,
                                            'changed_at'=>now(),
                                            'detected_at'=>now(),
                                            'source_snapshot_hash'=>$detail->rawHash,
                                        ]); $statesInserted++; $prev = $code;
                    $order->last_state_code = $code; // advance denormalized
                                    }
                                }
                                $order->source_raw_hash = $detail->rawHash;
                                $order->save();
                            });
                        }
                    }
                    if ($changed) { $updated++; }
                }
            }
            $durationMs = (int)round((microtime(true)-$started)*1000);
            $activity->update(['status'=>'success','meta'=>[
        'orders_seen'=>$seen,'orders_new'=>$new,'orders_updated'=>$updated,'last_order'=>$last,'duration_ms'=>$durationMs,'dry_run'=>$dry,
        'details_fetched'=>$detailsFetched,'items_inserted'=>$itemsInserted,'states_inserted'=>$statesInserted,'hash_updates'=>$hashUpdates,
        'integrity_mismatches'=>$integrityMismatches
            ]]);
            $this->info("Incremental sync done seen=$seen new=$new updated=$updated last=$last");
            return self::SUCCESS;
        } catch (Throwable $e) {
            $errors++;
            $activity->update(['status'=>'error','meta'=>['error'=>$e->getMessage()]]);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
