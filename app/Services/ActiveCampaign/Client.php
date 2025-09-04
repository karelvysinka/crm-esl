<?php

namespace App\Services\ActiveCampaign;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client
{
    protected string $baseUrl;
    protected string $token;

    public function __construct(?string $baseUrl = null, ?string $token = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.activecampaign.base_url'), '/');
        $this->token = $token ?? (string) config('services.activecampaign.api_token');
    }

    protected function http()
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Api-Token' => $this->token,
        ])->baseUrl($this->baseUrl);
    }

    public function ping(): bool
    {
        try {
            $res = $this->http()->get('/api/3/users/me');
            return $res->ok();
        } catch (\Throwable $e) {
            Log::warning('AC ping failed: '.$e->getMessage());
            return false;
        }
    }

    public function listContacts(int $limit = 10, int $offset = 0): array
    {
        $params = [
            'limit' => max(1, min(100, $limit)),
            'offset' => max(0, $offset),
        ];
        $res = $this->http()->get('/api/3/contacts', $params);
        $res->throw();
        return $res->json();
    }
}
