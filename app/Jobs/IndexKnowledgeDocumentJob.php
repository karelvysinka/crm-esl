<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeChunk;
use App\Services\Knowledge\EmbeddingsService;
use App\Services\Knowledge\QdrantClient;
use Ramsey\Uuid\Uuid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Indexuje jeden KnowledgeDocument do Qdrant:
 * - Načte chunk records, generuje embeddingy
 * - Upsert do kolekce (Qdrant)
 * - Aktualizuje metriky dokumentu (vectors_count, duration, provider, model)
 * - Audit insert do knowledge_embeddings_audit
 */
class IndexKnowledgeDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $documentId) {}

    public function handle(EmbeddingsService $embedder, QdrantClient $qdrant): void
    {
        if (!config('qdrant.enabled')) { return; }
        $collection = (string) config('qdrant.collection');
        $qdrant->ensureCollection($collection);

        $doc = KnowledgeDocument::find($this->documentId);
        if (!$doc || $doc->status !== 'ready') { return; }
        $startedAt = microtime(true);
        $provider = config('qdrant.embeddings.provider');
        $model = config('qdrant.embeddings.model');
        $chunks = KnowledgeChunk::where('document_id', $doc->id)->orderBy('chunk_index')->get();
        $points = [];
        $vectorsCount = 0; $dim = null;
        foreach ($chunks as $c) {
            // Idempotent content hash of doc+index+text
            $hash = hash('sha256', $doc->id.'|'.$c->chunk_index.'|'.mb_substr($c->text,0,4000));
            $c->chunk_hash = $hash;
            $vec = $embedder->embed($c->text);
            if (!$vec) { continue; }
            $vectorsCount++;
            $dim = $dim ?? count($vec);
            $c->embedding_dim = count($vec);
            $c->embedded_at = now();
            // Qdrant requires integer IDs or UUIDs; use deterministic UUIDv5 derived from hash
            $uuid = Uuid::uuid5(Uuid::NAMESPACE_DNS, $hash)->toString();
            $c->qdrant_point_id = $uuid;
            $c->save();
            $points[] = [
                'id' => $uuid,
                'vector' => $vec,
                'payload' => [
                    'document_id' => $doc->id,
                    'chunk_index' => $c->chunk_index,
                    'title' => $doc->title,
                    'visibility' => $doc->visibility,
                    'user_id' => $doc->user_id,
                    'preview' => mb_substr($c->text, 0, 500),
                ],
            ];
        }
        if ($points) {
            $ok = $qdrant->upsert($collection, $points);
            $duration = (int) ((microtime(true)-$startedAt)*1000);
            if ($ok) {
                $doc->vectorized_at = now();
                $doc->embedding_provider = $provider;
                $doc->embedding_model = $model;
                if ($dim) { $doc->embedding_dim = $dim; }
                $doc->vectors_count = $vectorsCount;
                $doc->last_index_duration_ms = $duration;
                $doc->save();
                DB::table('knowledge_embeddings_audit')->insert([
                    'document_id' => $doc->id,
                    'chunk_id' => null,
                    'provider' => $provider,
                    'model' => $model,
                    'status' => 'done',
                    'duration_ms' => $duration,
                    'error' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                Log::warning('Qdrant upsert failed for document '.$doc->id);
                DB::table('knowledge_embeddings_audit')->insert([
                    'document_id' => $doc->id,
                    'chunk_id' => null,
                    'provider' => $provider,
                    'model' => $model,
                    'status' => 'failed',
                    'duration_ms' => null,
                    'error' => 'qdrant_upsert_failed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
