<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class ActiveCampaignClient
{
    private Client $http;
    public function __construct(private LoggerInterface $logger)
    {
        $this->http = new Client([
            'base_uri' => rtrim(config('services.activecampaign.base_url'), '/') . '/api/3/',
            'headers' => [
                'Accept' => 'application/json',
                'Api-Token' => (string) config('services.activecampaign.api_token'),
            ],
            'timeout' => 20,
        ]);
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    private function request(string $method, string $path, array $options = []): array
    {
        $attempts = 0;
        $max = 3;
        start:
        try {
            $res = $this->http->request($method, ltrim($path, '/'), $options);
            return json_decode((string) $res->getBody(), true) ?: [];
        } catch (RequestException $e) {
            $attempts++;
            $code = $e->getResponse()?->getStatusCode();
            // Retry on 429 with simple backoff
            if ($code === 429 && $attempts < $max) {
                $delay = 200 * $attempts; // ms
                usleep($delay * 1000);
                goto start;
            }
            // Soft-fail for rate limit/exhaustion or forbidden (quota/token issues)
            if (in_array($code, [401, 403, 429], true)) {
                $this->logger->warning('AC API soft-fail', [
                    'method' => $method,
                    'path' => $path,
                    'code' => $code,
                    'error' => $e->getMessage(),
                ]);
                // Return a structured empty result so callers can proceed without crashing
                return ['ok' => false, 'status' => $code, 'error' => 'activecampaign_rate_limited_or_forbidden'];
            }

            $this->logger->error('AC API request failed (hard)', [
                'method' => $method,
                'path' => $path,
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
