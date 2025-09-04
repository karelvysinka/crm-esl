<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\AI\Tools\ContactsTool;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ChatSettingsController extends Controller
{
    public function index()
    {
        if (!(auth()->check() && (bool) (auth()->user()->is_admin ?? false))) {
            abort(403);
        }
        $data = [
            'enabled' => (bool) (SystemSetting::get('chat.enabled', '0')),
            'provider' => SystemSetting::get('chat.provider', 'openrouter'),
            'openrouter_key' => SystemSetting::get('chat.openrouter_api_key', ''),
            'openrouter_model' => SystemSetting::get('chat.openrouter_model', 'deepseek/deepseek-chat-v3-0324:free'),
            'gemini_key' => SystemSetting::get('chat.gemini_api_key', ''),
            'gemini_model' => SystemSetting::get('chat.gemini_model', 'gemini-1.5-flash'),
            'gemini_enabled' => (bool) (SystemSetting::get('chat.gemini_enabled', '0')),
            'show_diag_badges' => (bool) (SystemSetting::get('chat.show_diag_badges', '0')),
            'links_same_tab' => (bool) (SystemSetting::get('chat.links_same_tab', '1')),
        ];
        return view('system.chat.index', $data);
    }

    // One-off admin utility: backfill normalized_email/normalized_phone for legacy rows
    public function backfillNormalized()
    {
        if (!(auth()->check() && (bool) (auth()->user()->is_admin ?? false))) {
            abort(403);
        }
    $updated = 0;
    $skipped = 0;
    $dupes = [];
        $now = now();
        \Illuminate\Support\Facades\DB::table('contacts')
            ->select('id','email','phone')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (&$updated, $now) {
                foreach ($rows as $row) {
                    $normEmail = null;
                    if (!empty($row->email)) {
                        $normEmail = mb_strtolower(trim((string) $row->email));
                        if (str_contains($normEmail, '@placeholder.local') || str_starts_with($normEmail, 'noemail-')) {
                            $normEmail = null;
                        }
                    }
                    // phone normalization
                    $p = !empty($row->phone) ? preg_replace('/[^0-9+]/', '', (string) $row->phone) : null;
                    if ($p && !str_starts_with($p, '+')) {
                        $digits = preg_replace('/\D/', '', $p);
                        if ($digits && preg_match('/^420?\d{9}$/', $digits)) {
                            if (strlen($digits) === 12 && str_starts_with($digits, '420')) { $p = '+'.$digits; }
                            elseif (strlen($digits) === 9) { $p = '+420'.$digits; }
                        }
                    }
                    $normPhone = $p ?: null;

                    try {
                        \Illuminate\Support\Facades\DB::table('contacts')
                            ->where('id', $row->id)
                            ->update([
                                'normalized_email' => $normEmail,
                                'normalized_phone' => $normPhone,
                                'updated_at' => $now,
                            ]);
                        $updated++;
                    } catch (\Throwable $e) {
                        // Likely unique collision on normalized_email; record and continue
                        $skipped++;
                        if ($normEmail && count($dupes) < 10) {
                            $dupes[] = ['id' => $row->id, 'email' => $row->email, 'normalized_email' => $normEmail];
                        }
                    }
                }
            });

        $msg = 'Backfill hotov. Aktualizováno: '.$updated;
        if ($skipped > 0) { $msg .= '; přeskočeno: '.$skipped.' (pravděpodobně duplicitní normalized_email).'; }
        if (!empty($dupes)) { $msg .= ' Ukázky duplicit: '.json_encode($dupes, JSON_UNESCAPED_UNICODE); }
        return redirect()->route('system.chat.index')->with('status', $msg);
    }

    public function save(Request $request)
    {
        if (!(auth()->check() && (bool) (auth()->user()->is_admin ?? false))) {
            abort(403);
        }
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'provider' => 'required|in:openrouter,gemini',
            'openrouter_key' => 'nullable|string',
            'openrouter_model' => 'nullable|string',
            'gemini_key' => 'nullable|string',
            'gemini_model' => 'nullable|string',
            'gemini_enabled' => 'nullable|boolean',
            'show_diag_badges' => 'nullable|boolean',
            'links_same_tab' => 'nullable|boolean',
        ]);

        SystemSetting::set('chat.enabled', $request->boolean('enabled') ? '1' : '0');
        SystemSetting::set('chat.provider', $validated['provider']);
        if (isset($validated['openrouter_key'])) SystemSetting::set('chat.openrouter_api_key', $validated['openrouter_key']);
        if (isset($validated['openrouter_model'])) SystemSetting::set('chat.openrouter_model', $validated['openrouter_model']);
        if (isset($validated['gemini_key'])) SystemSetting::set('chat.gemini_api_key', $validated['gemini_key']);
        if (isset($validated['gemini_model'])) SystemSetting::set('chat.gemini_model', $validated['gemini_model']);
        SystemSetting::set('chat.gemini_enabled', $request->boolean('gemini_enabled') ? '1' : '0');
    SystemSetting::set('chat.show_diag_badges', $request->boolean('show_diag_badges') ? '1' : '0');
    SystemSetting::set('chat.links_same_tab', $request->boolean('links_same_tab') ? '1' : '0');

        return redirect()->route('system.chat.index')->with('status', 'Nastavení uloženo.');
    }

    public function test(Request $request)
    {
        if (!(auth()->check() && (bool) (auth()->user()->is_admin ?? false))) {
            abort(403);
        }
        $provider = SystemSetting::get('chat.provider', 'openrouter');
        try {
            if ($provider === 'openrouter') {
                $key = (string) SystemSetting::get('chat.openrouter_api_key', '');
                $model = (string) SystemSetting::get('chat.openrouter_model', 'deepseek/deepseek-chat-v3-0324:free');
                if (!$key) return back()->with('status', 'Chybí OpenRouter API klíč.');
                $resp = Http::withToken($key)
                    ->acceptJson()
                    ->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => 'Test připojení. Odpověz jedním slovem: OK.'],
                            ['role' => 'user', 'content' => 'Test.'],
                        ],
                        'stream' => false,
                        'max_tokens' => 5,
                    ]);
                if ($resp->ok()) {
                    return back()->with('status', 'OpenRouter OK (HTTP '.$resp->status().').');
                }
                $msg = 'OpenRouter chyba (HTTP '.$resp->status().').';
                $body = (string) $resp->body();
                if ($body) { $msg .= ' Odpověď: '.mb_strimwidth($body, 0, 300, '…'); }
                return back()->with('status', $msg);
            }
            if ($provider === 'gemini') {
                $key = (string) SystemSetting::get('chat.gemini_api_key', '');
                $model = (string) SystemSetting::get('chat.gemini_model', 'gemini-1.5-flash');
                if (!$key) return back()->with('status', 'Chybí Gemini API klíč.');
                // Jednoduchý test přes generative language API (non-stream)
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.urlencode($model).':generateContent?key='.urlencode($key);
                $resp = Http::acceptJson()->post($url, [
                    'contents' => [
                        ['parts' => [['text' => 'Test připojení. Odpověz OK.']]]
                    ]
                ]);
                if ($resp->ok()) {
                    return back()->with('status', 'Gemini OK (HTTP '.$resp->status().').');
                }
                $msg = 'Gemini chyba (HTTP '.$resp->status().').';
                $body = (string) $resp->body();
                if ($body) { $msg .= ' Odpověď: '.mb_strimwidth($body, 0, 300, '…'); }
                return back()->with('status', $msg);
            }
        } catch (\Throwable $e) {
            return back()->with('status', 'Chyba testu: '.$e->getMessage());
        }
        return back()->with('status', 'Provider není podporován.');
    }

    public function diagnostics()
    {
        if (!(auth()->check() && (bool) (auth()->user()->is_admin ?? false))) {
            abort(403);
        }
    $actions = DB::table('chat_actions')->orderByDesc('id')->limit(50)->get();
    $toolMessages = DB::table('chat_messages')->where('role', 'tool')->orderByDesc('id')->limit(50)->get();

        // Aggregate last N hours metrics from metrics.stream
        $hours = max(1, (int) request('hours', 24));
        $since = now()->subHours($hours);
        $metricsRows = DB::table('chat_actions')
            ->where('tool_name', 'metrics.stream')
            ->where('created_at', '>=', $since)
            ->orderByDesc('id')
            ->limit(5000)
            ->get();

        // Recent chat queries and answers within the same time window
        $chatMessages = DB::table('chat_messages')
            ->whereIn('role', ['user','assistant'])
            ->where('created_at', '>=', $since)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $ttfts = [];
        $durs = [];
        $chars = [];
        $byProviderModel = [];
        foreach ($metricsRows as $row) {
            $out = json_decode($row->outputs ?? '{}', true) ?: [];
            $inp = json_decode($row->inputs ?? '{}', true) ?: [];
            $prov = (string) ($inp['provider'] ?? 'unknown');
            $model = (string) ($inp['model'] ?? '');
            $ttft = isset($out['ttft_ms']) ? (int) $out['ttft_ms'] : null;
            $dur = isset($out['duration_ms']) ? (int) $out['duration_ms'] : null;
            $len = isset($out['chars']) ? (int) $out['chars'] : null;
            if ($ttft !== null) $ttfts[] = $ttft;
            if ($dur !== null) $durs[] = $dur;
            if ($len !== null) $chars[] = $len;
            $key = $prov.'|'.$model;
            if (!isset($byProviderModel[$key])) {
                $byProviderModel[$key] = ['provider' => $prov, 'model' => $model, 'count' => 0, 'ttft' => [], 'dur' => [], 'chars' => []];
            }
            $byProviderModel[$key]['count']++;
            if ($ttft !== null) $byProviderModel[$key]['ttft'][] = $ttft;
            if ($dur !== null) $byProviderModel[$key]['dur'][] = $dur;
            if ($len !== null) $byProviderModel[$key]['chars'][] = $len;
        }

        $summary = [
            'hours' => $hours,
            'samples' => count($metricsRows),
            'avg_ttft_ms' => $this->avg($ttfts),
            'p95_ttft_ms' => $this->p($ttfts, 95),
            'avg_duration_ms' => $this->avg($durs),
            'p95_duration_ms' => $this->p($durs, 95),
            'avg_chars' => $this->avg($chars),
        ];

        $breakdown = [];
        foreach ($byProviderModel as $k => $v) {
            $breakdown[] = [
                'provider' => $v['provider'],
                'model' => $v['model'],
                'count' => $v['count'],
                'avg_ttft_ms' => $this->avg($v['ttft']),
                'p95_ttft_ms' => $this->p($v['ttft'], 95),
                'avg_duration_ms' => $this->avg($v['dur']),
                'p95_duration_ms' => $this->p($v['dur'], 95),
                'avg_chars' => $this->avg($v['chars']),
            ];
        }

    return view('system.chat.diagnostics', compact('actions', 'toolMessages', 'summary', 'breakdown', 'chatMessages'));
    }

    // Lightweight admin lookup utility to debug contact resolution via ContactsTool
    public function lookup(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return response()->json(['ok' => false, 'error' => 'Missing q parameter'], 400);
        }
        $tool = new ContactsTool();
        $result = [];
        $meta = [];
        $debug = [];
        if (str_contains($q, '@')) {
            $norm = mb_strtolower(trim($q));
            $meta['type'] = 'email';
            $meta['normalized'] = $norm;
            $result = $tool->findByEmail($q);
            // Debug counts for diagnostics
            $debug['normalized_email_eq'] = DB::table('contacts')->where('normalized_email', $norm)->count();
            $debug['raw_email_eq'] = DB::table('contacts')->where('email', $norm)->count();
            $debug['raw_email_trim_lower_eq'] = DB::table('contacts')->whereRaw('TRIM(LOWER(email)) = ?', [$norm])->count();
            // Suggestions: show a few similar emails
            $local = explode('@', $norm)[0] ?? $norm;
            $like = '%'.str_replace(['%','_'], ['\\%','\\_'], $local).'%';
            $debug['suggestions'] = DB::table('contacts')
                ->select('id','email','normalized_email')
                ->where(function($w) use ($like){
                    $w->where('email', 'like', $like)->orWhere('normalized_email','like',$like);
                })
                ->limit(5)
                ->get();
        } else {
            $p = preg_replace('/[^0-9+]/', '', $q);
            if ($p && !str_starts_with($p, '+')) {
                $digits = preg_replace('/\D/', '', $p);
                if ($digits && preg_match('/^420?\d{9}$/', $digits)) {
                    if (strlen($digits) === 12 && str_starts_with($digits, '420')) { $p = '+'.$digits; }
                    elseif (strlen($digits) === 9) { $p = '+420'.$digits; }
                }
            }
            $meta['type'] = 'phone';
            $meta['normalized'] = $p;
            $result = $tool->findByPhone($q);
            $debug['normalized_phone_eq'] = $p ? DB::table('contacts')->where('normalized_phone', $p)->count() : 0;
            $debug['raw_phone_eq'] = DB::table('contacts')->where('phone', $p)->count();
        }
        return response()->json(['ok' => true, 'q' => $q, 'meta' => $meta, 'result' => $result, 'debug' => $debug]);
    }

    private function avg(array $xs): ?int
    {
        $xs = array_values(array_filter($xs, fn($v) => $v !== null));
        if (!$xs) return null;
        return (int) round(array_sum($xs) / count($xs));
    }

    private function p(array $xs, int $p): ?int
    {
        $xs = array_values(array_filter($xs, fn($v) => $v !== null));
        if (!$xs) return null;
        sort($xs);
        $idx = (int) floor((max(0, min(100, $p)) / 100) * (count($xs) - 1));
        return (int) $xs[$idx];
    }
}
