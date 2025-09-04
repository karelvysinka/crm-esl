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

class CreateGitTagJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $activityId, public string $tag){ $this->onQueue('ops'); }
    public function handle(): void
    {
        $a = OpsActivity::find($this->activityId); if(!$a) return;
        $a->update(['status'=>'running','started_at'=>now()]);
        $start = microtime(true);
        try {
            $barePath = env('GIT_BARE_REPO_PATH','/srv/git-cache/crm.git');
            $remote = env('GIT_REMOTE','origin');
            $revFile = base_path('REVISION');
            $commit = null;
            if (is_file($revFile)) {
                $data = trim(file_get_contents($revFile));
                $parts = explode(' ', $data);
                $commit = $parts[0] ?? null;
            }
            if (!$commit) {
                $cmdHead = 'git --git-dir '.escapeshellarg($barePath).' ls-remote '.escapeshellarg($remote).' HEAD 2>/dev/null';
                $out=[];$ret=0;exec($cmdHead,$out,$ret);
                if($ret===0 && isset($out[0])) { $commit = substr($out[0],0,40); }
            }
            if (!$commit) throw new \RuntimeException('Nelze zjistit commit');
            if (!is_dir($barePath)) throw new \RuntimeException('Chybí bare repo: '.$barePath);
            $fetchCmd = 'git --git-dir '.escapeshellarg($barePath).' fetch '.escapeshellarg($remote).' --prune 2>&1';
            exec($fetchCmd,$fOut,$fRet); if($fRet!==0) throw new \RuntimeException('fetch fail '.substr(implode("\n",$fOut),0,200));
            $tagCmd = 'git --git-dir '.escapeshellarg($barePath).' tag '.escapeshellarg($this->tag).' '.escapeshellarg($commit).' 2>&1';
            $out2=[];$ret2=0;exec($tagCmd,$out2,$ret2); if($ret2!==0) throw new \RuntimeException('tag fail '.substr(implode("\n",$out2),0,200));
            $pushCmd = 'git --git-dir '.escapeshellarg($barePath).' push '.escapeshellarg($remote).' '.escapeshellarg($this->tag).' 2>&1';
            $out3=[];$ret3=0;exec($pushCmd,$out3,$ret3); if($ret3!==0) throw new \RuntimeException('push fail '.substr(implode("\n",$out3),0,200));
            $meta = $a->meta ?? []; $meta['tag']=$this->tag; $meta['commit']=$commit;
            $a->update(['status'=>'success','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$start)*1000),'meta'=>$meta,'log_excerpt'=>'Tag vytvořen']);
            Log::channel('ops')->info('Tag created',['tag'=>$this->tag,'commit'=>$commit]);
            app(AlertService::class)->send('Git tag vytvořen',['tag'=>$this->tag,'commit'=>$commit]);
        } catch(\Throwable $e) {
            $a->update(['status'=>'failed','finished_at'=>now(),'duration_ms'=>(int)((microtime(true)-$start)*1000),'log_excerpt'=>substr($e->getMessage(),0,500)]);
            Log::channel('ops')->error('Tag create failed',['err'=>$e->getMessage()]);
            app(AlertService::class)->send('Git tag FAILED',['tag'=>$this->tag,'error'=>$e->getMessage()]);
            throw $e;
        }
    }
}
