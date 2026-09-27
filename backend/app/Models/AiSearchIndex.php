<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * صف فهرس البحث الخاص بالمساعد الذكي — نسخة مُعدّة للبحث من جدول properties.
 * المزامنة تلقائية عند أي إنشاء/تعديل/حذف للعقار (PropertyObserver).
 */
class AiSearchIndex extends Model
{
    use HasFactory;

    protected $table = 'ai_search_index';

    protected $fillable = [
        'property_id', 'title', 'description', 'transaction_type', 'status',
        'type_slug', 'type_name_ar', 'city', 'district', 'neighborhood',
        'price', 'currency', 'area', 'bedrooms', 'bathrooms', 'is_furnished',
        'is_new', 'is_featured', 'published_at', 'search_text', 'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'area' => 'decimal:2',
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'is_furnished' => 'boolean',
            'is_new' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** تجزئة المحتوى الحالي للعقار لكشف التغييرات بلا استعلامات ثقيلة. */
    public static function hashProperty(Property $property): string
    {
        return hash('sha256', serialize([
            $property->title,
            $property->description,
            $property->transaction_type instanceof \BackedEnum ? $property->transaction_type->value : $property->transaction_type,
            $property->status instanceof \BackedEnum ? $property->status->value : $property->status,
            $property->type?->slug,
            $property->type?->name_ar,
            $property->location?->city,
            $property->location?->district,
            $property->location?->neighborhood,
            (string) $property->price,
            $property->currency,
            (string) $property->area,
            $property->bedrooms,
            $property->bathrooms,
            $property->is_furnished,
            $property->is_new,
            $property->is_featured,
            optional($property->published_at)->toDateTimeString(),
            $property->features()->pluck('name_ar')->sort()->values()->all(),
        ]));
    }
}
