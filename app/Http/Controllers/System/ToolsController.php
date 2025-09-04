<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\ChatToolAudit;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;

class ToolsController extends Controller
{
    public function index(Request $request): View
    {
        // Playwright health
        $runnerUrl = SystemSetting::get('tools.playwright.url', env('TOOLS_PLAYWRIGHT_URL', 'http://playwright-runner:3000'));
        $health = ['ok' => false, 'error' => null];
        try {
            $resp = Http::timeout(3)->get(rtrim($runnerUrl,'/').'/healthz');
            $health['ok'] = $resp->ok();
            if (!$resp->ok()) { $health['error'] = 'HTTP '.$resp->status(); }
        } catch (\Throwable $e) {
            $health['ok'] = false; $health['error'] = $e->getMessage();
        }
        $audits = ChatToolAudit::where('tool','playwright')->orderByDesc('id')->limit(10)->get();

        $tools = [
            [
                'key' => 'playwright',
                'name' => 'Playwright – procházení webu',
                'enabled' => SystemSetting::get('tools.playwright.enabled', '0') === '1',
        'url' => SystemSetting::get('tools.playwright.url', env('TOOLS_PLAYWRIGHT_URL', 'http://playwright-runner:3000')),
        'timeout_ms' => (int) (SystemSetting::get('tools.playwright.timeout_ms', env('TOOLS_PLAYWRIGHT_TIMEOUT_MS', '20000'))),
        'max_steps' => (int) (SystemSetting::get('tools.playwright.max_steps', env('TOOLS_PLAYWRIGHT_MAX_STEPS', '5'))),
        'allowed_domains' => SystemSetting::get('tools.playwright.allowed_domains', env('TOOLS_PLAYWRIGHT_ALLOWED_DOMAINS', 'esl.cz')),
                'description' => 'Nástroj pro procházení webových stránek, získávání obsahu a vykonávání akcí (vyhledávání, klikání). Vhodné pro zjištění informací z internetu v reálném čase.',
                'docs' => route('system.tools.index').'#playwright',
                'health' => $health,
                'audits' => $audits,
            ],
        ];
        return view('system.tools.index', compact('tools'));
    }

    public function togglePlaywright(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('enabled');
        SystemSetting::set('tools.playwright.enabled', $enabled ? '1' : '0');
        return redirect()->route('system.tools.index')->with('status', 'Playwright '.($enabled ? 'zapnut' : 'vypnut').'.');
    }

    public function savePlaywright(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'timeout_ms' => 'required|integer|min:1000|max:60000',
            'max_steps' => 'required|integer|min:1|max:10',
            'allowed_domains' => 'required|string|max:500',
        ]);
        SystemSetting::set('tools.playwright.url', $validated['url']);
        SystemSetting::set('tools.playwright.timeout_ms', (string) $validated['timeout_ms']);
        SystemSetting::set('tools.playwright.max_steps', (string) $validated['max_steps']);
        SystemSetting::set('tools.playwright.allowed_domains', $validated['allowed_domains']);
        return redirect()->route('system.tools.index')->with('status', 'Playwright nastavení uloženo.');
    }

    public function testPlaywright(Request $request): RedirectResponse
    {
        try {
            $url = $request->input('test_url', 'https://www.esl.cz/');
            $allowed = array_values(array_filter(array_map('trim', explode(',', SystemSetting::get('tools.playwright.allowed_domains', 'esl.cz')))));
            $timeout = (int) SystemSetting::get('tools.playwright.timeout_ms', '20000');
            $out = app(\App\Services\Tools\PlaywrightTool::class)->fetch($url, [], $allowed, $timeout, auth()->id(), null, [
                'respect_robots' => true,
                'screenshot' => ['enabled' => true, 'fullPage' => false, 'type' => 'jpeg', 'quality' => 60],
            ]);
            if (!($out['ok'] ?? false)) {
                return redirect()->route('system.tools.index')->with('status', 'Test selhal: '.json_encode($out));
            }
            $hasShot = !empty($out['screenshot']);
            return redirect()->route('system.tools.index')->with('status', 'Test OK za '.(($out['timings']['total_ms'] ?? 0)).' ms, zdroje: '.json_encode($out['sources'] ?? []).($hasShot ? ' (vč. screenshotu)' : ''));
        } catch (\Throwable $e) {
            return redirect()->route('system.tools.index')->with('status', 'Chyba testu: '.$e->getMessage());
        }
    }
}
