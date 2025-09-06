<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Orders\OrderScrapeClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class OrdersBackfillItems extends Command
{
    protected $signature = 'orders:backfill-items {--limit=200} {--since-id=0} {--dry-run}';
    protected $description = 'Re-fetch detail (items + states) for existing orders missing items or having zero items.';

    public function handle(OrderScrapeClient $client): int
    {
        $dry = $this->option('dry-run');
        $limit = (int)$this->option('limit');
        $sinceId = (int)$this->option('since-id');
        // DB dostupnost – bezpečný exit když není DB (např. mimo kontejner)
        try { DB::select('select 1'); } catch (QueryException $e) { $this->warn('DB unavailable, skipping backfill: '.$e->getMessage()); return self::SUCCESS; }

        // Nahrubo vezmeme víc kandidátů (oversampling) a pak přefiltrujeme in-memory
        $raw = Order::query()->where('id','>', $sinceId)
            ->orderByDesc('id')
            ->limit($limit * 3)
            ->with(['items:id,order_id,line_type'])
            ->get();

        $candidates = [];
        foreach ($raw as $ord) {
            $cnt = $ord->items->count();
            if ($cnt === 0) { $candidates[] = $ord; }
            elseif ($cnt <= 3 && !$ord->items->contains(fn($i)=>$i->line_type === 'product')) { $candidates[] = $ord; }
            elseif ($cnt <= 2) { $candidates[] = $ord; }
            elseif ($ord->external_edit_id === null) { $candidates[] = $ord; }
            if (count($candidates) >= $limit) { break; }
        }

        if (!$candidates) { $this->info('Backfill: no candidates.'); return self::SUCCESS; }

        $processed=0; $updated=0; $skipped=0; $errors=0; $insertedItems=0; $replaced=0; $refetched=0;
        $client->login();
        foreach ($candidates as $order) {
            $processed++;
            $hasItems = $order->items->count();
            if ($hasItems > 3) { $skipped++; continue; }
            if ($hasItems > 0 && $order->items->contains(fn($i)=>$i->line_type === 'product')) { $skipped++; continue; }
            try {
                $detail = $client->fetchDetail($order->order_number, $order->external_edit_id);
                $newItems = $detail->items;
                if (empty($newItems)) { $skipped++; continue; }
                if ($dry) { $updated++; continue; }
                DB::transaction(function() use ($order,$newItems,&$insertedItems,&$replaced) {
                    foreach ($order->items as $it) { $it->delete(); }
                    foreach ($newItems as $it) { OrderItem::create(array_merge($it,['order_id'=>$order->id])); $insertedItems++; }
                    $replaced++;
                });
                $refetched++; $updated++;
            } catch (\Throwable $e) {
                $errors++; $this->error('Order '.$order->id.' error: '.$e->getMessage());
            }
        }
        $this->info("Backfill done candidates=".count($candidates)." processed=$processed refetched=$refetched updated=$updated skipped=$skipped replaced=$replaced items_inserted=$insertedItems errors=$errors");
        return self::SUCCESS;
    }
}
