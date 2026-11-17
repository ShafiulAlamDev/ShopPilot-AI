<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'currency'      => $this->currency,
            'timezone'      => $this->timezone,
            'locale'        => $this->locale,
            'logo'          => $this->logo ? asset('storage/' . $this->logo) : null,
            'favicon'       => $this->favicon ? asset('storage/' . $this->favicon) : null,
            'primary_color' => $this->primary_color,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at->toIso8601String(),
            'products_count'=> $this->whenCounted('products'),
            'settings'      => $this->whenLoaded('settings', fn () =>
                $this->settings->pluck('value', 'key')
            ),
        ];
    }
}
