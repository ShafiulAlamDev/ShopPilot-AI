<?php

namespace App\Services\AI;

use App\Models\Product;
use App\Models\Store;

class ProductDescriptionService
{
    public function __construct(
        private readonly OpenAiClient  $client,
        private readonly AiUsageLogger $logger,
    ) {}

    /**
     * Generate an AI description for a product and persist it.
     *
     * @return array{description: string, tokens_used: int}
     */
    public function generate(Product $product, Store $store, int $organizationId, ?int $userId = null): array
    {
        $model  = config('ai.openai.model', 'gpt-4o');
        $budget = (int) config('ai.token_budgets.product_description', 2000);

        $systemPrompt = <<<PROMPT
You are an expert e-commerce copywriter. Write a compelling, SEO-friendly product description.
- Tone: professional yet engaging
- Length: 150–300 words
- Highlight key features, benefits, and use cases
- Use short paragraphs, no markdown headers
- Currency: {$store->currency}
- Locale: {$store->locale}
PROMPT;

        $userPrompt = $this->buildUserPrompt($product);

        try {
            $result = $this->client->chat([
                'model'       => $model,
                'max_tokens'  => $budget,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature' => 0.7,
            ]);

            $response    = $result['response'];
            $description = trim($response['choices'][0]['message']['content'] ?? '');
            $usage       = $response['usage'] ?? [];

            $this->logger->logSuccess(
                organizationId:   $organizationId,
                feature:          'product_description',
                model:            $model,
                promptTokens:     $usage['prompt_tokens'] ?? 0,
                completionTokens: $usage['completion_tokens'] ?? 0,
                durationMs:       $result['duration_ms'],
                storeId:          $store->id,
                userId:           $userId,
            );

            // Persist the generated description
            $product->update([
                'description'              => $description,
                'ai_description_generated' => true,
            ]);

            return [
                'description' => $description,
                'tokens_used' => $usage['total_tokens'] ?? 0,
            ];
        } catch (\RuntimeException $e) {
            $this->logger->logError(
                organizationId: $organizationId,
                feature:        'product_description',
                model:          $model,
                errorMessage:   $e->getMessage(),
                storeId:        $store->id,
                userId:         $userId,
            );

            throw $e;
        }
    }

    private function buildUserPrompt(Product $product): string
    {
        $parts = ["Product Name: {$product->name}"];

        if ($product->short_description) {
            $parts[] = "Short Description: {$product->short_description}";
        }
        if ($product->sku) {
            $parts[] = "SKU: {$product->sku}";
        }
        if ($product->price) {
            $parts[] = "Price: {$product->price}";
        }
        if ($product->category) {
            $parts[] = "Category: {$product->category->name}";
        }
        if ($product->tags->isNotEmpty()) {
            $parts[] = "Tags: " . $product->tags->pluck('name')->join(', ');
        }
        if ($product->weight) {
            $parts[] = "Weight: {$product->weight} {$product->weight_unit}";
        }

        $parts[] = "\nWrite a full product description for the above product.";

        return implode("\n", $parts);
    }
}
