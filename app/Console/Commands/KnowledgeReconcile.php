<?php

namespace App\Console\Commands;

use App\Models\KnowledgeDocument;
use App\Services\Knowledge\QdrantClient;
use Illuminate\Console\Command;

class KnowledgeReconcile extends Command
{
    protected $signature = 'knowledge:reconcile {--doc=* : Reconcile only these document IDs}';
    protected $description = 'Compare DB and Qdrant counts and mark discrepancies';

    public function handle(QdrantClient $q): int
    {
        if (!config('qdrant.enabled')) { $this->warn('Qdrant disabled'); return 0; }
        $collection = (string) config('qdrant.collection');
        $ids = $this->option('doc');
        $docs = KnowledgeDocument::query();
        if ($ids) { $docs->whereIn('id',$ids); }
        $docs = $docs->get();
        foreach ($docs as $d) {
            $count = $q->count($collection, ['must' => [ ['match' => ['key' => 'document_id', 'value' => (int)$d->id]] ]]);
            $this->line("Doc #{$d->id} '{$d->title}': DB vectors={$d->vectors_count} Qdrant={$count}");
        }
        return 0;
    }
}
