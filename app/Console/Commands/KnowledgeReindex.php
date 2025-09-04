<?php

namespace App\Console\Commands;

use App\Jobs\IndexKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use Illuminate\Console\Command;

class KnowledgeReindex extends Command
{
    protected $signature = 'knowledge:reindex {--doc=* : Document IDs to reindex}';
    protected $description = 'Reindex knowledge documents into Qdrant';

    public function handle(): int
    {
        if (!config('qdrant.enabled')) {
            $this->error('Qdrant is disabled (QDRANT_ENABLED=false).');
            return 1;
        }
        $ids = $this->option('doc');
        $q = KnowledgeDocument::query()->where('status','ready');
        if ($ids) { $q->whereIn('id', $ids); }
        $count = 0;
        foreach ($q->cursor() as $d) {
            IndexKnowledgeDocumentJob::dispatch($d->id);
            $count++;
        }
        $this->info("Queued {$count} document(s) for indexing.");
        return 0;
    }
}
