<?php

namespace App\Console\Commands;

use App\Models\KnowledgeDocument;
use App\Services\Knowledge\QdrantClient;
use Illuminate\Console\Command;

class KnowledgeStatus extends Command
{
    protected $signature = 'knowledge:status {--doc=* : Filter by document IDs}';
    protected $description = 'Show knowledge/Qdrant indexing status summary';

    public function handle(QdrantClient $q): int
    {
        if (!config('qdrant.enabled')) {
            $this->warn('Qdrant disabled');
            return 0;
        }
        $collection = (string) config('qdrant.collection');
        $q->ensureCollection($collection);
    $ids = array_map('intval', (array) $this->option('doc'));
    $filter = null;
    if ($ids) {
        $filter = [
            'must' => [
                [
                    'key' => 'document_id',
                    'match' => [
                        'any' => $ids,
                    ],
                ],
            ],
        ];
    }
        $count = $q->count($collection, $filter);
        $this->info("Collection '{$collection}': {$count} vectors");
        $docs = KnowledgeDocument::query();
        if ($ids) { $docs->whereIn('id',$ids); }
        $docs = $docs->orderBy('id')->get(['id','title','status','vectors_count','vectorized_at','embedding_model']);
        $this->table(['ID','Title','Status','Vectors','Vectorized at','Model'], $docs->map(fn($d)=>[
            $d->id, $d->title, $d->status, $d->vectors_count, optional($d->vectorized_at)->toDateTimeString(), $d->embedding_model
        ])->toArray());
        return 0;
    }
}
