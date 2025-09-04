<?php

namespace App\Jobs;

use App\Services\ActiveCampaignClient;
use App\Services\ActiveCampaignImporter;
use App\Models\SystemSetting;
use App\Models\AcSyncRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Periodická inkrementální synchronizace kontaktů z ActiveCampaign.
 * - Respektuje runtime flag ac_sync_enabled (SystemSetting)
 * - Udržuje offset v SystemSetting (ac_sync_offset)
 * - Zapisuje průběh do tabulky ac_sync_runs
 */
class ActiveCampaignSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $limit;
    private bool $force = false;

    public function __construct(int $limit = 200, bool $force = false)
    {
        $this->limit = $limit;
        $this->force = $force;
        $this->onQueue('default');
    }

    public function handle(ActiveCampaignClient $ac, ActiveCampaignImporter $importer, LoggerInterface $logger): void
    {
    $enabled = (bool) json_decode((string) optional(SystemSetting::where('key','ac_sync_enabled')->first())->value ?: 'false');
    if (!$this->force && !$enabled) { $logger->info('AC sync skipped: disabled'); return; }

        $lastOffset = (int) json_decode((string) optional(SystemSetting::where('key','ac_sync_offset')->first())->value ?: '0');
    $run = AcSyncRun::create(['started_at'=>now(),'limit'=>$this->limit,'offset'=>$lastOffset]);
        $query = ['limit' => $this->limit, 'offset' => $lastOffset, 'orders[cdate]' => 'DESC'];
        try {
            $data = $ac->get('contacts', $query);
            // Handle soft-fail result from client (e.g., 429/403)
            if (isset($data['ok']) && $data['ok'] === false) {
                $run->update(['finished_at'=>now(),'errors'=>0,'message'=>'AC API exhausted or forbidden (soft-fail)']);
                $logger->warning('AC sync skipped due to API soft-fail', ['status'=>$data['status'] ?? null, 'offset'=>$lastOffset, 'limit'=>$this->limit]);
                return; // do not throw, allow job to finish gracefully
            }
            $contacts = $data['contacts'] ?? [];
            $res = $importer->importContacts($contacts);
        } catch (\Throwable $e) {
            $run->update(['finished_at'=>now(),'errors'=>1,'message'=>$e->getMessage()]);
            throw $e;
        }

        $newOffset = $lastOffset + count($contacts);
        SystemSetting::updateOrCreate(['key'=>'ac_sync_offset'], ['value'=>json_encode($newOffset)]);
        $run->update([
            'finished_at' => now(),
            'created' => $res['created'] ?? 0,
            'updated' => $res['updated'] ?? 0,
            'skipped' => $res['skipped'] ?? 0,
            'skipped_unchanged' => $res['skippedUnchanged'] ?? 0,
            'errors' => $res['errors'] ?? 0,
            'sample_created_ids' => $res['sampleCreatedIds'] ?? [],
            'sample_updated_ids' => $res['sampleUpdatedIds'] ?? [],
            'message' => $this->force ? 'OK (manual run)' : 'OK'
        ]);
        $logger->info('AC sync batch done', ['created'=>$res['created'] ?? 0, 'updated'=>$res['updated'] ?? 0, 'offset'=>$newOffset, 'run_id'=>$run->id]);
    }
}
