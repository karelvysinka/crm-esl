<?php

namespace App\Services\Tools;

use App\Models\ChatToolAudit;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlaywrightTool
{
    public function fetch(string $url, array $selectors = [], array $allowedDomains = [], int $timeoutMs = 20000, ?int $userId = null, ?int $conversationId = null, array $options = []): array
    {
        $base = rtrim((string) SystemSetting::get('tools.playwright.url', env('TOOLS_PLAYWRIGHT_URL', 'http://playwright-runner:3000')), '/');
        $payload = [
            'url' => $url,
            'selectors' => $selectors,
            'allowed_domains' => $allowedDomains,
            'timeout_ms' => $timeoutMs,
            'respect_robots' => $options['respect_robots'] ?? true,
            'screenshot' => $options['screenshot'] ?? null,
            // Extended options (supported by runner versions s>=2025.06)
            'wait_until' => $options['wait_until'] ?? null,          // 'load' | 'domcontentloaded' | 'networkidle'
            'auto_scroll' => $options['auto_scroll'] ?? null,        // true => scroll to bottom to trigger lazy loading
            'full_text' => $options['full_text'] ?? null,            // true => prefer full textContent over article extractor
            'read_mode' => $options['read_mode'] ?? null,            // 'readability' | 'textContent' | 'innerText'
            'max_chars' => $options['max_chars'] ?? null,            // hard cap for returned text
        ];
    $t0 = microtime(true);
    $httpTimeout = max(7, min(60, (int) ceil($timeoutMs/1000) + 7));
    $resp = Http::timeout($httpTimeout)
            ->acceptJson()
            ->post($base . '/browse/fetch', $payload);
        $ms = (int) round((microtime(true) - $t0) * 1000);
        $json = null; try { $json = $resp->json(); } catch (\Throwable $e) { $json = null; }
        $meta = [
            'status' => $resp->status(),
            'ok' => $resp->ok(),
            'ms' => $ms,
            'runner_timings' => is_array($json) ? ($json['timings'] ?? null) : null,
            'title' => is_array($json) ? ($json['title'] ?? null) : null,
        ];
        // Structured log for diagnostics
        try {
            Log::channel('tools')->info('playwright.fetch', [
                'payload' => $payload,
                'meta' => $meta,
                'user_id' => $userId,
                'conversation_id' => $conversationId,
            ]);
        } catch (\Throwable $e) { /* logging optional */ }
        ChatToolAudit::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'tool' => 'playwright',
            'intent' => 'fetch',
            'payload' => $payload,
            'result_meta' => $meta,
            'duration_ms' => $ms,
        ]);
        if (!$resp->ok()) {
            return [ 'ok' => false, 'error' => 'runner_error', 'status' => $resp->status(), 'body' => $json ];
        }
        return (array) $json;
    }

    /**
     * Fetch multiple targets in parallel. Returns an array of results in the same order as jobs.
     * Each job: ['url'=>..., 'selectors'=>[], 'allowed_domains'=>[], 'timeout_ms'=>int, 'options'=>[]]
     */
    public function fetchMany(array $jobs, ?int $userId = null, ?int $conversationId = null): array
    {
        $base = rtrim((string) SystemSetting::get('tools.playwright.url', env('TOOLS_PLAYWRIGHT_URL', 'http://playwright-runner:3000')), '/');
        $requests = [];
        foreach ($jobs as $i => $job) {
            $payload = [
                'url' => (string)($job['url'] ?? ''),
                'selectors' => $job['selectors'] ?? [],
                'allowed_domains' => $job['allowed_domains'] ?? [],
                'timeout_ms' => (int)($job['timeout_ms'] ?? 20000),
                'respect_robots' => ($job['options']['respect_robots'] ?? true),
                'screenshot' => ($job['options']['screenshot'] ?? null),
                'wait_until' => $job['options']['wait_until'] ?? null,
                'auto_scroll' => $job['options']['auto_scroll'] ?? null,
                'full_text' => $job['options']['full_text'] ?? null,
                'read_mode' => $job['options']['read_mode'] ?? null,
                'max_chars' => $job['options']['max_chars'] ?? null,
            ];
            $requests[$i] = $payload;
        }
        $responses = \Illuminate\Support\Facades\Http::pool(function ($pool) use ($requests, $base) {
            foreach ($requests as $idx => $payload) {
                $pool->as((string)$idx)->acceptJson()->post($base.'/browse/fetch', $payload);
            }
        });
        $out = [];
        foreach ($requests as $idx => $_) {
            $resp = $responses[(string)$idx];
            $json = null; try { $json = $resp->json(); } catch (\Throwable $e) { $json = null; }
            $meta = [ 'status' => $resp->status(), 'ok' => $resp->ok(), 'title' => is_array($json) ? ($json['title'] ?? null) : null, 'timings' => is_array($json) ? ($json['timings'] ?? null) : null ];
            try { Log::channel('tools')->info('playwright.fetchMany', ['idx'=>$idx, 'meta'=>$meta, 'user_id'=>$userId, 'conversation_id'=>$conversationId]); } catch (\Throwable $e) {}
            $out[$idx] = [ 'ok' => (bool)($resp->ok()), 'json' => $json, 'meta' => $meta ];
        }
        return array_values($out);
    }

    /**
     * Execute a universal Playwright flow (actions sequence) on the runner.
     * Flow example: [
     *   { type: 'goto', url: 'https://example.com' },
     *   { type: 'fill', selector: 'input[name=q]', value: 'term' },
     *   { type: 'click', selector: 'form button[type=submit]' },
     *   { type: 'extract', read_mode: 'textContent', max_chars: 500000 }
     * ]
     */
    public function flow(array $flow, array $allowedDomains = ['*'], int $timeoutMs = 30000, ?int $userId = null, ?int $conversationId = null, array $options = []): array
    {
        $base = rtrim((string) SystemSetting::get('tools.playwright.url', env('TOOLS_PLAYWRIGHT_URL', 'http://playwright-runner:3000')), '/');
        $payload = [
            'flow' => $flow,
            'allowed_domains' => $allowedDomains,
            'timeout_ms' => $timeoutMs,
            'respect_robots' => $options['respect_robots'] ?? true,
        ];
        $t0 = microtime(true);
        $httpTimeout = max(7, min(90, (int) ceil($timeoutMs/1000) + 7));
        $resp = Http::timeout($httpTimeout)->acceptJson()->post($base . '/browse/flow', $payload);
        $ms = (int) round((microtime(true) - $t0) * 1000);
        $json = null; try { $json = $resp->json(); } catch (\Throwable $e) { $json = null; }
        $meta = [
            'status' => $resp->status(),
            'ok' => $resp->ok(),
            'ms' => $ms,
            'runner_timings' => is_array($json) ? ($json['timings'] ?? null) : null,
            'title' => is_array($json) ? ($json['title'] ?? null) : null,
        ];
        try {
            Log::channel('tools')->info('playwright.flow', [ 'payload_meta' => ['steps' => count($flow)], 'meta' => $meta, 'user_id' => $userId, 'conversation_id' => $conversationId ]);
        } catch (\Throwable $e) {}
        ChatToolAudit::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'tool' => 'playwright',
            'intent' => 'flow',
            'payload' => [ 'steps' => count($flow) ],
            'result_meta' => $meta,
            'duration_ms' => $ms,
        ]);
        if (!$resp->ok()) {
            return [ 'ok' => false, 'error' => 'runner_error', 'status' => $resp->status(), 'body' => $json ];
        }
        return (array) $json;
    }
}
