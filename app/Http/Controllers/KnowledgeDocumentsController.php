<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeDocument;
use App\Models\KnowledgeChunk;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Jobs\IndexKnowledgeDocumentJob;
use App\Services\Knowledge\QdrantClient;
use Illuminate\View\View;
use Illuminate\Support\Str;

class KnowledgeDocumentsController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $base = KnowledgeDocument::query()
            ->when($q !== '', function($w) use ($q){
                $w->where('title','like',"%$q%")
                  ->orWhereJsonContains('tags', $q);
            })
            ->where(function($w){
                $w->where('visibility','public');
                if (auth()->check()) { $w->orWhere('user_id', auth()->id()); }
            })
            ->orderByDesc('updated_at');

        $docs = $base->clone()->paginate(20);

        // Stats
        $total = $base->clone()->count();
        $public = $base->clone()->where('visibility','public')->count();
        $private = auth()->check() ? $base->clone()->where('visibility','private')->where('user_id', auth()->id())->count() : 0;
        $ready = $base->clone()->where('status','ready')->count();
        $processing = $base->clone()->where('status','processing')->count();
        $failed = $base->clone()->where('status','failed')->count();
        $vectorized = $base->clone()->whereNotNull('vectorized_at')->count();
        $vectorsTotal = (int) $base->clone()->sum('vectors_count');
        $avgVectors = $total > 0 ? round($vectorsTotal / $total, 1) : 0;
        $newMonth = $base->clone()->whereBetween('created_at',[now()->startOfMonth(), now()->endOfMonth()])->count();
        $updatedMonth = $base->clone()->whereBetween('updated_at',[now()->startOfMonth(), now()->endOfMonth()])->count();
        $stats = compact('total','public','private','ready','processing','failed','vectorized','vectorsTotal','avgVectors','newMonth','updatedMonth');

        return view('crm.knowledge.docs.index', compact('docs','q','stats'));
    }

    public function create(): View
    {
        return view('crm.knowledge.docs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'file' => ['required','file','max:20480'], // 20MB
            'tags' => ['nullable','string'],
            'visibility' => ['required','in:public,private'],
        ]);
        $file = $request->file('file');
        $path = $file->store('knowledge','public');
        $doc = KnowledgeDocument::create([
            'user_id' => auth()->id(),
            'title' => $data['title'],
            'source_type' => 'upload',
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'status' => 'queued',
            'visibility' => $data['visibility'],
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string)($data['tags'] ?? ''))))),
        ]);
        // Pro MVP: synchronní extrakce textu pro TXT/MD/HTML/PDF (ostatní označit jako processing)
        try{
            $content = '';
            if (in_array($doc->mime, ['text/plain','text/markdown','text/x-markdown','text/html'])) {
                $content = Storage::disk('public')->get($path);
            } elseif (str_contains((string)$doc->mime, 'pdf') || Str::endsWith(strtolower((string)$path), '.pdf')) {
                // PDF -> extrakce textu přes smalot/pdfparser
                $abs = Storage::disk('public')->path($path);
                if (is_readable($abs)) {
                    try {
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf = $parser->parseFile($abs);
                        $content = (string) $pdf->getText();
                    } catch (\Throwable $e) {
                        // ponechat na processing pokud PDF nelze přečíst nyní
                        $content = '';
                        $doc->error = 'PDF parse: '.substr($e->getMessage(),0,200);
                    }
                }
            }
            if ($content !== '') {
                $this->ingestText($doc, $content);
                $doc->status = 'ready';
                $doc->save();
                // Queue vector indexing if enabled
                if (config('qdrant.enabled')) {
                    \App\Jobs\IndexKnowledgeDocumentJob::dispatch($doc->id);
                }
            } else {
                $doc->status = 'processing';
                $doc->save();
                if (config('qdrant.enabled')) {
                    \App\Jobs\IndexKnowledgeDocumentJob::dispatch($doc->id);
                }
            }
        }catch(\Throwable $e){ $doc->status='failed'; $doc->error=$e->getMessage(); $doc->save(); }
        return redirect()->route('knowledge.docs.index')->with('success','Dokument nahrán.');
    }

    protected function ingestText(KnowledgeDocument $doc, string $content): void
    {
        // Normalize and chunk simple text (basic splitter by paragraphs with overlap)
        $text = preg_replace("/[\r\t]+/"," ", $content);
        $paras = preg_split("/\n\n+/", (string)$text) ?: [];
        $chunkSize = 800; $overlap = 100;
        $buf = ''; $idx = 0;
        foreach ($paras as $p) {
            if (mb_strlen($buf) + mb_strlen($p) + 1 > $chunkSize) {
                if ($buf !== '') {
                    KnowledgeChunk::create(['document_id'=>$doc->id,'chunk_index'=>$idx++,'text'=>$buf,'meta'=>['title'=>$doc->title]]);
                    // create overlap
                    $buf = mb_substr($buf, max(0, mb_strlen($buf)-$overlap));
                }
            }
            $buf .= ($buf ? "\n\n" : '') . trim($p);
        }
        if (trim($buf) !== '') {
            KnowledgeChunk::create(['document_id'=>$doc->id,'chunk_index'=>$idx++,'text'=>$buf,'meta'=>['title'=>$doc->title]]);
        }
    }

    public function reindex(int $id): RedirectResponse
    {
        $doc = KnowledgeDocument::findOrFail($id);
        if ($doc->status !== 'ready') { return redirect()->back()->with('error','Dokument není ve stavu ready.'); }
        IndexKnowledgeDocumentJob::dispatch($doc->id);
        return redirect()->back()->with('success','Reindex naplánován.');
    }

    public function purge(int $id, QdrantClient $qdrant): RedirectResponse
    {
        if (!config('qdrant.enabled')) { return redirect()->back()->with('error','Qdrant je vypnutý.'); }
        $collection = (string) config('qdrant.collection');
        $ok = $qdrant->deleteByFilter($collection, ['must' => [ ['match' => ['key'=>'document_id','value'=>(int)$id]] ]]);
        if ($ok) {
            DB::table('knowledge_documents')->where('id',$id)->update(['vectorized_at'=>null,'vectors_count'=>0,'last_index_duration_ms'=>null]);
            DB::table('knowledge_chunks')->where('document_id',$id)->update(['embedded_at'=>null,'qdrant_point_id'=>null]);
        }
        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Vektory smazány.' : 'Smazání se nezdařilo.');
    }
}
