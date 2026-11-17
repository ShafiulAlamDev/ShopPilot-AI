<?php

namespace App\Services\AI;

use App\Models\Product;
use App\Models\ProductEmbedding;
use Illuminate\Support\Str;

class EmbeddingService
{
    public function __construct(
        private readonly OpenAiClient  $client,
        private readonly AiUsageLogger $logger,
    ) {}

    /**
     * Generate and store/update an embedding for a single product.
     */
    public function embedProduct(Product $product, int $organizationId, ?int $userId = null): ProductEmbedding
    {
        $model = config('ai.openai.embedding_model', 'text-embedding-3-small');
        $text  = $this->buildEmbeddingText($product);

        $result   = $this->client->embeddings([
            'model' => $model,
            'input' => $text,
        ]);

        $response = $result['response'];
        $vector   = $response['data'][0]['embedding'] ?? [];
        $usage    = $response['usage'] ?? [];

        $this->logger->logSuccess(
            organizationId:   $organizationId,
            feature:          'semantic_search',
            model:            $model,
            promptTokens:     $usage['prompt_tokens'] ?? 0,
            completionTokens: 0,
            durationMs:       $result['duration_ms'],
            storeId:          $product->store_id,
            userId:           $userId,
        );

        return ProductEmbedding::updateOrCreate(
            ['product_id' => $product->id],
            [
                'embedding'       => json_encode($vector),
                'embedding_model' => $model,
                'embedded_text'   => $text,
            ]
        );
    }

    /**
     * Generate an embedding for a search query string.
     *
     * @return float[]
     */
    public function embedQuery(string $query, int $organizationId, ?int $storeId = null, ?int $userId = null): array
    {
        $model = config('ai.openai.embedding_model', 'text-embedding-3-small');

        $result   = $this->client->embeddings(['model' => $model, 'input' => $query]);
        $response = $result['response'];
        $usage    = $response['usage'] ?? [];

        $this->logger->logSuccess(
            organizationId:   $organizationId,
            feature:          'semantic_search',
            model:            $model,
            promptTokens:     $usage['prompt_tokens'] ?? 0,
            completionTokens: 0,
            durationMs:       $result['duration_ms'],
            storeId:          $storeId,
            userId:           $userId,
        );

        return $response['data'][0]['embedding'] ?? [];
    }

    /**
     * Find the top-N most similar products to a query vector within a store.
     * Returns products sorted by cosine similarity descending.
     *
     * @param  float[]  $queryVector
     * @return array<array{product: Product, score: float}>
     */
    public function findSimilar(array $queryVector, int $storeId, int $topN = 10): array
    {
        // Load all embeddings for active products in this store
        $embeddings = ProductEmbedding::whereHas('product', function ($q) use ($storeId) {
            $q->where('store_id', $storeId)->where('status', 'active');
        })->with('product')->get();

        $scored = $embeddings->map(function (ProductEmbedding $emb) use ($queryVector) {
            return [
                'product' => $emb->product,
                'score'   => $this->cosineSimilarity($queryVector, $emb->getVector()),
            ];
        });

        return $scored
            ->sortByDesc('score')
            ->take($topN)
            ->values()
            ->toArray();
    }

    /**
     * Compute cosine similarity between two vectors.
     *
     * @param  float[]  $a
     * @param  float[]  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dot   = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $val) {
            $dot   += $val * ($b[$i] ?? 0);
            $normA += $val * $val;
            $normB += ($b[$i] ?? 0) * ($b[$i] ?? 0);
        }

        $denom = sqrt($normA) * sqrt($normB);

        return $denom > 0 ? $dot / $denom : 0.0;
    }

    /**
     * Build the text string to embed for a product.
     */
    private function buildEmbeddingText(Product $product): string
    {
        $product->loadMissing(['category', 'tags']);

        $parts = [$product->name];

        if ($product->short_description) {
            $parts[] = $product->short_description;
        }
        if ($product->description) {
            // Truncate long descriptions to keep token count manageable
            $parts[] = Str::limit($product->description, 500);
        }
        if ($product->category) {
            $parts[] = 'Category: ' . $product->category->name;
        }
        if ($product->tags->isNotEmpty()) {
            $parts[] = 'Tags: ' . $product->tags->pluck('name')->join(', ');
        }

        return implode('. ', $parts);
    }
}
