<?php
namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\OpsActivity;
use App\Jobs\Ops\RunDbBackupJob;
use App\Jobs\Ops\RunStorageSnapshotJob;
use App\Jobs\Ops\RunVerifyRestoreJob;
use App\Jobs\Ops\GenerateBackupReportJob;
use App\Jobs\Ops\CreateGitTagJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class ActionController extends Controller
{
    public function __construct() {
        $this->middleware('permission:ops.execute');
    }
    public function run(Request $request, string $action)
    {
        if (function_exists('auth') && auth()->check()) {
            $user = auth()->user();
            if (!$user->can('ops.execute')) abort(403);
            if ($action==='create_tag' && !$user->can('ops.release')) abort(403);
        }
        if (!in_array($action,['db_backup','storage_snapshot','verify_restore','report','create_tag'])) abort(404);
        $token = $request->input('_ops_token');
        $used = session()->get('ops.used_tokens', []);
        if (!$token || in_array($token, $used, true)) {
            return redirect()->route('ops.dashboard')->withErrors('Neplatný nebo již použitý akční token.');
        }
        $used[] = $token; if (count($used) > 50) { $used = array_slice($used, -50); }
        session()->put('ops.used_tokens', $used);
        $meta = [];
        if ($action==='create_tag') {
            $request->validate(['tag'=>'required|regex:/^v?\d+\.\d+\.\d+$/']);
            $meta['tag']=$request->input('tag');
        }
        $activity = OpsActivity::create([
            'type'=>$action,
            'status'=>'queued',
            'user_id'=>optional($request->user())->id,
            'meta'=>$meta + [ 'ip'=>$request->ip(), 'ua'=>substr($request->userAgent() ?? '',0,120) ],
        ]);
        $dispatched = match($action) {
            'db_backup' => RunDbBackupJob::dispatch($activity->id),
            'storage_snapshot' => RunStorageSnapshotJob::dispatch($activity->id),
            'verify_restore' => RunVerifyRestoreJob::dispatch($activity->id),
            'report' => GenerateBackupReportJob::dispatch($activity->id),
            'create_tag' => CreateGitTagJob::dispatch($activity->id, $meta['tag']),
        };
        if (method_exists($dispatched, 'job')) {
            $payloadMeta = $activity->meta; $payloadMeta['queue_id'] = spl_object_hash($dispatched);
            $activity->meta = $payloadMeta; $activity->save();
        }
        Log::channel('ops')->info('Enqueued action',['activity_id'=>$activity->id,'type'=>$action]);
        return redirect()->route('ops.dashboard')->with('status','Akce zařazena: '.$action);
    }

    public function docsBuild(Request $request)
    {
        if (function_exists('auth') && auth()->check()) {
            $user = auth()->user();
            if (!$user->can('ops.execute')) abort(403);
        }
        $token = $request->input('_ops_token');
        $used = session()->get('ops.used_tokens', []);
        if (!$token || in_array($token, $used, true)) {
            return redirect()->route('ops.dashboard')->withErrors('Neplatný nebo již použitý akční token.');
        }
        $used[] = $token; if (count($used) > 50) { $used = array_slice($used, -50); }
        session()->put('ops.used_tokens', $used);
        // Spustíme artisan docs:refresh synchronně + volitelně build skript (bez docker fallbacku pokud není dostupný)
        $activity = OpsActivity::create([
            'type'=>'docs_build',
            'status'=>'running',
            'user_id'=>optional($request->user())->id,
            'meta'=>['ip'=>$request->ip(),'ua'=>substr($request->userAgent() ?? '',0,120)],
        ]);
        try {
            // 1) artisan docs:refresh
            \Artisan::call('docs:refresh');
            $refreshOutput = trim(\Artisan::output());
            // 2) mkdocs build (pokud existuje skript)
            $script = base_path('scripts/build-docs.sh');
            $mkdocsOutput = null; $exitCode = null;
            if (is_file($script) && is_executable($script)) {
                $proc = Process::fromShellCommandline('bash '.escapeshellarg($script));
                $proc->setTimeout(300);
                $proc->run();
                $mkdocsOutput = substr($proc->getOutput(), -4000);
                $exitCode = $proc->getExitCode();
            }
            $activity->status='success';
            $activity->meta = $activity->meta + [
                'refresh_tail'=>Str::limit($refreshOutput,500),
                'mkdocs_exit'=>$exitCode,
                'mkdocs_tail'=>$mkdocsOutput,
            ];
            $activity->save();
            return redirect()->route('ops.dashboard')->with('status','Dokumentace přegenerována.');
        } catch (\Throwable $e) {
            $activity->status='error';
            $activity->meta = $activity->meta + ['error'=>$e->getMessage()];
            $activity->save();
            return redirect()->route('ops.dashboard')->withErrors('Chyba při generování dokumentace: '.$e->getMessage());
        }
    }
}
