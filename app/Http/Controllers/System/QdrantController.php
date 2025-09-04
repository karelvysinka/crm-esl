<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\Knowledge\QdrantClient;
use App\Services\Knowledge\EmbeddingsService;
use App\Models\SystemSetting;

class QdrantController extends Controller
{
    public function index(Request $request, QdrantClient $qdrant)
    {
        $collection = (string) config('qdrant.collection', 'crm_knowledge');
        $baseUrl = (string) config('qdrant.url');

        // Try health check (best-effort)
        $health = null;
        try {
            $resp = Http::timeout(5)->get(rtrim($baseUrl, '/').'/healthz');
            $health = $resp->ok() ? ($resp->json()['status'] ?? 'ok') : 'unavailable';
        } catch (\Throwable $e) {
            $health = 'unavailable';
        }

        // Ensure collection exists (best-effort)
        $ensured = false;
        try { $ensured = $qdrant->ensureCollection($collection); } catch (\Throwable $e) { $ensured = false; }

        // Count vectors
        $total = 0;
        try { $total = $qdrant->count($collection); } catch (\Throwable $e) { $total = 0; }

        return view('system.qdrant.index', [
            'baseUrl' => $baseUrl,
            'collection' => $collection,
            'health' => $health,
            'ensured' => $ensured,
            'total' => $total,
            'results' => null,
            'query' => '',
            'embed' => $this->embedConfig(),
        ]);
    }

    public function verify(Request $request, QdrantClient $qdrant, EmbeddingsService $embeddings)
    {
        $collection = (string) config('qdrant.collection', 'crm_knowledge');
        $baseUrl = (string) config('qdrant.url');
        $checks = [
            'embedder' => 'unknown',
            'qdrant' => 'unknown',
            'dimension_match' => 'unknown',
        ];
        $messages = [];
        $embedderModel = null;
        // Embedder health
        try {
            $localUrl = (string) \App\Models\SystemSetting::get('embeddings.local_url', (string) config('qdrant.embeddings.local_url'));
            $resp = \Illuminate\Support\Facades\Http::timeout(5)->get(rtrim($localUrl, '/').'/healthz');
            $checks['embedder'] = $resp->ok() ? 'ok' : 'fail';
            if ($resp->ok()) { $embedderModel = $resp->json()['model'] ?? null; }
            if (!$resp->ok()) { $messages[] = 'Embedder health: '.($resp->status()); }
        } catch (\Throwable $e) { $checks['embedder'] = 'fail'; $messages[] = 'Embedder error: '.$e->getMessage(); }
        // Qdrant health and vectors size
        $qdrantOk = false; $qdrantSize = null;
        try {
            $resp = \Illuminate\Support\Facades\Http::timeout(5)->get(rtrim($baseUrl, '/').'/healthz');
            $qdrantOk = $resp->ok();
        } catch (\Throwable $e) { $qdrantOk = false; $messages[] = 'Qdrant error: '.$e->getMessage(); }
        $checks['qdrant'] = $qdrantOk ? 'ok' : 'fail';
        try {
            $info = \Illuminate\Support\Facades\Http::timeout(5)->get(rtrim($baseUrl, '/').'/collections/'.urlencode($collection))->json();
            $qdrantSize = $info['result']['config']['params']['vectors']['size'] ?? null;
        } catch (\Throwable $e) { $messages[] = 'Qdrant collection info error: '.$e->getMessage(); }
        // Compare dimension
    $dim = (int) (\App\Models\SystemSetting::get('embeddings.dimension', config('qdrant.embeddings.dimension', 0)) ?: 0);
        if ($dim && $qdrantSize) { $checks['dimension_match'] = ($dim === (int) $qdrantSize) ? 'ok' : 'mismatch'; }
        if ($checks['dimension_match'] === 'mismatch') { $messages[] = 'Rozdílná dimenze: UI='.$dim.' vs Qdrant='.$qdrantSize; }

        // Mini test: embed sample text and try a small search (best-effort)
        $searchCount = null; $sampleText = 'Test vyhledávání v CRM';
        try {
            $vec = $embeddings->embed($sampleText);
            if (is_array($vec)) {
                $mini = $qdrant->search($collection, $vec, 5);
                if (is_array($mini)) { $searchCount = count($mini); }
            }
        } catch (\Throwable $e) {
            $messages[] = 'Mini test selhal: '.$e->getMessage();
        }

        // Render back to index with checks summary
        // Also include usual status badges
        $health = null; $ensured = false; $total = 0;
        try { $resp = \Illuminate\Support\Facades\Http::timeout(5)->get(rtrim($baseUrl, '/').'/healthz'); $health = $resp->ok() ? ($resp->json()['status'] ?? 'ok') : 'unavailable'; } catch (\Throwable $e) { $health = 'unavailable'; }
        try { $ensured = $qdrant->ensureCollection($collection); } catch (\Throwable $e) { $ensured = false; }
        try { $total = $qdrant->count($collection); } catch (\Throwable $e) { $total = 0; }

        return view('system.qdrant.index', [
            'baseUrl' => $baseUrl,
            'collection' => $collection,
            'health' => $health,
            'ensured' => $ensured,
            'total' => $total,
            'results' => null,
            'query' => '',
            'embed' => $this->embedConfig(),
            'verify' => $checks,
            'verify_messages' => $messages,
            'verify_dim' => $dim,
            'verify_qdrant_size' => $qdrantSize,
            'verify_embedder_model' => $embedderModel,
            'verify_search_count' => $searchCount,
            'verify_sample' => $sampleText,
        ]);
    }

    public function test(Request $request, EmbeddingsService $embeddings, QdrantClient $qdrant)
    {
        $request->validate(['q' => 'required|string|min:1']);
        $q = (string) $request->input('q');
        $collection = (string) config('qdrant.collection', 'crm_knowledge');

        $results = [];
        $error = null;
        try {
            $vector = $embeddings->embed($q);
            $results = $qdrant->search($collection, $vector, 5);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        // Current status
        $baseUrl = (string) config('qdrant.url');
        $health = null;
        try {
            $resp = Http::timeout(5)->get(rtrim($baseUrl, '/').'/healthz');
            $health = $resp->ok() ? ($resp->json()['status'] ?? 'ok') : 'unavailable';
        } catch (\Throwable $e) {
            $health = 'unavailable';
        }
        $ensured = false;
        try { $ensured = $qdrant->ensureCollection($collection); } catch (\Throwable $e) { $ensured = false; }
        $total = 0;
        try { $total = $qdrant->count($collection); } catch (\Throwable $e) { $total = 0; }

        return view('system.qdrant.index', [
            'baseUrl' => $baseUrl,
            'collection' => $collection,
            'health' => $health,
            'ensured' => $ensured,
            'total' => $total,
            'results' => $results,
            'query' => $q,
            'error' => $error,
            'embed' => $this->embedConfig(),
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|in:openai,openrouter,gemini,local',
            'model' => 'required|string|min:1',
            'openrouter_api_key' => 'nullable|string',
            'openrouter_referer' => 'nullable|string',
            'openrouter_title' => 'nullable|string',
            'local_url' => 'nullable|url',
            'dimension' => 'nullable|integer|min:128|max:8192',
        ]);

        SystemSetting::set('embeddings.provider', $validated['provider']);
        SystemSetting::set('embeddings.model', $validated['model']);
        if (array_key_exists('openrouter_api_key', $validated)) {
            SystemSetting::set('embeddings.openrouter_api_key', $validated['openrouter_api_key']);
        }
        if (array_key_exists('openrouter_referer', $validated)) {
            SystemSetting::set('embeddings.openrouter_referer', $validated['openrouter_referer']);
        }
        if (array_key_exists('openrouter_title', $validated)) {
            SystemSetting::set('embeddings.openrouter_title', $validated['openrouter_title']);
        }
        // Store dimension as string to keep parity with other settings
        if (array_key_exists('dimension', $validated)) {
            SystemSetting::set('embeddings.dimension', (string) ($validated['dimension'] ?? ''));
        }
        if (array_key_exists('local_url', $validated)) {
            SystemSetting::set('embeddings.local_url', (string) ($validated['local_url'] ?? ''));
        }

        return redirect()->route('system.qdrant.index')->with('status', 'Embeddings nastavení uloženo.');
    }

    private function embedConfig(): array
    {
        // Prefer SystemSetting overrides; fallback to config
        $get = fn(string $k, $def=null) => SystemSetting::get('embeddings.'.$k, $def);
        return [
            'provider' => $get('provider', (string) config('qdrant.embeddings.provider', 'openai')),
            'model' => $get('model', (string) config('qdrant.embeddings.model', 'text-embedding-3-small')),
            'openrouter_api_key' => $get('openrouter_api_key', (string) config('qdrant.embeddings.openrouter_api_key')),
            'openrouter_referer' => $get('openrouter_referer', (string) config('qdrant.embeddings.openrouter_referer')),
            'openrouter_title' => $get('openrouter_title', (string) config('qdrant.embeddings.openrouter_title', 'CRM ESL')),
            'local_url' => $get('local_url', (string) config('qdrant.embeddings.local_url')),
            'dimension' => $get('dimension', config('qdrant.embeddings.dimension')),
        ];
    }
}
