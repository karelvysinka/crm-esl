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

class RunDbBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $activityId;

    public function __construct(int $activityId)
    {
        $this->activityId = $activityId;
        $this->onQueue('ops');
    }

    public function handle(): void
    {
        $activity = OpsActivity::find($this->activityId);
        if (!$activity) return;
        $activity->update(['status'=>'running','started_at'=>now()]);
        $start = microtime(true);
        try {
            $dbHost = env('BACKUP_DB_HOST','db');
            $dbName = env('BACKUP_DB_NAME','crm');
            $dbUser = env('BACKUP_DB_USER','crm');
            $dbPass = env('BACKUP_DB_PASSWORD');
            $baseRoot = rtrim(env('OPS_BACKUP_BASE','/srv/backups/crm'),'/');
            $baseDir = $baseRoot.'/db';
            if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
            $ts = date('Ymd-His');
            $dumpPath = "$baseDir/full-$ts.sql.gz";
            $shaPath = $dumpPath.'.sha256';
            $envExport = $dbPass ? 'MYSQL_PWD='.escapeshellarg($dbPass).' ' : '';
            // Prefer pigz for multi-core compression if available, fallback to gzip
            $compressor = trim(shell_exec('command -v pigz')) ? 'pigz -9' : 'gzip -9';
            $cmd = $envExport.'mysqldump --single-transaction --routines --triggers --events -h '
                .escapeshellarg($dbHost).' -u '.escapeshellarg($dbUser).' '.escapeshellarg($dbName)
                .' | '.$compressor.' > '.escapeshellarg($dumpPath).' 2>&1';
            $output = [];$ret = 0;
            exec($cmd, $output, $ret);
            if ($ret !== 0 || !is_file($dumpPath)) {
                throw new \RuntimeException('mysqldump failed exit='.$ret.' out='.substr(implode("\n", $output),0,400));
            }
            $hashOut = [];$hashRet=0;
            exec('sha256sum '.escapeshellarg($dumpPath), $hashOut, $hashRet);
            if ($hashRet===0 && isset($hashOut[0])) {
                file_put_contents($shaPath, $hashOut[0]."\n");
            }
            $meta = $activity->meta ?? [];
            $meta['dump_path'] = $dumpPath;
            $meta['size_bytes'] = filesize($dumpPath) ?: null;
            $activity->update([
                'status'=>'success',
                'finished_at'=>now(),
                'duration_ms'=>(int)((microtime(true)-$start)*1000),
                'log_excerpt'=> 'Dump OK '.basename($dumpPath),
                'meta'=>$meta,
            ]);
            Log::channel('ops')->info('DB backup done',['activity_id'=>$activity->id,'path'=>$dumpPath]);
            app(AlertService::class)->send('DB backup OK', ['path'=>$dumpPath,'size'=>$meta['size_bytes']??null]);
        } catch (\Throwable $e) {
            $activity->update([
                'status'=>'failed',
                'finished_at'=>now(),
                'duration_ms'=>(int)((microtime(true)-$start)*1000),
                'log_excerpt'=>substr($e->getMessage(),0,500)
            ]);
            Log::channel('ops')->error('DB backup failed',['error'=>$e->getMessage()]);
            app(AlertService::class)->send('DB backup FAILED', ['error'=>$e->getMessage()]);
            throw $e;
        }
    }
}
