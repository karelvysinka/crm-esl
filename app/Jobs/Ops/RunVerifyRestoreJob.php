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

class RunVerifyRestoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $activityId){ $this->onQueue('ops'); }
    public function handle(): void
    {
        $a = OpsActivity::find($this->activityId); if(!$a) return;
        $a->update(['status'=>'running','started_at'=>now()]);
        $start = microtime(true);
        try {
            $dbHost = env('BACKUP_DB_HOST','db');
            $dbUser = env('BACKUP_DB_USER','crm');
            $dbPass = env('BACKUP_DB_PASSWORD');
            $baseRoot = rtrim(env('OPS_BACKUP_BASE','/srv/backups/crm'),'/');
            $baseDir = $baseRoot.'/db';
            $verifyDir = $baseRoot.'/verify'; if (!is_dir($verifyDir)) @mkdir($verifyDir,0775,true);
            $dumps = glob($baseDir.'/full-*.sql.gz');
            if (!$dumps) throw new \RuntimeException('Nenalezen žádný dump ve '.$baseDir);
            usort($dumps, fn($x,$y)=> filemtime($y)<=>filemtime($x));
            $latest = $dumps[0];
            $tempDb = 'verify_'.substr(md5($latest.microtime()),0,8);
            $envExport = $dbPass ? 'MYSQL_PWD='.escapeshellarg($dbPass).' ' : '';
            $createCmd = $envExport
                .'mysql -h '.escapeshellarg($dbHost)
                .' -u '.escapeshellarg($dbUser)
                .' -e '.escapeshellarg('CREATE DATABASE `'.$tempDb.'`;');
            exec($createCmd, $o1, $r1); if($r1!==0) throw new \RuntimeException('Create DB fail');
            $importCmd = $envExport
                .'gunzip -c '.escapeshellarg($latest)
                .' | mysql -h '.escapeshellarg($dbHost)
                .' -u '.escapeshellarg($dbUser)
                .' '.escapeshellarg($tempDb).' 2>&1';
            $out=[];$ret=0; exec($importCmd,$out,$ret); if($ret!==0) throw new \RuntimeException('Import fail exit='.$ret.' out='.substr(implode("\n",$out),0,300));
            $countCmd = $envExport
                .'mysql -N -h '.escapeshellarg($dbHost)
                .' -u '.escapeshellarg($dbUser)
                .' -e '.escapeshellarg("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$tempDb'");
            $cntOut=[];$cntRet=0; exec($countCmd,$cntOut,$cntRet); $tableCount = ($cntRet===0 && isset($cntOut[0])) ? (int)$cntOut[0] : null;
            $dropCmd = $envExport
                .'mysql -h '.escapeshellarg($dbHost)
                .' -u '.escapeshellarg($dbUser)
                .' -e '.escapeshellarg('DROP DATABASE `'.$tempDb.'`;');
            exec($dropCmd);
            $logFile = $verifyDir.'/verify-'.date('Ymd-His').'.log';
            file_put_contents($logFile, "dump=".basename($latest)."\n"."tables=$tableCount\n"."duration_ms=".(int)((microtime(true)-$start)*1000)."\n");
            if ($tableCount === null || $tableCount < 5) throw new \RuntimeException('Nízký počet tabulek: '.$tableCount);
            $meta = $a->meta ?? [];
            $meta['verify_table_count']=$tableCount;
            $meta['verify_dump']=basename($latest);
            $meta['verify_log']=basename($logFile);
            $a->update(['status'=>'success','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$start)*1000),'meta'=>$meta,'log_excerpt'=>'Verify OK '.$tableCount.' tabulek']);
            Log::channel('ops')->info('Verify restore ok',['activity_id'=>$a->id,'tables'=>$tableCount]);
            app(AlertService::class)->send('Verify restore OK',['tables'=>$tableCount]);
        } catch(\Throwable $e) {
            // Write fail log
            if (isset($verifyDir)) {
                $failLog = $verifyDir.'/verify-'.date('Ymd-His').'-fail.log';
                @file_put_contents($failLog, 'error='.$e->getMessage().'\n');
            }
            $a->update(['status'=>'failed','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$start)*1000),'log_excerpt'=>substr($e->getMessage(),0,500)]);
            Log::channel('ops')->error('Verify restore fail',['err'=>$e->getMessage()]);
            app(AlertService::class)->send('Verify restore FAILED',['error'=>$e->getMessage()]);
            throw $e;
        }
    }
}
