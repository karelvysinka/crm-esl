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
        $cmd = ['php','artisan','orders:import-full'];
        if ($this->pagesLimit) { $cmd[] = '--pages='.$this->pagesLimit; }
        $proc = new Process($cmd, base_path());
        $proc->setTimeout(3600);
        $proc->run();
        $meta['import_exit'] = $proc->getExitCode();
        $meta['import_tail'] = substr($proc->getOutput(), -4000);
        $activity->meta = $meta;
        $activity->status = $proc->isSuccessful() ? 'success' : 'error';
        $activity->save();
    }
}
