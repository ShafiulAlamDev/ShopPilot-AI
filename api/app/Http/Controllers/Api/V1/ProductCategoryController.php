<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    /**
     * List all active categories for a store (with their children eager-loaded).
     */
    public function index(Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $categories = ProductCategory::where('store_id', $store->id)
            ->active()
            ->whereNull('parent_id')   // only top-level, children nested inside
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => ProductCategoryResource::collection($categories),
        ]);
    }

    /**
     * Create a new category for the given store.
     */
    public function store(StoreCategoryRequest $request, Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $category = ProductCategory::create(
            array_merge($request->validated(), ['store_id' => $store->id])
        );

        return response()->json([
            'message' => 'Category created successfully.',
            'data'    => new ProductCategoryResource($category),
        ], 201);
    }

    /**
     * Show a single category with its children.
     */
    public function show(Store $store, ProductCategory $category): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if ($category->store_id !== $store->id) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $category->load('children');

        return response()->json([
            'data' => new ProductCategoryResource($category),
        ]);
    }

    /**
     * Update an existing category.
     */
    public function update(StoreCategoryRequest $request, Store $store, ProductCategory $category): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if ($category->store_id !== $store->id) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        $category->update($request->validated());

        return response()->json([
            'message' => 'Category updated successfully.',
            'data'    => new ProductCategoryResource($category->fresh('children')),
        ]);
    }

    /**
     * Soft-delete a category.
     * Products assigned to this category will have their category_id nullified first.
     */
    public function destroy(Store $store, ProductCategory $category): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if ($category->store_id !== $store->id) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        // Detach products from this category before deleting
        Product::where('category_id', $category->id)
            ->update(['category_id' => null]);

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
