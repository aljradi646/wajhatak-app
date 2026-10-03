<?php

namespace App\Observers;

use App\Models\Property;
use App\Services\AI\AiIndexSyncService;

/**
 * مراقب العقار — نقطة مزامنة بيانات البحث الخاصة بالمساعد الذكي.
 * يغطي: إنشاء، تعديل (سعر/توفر/موقع/أي حقل)، حذف ناعم، حذف نهائي، استعادة.
 */
class PropertyObserver
{
    public function __construct(private readonly AiIndexSyncService $sync) {}

    public function created(Property $property): void
    {
        $this->sync->sync($property);
    }

    public function updated(Property $property): void
    {
        $this->sync->sync($property);
    }

    public function deleted(Property $property): void
    {
        $this->sync->remove($property->id);
    }

    public function restored(Property $property): void
    {
        $this->sync->sync($property);
    }

    public function forceDeleted(Property $property): void
    {
        $this->sync->remove($property->id);
    }
}
