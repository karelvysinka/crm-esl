<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrdersPurgeFake extends Command
{
    protected $signature = 'orders:purge-fake {--dry-run}';
    protected $description = 'Remove demo/fake seeded orders (order_number starts with FAKE) including items & state changes';

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $q = Order::where('order_number','LIKE','FAKE%');
        $count = $q->count();
        if ($count === 0) { $this->info('No fake orders found.'); return self::SUCCESS; }
        if ($dry) { $this->info("Would delete $count fake orders (dry-run)"); return self::SUCCESS; }
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        $q->chunkById(100, function($chunk) use ($bar) {
            foreach ($chunk as $order) {
                DB::transaction(function() use ($order) {
                    $order->items()->delete();
                    $order->stateChanges()->delete();
                    $order->delete();
                });
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine();
        $this->info("Purged $count fake orders.");
        return self::SUCCESS;
    }
}
