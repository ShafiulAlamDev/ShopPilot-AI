<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Store;

class StoreService
{
    /**
     * Create a new store for the given organization.
     *
     * @param  Organization  $organization
     * @param  array<string, mixed>  $data
     */
    public function createStore(Organization $organization, array $data): Store
    {
        $store = $organization->stores()->create($data);

        return $store->fresh(['settings']);
    }

    /**
     * Update an existing store with the provided data.
     *
     * @param  Store  $store
     * @param  array<string, mixed>  $data
     */
    public function updateStore(Store $store, array $data): Store
    {
        $store->update($data);

        return $store->fresh(['settings']);
    }

    /**
     * Soft-delete a store.
     * Throws a RuntimeException if this is the organization's only active store.
     *
     * @param  Store  $store
     * @throws \RuntimeException
     */
    public function deleteStore(Store $store): void
    {
        $activeCount = Store::where('organization_id', $store->organization_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        if ($activeCount <= 1) {
            throw new \RuntimeException('Cannot delete the only active store.');
        }

        $store->delete();
    }
}
