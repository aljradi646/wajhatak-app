<?php

namespace App\Services\AI;

use App\Models\AiSearchIndex;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * تحليلات سوقية وصفية من فهرس العقارات المنشورة فقط.
 *
 * لا تُخلط العملات أو أنواع العملية أو أنواع العقارات في متوسط واحد؛
 * تُعرض كل مجموعة كوحدة مقارنة مستقلة مع حجم العينة وتاريخ الاستخراج.
 * هذه إحصاءات للعروض الحالية وليست تقييمًا رسميًا أو توقعًا للعائد.
 */
class AiMarketInsightsService
{
    /**
     * @param array{city?:string,district?:string,property_type?:string,transaction_type?:string} $filters
     * @return array<string,mixed>
     */
    public function summarize(array $filters = []): array
    {
        try {
            if (! Schema::hasTable('ai_search_index')) {
                return [
                    'success' => false,
                    'error' => 'MARKET_INDEX_UNAVAILABLE',
                    'total_listings' => 0,
                    'groups' => [],
                    'as_of' => now()->toISOString(),
                ];
            }

            $query = AiSearchIndex::query()
                ->where('status', 'published')
                ->whereNotNull('price')
                ->where('price', '>', 0);

            foreach (['city', 'district', 'property_type', 'transaction_type'] as $field) {
                if (! isset($filters[$field]) || trim((string) $filters[$field]) === '') {
                    continue;
                }

                $value = trim((string) $filters[$field]);
                $column = match ($field) {
                    'property_type' => 'type_slug',
                    default => $field,
                };
                $query->where($column, $value);
            }

            $total = (clone $query)->count();

            $dimensions = ['city', 'currency', 'transaction_type', 'type_slug', 'type_name_ar'];
            if (! empty($filters['district'])) {
                $dimensions[] = 'district';
            }

            $rows = $query
                ->select($dimensions)
                ->selectRaw('COUNT(*) as listings_count')
                ->selectRaw('AVG(price) as average_price')
                ->selectRaw('MIN(price) as minimum_price')
                ->selectRaw('MAX(price) as maximum_price')
                ->selectRaw('AVG(NULLIF(area, 0)) as average_area')
                ->selectRaw('AVG(price / NULLIF(area, 0)) as average_price_per_sqm')
                ->groupBy($dimensions)
                ->orderByDesc('listings_count')
                ->limit(8)
                ->get();

            $groups = $rows->map(static function (AiSearchIndex $row): array {
                return [
                    'city' => $row->city,
                    'district' => $row->district,
                    'currency' => $row->currency,
                    'transaction_type' => $row->transaction_type,
                    'property_type' => $row->type_slug,
                    'property_type_name' => $row->type_name_ar,
                    'listings_count' => (int) $row->listings_count,
                    'average_price' => $row->average_price !== null ? round((float) $row->average_price, 2) : null,
                    'minimum_price' => $row->minimum_price !== null ? round((float) $row->minimum_price, 2) : null,
                    'maximum_price' => $row->maximum_price !== null ? round((float) $row->maximum_price, 2) : null,
                    'average_area' => $row->average_area !== null ? round((float) $row->average_area, 2) : null,
                    'average_price_per_sqm' => $row->average_price_per_sqm !== null
                        ? round((float) $row->average_price_per_sqm, 2)
                        : null,
                ];
            })->all();

            return [
                'success' => true,
                'total_listings' => $total,
                'groups' => $groups,
                'filters' => array_intersect_key($filters, array_flip(['city', 'district', 'property_type', 'transaction_type'])),
                'as_of' => now()->toISOString(),
                'disclaimer' => 'إحصاءات وصفية للعروض المنشورة ذات السعر المسجل؛ ليست تقييمًا رسميًا أو ضمانًا للعائد.',
            ];
        } catch (Throwable $exception) {
            Log::warning('ai.market_insights_failed', [
                'exception' => class_basename($exception),
            ]);

            return [
                'success' => false,
                'error' => 'MARKET_DATA_UNAVAILABLE',
                'total_listings' => 0,
                'groups' => [],
                'as_of' => now()->toISOString(),
            ];
        }
    }
}
