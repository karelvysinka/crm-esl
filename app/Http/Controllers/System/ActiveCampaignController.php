<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\ActiveCampaignClient;
use App\Services\ActiveCampaignImporter;
use App\Jobs\ImportActiveCampaignAll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\SystemSetting;
use App\Jobs\ActiveCampaignSyncJob;

class ActiveCampaignController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdmin($request);
    $enabled = (bool) json_decode((string) optional(SystemSetting::where('key','ac_sync_enabled')->first())->value ?: 'false');
    $offset = (int) json_decode((string) optional(SystemSetting::where('key','ac_sync_offset')->first())->value ?: '0');
    $runs = \App\Models\AcSyncRun::orderByDesc('id')->limit(20)->get();
    return view('system.activecampaign.index', compact('enabled','offset','runs'));
    }

    public function test(Request $request, ActiveCampaignClient $ac)
    {
        try {
            $this->ensureAdmin($request);
            $key = 'ac-test:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableIn($key);
                return back()->with('error', 'Příliš mnoho testů. Zkuste znovu za '.$seconds.' s.');
            }
            RateLimiter::hit($key, 60);
            $base = (string) config('services.activecampaign.base_url');
            $token = (string) config('services.activecampaign.api_token');
            if (!$base || !preg_match('#^https?://#', $base) || !$token) {
                return back()->with('error', 'Chybí nebo je neplatná konfigurace ActiveCampaign. Nastavte prosím AC_BASE_URL (např. https://YOURACCOUNT.api-us1.com) a AC_API_TOKEN v .env a zkuste znovu.');
            }
            $data = $ac->get('users/me');
            return back()->with('success', 'Připojení k ActiveCampaign OK.')->with('ac_me', $data);
        } catch (\Throwable $e) {
            return back()->with('error', 'AC test selhal: '.$e->getMessage());
        }
    }

    public function importTen(Request $request, ActiveCampaignClient $ac)
    {
        $this->ensureAdmin($request);
        return $this->importProcess($ac, 10, 0, 'Import 10');
    }

    public function importBatch(Request $request, ActiveCampaignClient $ac)
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:500',
            'offset' => 'nullable|integer|min:0',
        ]);
        $limit = $validated['limit'] ?? 100;
        $offset = $validated['offset'] ?? 0;
        return $this->importProcess($ac, $limit, $offset, "Import {$limit} (offset {$offset})");
    }

    public function importAll(Request $request)
    {
        $this->ensureAdmin($request);
        $key = 'ac-import-all:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->with('error', 'Příliš mnoho požadavků na Import všeho. Zkuste znovu za '.$seconds.' s.');
        }
        RateLimiter::hit($key, 300); // 3 per 5 minutes
        $limit = (int)($request->input('limit', 100));
        $start = (int)($request->input('start', 0));
        $max = $request->filled('max') ? (int)$request->input('max') : null;

        ImportActiveCampaignAll::dispatch($limit, $start, $max);
        return back()->with('success', "Úloha 'Import všeho' byla zařazena do fronty. Limit: {$limit}, start: {$start}. Sledujte logy.");
    }

    public function toggleAuto(Request $request)
    {
        $this->ensureAdmin($request);
        $enabled = (bool) $request->input('enabled', false);
        SystemSetting::updateOrCreate(['key'=>'ac_sync_enabled'], ['value'=>json_encode($enabled)]);
        return back()->with('success', 'Automatická synchronizace ' . ($enabled ? 'zapnuta' : 'vypnuta'));
    }

    public function resetOffset(Request $request)
    {
        $this->ensureAdmin($request);
        SystemSetting::updateOrCreate(['key'=>'ac_sync_offset'], ['value'=>json_encode(0)]);
        return back()->with('success', 'Offset pro synchronizaci byl resetován na 0.');
    }

    public function runBatch(Request $request)
    {
        $this->ensureAdmin($request);
        $limit = (int) $request->input('limit', 200);
    ActiveCampaignSyncJob::dispatch($limit, true);
        return back()->with('success', 'Synchronizační dávka byla zařazena do fronty.');
    }

    public function runs(Request $request)
    {
        $this->ensureAdmin($request);
        $perPage = (int) $request->input('per_page', 50);
        $perPage = max(10, min(200, $perPage));
        $runs = \App\Models\AcSyncRun::orderByDesc('id')->paginate($perPage)->withQueryString();
        return view('system.activecampaign.runs', compact('runs'));
    }

    private function importProcess(ActiveCampaignClient $ac, int $limit, int $offset, string $label)
    {
        try {
            // rudimentary rate limit per IP to avoid abuse
            $key = 'ac-import:' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableIn($key);
                return back()->with('error', 'Příliš mnoho požadavků. Zkuste znovu za '.$seconds.' s.');
            }
            RateLimiter::hit($key, 60); // 10 req/min

            $query = ['limit' => $limit, 'offset' => $offset, 'orders[cdate]' => 'DESC'];
            $data = $ac->get('contacts', $query);
            if (isset($data['ok']) && $data['ok'] === false) {
                return back()->with('error', $label.' selhal: ActiveCampaign API je vyčerpáno nebo zakázáno (403/429). Zkuste to prosím později.');
            }
            $out = 'ac_samples/import_' . $limit . '_' . $offset . '_' . now()->format('Ymd_His') . '.json';
            Storage::disk('local')->put($out, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

            $contacts = $data['contacts'] ?? [];
            $importer = app(ActiveCampaignImporter::class);
            $res = $importer->importContacts($contacts);

            $created = $res['created'];
            $updated = $res['updated'];
            $skipped = $res['skipped'];
            $skippedUnchanged = $res['skippedUnchanged'];
            $errors = $res['errors'];
            $errorSamples = $res['errorSamples'];

            $msg = "{$label} hotov. Vytvořeno: {$created}, aktualizováno: {$updated}, přeskočeno: {$skipped}, beze změny: {$skippedUnchanged}, chyby: {$errors}. JSON: storage/app/{$out}";
            if ($errors && $errorSamples) { $msg .= ' | Ukázka chyb: ' . implode(' | ', $errorSamples); }
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', $label.' selhal: '.$e->getMessage());
        }
    }

    private function ensureAdmin(Request $request): void
    {
        // Interim simple admin gate: allow if
        // - logged-in user id=1, or
        // - header X-Admin-Token matches ADMIN_TOKEN, or
        // - query/input token matches ADMIN_TOKEN (sets session), or
        // - session('admin_ok') already true.
        if (session('admin_ok') === true) { return; }
        // If logged-in and user is admin, allow
        if (function_exists('auth') && auth()->check() && method_exists(auth()->user(), 'getAttribute') && (bool) auth()->user()->getAttribute('is_admin')) {
            return;
        }
        $envToken = config('app.admin_token', env('ADMIN_TOKEN'));
        $ok = false;
        if (function_exists('auth') && auth()->check() && auth()->id() === 1) { $ok = true; }
        if (!$ok && $envToken && hash_equals((string)$envToken, (string) $request->header('X-Admin-Token'))) { $ok = true; }
        $inputToken = (string)$request->input('token', '');
        if (!$ok && $envToken && $inputToken && hash_equals((string)$envToken, $inputToken)) { $ok = true; }
        if ($ok) {
            session(['admin_ok' => true]);
            return;
        }
        abort(403, 'Pouze pro administrátory');
    }
}
