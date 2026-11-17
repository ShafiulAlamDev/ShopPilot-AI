<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    /**
     * List paginated products for a store with optional filters.
     */
    public function index(Request $request, Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $query = Product::forStore($store->id)
            ->with(['primaryImage', 'category', 'tags']);

        // Filter: status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter: is_featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', (bool) $request->input('is_featured'));
        }

        // Filter: category_id
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        // Filter: search (name or SKU)
        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('sku', 'like', $term);
            });
        }

        // Filter: in_stock
        if ($request->has('in_stock')) {
            if ((bool) $request->input('in_stock')) {
                $query->inStock();
            }
        }

        $products = $query->paginate(15);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    /**
     * Create a new product for the given store.
     */
    public function store(StoreProductRequest $request, Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $product = $this->productService->createProduct($store, $request->validated());

        return response()->json([
            'message' => 'Product created successfully.',
            'data'    => new ProductResource($product),
        ], 201);
    }

    /**
     * Show a single product (verifies it belongs to the given store and org).
     */
    public function show(Store $store, Product $product): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if ($product->store_id !== $store->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product->load(['images', 'category', 'tags']);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Update an existing product.
     */
    public function update(StoreProductRequest $request, Store $store, Product $product): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if ($product->store_id !== $store->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product = $this->productService->updateProduct($product, $request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'data'    => new ProductResource($product),
        ]);
    }

    /**
     * Soft-delete a product.
     */
    public function destroy(Store $store, Product $product): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if ($product->store_id !== $store->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $this->productService->deleteProduct($product);

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
