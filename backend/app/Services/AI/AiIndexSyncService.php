<?php

namespace App\Services\AI;

use App\Models\AiSearchIndex;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * مزامنة فهرس البحث مع العقارات — إلزامية: أي إنشاء/تعديل/حذف/تغيير سعر
 * أو توفر أو موقع ينعكس مباشرة على ما يراه المساعد (لا snapshot قديمة).
 * PropertyObserver يستدعي هذه الخدمة عند كل حدث Eloquent.
 */
class AiIndexSyncService
{
    public function sync(Property $property): void
    {
        $property->refresh()->loadMissing(['type', 'location', 'features']);

        $hash = AiSearchIndex::hashProperty($property);
        $existing = AiSearchIndex::query()->where('property_id', $property->id)->first();

        // تحديث رخيص: نفس المحتوى → لا كتابة.
        if ($existing && $existing->content_hash === $hash) {
            return;
        }

        $searchText = $this->buildSearchText($property);

        AiSearchIndex::query()->updateOrCreate(
            ['property_id' => $property->id],
            [
                'title' => $property->title,
                'description' => $property->description,
                'transaction_type' => $property->transaction_type instanceof \BackedEnum ? $property->transaction_type->value : (string) $property->transaction_type,
                'status' => $property->status instanceof \BackedEnum ? $property->status->value : (string) $property->status,
                'type_slug' => $property->type?->slug,
                'type_name_ar' => $property->type?->name_ar,
                'city' => $property->location?->city,
                'district' => $property->location?->district,
                'neighborhood' => $property->location?->neighborhood,
                'price' => $property->price,
                'currency' => $property->currency,
                'area' => $property->area,
                'bedrooms' => $property->bedrooms,
                'bathrooms' => $property->bathrooms,
                'is_furnished' => (bool) $property->is_furnished,
                'is_new' => (bool) $property->is_new,
                'is_featured' => (bool) $property->is_featured,
                'published_at' => $property->published_at,
                // الإحداثيات الحقيقية لموقع العقار — للبحث الجغرافي القريب.
                'latitude' => $property->location?->latitude,
                'longitude' => $property->location?->longitude,
                'search_text' => $searchText,
                'content_hash' => $hash,
            ],
        );
    }

    public function remove(int $propertyId): void
    {
        AiSearchIndex::query()->where('property_id', $propertyId)->delete();
    }

    /** إعادة بناء كامل (Command: ai:reindex). */
    public function reindexAll(): int
    {
        $count = 0;
        Property::query()->with(['type', 'location', 'features'])->chunkById(200, function ($properties) use (&$count) {
            foreach ($properties as $property) {
                $this->sync($property);
                $count++;
            }
        });

        return $count;
    }

    /** نص بحث حر مُعد مسبقًا: عنوان + موقع + مزايا + وصف مقصوص. */
    private function buildSearchText(Property $property): string
    {
        return collect([
            $property->title,
            $property->location?->city,
            $property->location?->district,
            $property->location?->neighborhood,
            $property->location?->address,
            $property->type?->name_ar,
            $property->type?->name_en,
            $property->features->pluck('name_ar')->implode(' '),
            $property->is_furnished ? 'مفروش مؤثث' : null,
            $property->transaction_type === 'rent' ? 'إيجار كراء' : 'بيع تمليك',
            mb_substr((string) $property->description, 0, 600),
        ])->filter()->implode(' | ');
    }
}
