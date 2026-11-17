<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'domain'      => $this->domain,
            'logo_url'    => $this->logo_url,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'website'     => $this->website,
            'plan'        => $this->plan,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at->toIso8601String(),
            'stores'      => StoreResource::collection($this->whenLoaded('stores')),
            'members'     => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
