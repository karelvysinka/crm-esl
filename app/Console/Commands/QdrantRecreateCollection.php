<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Knowledge\QdrantClient;

class QdrantRecreateCollection extends Command
{
    protected $signature = 'qdrant:recreate {--dimension=} {--distance=Cosine}';
    protected $description = 'Recreate Qdrant collection with the configured or specified dimension.';

    public function handle(QdrantClient $q): int
    {
        if (!config('qdrant.enabled')) { $this->error('Qdrant disabled'); return 1; }
        $collection = (string) config('qdrant.collection', 'crm_knowledge');
        $dimOpt = $this->option('dimension');
        $dim = $dimOpt ? (int) $dimOpt : (int) (\App\Models\SystemSetting::get('embeddings.dimension', config('qdrant.embeddings.dimension', 1536)) ?: 1536);
        $distance = (string) $this->option('distance') ?: 'Cosine';
        $this->info("Recreating collection '{$collection}' with size={$dim}, distance={$distance}...");
        $ok = $q->recreateCollection($collection, $dim, $distance);
        if ($ok) { $this->info('Done.'); return 0; }
        $this->error('Failed.');
        return 1;
    }
}
