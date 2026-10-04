<?php

namespace App\Observers;

use App\Models\PropertyImage;
use App\Services\AI\AiIndexSyncService;

class PropertyImageObserver
{
    public function __construct(private readonly AiIndexSyncService $sync) {}

    public function saved(PropertyImage $image): void
    {
        $this->syncProperty($image);
    }

    public function deleted(PropertyImage $image): void
    {
        $this->syncProperty($image);
    }

    private function syncProperty(PropertyImage $image): void
    {
        if (! $image->property_id) {
            return;
        }

        $property = \App\Models\Property::query()->find($image->property_id);
        if ($property) {
            $this->sync->sync($property);
        }
    }
}
