<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $apiUser = $request->user('sanctum') ?? $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'reference_code' => $this->reference_code,
            'description' => $this->when($request->routeIs('api.v1.properties.show'), $this->description),
            'transaction_type' => $this->transaction_type->value,
            'status' => $this->when($apiUser, $this->status->value),
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'area' => $this->area ? (float) $this->area : null,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'parking_spaces' => $this->parking_spaces,
            'is_furnished' => $this->is_furnished,
            'is_new' => $this->is_new,
            'is_featured' => $this->is_featured,
            'published_at' => optional($this->published_at)->toISOString(),
            'type' => $this->whenLoaded('type', fn () => [
                'id' => $this->type->id,
                'name_ar' => $this->type->name_ar,
                'name_en' => $this->type->name_en,
                'slug' => $this->type->slug,
            ]),
            'location' => $this->whenLoaded('location', fn () => [
                'country_id' => $this->location->country_id,
                'region_id' => $this->location->region_id,
                'city_id' => $this->location->city_id,
                'area_id' => $this->location->area_id,
                'country' => $this->location->country?->name_ar,
                'region' => $this->location->region?->name_ar,
                'city_name' => $this->location->cityReference?->name_ar,
                'area' => $this->location->area?->name_ar,
                'city' => $this->location->city,
                'district' => $this->location->district,
                'neighborhood' => $this->location->neighborhood,
                'address' => $this->location->address,
                'latitude' => $this->location->latitude ? (float) $this->location->latitude : null,
                'longitude' => $this->location->longitude ? (float) $this->location->longitude : null,
            ]),
            'agent' => $this->whenLoaded('agent', fn () => new AgentResource($this->agent)),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => asset('storage/'.$image->path),
                'alt_text' => $image->alt_text,
                'is_cover' => $image->is_cover,
            ])),
            'features' => $this->whenLoaded('features', fn () => $this->features->map(fn ($feature) => [
                'id' => $feature->id,
                'name_ar' => $feature->name_ar,
                'name_en' => $feature->name_en,
                'icon' => $feature->icon,
            ])),
            'is_favorited' => (bool) ($this->is_favorited ?? false),
        ];
    }
}
