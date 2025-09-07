<?php

namespace App\Jobs\Orders;

use App\Models\OrderSyncSetting;
use App\Models\OrderSyncRun;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class AutoSyncOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // fail fast, next minute tries again

    public function handle(): void
    {
        if (!Schema::hasTable('order_sync_settings') || !Schema::hasTable('order_sync_runs')) {
            return; // migrations not ready
        }
        $setting = OrderSyncSetting::first();
        if (!$setting || !$setting->enabled) { return; }
        $lastRun = OrderSyncRun::orderByDesc('id')->first();
        if ($lastRun && $lastRun->started_at && now()->diffInMinutes($lastRun->started_at) < $setting->interval_minutes) {
            return; // interval not elapsed
        }
        $run = OrderSyncRun::create([
            'started_at'=>now(),
            'status'=>'running',
            'message'=>null,
        ]);
        $countBefore = Order::count();
        try {
            // Reuse existing incremental sync command
            Artisan::call('orders:sync-incremental');
            $countAfter = Order::count();
            $newOrders = max(0, $countAfter - $countBefore);
            $run->update([
                'finished_at'=>now(),
                'status'=>'success',
                'new_orders'=>$newOrders,
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'finished_at'=>now(),
                'status'=>'failed',
                'message'=>substr($e->getMessage(),0,480)
            ]);
        }
    }
}
