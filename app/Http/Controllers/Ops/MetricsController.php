<?php
namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\OpsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MetricsController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!env('OPS_METRICS_ENABLED')) abort(404);
        if (function_exists('auth') && auth()->check() && !auth()->user()->can('ops.view')) abort(403);
        $metrics = Cache::remember('ops.metrics.export', 15, function () {
            $lines = [];
            $lines[] = '# HELP ops_activity_total Celkový počet aktivit podle typu a statusu';
            $lines[] = '# TYPE ops_activity_total counter';
            $all = OpsActivity::selectRaw('type,status, COUNT(*) c')->groupBy('type','status')->get();
            foreach ($all as $row) {
                $lines[] = sprintf('ops_activity_total{type="%s",status="%s"} %d', $row->type, $row->status, $row->c);
            }
            // Success/fail counts last 24h
            $since = now()->subDay();
            $recent = OpsActivity::selectRaw('type,status, COUNT(*) c')->where('created_at','>=',$since)->groupBy('type','status')->get();
            $lines[] = '# HELP ops_activity_24h_total Počet aktivit za posledních 24h';
            $lines[] = '# TYPE ops_activity_24h_total counter';
            foreach ($recent as $r) {
                $lines[] = sprintf('ops_activity_24h_total{type="%s",status="%s"} %d', $r->type, $r->status, $r->c);
            }
            $lines[] = '# HELP ops_activity_duration_ms Průměrné trvání (ms) podle typu';
            $lines[] = '# TYPE ops_activity_duration_ms gauge';
            $dur = OpsActivity::whereNotNull('duration_ms')->selectRaw('type, AVG(duration_ms) a')->groupBy('type')->get();
            foreach ($dur as $d) {
                $lines[] = sprintf('ops_activity_duration_ms{type="%s"} %.2f', $d->type, $d->a);
            }
            $lastFail = OpsActivity::where('status','failed')->latest('finished_at')->value('finished_at');
            if ($lastFail) {
                $lines[] = '# HELP ops_last_failure_timestamp Poslední selhání (unix timestamp)';
                $lines[] = '# TYPE ops_last_failure_timestamp gauge';
                $lines[] = 'ops_last_failure_timestamp '.strtotime($lastFail);
            }
            // Freshness gauges (age in minutes/hours)
            $svc = app(\App\Services\Ops\BackupStatusService::class);
            $db = $svc->latestDbDumpStatus();
            if (isset($db['age_minutes'])) {
                $lines[] = '# HELP ops_db_dump_age_minutes Stáří posledního DB dumpu (min)';
                $lines[] = '# TYPE ops_db_dump_age_minutes gauge';
                $lines[] = 'ops_db_dump_age_minutes '.((float)$db['age_minutes']);
            }
            $snap = $svc->latestSnapshotStatus();
            if (isset($snap['age_minutes'])) {
                $lines[] = '# HELP ops_snapshot_age_minutes Stáří posledního snapshot markeru (min)';
                $lines[] = '# TYPE ops_snapshot_age_minutes gauge';
                $lines[] = 'ops_snapshot_age_minutes '.((float)$snap['age_minutes']);
            }
            $ver = $svc->latestVerifyStatus();
            if (isset($ver['age_hours'])) {
                $lines[] = '# HELP ops_verify_age_hours Stáří posledního verify restore (h)';
                $lines[] = '# TYPE ops_verify_age_hours gauge';
                $lines[] = 'ops_verify_age_hours '.((float)$ver['age_hours']);
            }
            return implode("\n", $lines)."\n";
        });
        return response($metrics, 200)->header('Content-Type','text/plain; version=0.0.4');
    }
}
