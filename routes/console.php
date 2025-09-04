<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\ActiveCampaignSyncJob;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Trigger one AC sync batch (used by scheduler and for manual testing)
Artisan::command('ac:sync-tick {--limit=200} {--force}', function () {
    $limit = (int) $this->option('limit');
    $force = (bool) $this->option('force');
    ActiveCampaignSyncJob::dispatch($limit, $force);
    $this->info("Dispatched ActiveCampaignSyncJob (limit={$limit}, force=".($force?'yes':'no').")");
})->purpose('Dispatch one ActiveCampaign sync batch')
  ->everyMinute()
  ->withoutOverlapping()
  ->onOneServer();

// Quick status of AC sync runs for diagnostics
Artisan::command('ac:runs:stats', function () {
    $count = DB::table('ac_sync_runs')->count();
    $last = DB::table('ac_sync_runs')->orderByDesc('id')->first();
    $open = DB::table('ac_sync_runs')->whereNull('finished_at')->count();
    $this->line("runs={$count}");
    if ($last) {
        $this->line('last_id=' . $last->id . ' finished_at=' . ($last->finished_at ?? 'null'));
    }
    $this->line("unfinished={$open}");
})->purpose('Show AC sync run counters');
