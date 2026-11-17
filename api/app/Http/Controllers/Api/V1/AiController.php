<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\AiUsageLog;
use App\Models\Product;
use App\Models\Store;
use App\Services\AI\EmbeddingService;
use App\Services\AI\ProductComparisonService;
use App\Services\AI\ProductDescriptionService;
use App\Services\AI\ShoppingAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(
        private readonly ProductDescriptionService $descriptionService,
        private readonly EmbeddingService          $embeddingService,
        private readonly ShoppingAssistantService  $assistantService,
        private readonly ProductComparisonService  $comparisonService,
    ) {}

    // -------------------------------------------------------------------------
    // Product Description Generation
    // -------------------------------------------------------------------------

    /**
     * Generate an AI description for a product.
     *
     * POST /api/v1/organizations/{org}/stores/{store}/products/{product}/ai/description
     */
    public function generateDescription(Request $request, Store $store, Product $product): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }
        if ($product->store_id !== $store->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        if (!config('ai.features.product_description_generation')) {
            return response()->json(['message' => 'This AI feature is disabled.'], 403);
        }

        $product->loadMissing(['category', 'tags']);

        try {
            $result = $this->descriptionService->generate(
                product:        $product,
                store:          $store,
                organizationId: app('current_organization')->id,
                userId:         $request->user()->id,
            );

            return response()->json([
                'message'     => 'Description generated successfully.',
                'description' => $result['description'],
                'tokens_used' => $result['tokens_used'],
                'product'     => new ProductResource($product->fresh()),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'AI service error. Please try again.',
                'error'   => $e->getMessage(),
            ], 503);
        }
    }

    // -------------------------------------------------------------------------
    // Semantic Search
    // -------------------------------------------------------------------------

    /**
     * Embed a product (or re-embed after changes).
     *
     * POST /api/v1/organizations/{org}/stores/{store}/products/{product}/ai/embed
     */
    public function embedProduct(Request $request, Store $store, Product $product): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }
        if ($product->store_id !== $store->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        if (!config('ai.features.semantic_search')) {
            return response()->json(['message' => 'This AI feature is disabled.'], 403);
        }

        try {
            $embedding = $this->embeddingService->embedProduct(
                product:        $product,
                organizationId: app('current_organization')->id,
                userId:         $request->user()->id,
            );

            return response()->json([
                'message'         => 'Product embedded successfully.',
                'embedding_model' => $embedding->embedding_model,
                'embedded_at'     => $embedding->updated_at->toIso8601String(),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'AI service error. Please try again.',
                'error'   => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Semantic product search within a store.
     *
     * POST /api/v1/organizations/{org}/stores/{store}/ai/search
     *
     * Body: { "query": "comfortable running shoes", "limit": 10 }
     */
    public function semanticSearch(Request $request, Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if (!config('ai.features.semantic_search')) {
            return response()->json(['message' => 'This AI feature is disabled.'], 403);
        }

        $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:500'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = (int) $request->input('limit', 10);

        try {
            $queryVector = $this->embeddingService->embedQuery(
                query:          $request->input('query'),
                organizationId: app('current_organization')->id,
                storeId:        $store->id,
                userId:         $request->user()->id,
            );

            $results = $this->embeddingService->findSimilar($queryVector, $store->id, $limit);

            $data = array_map(fn ($r) => [
                'score'   => round($r['score'], 4),
                'product' => new ProductResource($r['product']),
            ], $results);

            return response()->json([
                'query'   => $request->input('query'),
                'results' => $data,
                'count'   => count($data),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'AI service error. Please try again.',
                'error'   => $e->getMessage(),
            ], 503);
        }
    }

    // -------------------------------------------------------------------------
    // Shopping Assistant
    // -------------------------------------------------------------------------

    /**
     * Chat with the shopping assistant.
     *
     * POST /api/v1/organizations/{org}/stores/{store}/ai/chat
     *
     * Body: {
     *   "message": "Do you have any wireless headphones?",
     *   "history": [
     *     {"role": "user", "content": "..."},
     *     {"role": "assistant", "content": "..."}
     *   ]
     * }
     */
    public function chat(Request $request, Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if (!config('ai.features.shopping_assistant')) {
            return response()->json(['message' => 'This AI feature is disabled.'], 403);
        }

        $request->validate([
            'message'           => ['required', 'string', 'min:1', 'max:2000'],
            'history'           => ['nullable', 'array', 'max:20'],
            'history.*.role'    => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string'],
        ]);

        try {
            $result = $this->assistantService->chat(
                store:          $store,
                history:        $request->input('history', []),
                userMessage:    $request->input('message'),
                organizationId: app('current_organization')->id,
                userId:         $request->user()->id,
            );

            return response()->json([
                'reply'       => $result['reply'],
                'tokens_used' => $result['tokens_used'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'AI service error. Please try again.',
                'error'   => $e->getMessage(),
            ], 503);
        }
    }

    // -------------------------------------------------------------------------
    // Product Comparison
    // -------------------------------------------------------------------------

    /**
     * Compare 2–4 products side by side.
     *
     * POST /api/v1/organizations/{org}/stores/{store}/ai/compare
     *
     * Body: { "product_ids": [1, 2, 3] }
     */
    public function compare(Request $request, Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if (!config('ai.features.product_comparison')) {
            return response()->json(['message' => 'This AI feature is disabled.'], 403);
        }

        $request->validate([
            'product_ids'   => ['required', 'array', 'min:2', 'max:4'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::whereIn('id', $request->input('product_ids'))
            ->where('store_id', $store->id)
            ->with(['category', 'tags'])
            ->get();

        if ($products->count() < 2) {
            return response()->json([
                'message' => 'At least 2 products from this store are required for comparison.',
            ], 422);
        }

        try {
            $result = $this->comparisonService->compare(
                products:       $products,
                organizationId: app('current_organization')->id,
                storeId:        $store->id,
                userId:         $request->user()->id,
            );

            return response()->json([
                'products'       => ProductResource::collection($products),
                'comparison'     => $result['comparison'],
                'recommendation' => $result['recommendation'],
                'tokens_used'    => $result['tokens_used'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'AI service error. Please try again.',
                'error'   => $e->getMessage(),
            ], 503);
        }
    }

    // -------------------------------------------------------------------------
    // AI Usage Stats
    // -------------------------------------------------------------------------

    /**
     * Get AI usage summary for the organization.
     *
     * GET /api/v1/organizations/{org}/ai/usage
     */
    public function usage(Request $request): JsonResponse
    {
        $organization = app('current_organization');

        $request->validate([
            'period' => ['nullable', 'in:today,week,month'],
        ]);

        $period = $request->input('period', 'month');

        $query = AiUsageLog::where('organization_id', $organization->id);

        $query->when($period === 'today', fn ($q) => $q->whereDate('created_at', today()));
        $query->when($period === 'week',  fn ($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]));
        $query->when($period === 'month', fn ($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year));

        $stats = (clone $query)->selectRaw('
            feature,
            COUNT(*) as requests,
            SUM(total_tokens) as total_tokens,
            SUM(cost_usd) as total_cost_usd,
            AVG(duration_ms) as avg_duration_ms,
            SUM(CASE WHEN status = "error" THEN 1 ELSE 0 END) as errors
        ')->groupBy('feature')->get();

        $totals = (clone $query)->selectRaw('
            COUNT(*) as total_requests,
            SUM(total_tokens) as total_tokens,
            SUM(cost_usd) as total_cost_usd
        ')->first();

        return response()->json([
            'period'     => $period,
            'by_feature' => $stats,
            'totals'     => $totals,
        ]);
    }
}
