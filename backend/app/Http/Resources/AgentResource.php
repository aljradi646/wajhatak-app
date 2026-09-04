<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'avatar_url' => $this->user->avatar_path ? asset('storage/'.$this->user->avatar_path) : null,
            'bio' => $this->bio,
            'rating' => (float) $this->rating,
            'reviews_count' => $this->reviews_count,
            'properties_count' => $this->whenCounted('properties'),
        ];
    }
}
