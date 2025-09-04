<?php

namespace App\Services\Knowledge;

use Illuminate\Support\Facades\Http;
use App\Models\SystemSetting;

class EmbeddingsService
{
    public function embed(string $text): ?array
    {
    // Prefer runtime settings from DB (SystemSetting), fallback to config/.env
    $provider = (string) (SystemSetting::get('embeddings.provider', config('qdrant.embeddings.provider', 'openai')));
    $model = (string) (SystemSetting::get('embeddings.model', config('qdrant.embeddings.model', 'text-embedding-3-small')));
        $text = mb_substr($text, 0, 8000);

        if ($provider === 'openai') {
            $key = (string) (SystemSetting::get('embeddings.openai_api_key', config('qdrant.embeddings.openai_api_key')));
            if (!$key) { return null; }
            $resp = Http::withToken($key)
                ->timeout(20)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => $model,
                    'input' => $text,
                ]);
            if (!$resp->ok()) { return null; }
            $data = $resp->json();
            return $data['data'][0]['embedding'] ?? null;
        }

        if ($provider === 'gemini') {
            $key = (string) (SystemSetting::get('embeddings.gemini_api_key', config('qdrant.embeddings.gemini_api_key')));
            if (!$key) { return null; }
            $resp = Http::timeout(20)
                ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':embedContent?key='.urlencode($key), [
                    'content' => [ 'parts' => [ ['text' => $text] ] ],
                ]);
            if (!$resp->ok()) { return null; }
            $data = $resp->json();
            return $data['embedding']['values'] ?? null;
        }

        if ($provider === 'openrouter') {
            $key = (string) (SystemSetting::get('embeddings.openrouter_api_key', config('qdrant.embeddings.openrouter_api_key')));
            if (!$key) { return null; }
            $headers = [
                'Authorization' => 'Bearer '.$key,
            ];
            $ref = (string) (SystemSetting::get('embeddings.openrouter_referer', config('qdrant.embeddings.openrouter_referer')));
            $title = (string) (SystemSetting::get('embeddings.openrouter_title', config('qdrant.embeddings.openrouter_title')));
            if ($ref) { $headers['HTTP-Referer'] = $ref; }
            if ($title) { $headers['X-Title'] = $title; }
            // Normalize model for OpenRouter if vendor prefix missing
            $openrouterModel = $model;
            if (!str_contains($openrouterModel, '/')) {
                // Assume OpenAI family if not specified
                $openrouterModel = 'openai/'.$openrouterModel;
            }
            $resp = Http::withHeaders($headers)
                ->timeout(20)
                ->post('https://openrouter.ai/api/v1/embeddings', [
                    'model' => $openrouterModel,
                    'input' => $text,
                ]);
            if (!$resp->ok()) { return null; }
            $data = $resp->json();
            // OpenRouter returns OpenAI-compatible shape
            return $data['data'][0]['embedding'] ?? null;
        }

        if ($provider === 'local') {
            $base = (string) (SystemSetting::get('embeddings.local_url', env('EMBEDDINGS_LOCAL_URL', 'http://embedder:8080')));
            if (!$base) { return null; }
            $resp = Http::timeout(20)
                ->post(rtrim($base, '/').'/embeddings', [
                    'input' => [$text],
                    'normalize' => true,
                ]);
            if (!$resp->ok()) { return null; }
            $data = $resp->json();
            $vecs = $data['vectors'][0] ?? null;
            if (!is_array($vecs)) { return null; }
            return array_map('floatval', $vecs);
        }

        return null;
    }
}
