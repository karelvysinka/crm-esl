<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductPriceChange;
use App\Models\OpsActivity;
use App\Services\Products\CanonicalBuilder;
use App\Services\Products\HeurekaProductStream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductsImportFull extends Command
{
    protected $signature = 'products:import-full {file=docs/feeds/heureka.xml}';
    protected $description = 'Full import / upsert produktů z Heureka XML feedu';

    public function handle(): int
    {
        $activity = OpsActivity::create([
            'type' => 'products.full_import',
            'status' => 'running',
            'started_at' => now(),
            'meta' => []
        ]);
        $started = hrtime(true);
        $file = base_path($this->argument('file'));
        if (!is_file($file)) {
            $this->error("Soubor nenalezen: $file");
            $activity->update(['status'=>'failed','finished_at'=>now(),'log_excerpt'=>'file missing']);
            return 1;
        }

        $stream = new HeurekaProductStream($file);
        $new = 0; $updated = 0; $unchanged = 0; $priceChanges = 0; $errors = 0;
        $bufferInsert = []; $bufferUpdate = [];
        $chunkSize = 500;

        foreach ($stream as $item) {
            try {
                $canonical = CanonicalBuilder::build($item);
                $hash = CanonicalBuilder::hash($canonical);
                $existing = Product::where('external_id', $canonical['external_id'])->first();
                if (!$existing) {
                    $bufferInsert[] = array_merge($canonical, [
                        'hash_payload' => $hash,
                        'first_imported_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $new++;
                } else {
                    if ($existing->hash_payload !== $hash) {
                        // Detect price change
                        if ($existing->price_vat_cents !== $canonical['price_vat_cents']) {
                            ProductPriceChange::create([
                                'product_id' => $existing->id,
                                'old_price_cents' => $existing->price_vat_cents,
                                'new_price_cents' => $canonical['price_vat_cents'],
                                'changed_at' => now(),
                            ]);
                            $priceChanges++;
                            $canonical['last_price_changed_at'] = now();
                        }
                        $bufferUpdate[] = [$existing, array_merge($canonical, [
                            'hash_payload' => $hash,
                            'last_synced_at' => now(),
                            'updated_at' => now(),
                        ])];
                        $updated++;
                    } else {
                        $unchanged++;
                    }
                }

                if (($new + $updated + $unchanged + $errors) % $chunkSize === 0) {
                    $this->flushBuffers($bufferInsert, $bufferUpdate);
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->warn('Chyba položky: '.$e->getMessage());
            }
        }

        $this->flushBuffers($bufferInsert, $bufferUpdate);

        $durationMs = (int) ((hrtime(true)-$started)/1_000_000);
        $summary = "New=$new Updated=$updated Unchanged=$unchanged PriceChanges=$priceChanges Errors=$errors";
        $this->info("Hotovo. $summary");
        $activity->update([
            'status' => $errors>0? 'completed_with_errors':'success',
            'finished_at' => now(),
            'duration_ms' => $durationMs,
            'meta' => [
                'new'=>$new,'updated'=>$updated,'unchanged'=>$unchanged,'price_changes'=>$priceChanges,'errors'=>$errors
            ],
            'log_excerpt' => $summary
        ]);
        return 0;
    }

    private function flushBuffers(array &$insert, array &$update): void
    {
        if ($insert) {
            DB::table('products')->insert($insert);
            $insert = [];
        }
        if ($update) {
            foreach ($update as [$model, $data]) {
                $model->fill($data);
                $model->save();
            }
            $update = [];
        }
    }
}
