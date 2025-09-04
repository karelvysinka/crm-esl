<?php

namespace App\Console\Commands;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeChunk;
use Illuminate\Console\Command;

class KnowledgeIngestFile extends Command
{
    protected $signature = 'knowledge:ingest-file {path : Absolute or workspace-relative path to a text/markdown file} {--title=} {--visibility=public}';
    protected $description = 'Create a KnowledgeDocument from a local text/markdown file, chunk it, and queue vector indexing if enabled.';

    public function handle(): int
    {
        $pathArg = (string) $this->argument('path');
        $real = $pathArg;
        if (!is_file($real)) {
            // Try relative to project root
            $real = base_path(trim($pathArg, '/'));
        }
        if (!is_file($real)) {
            $this->error('File not found: '.$pathArg);
            return self::INVALID;
        }
        $content = file_get_contents($real);
        if ($content === false || $content === '') {
            $this->error('Empty file.');
            return self::INVALID;
        }
        $title = (string) ($this->option('title') ?: basename($real));
        $visibility = (string) ($this->option('visibility') ?: 'public');

        $doc = KnowledgeDocument::create([
            'user_id' => 1,
            'title' => $title,
            'source_type' => 'local-file',
            'mime' => 'text/markdown',
            'size' => strlen($content),
            'path' => null,
            'status' => 'queued',
            'visibility' => in_array($visibility, ['public','private']) ? $visibility : 'public',
            'tags' => ['ingest','cli'],
        ]);

        // Simple chunking (match KnowledgeDocumentsController::ingestText)
        $text = preg_replace("/[\r\t]+/"," ", $content);
        $paras = preg_split("/\n\n+/", (string)$text) ?: [];
        $chunkSize = 800; $overlap = 100;
        $buf = ''; $idx = 0; $chunks = 0;
        foreach ($paras as $p) {
            if (mb_strlen($buf) + mb_strlen($p) + 1 > $chunkSize) {
                if ($buf !== '') {
                    KnowledgeChunk::create(['document_id'=>$doc->id,'chunk_index'=>$idx++,'text'=>$buf,'meta'=>['title'=>$doc->title]]);
                    $chunks++;
                    $buf = mb_substr($buf, max(0, mb_strlen($buf)-$overlap));
                }
            }
            $buf .= ($buf ? "\n\n" : '') . trim($p);
        }
        if (trim($buf) !== '') {
            KnowledgeChunk::create(['document_id'=>$doc->id,'chunk_index'=>$idx++,'text'=>$buf,'meta'=>['title'=>$doc->title]]);
            $chunks++;
        }

        $doc->status = 'ready';
        $doc->save();

        if (config('qdrant.enabled')) {
            \App\Jobs\IndexKnowledgeDocumentJob::dispatch($doc->id);
        }

        $this->info("Ingested '{$title}' (doc ID {$doc->id}), chunks={$chunks}. Indexing queued: ".(config('qdrant.enabled') ? 'yes' : 'no'));
        return 0;
    }
}
