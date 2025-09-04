<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeNote;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $notes = KnowledgeNote::query()
            ->when($q !== '', function($w) use ($q) {
                $w->where('title','like',"%$q%")
                  ->orWhere('content','like',"%$q%")
                  ->orWhereJsonContains('tags', $q);
            })
            ->where(function($w){
                $w->where('visibility','public');
                if (auth()->check()) {
                    $w->orWhere('user_id', auth()->id());
                }
            })
            ->orderByDesc('updated_at')
            ->paginate(20);
        return view('crm.knowledge.index', compact('notes','q'));
    }

    public function create(): View
    {
        return view('crm.knowledge.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'content' => ['required','string'],
            'tags' => ['nullable','string'],
            'visibility' => ['required','in:public,private'],
        ]);
        $tags = array_values(array_filter(array_map('trim', explode(',', (string)($data['tags'] ?? '')))));
        $note = KnowledgeNote::create([
            'user_id' => auth()->id(),
            'title' => $data['title'],
            'content' => $data['content'],
            'tags' => $tags,
            'visibility' => $data['visibility'],
        ]);
        return redirect()->route('knowledge.index')->with('success', 'Poznámka uložena.');
    }

    public function edit(KnowledgeNote $knowledge): View
    {
        $this->authorize('update', $knowledge);
        return view('crm.knowledge.edit', ['note' => $knowledge]);
    }

    public function update(Request $request, KnowledgeNote $knowledge): RedirectResponse
    {
        $this->authorize('update', $knowledge);
        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'content' => ['required','string'],
            'tags' => ['nullable','string'],
            'visibility' => ['required','in:public,private'],
        ]);
        $knowledge->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string)($data['tags'] ?? ''))))),
            'visibility' => $data['visibility'],
        ]);
        return redirect()->route('knowledge.index')->with('success', 'Poznámka aktualizována.');
    }

    public function destroy(KnowledgeNote $knowledge): RedirectResponse
    {
        $this->authorize('delete', $knowledge);
        $knowledge->delete();
        return redirect()->route('knowledge.index')->with('success', 'Poznámka smazána.');
    }

    // Lightweight AJAX search for chat retriever
    public function search(Request $request)
    {
        $q = trim((string)$request->get('q',''));
        if($q===''){ return response()->json(['items'=>[]]); }
        $items = KnowledgeNote::query()
            ->where(function($w) use ($q){
                $w->where('title','like',"%$q%")
                  ->orWhere('content','like',"%$q%")
                  ->orWhereJsonContains('tags', $q);
            })
            ->where(function($w){
                $w->where('visibility','public');
                if (auth()->check()) { $w->orWhere('user_id', auth()->id()); }
            })
            ->limit(5)
            ->get(['id','title','content','tags','updated_at']);
        // compress content to a short snippet
        $items = $items->map(function($n){
            $snippet = trim(mb_substr(strip_tags($n->content), 0, 500));
            return [
                'id' => $n->id,
                'title' => $n->title,
                'snippet' => $snippet,
                'tags' => $n->tags,
                'updated_at' => $n->updated_at,
            ];
        });
        return response()->json(['items' => $items]);
    }
}
