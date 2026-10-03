<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'locale' => $this->locale,
            'email_verified' => $this->email_verified_at !== null,
            'agent_verification_status' => $this->when(
                $this->relationLoaded('agentProfile') || $this->agentProfile !== null,
                fn () => $this->agentProfile?->verification_status,
            ),
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()->values()),
            'capabilities' => $this->whenLoaded('permissions', fn () => $this->getAllPermissions()->pluck('name')->values()),
        ];
    }
}
