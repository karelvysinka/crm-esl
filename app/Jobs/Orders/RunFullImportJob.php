<?php

namespace App\Jobs\Orders;

use App\Models\OpsActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;

class RunFullImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $activityId;
    public ?int $pagesLimit;

    public function __construct(int $activityId, ?int $pagesLimit = null)
    {
        $this->activityId = $activityId;
        $this->pagesLimit = $pagesLimit;
    }

    public function handle(): void
    {
        $activity = OpsActivity::find($this->activityId);
        if (!$activity) { return; }
        $meta = $activity->meta ?? [];
        // Concurrency guard: if another full import (web or cli) queued/running in last 60 min (excluding self), skip
        $recent = OpsActivity::query()
            ->whereIn('type',[ 'orders.full_import.web','orders.full_import','orders.full_import.manual' ])
            ->whereIn('status',['queued','running'])
            ->where('id','<>',$activity->id)
            ->where('created_at','>=',now()->subHour())
            ->exists();
        if ($recent) {
            $activity->status = 'skipped';
            $meta['skipped_reason'] = 'another_import_in_progress';
            $activity->meta = $meta; $activity->save();
            return;
        }
        // Mark running before launching external process
        $activity->status='running'; $activity->save();
        $cmd = ['php','artisan','orders:import-full'];
        if ($this->pagesLimit) { $cmd[] = '--pages='.$this->pagesLimit; }
        $proc = new Process($cmd, base_path());
        $proc->setTimeout(3600);
        $proc->run();
        $fullOut = $proc->getOutput()."\n".$proc->getErrorOutput();
        $meta['import_exit'] = $proc->getExitCode();
        $meta['import_tail'] = substr($fullOut, -4000);
        // Parse basic metrics from output line e.g.: Full import done pages=3 seen=60 new=5 durationMs=12345
        if (preg_match('/Full import done pages=(\d+) seen=(\d+) new=(\d+) durationMs=(\d+)/', $fullOut, $m)) {
            $meta['pages'] = (int)$m[1];
            $meta['orders_seen'] = (int)$m[2];
            $meta['orders_new'] = (int)$m[3];
            $meta['duration_ms'] = (int)$m[4];
        }
        $activity->meta = $meta;
        $activity->status = $proc->isSuccessful() ? 'success' : 'error';
        $activity->save();
    }
}
