<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Services\StoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(
        private readonly StoreService $storeService,
    ) {}

    /**
     * List all stores for the current organization.
     */
    public function index(): JsonResponse
    {
        $organization = app('current_organization');

        $stores = Store::where('organization_id', $organization->id)
            ->withCount('products')
            ->get();

        return response()->json([
            'data' => StoreResource::collection($stores),
        ]);
    }

    /**
     * Create a new store for the current organization.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $organization = app('current_organization');

        try {
            $store = $this->storeService->createStore($organization, $request->validated());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Store created successfully.',
            'data'    => new StoreResource($store),
        ], 201);
    }

    /**
     * Show a single store (must belong to the current organization).
     */
    public function show(Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $store->load('settings');

        return response()->json([
            'data' => new StoreResource($store),
        ]);
    }

    /**
     * Update a store (must belong to the current organization).
     */
    public function update(StoreRequest $request, Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $store = $this->storeService->updateStore($store, $request->validated());

        return response()->json([
            'message' => 'Store updated successfully.',
            'data'    => new StoreResource($store),
        ]);
    }

    /**
     * Delete a store (must belong to the current organization).
     */
    public function destroy(Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        try {
            $this->storeService->deleteStore($store);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Store deleted successfully.']);
    }

    /**
     * Get all settings for a store as a key => value map.
     */
    public function settings(Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $settings = $store->settings()->get()->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }

    /**
     * Bulk-update settings for a store.
     * Accepts an array of { key, value, type? } objects.
     */
    public function updateSettings(Request $request, Store $store): JsonResponse
    {
        if ($store->organization_id !== app('current_organization')->id) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $validated = $request->validate([
            'settings'           => ['required', 'array'],
            'settings.*.key'     => ['required', 'string', 'max:100'],
            'settings.*.value'   => ['nullable'],
            'settings.*.type'    => ['nullable', 'string', 'in:string,integer,boolean,json'],
        ]);

        foreach ($validated['settings'] as $setting) {
            $store->setSetting(
                $setting['key'],
                $setting['value'],
                $setting['type'] ?? 'string'
            );
        }

        $settings = $store->settings()->get()->pluck('value', 'key');

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data'    => $settings,
        ]);
    }
}
