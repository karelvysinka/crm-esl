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

class GenerateBackupReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $activityId){ $this->onQueue('ops'); }
    public function handle(): void
    {
        $a = OpsActivity::find($this->activityId); if(!$a) return;
        $a->update(['status'=>'running','started_at'=>now()]);
        $start = microtime(true);
        try {
            $dbDir = '/srv/backups/crm/db';
            $dumpFiles = glob($dbDir.'/full-*.sql.gz');
            usort($dumpFiles, fn($x,$y)=>filemtime($y)<=>filemtime($x));
            $latestDump = $dumpFiles[0] ?? null;
            $latestDumpAgeMin = $latestDump ? (int)((time()-filemtime($latestDump))/60) : null;
            $latestDumpSize = $latestDump ? filesize($latestDump) : null;
            $resticRepo = env('RESTIC_REPO');
            $snapInfo = null;
            if ($resticRepo && is_dir($resticRepo)) {
                $pwd = env('RESTIC_PASSWORD');
                if ($pwd) {
                    $env = 'RESTIC_PASSWORD='.escapeshellarg($pwd).' ';
                    $json = shell_exec($env.'restic -r '.escapeshellarg($resticRepo).' snapshots --last --json 2>/dev/null');
                    if ($json) $snapInfo = json_decode($json,true);
                }
            }
            $content = "# Backup Report\n\nGenerováno: ".now()->toDateTimeString()."\n\n";
            $content .= "## DB Dump\n";
            if ($latestDump) {
                $sizeMb = $latestDumpSize ? number_format($latestDumpSize/1024/1024,2) : 'N/A';
                $content .= '- Soubor: '.basename($latestDump)."\n";
                $content .= '- Věk (min): '.$latestDumpAgeMin."\n";
                $content .= '- Velikost (MB): '.$sizeMb."\n";
            } else {
                $content .= "Nenalezen žádný dump\n";
            }
            $content .= "\n## Uploads Snapshot\n";
            if ($snapInfo) {
                $snapId = $snapInfo['snapshots'][0]['short_id'] ?? 'N/A';
                $content .= '- Poslední snapshot ID: '.$snapId."\n";
                $content .= '- Cesta repo: '.$resticRepo."\n";
            } else {
                $content .= "Snapshot data nedostupná\n";
            }
            $repDir = '/srv/backups/crm/reports';
            if (!is_dir($repDir)) @mkdir($repDir,0775,true);
            $path = $repDir.'/backup-report-'.date('Ymd-His').'.md';
            file_put_contents($path, $content);
            $meta = $a->meta ?? []; $meta['report_path']=$path;
            $a->update(['status'=>'success','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$start)*1000),'meta'=>$meta,'log_excerpt'=>'Report vytvořen']);
            Log::channel('ops')->info('Report generated',[ 'path'=>$path]);
            app(AlertService::class)->send('Backup report vytvořen',['path'=>$path]);
        } catch(\Throwable $e) {
            $a->update(['status'=>'failed','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$start)*1000),'log_excerpt'=>substr($e->getMessage(),0,500)]);
            Log::channel('ops')->error('Report generation failed',['err'=>$e->getMessage()]);
            app(AlertService::class)->send('Backup report FAILED',['error'=>$e->getMessage()]);
            throw $e;
        }
    }
}
