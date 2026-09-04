<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'sender_id' => $this->sender_id,
            'message_type' => $this->message_type ?? 'text',
            'property_id' => $this->property_id,
            'property' => $this->whenLoaded('property', fn () => [
                'id' => $this->property->id,
                'title' => $this->property->title,
                'price' => (float) $this->property->price,
                'currency' => $this->property->currency,
                'transaction_type' => $this->property->transaction_type->value,
                'area' => $this->property->area ? (float) $this->property->area : null,
                'bedrooms' => $this->property->bedrooms,
                'bathrooms' => $this->property->bathrooms,
                'location' => [
                    'city' => $this->property->location?->city,
                    'district' => $this->property->location?->district,
                ],
                'cover_url' => $this->property->images->firstWhere('is_cover', true)?->path
                    ? asset('storage/' . $this->property->images->firstWhere('is_cover', true)->path)
                    : ($this->property->images->isNotEmpty()
                        ? asset('storage/' . $this->property->images->first()->path)
                        : null),
            ]),
            'read_at' => optional($this->read_at)->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
