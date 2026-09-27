<?php

namespace App\Services\AI;

use App\Models\AiSearchIndex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * بحث العقارات للمساعد — يقرأ من ai_search_index (المتزامنة لحظيًا مع
 * properties) ويبني الاستعلام من معايير منظمة *مُتحقق منها* فقط.
 * هذه الخدمة هي الوحيدة التي تلمس البيانات — لا SQL حر من أي مصدر خارجي.
 * الترتيب: مطابقة هيكلية + نص حر + تعزيز (مميز/جديد) ثم قص إلى أفضل النتائج.
 */
class AiPropertySearchService
{
    public function __construct(private readonly AiSettingsService $settings) {}

    /**
     * @param  array<string, mixed>  $filters  معايير منظمة (بعد تطهير AiIntentService).
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function search(array $filters, ?int $limit = null): array
    {
        $limit = $limit ?? (int) $this->settings->get('ai_max_results', 6);
        $maxCandidates = (int) $this->settings->get('ai_max_candidates', 60);
        $minScore = (float) $this->settings->get('ai_min_match_score', 0.05);

        $query = AiSearchIndex::query()
            ->whereIn('status', ['published']);

        // --- تصفية هيكلية (كلها AND) ---
        $query->when(! empty($filters['transaction_type']), fn (Builder $q) => $q->where('transaction_type', $filters['transaction_type']))
            ->when(! empty($filters['property_type']), fn (Builder $q) => $q->where('type_slug', $this->typeSlug($filters['property_type'])))
            ->when(! empty($filters['city']), fn (Builder $q) => $q->where('city', $filters['city']))
            ->when(! empty($filters['district']), fn (Builder $q) => $q->where(function (Builder $q) use ($filters) {
                $q->where('district', $filters['district'])->orWhere('neighborhood', $filters['district']);
            }))
            ->when(isset($filters['bedrooms_min']), fn (Builder $q) => $q->where('bedrooms', '>=', (int) $filters['bedrooms_min']))
            ->when(isset($filters['bedrooms_max']), fn (Builder $q) => $q->where('bedrooms', '<=', (int) $filters['bedrooms_max']))
            ->when(isset($filters['bathrooms_min']), fn (Builder $q) => $q->where('bathrooms', '>=', (int) $filters['bathrooms_min']))
            ->when(isset($filters['min_price']), fn (Builder $q) => $q->where('price', '>=', (float) $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $q) => $q->where('price', '<=', (float) $filters['max_price']))
            ->when(isset($filters['min_area']), fn (Builder $q) => $q->where('area', '>=', (float) $filters['min_area']))
            ->when(isset($filters['max_area']), fn (Builder $q) => $q->where('area', '<=', (float) $filters['max_area']))
            ->when(array_key_exists('furnished', $filters) && $filters['furnished'] !== null, fn (Builder $q) => $q->where('is_furnished', (bool) $filters['furnished']))
            ->when(! empty($filters['is_new']), fn (Builder $q) => $q->where('is_new', true));

        // --- كلمات مفتاحية ناعمة (قريب من الجامعة / هادئ / للعائلة...) ---
        // تُطبق كـ OR على نص البحث المُعد؛ النتيجة التي لا تطابق أي كلمة تُعاقب في التسجيل بدل حذفها.
        $keywords = array_filter((array) ($filters['keywords'] ?? []));

        // --- نص حر على عمود البحث المُعد (LIKE محمي عبر binding) ---
        $freeText = trim((string) ($filters['q'] ?? ''));
        if ($freeText !== '') {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                // FULLTEXT عبر MATCH...AGAINST في وضع boolean مع تراجع إلى LIKE.
                $query->where(function (Builder $q) use ($freeText) {
                    $q->whereFullText(['search_text'], $freeText, ['mode' => 'boolean'])
                        ->orWhere(function (Builder $qq) use ($freeText) {
                            $qq->where('title', 'like', '%'.$freeText.'%')
                                ->orWhere('search_text', 'like', '%'.$freeText.'%');
                        });
                });
            } else {
                $terms = preg_split('/\s+/u', $freeText) ?: [];
                foreach (array_slice($terms, 0, 4) as $term) {
                    $query->where('search_text', 'like', '%'.$term.'%');
                }
            }
        }

        $total = (clone $query)->count();

        // --- ترشيح المترشحين وترتيبهم ---
        $sort = $filters['sort'] ?? $this->settings->get('ai_sort_strategy', 'relevance');
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'area_desc' => $query->orderByDesc('area'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_new')->orderBy('price'),
        };

        $rows = $query->limit($maxCandidates)->get();

        // --- تسجيل النتائج (Matching Score) وترتيبها النهائي ---
        $scored = [];
        foreach ($rows as $row) {
            $score = $this->score($row, $filters);

            // تعزيز الكلمات المفتاحية الناعمة في الترتيب (مطابقة نصية على search_text).
            foreach ($keywords as $keyword) {
                if (mb_stripos((string) $row->search_text, $keyword) !== false) {
                    $score = min(1.0, $score + 0.1);
                }
            }

            if ($score >= $minScore) {
                $scored[] = ['row' => $row, 'score' => $score];
            }
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $scored = array_slice($scored, 0, $limit);

        return [
            'items' => array_map(fn ($entry) => $this->present($entry['row'], $entry['score'], detailed: true), $scored),
            'total' => $total,
        ];
    }

    /** بحث بالمدينة/الحي لأدوات search_locations. */
    public function findLocations(string $term, int $limit = 8): array
    {
        $like = '%'.$term.'%';

        return AiSearchIndex::query()
            ->whereIn('status', ['published'])
            ->where(fn (Builder $q) => $q->where('city', 'like', $like)
                ->orWhere('district', 'like', $like)
                ->orWhere('neighborhood', 'like', $like))
            ->selectRaw('city, district, neighborhood, count(*) as properties_count, min(price) as min_price, max(price) as max_price')
            ->groupBy('city', 'district', 'neighborhood')
            ->orderByDesc('properties_count')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'city' => $r->city,
                'district' => $r->district,
                'neighborhood' => $r->neighborhood,
                'properties_count' => (int) $r->properties_count,
                'min_price' => $r->min_price !== null ? (float) $r->min_price : null,
                'max_price' => $r->max_price !== null ? (float) $r->max_price : null,
            ])
            ->all();
    }

    /** تفاصيل موسعة لعقار واحد من الفهرس (لأدوات get_property_details). */
    public function details(int $propertyId): ?array
    {
        $row = AiSearchIndex::query()->where('property_id', $propertyId)->whereIn('status', ['published'])->first();

        return $row ? $this->present($row, 1.0, detailed: true) : null;
    }

    /** عقارات مشابهة (نفس النوع/المدينة وسعر مقارب) — لعقار مشابه لهذا. */
    public function similar(int $propertyId, int $limit = 4): array
    {
        $base = AiSearchIndex::query()->where('property_id', $propertyId)->first();
        if (! $base) {
            return [];
        }

        $rows = AiSearchIndex::query()
            ->whereIn('status', ['published'])
            ->where('property_id', '!=', $propertyId)
            ->when($base->type_slug, fn (Builder $q) => $q->where('type_slug', $base->type_slug))
            ->when($base->city, fn (Builder $q) => $q->where('city', $base->city))
            ->get();

        $scored = $rows->map(function (AiSearchIndex $row) use ($base) {
            $score = 0.4;
            if ($row->type_slug === $base->type_slug) {
                $score += 0.2;
            }
            if ($row->city === $base->city) {
                $score += 0.15;
            }
            if ($base->price > 0 && $row->price > 0) {
                $diff = abs((float) $row->price - (float) $base->price) / max((float) $base->price, (float) $row->price);
                $score += max(0, 0.25 * (1 - $diff * 2));
            }

            return ['row' => $row, 'score' => min(1.0, $score)];
        })->sortByDesc('score')->take($limit)->values();

        return $scored->map(fn ($e) => $this->present($e['row'], $e['score']))->all();
    }

    // ------------------------------------------------------------------

    /** درجة مطابقة 0..1 بين صف الفهرس والمعايير (تُستخدم للترتيب والحد الأدنى). */
    private function score(AiSearchIndex $row, array $filters): float
    {
        $checks = 0;
        $passed = 0;

        foreach ([['transaction_type', 'transaction_type'], ['type_slug', 'type'], ['city', 'city'], ['district', 'district']] as [$column, $filterKey]) {
            if (! empty($filters[$filterKey])) {
                $checks++;
                $value = $filterKey === 'type' ? $this->typeSlug($filters[$filterKey]) : $filters[$filterKey];
                if ($row->{$column} === $value || ($filterKey === 'district' && $row->neighborhood === $value)) {
                    $passed++;
                }
            }
        }
        if (isset($filters['bedrooms_min'])) {
            $checks++;
            $passed += ($row->bedrooms !== null && $row->bedrooms >= (int) $filters['bedrooms_min']) ? 1 : 0;
        }
        if (isset($filters['bedrooms_max'])) {
            $checks++;
            $passed += ($row->bedrooms !== null && $row->bedrooms <= (int) $filters['bedrooms_max']) ? 1 : 0;
        }
        if (isset($filters['max_price'])) {
            $checks++;
            $passed += ($row->price !== null && (float) $row->price <= (float) $filters['max_price']) ? 1 : 0;
        }
        if (isset($filters['min_price'])) {
            $checks++;
            $passed += ($row->price !== null && (float) $row->price >= (float) $filters['min_price']) ? 1 : 0;
        }
        if (array_key_exists('furnished', $filters) && $filters['furnished'] !== null) {
            $checks++;
            $passed += ($row->is_furnished === (bool) $filters['furnished']) ? 1 : 0;
        }

        $score = $checks > 0 ? $passed / $checks : 0.5;

        // تعزيز خفيف للعقارات المميزة والجديدة.
        if ($row->is_featured) {
            $score = min(1.0, $score + 0.05);
        }

        return round($score, 3);
    }

    /** شكل القطعة الموثوقة التي تُمرر للنموذج وللواجهة (بلا بيانات حساسة). */
    private function present(AiSearchIndex $row, float $score, bool $detailed = false): array
    {
        $data = [
            'property_id' => $row->property_id,
            'title' => $row->title,
            'type' => $row->type_name_ar,
            'type_slug' => $row->type_slug,
            'transaction_type' => $row->transaction_type,
            'city' => $row->city,
            'district' => $row->district,
            'neighborhood' => $row->neighborhood,
            'price' => $row->price !== null ? (float) $row->price : null,
            'currency' => $row->currency,
            'area' => $row->area !== null ? (float) $row->area : null,
            'bedrooms' => $row->bedrooms,
            'bathrooms' => $row->bathrooms,
            'is_furnished' => (bool) $row->is_furnished,
            'is_new' => (bool) $row->is_new,
            'is_featured' => (bool) $row->is_featured,
            'available' => $row->status === 'published',
            'match_score' => $score,
        ];

        if ($detailed) {
            $data['description'] = $row->description;
            // رابط صورة الغلاف الحقيقية من جدول property_images (إن وجدت).
            $image = \App\Models\PropertyImage::query()
                ->where('property_id', $row->property_id)
                ->orderByDesc('is_cover')->orderBy('sort_order')
                ->first(['path']);
            $data['image_url'] = $image ? asset('storage/'.$image->path) : null;
        }

        return $data;
    }

    private function typeSlug(string $input): string
    {
        return match ($input) {
            'apartment' => 'apartment', 'villa' => 'villa', 'floor' => 'floor',
            'townhouse' => 'townhouse', 'land' => 'land', 'shop' => 'shop',
            'office' => 'office', 'building' => 'building', 'farm' => 'farm', 'house' => 'house',
            default => $input,
        };
    }
}
