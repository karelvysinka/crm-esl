<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Knowledge\QdrantClient;

class QdrantMaintenanceController extends Controller
{
    public function recreate(Request $request, QdrantClient $qdrant)
    {
        $request->validate([
            'dimension' => 'required|integer|min:64|max:8192',
        ]);
        $collection = (string) config('qdrant.collection', 'crm_knowledge');
        $dim = (int) $request->input('dimension');
        $ok = false;
        try { $ok = $qdrant->recreateCollection($collection, $dim, 'Cosine'); } catch (\Throwable $e) { $ok = false; }
        return redirect()->route('system.qdrant.index')->with('status', $ok ? 'Kolekce znovu vytvořena.' : 'Recreate selhalo.');
    }

    public function purgeReindex()
    {
        $collection = (string) config('qdrant.collection', 'crm_knowledge');
        $dim = (int) (\App\Models\SystemSetting::get('embeddings.dimension', config('qdrant.embeddings.dimension', 1536)) ?: 1536);
        try {
            app(\App\Services\Knowledge\QdrantClient::class)->recreateCollection($collection, $dim, 'Cosine');
            \Artisan::call('knowledge:reindex');
            $out = trim(\Artisan::output());
        } catch (\Throwable $e) {
            $out = $e->getMessage();
        }
        return redirect()->route('system.qdrant.index')->with('status', 'Purge & Reindex spuštěno. '.$out);
    }
}
