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
            'client' => $this->whenLoaded('client', function () {
                $client = $this->client;
                $avatarPath = $client->avatar_path ?: $client->agentProfile?->photo_path;

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'avatar_url' => $avatarPath ? asset('storage/'.$avatarPath) : null,
                ];
            }),
            'agent' => $this->whenLoaded('agent', function () {
                $agent = $this->agent;
                $avatarPath = $agent->avatar_path ?: $agent->agentProfile?->photo_path;

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'avatar_url' => $avatarPath ? asset('storage/'.$avatarPath) : null,
                ];
            }),
            'unread_count' => (int) ($this->unread_count ?? 0),
            'last_message_at' => optional($this->last_message_at)->toISOString(),
            'last_message' => $this->whenLoaded('messages', fn () => $this->messages->first() ? new MessageResource($this->messages->first()) : null),
        ];
    }
}
