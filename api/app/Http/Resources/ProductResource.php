<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'slug'                    => $this->slug,
            'sku'                     => $this->sku,
            'description'             => $this->description,
            'short_description'       => $this->short_description,

            // Pricing
            'price'                   => number_format((float) $this->price, 2, '.', ''),
            'compare_price'           => $this->compare_price !== null
                                            ? number_format((float) $this->compare_price, 2, '.', '')
                                            : null,
            'cost_price'              => $this->cost_price !== null
                                            ? number_format((float) $this->cost_price, 2, '.', '')
                                            : null,
            'discount_percentage'     => $this->discount_percentage,

            // Inventory
            'stock_quantity'          => $this->stock_quantity,
            'low_stock_threshold'     => $this->low_stock_threshold,
            'track_stock'             => $this->track_stock,
            'is_in_stock'             => $this->isInStock(),
            'is_low_stock'            => $this->isLowStock(),

            // Status
            'status'                  => $this->status,
            'is_featured'             => $this->is_featured,

            // Shipping
            'weight'                  => $this->weight,
            'weight_unit'             => $this->weight_unit,

            // SEO
            'meta_title'              => $this->meta_title,
            'meta_description'        => $this->meta_description,

            // AI
            'ai_description_generated'=> $this->ai_description_generated,

            'created_at'              => $this->created_at->toIso8601String(),
            'updated_at'              => $this->updated_at->toIso8601String(),

            // Relationships (conditionally loaded)
            'category'                => $this->whenLoaded(
                'category',
                fn () => new ProductCategoryResource($this->category)
            ),
            'images'                  => $this->whenLoaded(
                'images',
                fn () => ProductImageResource::collection($this->images)
            ),
            'tags'                    => $this->whenLoaded(
                'tags',
                fn () => ProductTagResource::collection($this->tags)
            ),
        ];
    }
}
