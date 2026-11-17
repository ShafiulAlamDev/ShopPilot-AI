<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'image_url'   => $this->image_url,  // from getImageUrlAttribute() accessor
            'sort_order'  => $this->sort_order,
            'is_active'   => $this->is_active,
            'parent_id'   => $this->parent_id,
            'children'    => $this->whenLoaded(
                'children',
                fn () => ProductCategoryResource::collection($this->children)
            ),
        ];
    }
}
