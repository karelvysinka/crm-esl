<?php

namespace App\Console\Commands;

use App\Services\ActiveCampaignClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AcTestFetch extends Command
{
    protected $signature = 'ac:test:fetch {--limit=10} {--outfile=}';
    protected $description = 'Fetch sample contacts from ActiveCampaign and store JSON for inspection';

    public function handle(ActiveCampaignClient $ac)
    {
        $limit = (int) $this->option('limit');
        $limit = $limit > 0 ? $limit : 10;

        $this->info("Fetching {$limit} contacts from ActiveCampaign...");
        $data = $ac->get('contacts', ['limit' => $limit, 'orders[cdate]' => 'DESC']);
        if (isset($data['ok']) && $data['ok'] === false) {
            $this->warn('ActiveCampaign API seems exhausted or forbidden (403/429). Returning empty list.');
            $data = ['contacts' => [], 'meta' => $data];
        }

        $out = $this->option('outfile') ?: ('ac_samples/contacts_' . now()->format('Ymd_His') . '.json');
        Storage::disk('local')->put($out, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $count = is_countable($data['contacts'] ?? null) ? count($data['contacts']) : 0;
        $this->info("Stored sample ({$count} contacts) at storage/app/{$out}");
        return self::SUCCESS;
    }
}
