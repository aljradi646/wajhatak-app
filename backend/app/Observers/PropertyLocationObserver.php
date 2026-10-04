<?php

namespace App\Observers;

use App\Models\PropertyLocation;
use App\Services\AI\AiIndexSyncService;

class PropertyLocationObserver
{
    public function __construct(private readonly AiIndexSyncService $sync) {}

    public function saved(PropertyLocation $location): void
    {
        $location->properties()->each(fn ($property) => $this->sync->sync($property));
    }

    public function deleted(PropertyLocation $location): void
    {
        // العلاقات التابعة قد لا تُتاح بعد الحذف، لذلك لا نحاول اختلاق
        // بيانات الموقع؛ العقار نفسه سيُعاد مزامنته عند أي تحديث لاحق.
    }
}
