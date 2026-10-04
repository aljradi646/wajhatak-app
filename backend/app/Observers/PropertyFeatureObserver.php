<?php

namespace App\Observers;

use App\Models\PropertyFeature;
use App\Services\AI\AiIndexSyncService;

class PropertyFeatureObserver
{
    public function __construct(private readonly AiIndexSyncService $sync) {}

    public function saved(PropertyFeature $feature): void
    {
        $feature->properties()->with(['type', 'location', 'features'])->each(
            fn ($property) => $this->sync->sync($property),
        );
    }

    public function deleted(PropertyFeature $feature): void
    {
        // حذف تعريف الميزة لا يعطي قائمة العقارات القديمة بعد حذفها.
        // تحديثات علاقة العقار نفسها تتم مزامنتها صراحة في PropertyController.
    }
}
