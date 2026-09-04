<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViewingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property' => $this->whenLoaded('property', fn () => ['id' => $this->property->id, 'title' => $this->property->title]),
            'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->name, 'phone' => $this->client->phone, 'avatar_url' => $this->client->avatar_path ? asset('storage/'.$this->client->avatar_path) : null]),
            'agent' => $this->whenLoaded('agent', fn () => new AgentResource($this->agent)),
            'scheduled_date' => $this->scheduled_date->toDateString(),
            'scheduled_time' => $this->scheduled_time,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
