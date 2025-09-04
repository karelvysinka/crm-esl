<?php
namespace App\Jobs\Ops;

use App\Models\OpsActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\Ops\AlertService;

class RunStorageSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $activityId){ $this->onQueue('ops'); }

    public function handle(): void
    {
        $a = OpsActivity::find($this->activityId); if(!$a) return;
        $a->update(['status'=>'running','started_at'=>now()]);
        $start = microtime(true);
        try {
            $repo = env('RESTIC_REPO');
            $pwd = env('RESTIC_PASSWORD');
            $uploads = env('CRM_UPLOADS_PATH','/srv/volumes/crm/uploads');
            if (!$repo || !$pwd) throw new \RuntimeException('RESTIC_REPO nebo RESTIC_PASSWORD není nastaveno');
            if (!is_dir($uploads)) throw new \RuntimeException('Uploads path neexistuje: '.$uploads);
            $tagEnv = env('RESTIC_TAG_ENV','env');
            $envExport = 'RESTIC_PASSWORD='.escapeshellarg($pwd).' ';
            $cmd = $envExport.'restic -r '.escapeshellarg($repo).' backup '.escapeshellarg($uploads).' --tag uploads,'.$tagEnv.' 2>&1';
            $out=[];$ret=0; exec($cmd,$out,$ret);
            if ($ret!==0) throw new \RuntimeException('restic backup failed exit='.$ret.' out='.substr(implode("\n",$out),0,400));
            $snapCmd = $envExport.'restic -r '.escapeshellarg($repo).' snapshots --last --json 2>/dev/null';
            $snapJson = shell_exec($snapCmd); $snapshotId = null;
            if ($snapJson) { $data = json_decode($snapJson,true); if (is_array($data)) { $last = end($data['snapshots'] ?? []); if ($last && isset($last['short_id'])) $snapshotId = $last['short_id']; } }
            $baseRoot = rtrim(env('OPS_BACKUP_BASE','/srv/backups/crm'),'/');
            $snapDir = $baseRoot.'/snapshots'; if (!is_dir($snapDir)) @mkdir($snapDir,0775,true);
            $marker = $snapDir.'/snapshot-'.date('Ymd-His').'.txt';
            @file_put_contents($marker, ($snapshotId ? 'id='.$snapshotId.'\n' : '').'path='.$uploads."\n");
            $meta = $a->meta ?? []; $meta['snapshot_id'] = $snapshotId; $meta['snapshot_marker']=basename($marker);
            $a->update(['status'=>'success','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$start)*1000),'log_excerpt'=>'Snapshot OK '.($snapshotId ?? ''),'meta'=>$meta]);
            Log::channel('ops')->info('Storage snapshot ok',['activity_id'=>$a->id,'snapshot'=>$snapshotId]);
            app(AlertService::class)->send('Storage snapshot OK',['snapshot'=>$snapshotId]);
        } catch(\Throwable $e) {
            $a->update(['status'=>'failed','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$start)*1000),'log_excerpt'=>substr($e->getMessage(),0,500)]);
            Log::channel('ops')->error('Storage snapshot fail',['err'=>$e->getMessage()]);
            app(AlertService::class)->send('Storage snapshot FAILED',['error'=>$e->getMessage()]);
            throw $e;
        }
    }
}
