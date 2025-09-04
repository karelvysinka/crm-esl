<?php

namespace App\Console\Commands;

use App\Services\Knowledge\QdrantClient;
use Illuminate\Console\Command;

class KnowledgePurge extends Command
{
    protected $signature = 'knowledge:purge {--doc=* : Document IDs to purge from Qdrant} {--hard : Also reset DB vector flags}';
    protected $description = 'Delete vectors from Qdrant by document_id (and optionally reset DB metadata)';

    public function handle(QdrantClient $q): int
    {
        if (!config('qdrant.enabled')) { $this->error('Qdrant disabled'); return 1; }
        $collection = (string) config('qdrant.collection');
        $ids = array_map('intval', (array) $this->option('doc'));
        if (!$ids) { $this->error('Provide --doc=ID[,ID...]'); return 1; }
    $ok = $q->deleteByFilter($collection, ['should' => array_map(fn($i)=>['match' => ['key' => 'document_id', 'value' => $i]], $ids)]);
        $this->info($ok ? 'Delete request sent.' : 'Delete failed.');
        if ($ok && $this->option('hard')) {
            \DB::table('knowledge_documents')->whereIn('id',$ids)->update([
                'vectorized_at' => null, 'vectors_count' => 0, 'last_index_duration_ms' => null
            ]);
            \DB::table('knowledge_chunks')->whereIn('document_id',$ids)->update([
                'embedded_at' => null, 'qdrant_point_id' => null
            ]);
            $this->info('DB flags reset.');
        }
        return $ok ? 0 : 1;
    }
}
