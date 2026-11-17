<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'email'                => $this->email,
            'avatar_url'           => $this->avatar_url,
            'phone'                => $this->phone,
            'is_active'            => $this->is_active,
            'email_verified'       => !is_null($this->email_verified_at),
            'last_login_at'        => $this->last_login_at?->toIso8601String(),
            'created_at'           => $this->created_at->toIso8601String(),
            'organizations'        => OrganizationResource::collection(
                $this->whenLoaded('organizations')
            ),
            // org-level role (available when user is loaded via org relationship)
            'pivot_role'           => $this->when(
                isset($this->pivot),
                fn () => $this->pivot?->role
            ),
        ];
    }
}
