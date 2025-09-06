<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OpsActivity;

class OrdersImportStatus extends Command
{
    protected $signature = 'orders:import-status {--limit=5}';
    protected $description = 'Show recent full import activities (web/cli) with status.';

    public function handle(): int
    {
        $limit = (int)$this->option('limit');
        $rows = OpsActivity::query()
            ->whereIn('type',[ 'orders.full_import','orders.full_import.web','orders.full_import.manual' ])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
        if ($rows->isEmpty()) { $this->info('No import activities found.'); return self::SUCCESS; }
        $data = $rows->map(function($a){
            return [
                'id'=>$a->id,
                'type'=>$a->type,
                'status'=>$a->status,
                'created'=>$a->created_at?->format('H:i:s'),
                'meta_pages'=>$a->meta['pages'] ?? ($a->meta['import_pages'] ?? null),
                'exit'=>$a->meta['import_exit'] ?? null,
                'skipped_reason'=>$a->meta['skipped_reason'] ?? null,
            ];
        });
        $this->table(['ID','Type','Status','Created','Pages','Exit','Skip'], $data);
        $running = $rows->firstWhere('status','running');
        if ($running) { $this->info('Currently running import: activity #'.$running->id); }
        return self::SUCCESS;
    }
}
