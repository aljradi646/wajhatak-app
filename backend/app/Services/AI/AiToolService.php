<?php

namespace App\Services\AI;

use Illuminate\Support\Arr;

/**
 * أدوات الخلفية الآمنة — القائمة البيضاء الوحيدة لما يمكن للمساعد الوصول إليه.
 * النموذج لا يرى SQL ولا الجداول؛ يستدعي أسماء أدوات محددة، وهنا نُطبق
 * validation و authorization والحدود (pagination/privacy) قبل أي استعلام.
 */
class AiToolService
{
    /** خرائط أسماء الأدوات إلى معالجاتها (قابلة للتسجيل من الخارج مستقبلًا). */
    private array $tools = [];

    public function __construct(
        private readonly AiPropertySearchService $search,
        private readonly AiSettingsService $settings,
    ) {
        $this->tools = [
            'search_properties' => $this->searchProperties(...),
            'get_property_details' => $this->getPropertyDetails(...),
            'compare_properties' => $this->compareProperties(...),
            'search_locations' => $this->searchLocations(...),
            'get_property_features' => $this->getPropertyFeatures(...),
            'check_property_availability' => $this->checkAvailability(...),
        ];
    }

    /** @return list<string> أسماء الأدوات المتاحة (للتسجيل والتشخيص). */
    public function names(): array
    {
        return array_keys($this->tools);
    }

    /**
     * تنفيذ أداة باسمها ووسائطها — يرفض أي أداة غير مسجلة.
     *
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, data?: mixed, error?: string, tool: string}
     */
    public function call(string $name, array $args): array
    {
        $handler = $this->tools[$name] ?? null;
        if ($handler === null) {
            return ['ok' => false, 'error' => 'أداة غير معروفة.', 'tool' => $name];
        }

        try {
            return ['ok' => true, 'data' => $handler($args), 'tool' => $name];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => 'تعذر تنفيذ الأداة.', 'tool' => $name];
        }
    }

    // ------------------------------------------------------------------
    // الأدوات
    // ------------------------------------------------------------------

    private function searchProperties(array $args): array
    {
        $filters = Arr::only($args, [
            'transaction_type', 'property_type', 'city', 'district', 'neighborhood',
            'bedrooms_min', 'bedrooms_max', 'bathrooms_min', 'min_price', 'max_price',
            'min_area', 'max_area', 'furnished', 'is_new', 'sort', 'q',
        ]);

        $result = $this->search->search($filters, min((int) ($args['limit'] ?? 0) ?: null, (int) $this->settings->get('ai_max_results', 6)));

        return [
            'total' => $result['total'],
            'properties' => $result['items'],
        ];
    }

    private function getPropertyDetails(array $args): array
    {
        $id = (int) ($args['property_id'] ?? 0);
        abort_unless($id > 0, 422, 'معرف عقار غير صالح.');

        $details = $this->search->details($id);
        if ($details === null) {
            return ['found' => false];
        }

        return ['found' => true, 'property' => $details];
    }

    private function compareProperties(array $args): array
    {
        abort_unless((bool) $this->settings->get('ai_allow_comparison', true), 403, 'المقارنة معطلة من الإدارة.');

        $ids = collect($args['property_ids'] ?? [])->map(fn ($v) => (int) $v)->filter()->unique()->take(4)->values();
        abort_if($ids->count() < 2, 422, 'يلزم عقاران على الأقل للمقارنة.');

        $properties = collect($ids)
            ->map(fn (int $id) => $this->search->details($id))
            ->filter()
            ->values()
            ->all();

        return ['properties' => $properties];
    }

    private function searchLocations(array $args): array
    {
        abort_unless($this->settings->isScoped('locations'), 403, 'بحث المواقع غير مفعّل.');

        $term = trim((string) ($args['term'] ?? ''));
        abort_unless($term !== '', 422, 'اكتب اسم مدينة أو حي للبحث.');

        return ['locations' => $this->search->findLocations($term)];
    }

    private function getPropertyFeatures(array $args): array
    {
        abort_unless($this->settings->isScoped('features'), 403, 'المزايا غير مفعّلة.');

        $id = (int) ($args['property_id'] ?? 0);
        $details = $id > 0 ? $this->search->details($id) : null;

        return [
            'found' => $details !== null,
            // المزايا داخل البحث تُمثّل في الوصف النصي؛ نعيد المعطيات المعروفة فقط.
            'features' => $details ? Arr::only($details, ['is_furnished', 'bedrooms', 'bathrooms', 'area', 'type', 'city', 'district']) : [],
        ];
    }

    private function checkAvailability(array $args): array
    {
        abort_unless($this->settings->isScoped('availability'), 403, 'التوفر غير مفعّل.');

        $id = (int) ($args['property_id'] ?? 0);
        $details = $id > 0 ? $this->search->details($id) : null;

        if ($details === null) {
            return ['available' => false, 'found' => false];
        }

        return [
            'found' => true,
            'available' => $details['status'] === 'متاح للعرض',
            'status' => $details['status'],
        ];
    }
}
