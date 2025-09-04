<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Run Laravel schedule every minute via external cron: php artisan schedule:run
        // Backup schedule (can be toggled via config/backup.php custom keys)
        if (config('backup.schedule.enabled', false)) {
            $schedule->command('backup:run')->cron(config('backup.schedule.cron', '0 3 * * *'))
                ->onOneServer()
                ->withoutOverlapping();
        }

        // Optional cleanup old backups weekly
        if (config('backup.schedule.cleanup_enabled', true)) {
            $schedule->command('backup:clean')->weeklyOn(1, '4:00');
        }

        // ActiveCampaign sync automation: run a small batch every minute if enabled
        $schedule->job(new \App\Jobs\ActiveCampaignSyncJob(200, false))
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        // Ops: evaluate backup health periodically
            // Ops health evaluation every 10 minutes
            $schedule->job(new \App\Jobs\Ops\EvaluateBackupHealthJob())->everyTenMinutes()->onQueue('ops');
            // (Optional) Weekly automatic verify restore (can be toggled by env OPS_VERIFY_AUTO)
            if (env('OPS_VERIFY_AUTO', true)) {
                $schedule->call(function(){
                    // Dispatch verify restore activity wrapper only if write actions allowed
                    if (!env('OPS_ALLOW_WRITE', false)) return;
                    $act = \App\Models\OpsActivity::create(['type'=>'verify_restore','status'=>'queued']);
                    dispatch(new \App\Jobs\Ops\RunVerifyRestoreJob($act->id));
                })->weeklyOn(7,'03:15');
            }
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
