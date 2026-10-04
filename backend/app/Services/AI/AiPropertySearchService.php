<?php

namespace App\Services\AI;

use App\Models\AiSearchIndex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * بحث العقارات للمساعد — يقرأ من ai_search_index (المتزامنة لحظيًا مع
 * properties) ويبني الاستعلام من معايير منظمة *مُتحقق منها* فقط.
 * هذه الخدمة هي الوحيدة التي تلمس البيانات — لا SQL حر من أي مصدر خارجي.
 * الترتيب: مطابقة هيكلية + نص حر + تعزيز (مميز/جديد) ثم قص إلى أفضل النتائج.
 */
class AiPropertySearchService
{
    /** ذاكرة الطلب الواحد لفحص المخطط (الخدمة تُنشأ لكل طلب — لا تُحفظ بين الطلبات). */
    private ?bool $indexAvailable = null;

    private ?bool $fullTextIndexAvailable = null;

    public function __construct(private readonly AiSettingsService $settings) {}

    /**
     * @param  array<string, mixed>  $filters  معايير منظمة (بعد تطهير AiIntentService).
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function search(array $filters, ?int $limit = null): array
    {
        // حماية من مخطط ناقص (جدول فهرس غير موجود على استضافة نفّذت النشر
        // قبل الهجرة): لا نرمي خطأ في وجه المستخدم بل نُبلّغ بحالة متدهورة.
        if (! $this->indexAvailable()) {
            return ['items' => [], 'total' => 0, 'degraded' => true];
        }

        try {
            return $this->runSearch($filters, $limit);
        } catch (\Illuminate\Database\QueryException $e) {
            // خطأ مخطط/استعلام — نُسجّل السبب الحقيقي بوضوح ونكمل بأمان.
            Log::error('ai.search_failed', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'filters' => $filters,
            ]);
            report($e);

            return ['items' => [], 'total' => 0, 'degraded' => true];
        }
    }

    /** @return array{items: list<array<string, mixed>>, total: int, degraded?: bool} */
    private function runSearch(array $filters, ?int $limit = null): array
    {
        $limit = $limit ?? (int) $this->settings->get('ai_max_results', 6);
        $maxCandidates = (int) $this->settings->get('ai_max_candidates', 60);
        $minScore = (float) $this->settings->get('ai_min_match_score', 0.05);

        $query = AiSearchIndex::query()
            ->with(['property.agent.user', 'property.location'])
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

        // --- بحث جغرافي حقيقي: «قريب مني» بإحداثيات العميل ---
        // يُحدد مربعًا محيطيًا (bounding box) حول الموقع ثم يحسب المسافة
        // الفعلية (هافرسين) لكل مترشح ويرفض ما خارج نصف القطر.
        $nearby = $filters['nearby'] ?? null;
        if (is_array($nearby) && isset($nearby['latitude'], $nearby['longitude'])) {
            $lat = (float) $nearby['latitude'];
            $lng = (float) $nearby['longitude'];
            $radiusKm = max(0.5, min(100, (float) ($nearby['radius_km'] ?? 10)));
            $latDelta = $radiusKm / 111.0;
            $lngDelta = $radiusKm / (111.0 * max(0.2, cos(deg2rad($lat))));
            $query->where('latitude', '>=', $lat - $latDelta)
                ->where('latitude', '<=', $lat + $latDelta)
                ->where('longitude', '>=', $lng - $lngDelta)
                ->where('longitude', '<=', $lng + $lngDelta);
        }

        // --- كلمات مفتاحية ناعمة (قريب من الجامعة / هادئ / للعائلة...) ---
        // تُطبق كـ OR على نص البحث المُعد؛ النتيجة التي لا تطابق أي كلمة تُعاقب في التسجيل بدل حذفها.
        $keywords = array_filter((array) ($filters['keywords'] ?? []));

        // --- نص حر على عمود البحث المُعد (LIKE محمي عبر binding) ---
        $freeText = trim((string) ($filters['q'] ?? ''));
        if ($freeText !== '') {
            $driver = DB::getDriverName();
            if ($driver === 'mysql' && $this->hasFullTextIndex()) {
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

        // ترشيح المسافة الفعلية للبحث القريب + تعزيز القرب في الترتيب.
        if (is_array($nearby) && isset($nearby['latitude'], $nearby['longitude'])) {
            $radiusKm = max(0.5, min(100, (float) ($nearby['radius_km'] ?? 10)));
            $rows = $rows->filter(function (AiSearchIndex $row) use ($nearby, $radiusKm) {
                if ($row->latitude === null || $row->longitude === null) {
                    return false;
                }

                return $this->haversineKm(
                    (float) $nearby['latitude'], (float) $nearby['longitude'],
                    (float) $row->latitude, (float) $row->longitude,
                ) <= $radiusKm;
            })->values();
        }

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

            // تعزيز القرب الجغرافي: كلما اقترب العقار من العميل ارتفع ترتيبه.
            if (is_array($nearby) && isset($nearby['latitude'], $nearby['longitude'])
                && $row->latitude !== null && $row->longitude !== null) {
                $distance = $this->haversineKm(
                    (float) $nearby['latitude'], (float) $nearby['longitude'],
                    (float) $row->latitude, (float) $row->longitude,
                );
                $score = min(1.0, $score + max(0.0, 0.2 * (1 - $distance / $radiusKm)));
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

    /**
     * بحث بدائل قريبة عند فشل التطابق الحرفي.
     *
     * يحتفظ بمرتكزات الطلب المهمة (نوع العملية، نوع العقار، المدينة) ويرخي
     * القيود الأقل أهمية، ثم يعيد ترتيب النتائج حسب مقدار تطابقها مع الطلب الأصلي.
     * كل نتيجة تبقى من ai_search_index، أي من عقار منشور حقيقي.
     *
     * @return array{items:list<array<string,mixed>>,relaxations:list<string>}
     */
    public function searchClosestAlternatives(array $filters, ?int $limit = null): array
    {
        if (! $this->indexAvailable()) {
            return ['items' => [], 'relaxations' => []];
        }

        $limit = $limit ?? (int) $this->settings->get('ai_max_results', 6);
        $maxCandidates = max($limit * 10, (int) $this->settings->get('ai_max_candidates', 60));

        try {
            // نحتفظ فقط بالمرتكزات التي تجعل البديل ذا صلة واضحة، ونحوّل
            // بقية الشروط إلى درجات مطابقة بدل شروط SQL صلبة.
            $query = AiSearchIndex::query()
                ->with(['property.agent.user', 'property.location'])
                ->whereIn('status', ['published'])
                ->when(! empty($filters['transaction_type']), fn (Builder $q) => $q->where('transaction_type', $filters['transaction_type']))
                ->when(! empty($filters['property_type']), fn (Builder $q) => $q->where('type_slug', $this->typeSlug($filters['property_type'])))
                ->when(! empty($filters['city']), fn (Builder $q) => $q->where('city', $filters['city']));

            $rows = $query
                ->orderByDesc('is_featured')
                ->orderByDesc('is_new')
                ->orderBy('price')
                ->limit($maxCandidates)
                ->get();

            $scored = $rows->map(fn (AiSearchIndex $row) => [
                'row' => $row,
                'score' => $this->score($row, $filters),
            ])->sortByDesc('score')->values();

            $items = $scored
                ->take($limit)
                ->map(fn (array $entry) => $this->present($entry['row'], (float) $entry['score'], detailed: true, alternative: true))
                ->all();

            $relaxations = [];
            if (! empty($filters['district']) || ! empty($filters['neighborhood'])) {
                $relaxations[] = 'وسّعت النطاق من الحي إلى بقية المدينة';
            }
            if (isset($filters['max_price']) || isset($filters['min_price'])) {
                $relaxations[] = 'خففت حد الميزانية قليلًا';
            }
            if (isset($filters['bedrooms_min']) || isset($filters['bedrooms_max'])) {
                $relaxations[] = 'خففت شرط عدد الغرف';
            }
            if (array_key_exists('furnished', $filters)) {
                $relaxations[] = 'خففت شرط التأثيث';
            }
            if (! empty($filters['is_new'])) {
                $relaxations[] = 'خففت شرط حداثة العقار';
            }
            if (! empty($filters['q']) || ! empty($filters['keywords'])) {
                $relaxations[] = 'وسّعت المطابقة النصية';
            }

            return ['items' => $items, 'relaxations' => $relaxations];
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('ai.closest_alternatives_failed', [
                'message' => $e->getMessage(),
                'filters' => $filters,
            ]);

            return ['items' => [], 'relaxations' => []];
        }
    }

    /** بحث بالمدينة/الحي لأدوات search_locations. */
    public function findLocations(string $term, int $limit = 8): array
    {
        if (! $this->indexAvailable()) {
            return [];
        }

        $like = '%'.$term.'%';

        try {
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
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('ai.locations_failed', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /** تفاصيل موسعة لعقار واحد من الفهرس (لأدوات get_property_details). */
    public function details(int $propertyId): ?array
    {
        if (! $this->indexAvailable()) {
            return null;
        }

        try {
            $row = AiSearchIndex::query()
                ->with(['property.agent.user', 'property.location'])
                ->where('property_id', $propertyId)
                ->whereIn('status', ['published'])
                ->first();

            return $row ? $this->present($row, 1.0, detailed: true) : null;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('ai.details_failed', ['message' => $e->getMessage(), 'property_id' => $propertyId]);

            return null;
        }
    }

    /** عقارات مشابهة (نفس النوع/المدينة وسعر مقارب) — لعقار مشابه لهذا. */
    public function similar(int $propertyId, int $limit = 4): array
    {
        if (! $this->indexAvailable()) {
            return [];
        }

        try {
            return $this->runSimilar($propertyId, $limit);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('ai.similar_failed', ['message' => $e->getMessage(), 'property_id' => $propertyId]);

            return [];
        }
    }

    private function runSimilar(int $propertyId, int $limit): array
    {
        $base = AiSearchIndex::query()->where('property_id', $propertyId)->first();
        if (! $base) {
            return [];
        }

        $rows = AiSearchIndex::query()
            ->with(['property.agent.user', 'property.location'])
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

        return $scored->map(fn ($e) => $this->present($e['row'], $e['score'], detailed: true))->all();
    }

    // ------------------------------------------------------------------

    /**
     * هل جدول فهرس المساعد موجود وجاهز للاستعلام؟
     * النتيجة تُحفظ في الطلب الواحد لتفادي فحص المخطط في كل استدعاء.
     */
    private function indexAvailable(): bool
    {
        if ($this->indexAvailable === null) {
            try {
                $this->indexAvailable = Schema::hasTable('ai_search_index')
                    && Schema::hasColumn('ai_search_index', 'search_text')
                    && Schema::hasColumn('ai_search_index', 'type_slug');
            } catch (\Throwable) {
                $this->indexAvailable = false;
            }
        }

        return $this->indexAvailable;
    }

    /** هل فهرس FULLTEXT موجود فعلًا على عمود البحث؟ (MySQL يرفض MATCH بدونه). */
    private function hasFullTextIndex(): bool
    {
        if ($this->fullTextIndexAvailable === null) {
            $this->fullTextIndexAvailable = false;
            try {
                foreach (Schema::getIndexes('ai_search_index') as $index) {
                    $columns = array_map('strtolower', (array) ($index['columns'] ?? []));
                    if (($index['type'] ?? null) === 'fulltext' && in_array('search_text', $columns, true)) {
                        $this->fullTextIndexAvailable = true;
                        break;
                    }
                }
            } catch (\Throwable) {
                $this->fullTextIndexAvailable = false;
            }
        }

        return $this->fullTextIndexAvailable;
    }

    /** مسافة هافرسين بالكيلومتر بين نقطتين جغرافيتين. */
    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

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
        if (isset($filters['bathrooms_min'])) {
            $checks++;
            $passed += ($row->bathrooms !== null && $row->bathrooms >= (int) $filters['bathrooms_min']) ? 1 : 0;
        }
        if (isset($filters['min_area'])) {
            $checks++;
            $passed += ($row->area !== null && (float) $row->area >= (float) $filters['min_area']) ? 1 : 0;
        }
        if (isset($filters['max_area'])) {
            $checks++;
            $passed += ($row->area !== null && (float) $row->area <= (float) $filters['max_area']) ? 1 : 0;
        }
        if (isset($filters['is_new'])) {
            $checks++;
            $passed += ($row->is_new === (bool) $filters['is_new']) ? 1 : 0;
        }
        foreach (array_filter((array) ($filters['keywords'] ?? [])) as $keyword) {
            $checks++;
            $passed += mb_stripos((string) $row->search_text, (string) $keyword) !== false ? 1 : 0;
        }

        $score = $checks > 0 ? $passed / $checks : 0.5;

        // تعزيز خفيف للعقارات المميزة والجديدة.
        if ($row->is_featured) {
            $score = min(1.0, $score + 0.05);
        }

        return round($score, 3);
    }

    /** شكل القطعة الموثوقة التي تُمرر للنموذج وللواجهة (بلا بيانات حساسة). */
    private function present(
        AiSearchIndex $row,
        float $score,
        bool $detailed = false,
        bool $alternative = false,
    ): array
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
            $data['latitude'] = $row->latitude !== null ? (float) $row->latitude : null;
            $data['longitude'] = $row->longitude !== null ? (float) $row->longitude : null;

            // بيانات عامة من العقار المنشور والوكيل؛ تُستخدم فقط لإجراءات
            // النسخ/المشاركة في الواجهة ولا تتضمن أسرارًا أو بيانات داخلية.
            $property = $row->relationLoaded('property') ? $row->property : null;
            $agent = $property?->agent;
            $location = $property?->location;

            if ($property?->reference_code) {
                $data['reference_code'] = (string) $property->reference_code;
            }
            if ($location?->address) {
                $data['address'] = (string) $location->address;
            }

            $agentPhone = $agent?->phone ?: $agent?->user?->phone;
            if ($agentPhone) {
                $data['agent_phone'] = (string) $agentPhone;
            }

            $copyActions = [];
            if ($data['price'] !== null && ! empty($data['currency'])) {
                $copyActions[] = [
                    'type' => 'copy',
                    'field' => 'price',
                    'label' => 'نسخ السعر',
                    'value' => number_format((float) $data['price']).' '.$data['currency'],
                ];
            }

            $locationText = $data['address']
                ?? collect([$data['district'] ?? null, $data['neighborhood'] ?? null, $data['city'] ?? null])
                    ->filter()->unique()->implode(' - ');
            if ($locationText !== '') {
                $copyActions[] = [
                    'type' => 'copy',
                    'field' => 'location',
                    'label' => 'نسخ الموقع',
                    'value' => $locationText,
                ];
            }

            if (! empty($data['reference_code'])) {
                $copyActions[] = [
                    'type' => 'copy',
                    'field' => 'reference_code',
                    'label' => 'نسخ الرمز',
                    'value' => $data['reference_code'],
                ];
            }

            if (! empty($data['agent_phone'])) {
                $copyActions[] = [
                    'type' => 'copy',
                    'field' => 'agent_phone',
                    'label' => 'نسخ هاتف الوكيل',
                    'value' => $data['agent_phone'],
                ];
            }

            $data['is_alternative'] = $alternative;
            $data['ui'] = [
                'component' => 'property_card',
                'variant' => $alternative ? 'close_match' : 'exact_match',
                'badge' => $alternative ? 'قريب من طلبك' : null,
                'image_priority' => true,
                'open_action' => [
                    'type' => 'open_property',
                    'property_id' => (int) $row->property_id,
                ],
                'title_action' => [
                    'type' => 'open_property',
                    'property_id' => (int) $row->property_id,
                ],
                'share_action' => [
                    'type' => 'share_property',
                    'property_id' => (int) $row->property_id,
                ],
                'copy_actions' => $copyActions,
            ];
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
