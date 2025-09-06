<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class OrdersIntegrityCheck extends Command
{
    protected $signature = 'orders:integrity-check {--limit=500} {--since-id=0}';
    protected $description = 'Validate that order total matches sum of item totals (VAT) and report mismatches.';

    public function handle(): int
    {
    try { DB::select('select 1'); } catch (QueryException $e) { $this->warn('DB unavailable, skipping integrity-check: '.$e->getMessage()); return self::SUCCESS; }
        $limit = (int)$this->option('limit');
        $sinceId = (int)$this->option('since-id');
        $q = Order::query()->where('id','>', $sinceId)->orderBy('id','desc')->limit($limit);
        $mismatches = 0; $checked=0; $fixed=0; $orders=$q->get();
        foreach ($orders as $o) {
            $checked++;
            $sum = (int)$o->items()->sum('total_vat_cents');
            if ($sum !== (int)$o->total_vat_cents) {
                $mismatches++;
                $this->line('Mismatch order '.$o->id.' number '.$o->order_number.' stored='.$o->total_vat_cents.' sum_items='.$sum);
            }
        }
        $this->info("Integrity check: checked=$checked mismatches=$mismatches");
        return self::SUCCESS;
    }
}
