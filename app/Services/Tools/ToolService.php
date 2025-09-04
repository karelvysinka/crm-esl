<?php

namespace App\Services\Tools;

use Illuminate\Support\Facades\Log;

class ToolService
{
    public function call(string $tool, array $payload): array
    {
        switch ($tool) {
            case 'playwright':
                $url = (string) ($payload['url'] ?? '');
                $selectors = (array) ($payload['selectors'] ?? []);
                $allowed = (array) ($payload['allowed_domains'] ?? []);
                $timeout = (int) ($payload['timeout_ms'] ?? 20000);
                $userId = $payload['user_id'] ?? null;
                $convId = $payload['conversation_id'] ?? null;
                return app(PlaywrightTool::class)->fetch($url, $selectors, $allowed, $timeout, $userId, $convId);
            default:
                Log::warning('ToolService: unknown tool '.$tool);
                return ['ok' => false, 'error' => 'unknown_tool'];
        }
    }
}
