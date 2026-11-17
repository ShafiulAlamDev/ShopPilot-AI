<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenAiClient
{
    private Client $client;
    private string $apiKey;
    private int $maxRetries;
    private int $retryDelayMs;

    public function __construct()
    {
        $this->apiKey       = config('ai.openai.api_key');
        $this->maxRetries   = (int) config('ai.openai.max_retries', 3);
        $this->retryDelayMs = (int) config('ai.openai.retry_delay', 1000);

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => (int) config('ai.openai.timeout', 60),
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
        ]);
    }

    /**
     * POST /v1/chat/completions
     *
     * @param  array<string, mixed>  $payload
     * @return array{response: array, duration_ms: int}
     */
    public function chat(array $payload): array
    {
        return $this->post('chat/completions', $payload);
    }

    /**
     * POST /v1/embeddings
     *
     * @param  array<string, mixed>  $payload
     * @return array{response: array, duration_ms: int}
     */
    public function embeddings(array $payload): array
    {
        return $this->post('embeddings', $payload);
    }

    /**
     * Generic POST with retry + exponential backoff.
     *
     * @param  array<string, mixed>  $payload
     * @return array{response: array, duration_ms: int}
     */
    private function post(string $endpoint, array $payload): array
    {
        $attempt   = 0;
        $lastError = null;
        $startTime = hrtime(true);

        while ($attempt <= $this->maxRetries) {
            try {
                $res      = $this->client->post($endpoint, ['json' => $payload]);
                $duration = (int) round((hrtime(true) - $startTime) / 1_000_000);

                return [
                    'response'    => json_decode($res->getBody()->getContents(), true),
                    'duration_ms' => $duration,
                ];
            } catch (RequestException $e) {
                $lastError = $e;
                $attempt++;

                // Don't retry on 4xx client errors (bad request, auth, etc.)
                if ($e->hasResponse() && $e->getResponse()->getStatusCode() < 500) {
                    break;
                }

                if ($attempt <= $this->maxRetries) {
                    // Exponential backoff: delay * 2^(attempt-1)
                    usleep($this->retryDelayMs * 1000 * (2 ** ($attempt - 1)));
                }
            }
        }

        $duration = (int) round((hrtime(true) - $startTime) / 1_000_000);
        throw new \RuntimeException(
            'OpenAI API request failed after ' . $this->maxRetries . ' retries: ' . $lastError?->getMessage(),
            0,
            $lastError
        );
    }
}
