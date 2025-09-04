<?php

namespace App\Jobs;

use App\Services\ActiveCampaignClient;
use App\Services\ActiveCampaignImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

class ImportActiveCampaignAll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes per job slice

    public function __construct(
        private int $limit = 100,
        private int $startOffset = 0,
        private ?int $max = null // optional cap on total contacts to fetch
    ) {}

    public function handle(ActiveCampaignClient $ac, ActiveCampaignImporter $importer, LoggerInterface $logger): void
    {
        $offset = $this->startOffset;
        $totalImported = 0;
        $batchNo = 0;

        while (true) {
            $batchNo++;
            $query = ['limit' => $this->limit, 'offset' => $offset, 'orders[cdate]' => 'DESC'];
            $data = $ac->get('contacts', $query);
            if (isset($data['ok']) && $data['ok'] === false) {
                $logger->warning('AC import-all soft-stopped: API exhausted or forbidden', ['offset'=>$offset, 'limit'=>$this->limit, 'status'=>$data['status'] ?? null]);
                break; // stop without error
            }
            $contacts = $data['contacts'] ?? [];

            // store sample for auditing
            $out = 'ac_samples/import_all_' . $this->limit . '_' . $offset . '_' . now()->format('Ymd_His') . '.json';
            Storage::disk('local')->put($out, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

            if (empty($contacts)) {
                $logger->info('AC import-all: no more contacts, stopping', ['offset' => $offset]);
                break;
            }

            $res = $importer->importContacts($contacts);
            $totalImported += ($res['created'] + $res['updated']);

            $logger->info('AC import-all batch done', [
                'batch' => $batchNo,
                'offset' => $offset,
                'limit' => $this->limit,
                'created' => $res['created'],
                'updated' => $res['updated'],
                'skipped' => $res['skipped'],
                'unchanged' => $res['skippedUnchanged'],
                'errors' => $res['errors'],
            ]);

            $offset += $this->limit;
            if ($this->max !== null && $offset >= ($this->startOffset + $this->max)) {
                $logger->info('AC import-all: reached max cap, stopping', ['offset' => $offset]);
                break;
            }

            // gentle backoff between pages
            usleep(250 * 1000);
        }
    }
}
