<?php

namespace App\Jobs\Ops;

use App\Services\Ops\BackupStatusService;
use App\Services\Ops\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateBackupHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 30;

    public function handle(BackupStatusService $statusService, AlertService $alertService)
    {
        if (!config('ops.alerts_enabled')) return;
        $db = $statusService->latestDbDumpStatus();
        $alertService->maybeStaleDbDumpAlert(fn() => $db);
        // Restic snapshot age check
        $repo = env('RESTIC_REPO');
        $pwd = env('RESTIC_PASSWORD');
    if ($repo && $pwd && is_dir($repo)) {
            $env = 'RESTIC_PASSWORD='.escapeshellarg($pwd).' ';
            $json = shell_exec($env.'restic -r '.escapeshellarg($repo).' snapshots --last --json 2>/dev/null');
            if ($json) {
                $data = json_decode($json,true);
                $snap = $data['snapshots'][0] ?? null;
                if ($snap && isset($snap['time'])) {
                    $snapTs = strtotime($snap['time']);
                    $ageMin = (time()-$snapTs)/60;
            $stale = (int) config('ops.snapshot_stale_minutes', 25*60);
                    if ($ageMin > $stale) {
                        $alertService->send('Uploads snapshot STALE', ['age_minutes'=>(int)$ageMin,'snapshot'=>$snap['short_id']??null]);
                    }
                }
            }
        }
        // Verify restore freshness (look at last success verify_restore activity)
        $lastVerify = \App\Models\OpsActivity::where('type','verify_restore')->where('status','success')->latest('finished_at')->first();
        if ($lastVerify && $lastVerify->finished_at) {
            $ageH = now()->diffInHours($lastVerify->finished_at);
            $overdueH = (int) config('ops.verify_overdue_hours', 7*24 + 12);
            if ($ageH > $overdueH) {
                $alertService->send('Verify restore overdue', ['hours_old'=>$ageH]);
            }
        } elseif(!$lastVerify) {
            $alertService->send('Verify restore never ran', []);
        }
    }
}
