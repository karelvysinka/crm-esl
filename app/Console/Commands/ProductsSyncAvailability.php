<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductAvailabilityChange;
use App\Models\OpsActivity;
use App\Services\Products\AvailabilityDeltaStream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductsSyncAvailability extends Command
{
    protected $signature = 'products:sync-availability {file=docs/feeds/heureka-availability.xml} {--limit=0}';
    protected $description = 'Delta sync dostupností a skladů z availability feedu';

    public function handle(): int
    {
        $activity = OpsActivity::create([
            'type' => 'products.sync_availability',
            'status' => 'running',
            'started_at' => now(),
            'meta' => []
        ]);
        $started = hrtime(true);
        $file = $this->argument('file');
        if(!is_file($file)) {
            $this->error("Soubor nenalezen: $file");
            $activity->update(['status'=>'failed','finished_at'=>now(),'log_excerpt'=>'file missing']);
            return self::FAILURE;
        }
        $limit = (int)$this->option('limit');
        $stream = new AvailabilityDeltaStream($file);
        $updated = $skipped = $missing = 0;
        $changes = [];
        $now = now();
        foreach($stream as $row) {
            if($limit && ($updated+$skipped+$missing) >= $limit) break;
            $product = Product::where('external_id',$row['external_id'])->first();
            if(!$product) { $missing++; continue; }
            $oldCode = $product->availability_code;
            $oldStock = $product->stock_quantity;
            // Pokud nová availability_code je prázdná a nebyla žádná změna počtu kusů, přeskočit
            $hasChange = $oldCode !== $row['availability_code'] || $oldStock !== $row['stock_quantity'];
            if($hasChange && $row['availability_code'] === null && $oldCode === null && $oldStock === $row['stock_quantity']) {
                $hasChange = false;
            }
            if(!$hasChange) { $skipped++; continue; }
            try {
                DB::transaction(function() use (&$product,$row,$oldCode,$oldStock,&$changes,$now){
                    $product->availability_code = $row['availability_code'];
                    $product->availability_text = $row['availability_text'];
                    $product->stock_quantity = $row['stock_quantity'];
                    $product->availability_synced_at = $now;
                    $product->last_availability_changed_at = $now;
                    $product->save();
                    $changes[] = [
                        'product_id' => $product->id,
                        'old_code' => $oldCode,
                        'new_code' => $row['availability_code'],
                        'old_stock_qty' => $oldStock,
                        'new_stock_qty' => $row['stock_quantity'],
                        'changed_at' => $now,
                    ];
                });
                $updated++;
            } catch(Throwable $e) {
                $this->warn("Chyba update external_id={$row['external_id']}: {$e->getMessage()}");
            }
        }
        if($changes) {
            foreach(array_chunk($changes, 500) as $chunk) {
                ProductAvailabilityChange::insert($chunk);
            }
        }
        $changesCount = count($changes);
        $summary = "Updated=$updated Skipped=$skipped Missing=$missing ChangesInserted=$changesCount";
        $this->info($summary);
        $activity->update([
            'status' => 'success',
            'finished_at' => now(),
            'duration_ms' => (int)((hrtime(true)-$started)/1_000_000),
            'meta' => [
                'updated'=>$updated,'skipped'=>$skipped,'missing'=>$missing,'changes_inserted'=>$changesCount
            ],
            'log_excerpt' => $summary
        ]);
        return self::SUCCESS;
    }
}
