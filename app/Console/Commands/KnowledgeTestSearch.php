<?php

namespace App\Console\Commands;

use App\Services\Knowledge\EmbeddingsService;
use App\Services\Knowledge\QdrantClient;
use Illuminate\Console\Command;

class KnowledgeTestSearch extends Command
{
    protected $signature = 'knowledge:test-search {q : Query text} {--k=5 : Top K}';
    protected $description = 'Embed a query and run vector search in Qdrant, print top-K payloads';

    public function handle(EmbeddingsService $embed, QdrantClient $q): int
    {
        if (!config('qdrant.enabled')) { $this->error('Qdrant disabled'); return 1; }
        $query = (string) $this->argument('q');
        $k = (int) $this->option('k');
        $vec = $embed->embed($query);
        if (!$vec) { $this->error('No embedding (missing key?)'); return 1; }
        $res = $q->search((string) config('qdrant.collection'), $vec, $k);
        foreach ($res as $i => $r) {
            $score = $r['score'] ?? null;
            $p = $r['payload'] ?? [];
            $this->line(sprintf("%d) score=%.4f doc=%s chunk=%s title=%s preview=%s",
                $i+1, $score, $p['document_id'] ?? '-', $p['chunk_index'] ?? '-', $p['title'] ?? '-', mb_substr($p['preview'] ?? '',0,120)));
        }
        return 0;
    }
}
