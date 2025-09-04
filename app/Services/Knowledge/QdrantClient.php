<?php

namespace App\Services\Knowledge;

use Illuminate\Support\Facades\Http;

class QdrantClient
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('qdrant.url'), '/');
        $this->apiKey = config('qdrant.api_key');
        $this->timeout = (int) config('qdrant.timeout', 10);
    }

    protected function client()
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($this->apiKey) { $headers['api-key'] = $this->apiKey; }
        return Http::timeout($this->timeout)->withHeaders($headers);
    }

    public function ensureCollection(string $collection): bool
    {
        $resp = $this->client()->get($this->baseUrl.'/collections/'.urlencode($collection));
        if ($resp->ok()) { return true; }
        // Create default collection with cosine distance
        $dim = null;
        // Prefer explicit dimension from settings/config; fallback to 1536
        try {
            $dimSetting = \App\Models\SystemSetting::get('embeddings.dimension', config('qdrant.embeddings.dimension'));
            if ($dimSetting) { $dim = (int) $dimSetting; }
        } catch (\Throwable $e) { $dim = null; }
        if (!$dim) { $dim = 1536; }
        $payload = [
            'vectors' => [
                'size' => $dim,
                'distance' => 'Cosine',
            ],
        ];
        $r = $this->client()->put($this->baseUrl.'/collections/'.urlencode($collection), $payload);
        return $r->ok();
    }

    public function recreateCollection(string $collection, int $dimension = 1536, string $distance = 'Cosine'): bool
    {
        // Drop if exists
        $this->client()->delete($this->baseUrl.'/collections/'.urlencode($collection));
        // Create fresh
        $payload = [
            'vectors' => [
                'size' => $dimension,
                'distance' => $distance,
            ],
        ];
        $r = $this->client()->put($this->baseUrl.'/collections/'.urlencode($collection), $payload);
        return $r->ok();
    }

    public function upsert(string $collection, array $points): bool
    {
        $resp = $this->client()->put($this->baseUrl.'/collections/'.urlencode($collection).'/points', [
            'points' => $points,
        ]);
        if (!$resp->ok()) {
            try {
                $body = $resp->body();
            } catch (\Throwable $e) {
                $body = '<no-body>'; 
            }
            \Log::warning('[Qdrant] Upsert failed', [
                'status' => $resp->status(),
                'body' => $body,
            ]);
        }
        return $resp->ok();
    }

    public function search(string $collection, array $vector, int $limit = 5, array $filter = null): array
    {
        $payload = [
            'vector' => $vector,
            'limit' => $limit,
            'with_payload' => true,
            'with_vectors' => false,
        ];
        if ($filter) { $payload['filter'] = $filter; }
    $resp = $this->client()->post($this->baseUrl.'/collections/'.urlencode($collection).'/points/search', $payload);
        if (!$resp->ok()) { return []; }
        $json = $resp->json();
        return $json['result'] ?? [];
    }

    public function count(string $collection, array $filter = null): int
    {
        $payload = ['exact' => true];
        if ($filter) { $payload['filter'] = $filter; }
        $resp = $this->client()->post($this->baseUrl.'/collections/'.urlencode($collection).'/points/count', $payload);
        if (!$resp->ok()) {
            try { $body = $resp->body(); } catch (\Throwable $e) { $body = '<no-body>'; }
            \Log::warning('[Qdrant] Count failed', ['status' => $resp->status(), 'body' => $body, 'payload' => $payload]);
            return 0;
        }
        return (int) ($resp->json()['result']['count'] ?? 0);
    }

    public function deleteByFilter(string $collection, array $filter): bool
    {
        $resp = $this->client()->post($this->baseUrl.'/collections/'.urlencode($collection).'/points/delete', [
            'filter' => $filter,
        ]);
        return $resp->ok();
    }
}
