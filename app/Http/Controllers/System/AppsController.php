<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AppLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AppsController extends Controller
{
    private function ensureAdmin(): void
    {
        if (!auth()->check() || !auth()->user()->is_admin) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();
        $links = AppLink::orderBy('position')->orderBy('id')->get();
        return view('system.apps.index', compact('links'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:255',
            'icon_url' => 'nullable|url|max:255',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? true);
        $data['position'] = $data['position'] ?? 0;
        AppLink::create($data);
    Cache::forget('topbar.app_links');
        return back()->with('success', 'Aplikace přidána.');
    }

    public function update(Request $request, AppLink $appLink)
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:255',
            'icon_url' => 'nullable|url|max:255',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? false);
        $appLink->update($data);
    Cache::forget('topbar.app_links');
        return back()->with('success', 'Aplikace aktualizována.');
    }

    public function destroy(Request $request, AppLink $appLink)
    {
        $this->ensureAdmin();
        $appLink->delete();
    Cache::forget('topbar.app_links');
        return back()->with('success', 'Aplikace odstraněna.');
    }
}
