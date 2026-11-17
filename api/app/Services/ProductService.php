<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;

class ProductService
{
    /**
     * Create a new product for the given store.
     *
     * @param  Store  $store
     * @param  array<string, mixed>  $data
     */
    public function createProduct(Store $store, array $data): Product
    {
        $product = $store->products()->create($data);

        if (!empty($data['tag_ids'])) {
            $this->syncTags($product, $data['tag_ids']);
        }

        return $product->fresh(['images', 'category', 'tags']);
    }

    /**
     * Update an existing product with the provided data.
     *
     * @param  Product  $product
     * @param  array<string, mixed>  $data
     */
    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);

        if (array_key_exists('tag_ids', $data)) {
            $this->syncTags($product, $data['tag_ids'] ?? []);
        }

        return $product->fresh(['images', 'category', 'tags']);
    }

    /**
     * Soft-delete a product.
     *
     * @param  Product  $product
     */
    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }

    /**
     * Sync the product's tags to match the given array of tag IDs.
     *
     * @param  Product  $product
     * @param  array<int>  $tagIds
     */
    public function syncTags(Product $product, array $tagIds): void
    {
        $product->tags()->sync($tagIds);
    }
}
