<?php

namespace App\Services\AI;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductComparisonService
{
    public function __construct(
        private readonly OpenAiClient  $client,
        private readonly AiUsageLogger $logger,
    ) {}

    /**
     * Compare a collection of products (2–4) using GPT.
     *
     * @param  Collection<int, Product>  $products
     * @return array{comparison: string, recommendation: string, tokens_used: int}
     */
    public function compare(Collection $products, int $organizationId, int $storeId, ?int $userId = null): array
    {
        $model  = config('ai.openai.model', 'gpt-4o');
        $budget = (int) config('ai.token_budgets.comparison', 3000);

        $productList = $products->map(function (Product $p, int $i) {
            $lines = [
                "Product " . ($i + 1) . ": {$p->name}",
                "  Price: {$p->price}",
            ];
            if ($p->short_description) {
                $lines[] = "  Description: {$p->short_description}";
            }
            if ($p->category) {
                $lines[] = "  Category: {$p->category->name}";
            }
            if ($p->tags->isNotEmpty()) {
                $lines[] = "  Tags: " . $p->tags->pluck('name')->join(', ');
            }
            $lines[] = "  In Stock: " . ($p->isInStock() ? 'Yes' : 'No');

            return implode("\n", $lines);
        })->join("\n\n");

        $userPrompt = <<<PROMPT
Compare the following products and provide:
1. A side-by-side comparison of key differences
2. A recommendation of which product is best for different types of buyers

Products:
{$productList}

Respond in this JSON format:
{
  "comparison": "...",
  "recommendation": "..."
}
PROMPT;

        try {
            $result = $this->client->chat([
                'model'           => $model,
                'max_tokens'      => $budget,
                'messages'        => [
                    ['role' => 'system', 'content' => 'You are a helpful product comparison expert. Always respond with valid JSON.'],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature'     => 0.4,
                'response_format' => ['type' => 'json_object'],
            ]);

            $response = $result['response'];
            $content  = $response['choices'][0]['message']['content'] ?? '{}';
            $parsed   = json_decode($content, true) ?? [];
            $usage    = $response['usage'] ?? [];

            $this->logger->logSuccess(
                organizationId:   $organizationId,
                feature:          'comparison',
                model:            $model,
                promptTokens:     $usage['prompt_tokens'] ?? 0,
                completionTokens: $usage['completion_tokens'] ?? 0,
                durationMs:       $result['duration_ms'],
                storeId:          $storeId,
                userId:           $userId,
            );

            return [
                'comparison'     => $parsed['comparison'] ?? '',
                'recommendation' => $parsed['recommendation'] ?? '',
                'tokens_used'    => $usage['total_tokens'] ?? 0,
            ];
        } catch (\RuntimeException $e) {
            $this->logger->logError(
                organizationId: $organizationId,
                feature:        'comparison',
                model:          $model,
                errorMessage:   $e->getMessage(),
                storeId:        $storeId,
                userId:         $userId,
            );

            throw $e;
        }
    }
}
