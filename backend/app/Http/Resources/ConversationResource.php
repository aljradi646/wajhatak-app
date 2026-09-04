<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property' => $this->whenLoaded('property', fn () => ['id' => $this->property->id, 'title' => $this->property->title]),
            'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->name, 'avatar_url' => $this->client->avatar_path ? asset('storage/'.$this->client->avatar_path) : null]),
            'agent' => $this->whenLoaded('agent', fn () => ['id' => $this->agent->id, 'name' => $this->agent->name, 'avatar_url' => $this->agent->avatar_path ? asset('storage/'.$this->agent->avatar_path) : null]),
            'unread_count' => (int) ($this->unread_count ?? 0),
            'last_message_at' => optional($this->last_message_at)->toISOString(),
            'last_message' => $this->whenLoaded('messages', fn () => $this->messages->first() ? new MessageResource($this->messages->first()) : null),
        ];
    }
}
