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
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()->values()),
            'capabilities' => $this->whenLoaded('permissions', fn () => $this->getAllPermissions()->pluck('name')->values()),
        ];
    }
}
