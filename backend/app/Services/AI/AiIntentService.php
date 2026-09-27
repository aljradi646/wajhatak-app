<?php

namespace App\Services\AI;

use App\Services\AI\Providers\LocalGlmProvider;
use App\Services\AI\AiProviderException;
use Illuminate\Support\Facades\Log;

/**
 * محلل النية — يحول لغة المستخدم الطبيعية إلى معايير بحث منظمة.
 * الاستراتيجية: 1) قواعد سريعة (regex) تلتقط الحالات الصريحة رخيصًا وموثوقًا
 * (المدينة، النوع، الغرف، السعر، التأثيث، نوع العملية)؛ 2) النموذج المحلي
 * للمركب المتبقي؛ ثم 3) دمج مع سياق المحادثة والتحقق من النطاق.
 * النتيجة لا تفترض معلومات غير مذكورة (كل حقل غير واضح = null).
 */
class AiIntentService
{
    /** المخطط الذي يلتزم به النموذج. */
    public const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'transaction_type' => ['type' => ['string', 'null'], 'enum' => ['sale', 'rent', null]],
            'property_type' => ['type' => ['string', 'null'], 'enum' => ['apartment', 'villa', 'floor', 'townhouse', 'land', 'shop', 'office', 'building', 'farm', 'house', null]],
            'city' => ['type' => ['string', 'null']],
            'district' => ['type' => ['string', 'null']],
            'neighborhood' => ['type' => ['string', 'null']],
            'bedrooms_min' => ['type' => ['integer', 'null']],
            'bedrooms_max' => ['type' => ['integer', 'null']],
            'bathrooms_min' => ['type' => ['integer', 'null']],
            'min_price' => ['type' => ['number', 'null']],
            'max_price' => ['type' => ['number', 'null']],
            'min_area' => ['type' => ['number', 'null']],
            'max_area' => ['type' => ['number', 'null']],
            'furnished' => ['type' => ['boolean', 'null']],
            'is_new' => ['type' => ['boolean', 'null']],
            'sort' => ['type' => ['string', 'null'], 'enum' => ['price_asc', 'price_desc', 'area_desc', 'relevance', null]],
            'out_of_scope' => ['type' => ['boolean', 'null']],
        ],
    ];

    public function __construct(
        private readonly AiProviderManager $providers,
        private readonly AiGuardrailService $guardrails,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $history  رسائل المحادثة السابقة (قصيرة).
     * @param  array<string, mixed>  $previousFilters  المعايير المتراكمة من المحادثة.
     * @return array{filters: array<string, mixed>, out_of_scope: bool, parser: string}
     */
    public function parse(string $message, array $history = [], array $previousFilters = []): array
    {
        $guard = $this->guardrails->inspect($message);
        if ($guard['blocked']) {
            return ['filters' => [], 'out_of_scope' => true, 'parser' => 'guardrail:'.$guard['reason']];
        }

        $filters = $this->ruleBased($message);

        // حقول لم تلتقطها القواعد → النموذج المحلي (بحد صارم للمخرجات وبلا أدوات).
        $remaining = $this->unsettledFields($filters);
        if ($remaining !== [] || preg_match('/قريب|جوار|قرب|مناسب|هادئ|عائلة|جامعة|مستشفى|سوق/u', $message)) {
            try {
                $modelFilters = $this->modelParse($message, $history);
                $filters = $this->merge($filters, $modelFilters);
            } catch (AiProviderException $e) {
                // القواعد تكفي غالبًا؛ النموذج تعذر → نكمل بالقواعد فقط.
                Log::info('ai.intent.model_fallback', ['error' => class_basename($e)]);
            }
        }

        // دمج مع سياق المحادثة: الجديد يتغلب على القديم، والقديم يبقى للمفقود.
        $filters = $this->merge($previousFilters, $filters);

        return [
            'filters' => $this->normalizeFilters($filters),
            'out_of_scope' => (bool) ($filters['out_of_scope'] ?? false),
            'parser' => 'rules+model',
        ];
    }

    /** تحويل JSON النموذج إلى معايير آمنة (بلا أي حقول غير مسموحة). */
    private function modelParse(string $message, array $history): array
    {
        $messages = [
            ['role' => 'system', 'content' => app(AiPromptService::class)->parserSystemPrompt()],
            ...array_slice($history, -4),
            ['role' => 'user', 'content' => $message],
        ];

        $raw = $this->providers->provider()->structured($messages, self::SCHEMA, [
            'temperature' => 0.1,
            'max_tokens' => 250,
        ]);

        if (! empty($raw['out_of_scope'])) {
            return ['out_of_scope' => true];
        }

        $allowed = ['transaction_type', 'property_type', 'city', 'district', 'neighborhood',
            'bedrooms_min', 'bedrooms_max', 'bathrooms_min', 'min_price', 'max_price',
            'min_area', 'max_area', 'furnished', 'is_new', 'sort'];
        $out = [];
        foreach ($allowed as $key) {
            $value = $raw[$key] ?? null;
            if ($value !== null && $value !== '' && $value !== 'null') {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // طبقة القواعد — التقط الصريح دائمًا (أسرع وأدق من النموذج).
    // ------------------------------------------------------------------
    private function ruleBased(string $message): array
    {
        $filters = [];
        $text = $this->normalize($message);

        foreach ($this->transactionMap() as $pattern => $value) {
            if (preg_match($pattern, $text)) {
                $filters['transaction_type'] = $value;
                break;
            }
        }

        foreach ($this->typeMap() as $pattern => $value) {
            if (preg_match($pattern, $text)) {
                $filters['property_type'] = $value;
                break;
            }
        }

        if (preg_match('/(غرفتين|غرفتان|غرفتين أو (اثنتين|2)|2\s*غرف)/u', $text)) {
            $filters['bedrooms_min'] = 2;
        } elseif (preg_match('/(ثلاث\s*غرف|3\s*غرف|غرفتين أو ثلاث|غرفتين او ثلاث|2\s*(?:إلى|-)\s*3)/u', $text)) {
            $filters['bedrooms_min'] = 2;
            $filters['bedrooms_max'] = 3;
        } elseif (preg_match('/(غرفة\s*واحدة|غرفة\s*نوم\s*واحدة|1\s*غرفة)/u', $text)) {
            $filters['bedrooms_min'] = 1;
            $filters['bedrooms_max'] = 1;
        } elseif (preg_match('/(أربع|4)\s*غرف/u', $text)) {
            $filters['bedrooms_min'] = 4;
        } elseif (preg_match('/(خمس|5)\s*غرف/u', $text)) {
            $filters['bedrooms_min'] = 5;
        }

        if (preg_match('/مفروش/u', $text)) {
            $filters['furnished'] = true;
        } elseif (preg_match('/(غير\s*مفروش|بدون\s*أثاث|بدون\s*اثاث)/u', $text)) {
            $filters['furnished'] = false;
        }

        if (preg_match('/جديد|حديث\s*البناء/u', $text)) {
            $filters['is_new'] = true;
        }

        if (preg_match('/(أرخص|ارخص)/u', $text)) {
            $filters['sort'] = 'price_asc';
        } elseif (preg_match('/(أغلى|اغلى|الأفخم)/u', $text)) {
            $filters['sort'] = 'price_desc';
        }

        // الأسعار: "أقل من 150 ألف"، "من 50 إلى 100 مليون"، "150K"، "٢٠٠٠٠٠".
        $units = $this->extractPriceUnits($text);
        if ($units !== []) {
            foreach ($units as [$amount, $kind]) {
                if ($kind === 'max') {
                    $filters['max_price'] = $amount;
                } elseif ($kind === 'min') {
                    $filters['min_price'] = $amount;
                } else {
                    $filters['max_price'] = $filters['max_price'] ?? $amount;
                }
            }
        }

        // المدن والأحياء المعروفة.
        foreach ($this->cityMap() as $pattern => $city) {
            if (preg_match('/'.$pattern.'/u', $text)) {
                $filters['city'] = $city;
                break;
            }
        }

        foreach (['حدة' => 'حدة', 'السافية' => 'الصافية', 'شعوب' => 'شعوب', 'معين' => 'معين', 'آزال' => 'آزال', 'بني الحارث' => 'بني الحارث', 'الثورة' => 'الثورة', 'التحرير' => 'التحرير', 'الوحدة' => 'الوحدة', 'السفارة' => 'السفارة'] as $pattern => $district) {
            if (mb_strpos($text, $this->normalize($pattern)) !== false) {
                $filters['district'] = $district;
                break;
            }
        }

        return $filters;
    }

    /** @return list<array{0: float, 1: string}> */
    private function extractPriceUnits(string $text): array
    {
        $out = [];
        // رقم + وحدة (ألف/مليون/k/m) في سياق سعر.
        if (preg_match_all('/([\d٠-٩]+(?:[.,][\d٠-٩]+)?)\s*(مليون|مليونين|ألف|الف|الفين|ك\b|k\b|m\b|مليون)?/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $num = $this->toNumber($m[1]);
                if ($num === null) {
                    continue;
                }
                $unit = $m[2] ?? '';
                $multiplier = match (true) {
                    str_contains($unit, 'مليون') => 1_000_000,
                    str_contains($unit, 'ألف') || str_contains($unit, 'الف') || $unit === 'ك' || strtolower($unit) === 'k' => 1_000,
                    strtolower($unit) === 'm' => 1_000_000,
                    default => 1,
                };
                $value = $num * $multiplier;
                // تجاهل أعداد الغرف والمساحات الصغيرة: ليست أسعارًا.
                if ($value < 1000) {
                    continue;
                }
                // تحديد اتجاه السعر من السياق قبل الرقم.
                $position = mb_strpos($text, $m[0]);
                $before = $position !== false ? mb_substr($text, 0, $position) : $text;
                $isMax = (bool) preg_match('/(أقل|اقل|حتى|بحدود|ميزانية|دون)/u', $before);
                $isMin = (bool) preg_match('/(فوق|أكثر|اكثر|بداية|يبدأ)/u', $before);
                $out[] = [$value, $isMax && ! $isMin ? 'max' : ($isMin ? 'min' : 'max')];
            }
        }

        return $out;
    }

    private function toNumber(string $raw): ?float
    {
        $raw = str_replace(['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'], $raw);

        return is_numeric($raw) ? (float) $raw : null;
    }

    private function transactionMap(): array
    {
        return [
            '/(إيجار|ايجار|استئجار|للإيجار|للايجار|إيجاري|ايجاري|كراء)/u' => 'rent',
            '/(بيع|للبيع|تمليك|شراء|أشتري|اشتري)/u' => 'sale',
        ];
    }

    private function typeMap(): array
    {
        return [
            '/(شقة|شقق|دوبلكس)/u' => 'apartment',
            '/(فيلا|فلل)/u' => 'villa',
            '/(دور\s*كامل|دورين|دور\s*سكني)/u' => 'floor',
            '/تاون\s*هاوس/u' => 'townhouse',
            '/(أرض|ارض|قطعة\s*أرض)/u' => 'land',
            '/(محل|محلات|تجاري)/u' => 'shop',
            '/(مكتب|إداري)/u' => 'office',
            '/(عمارة|عمارات|برج)/u' => 'building',
            '/(مزرعة|مزارع)/u' => 'farm',
            '/(بيت|بيوت|منزل|منازل|هous)/u' => 'house',
        ];
    }

    private function cityMap(): array
    {
        return [
            'صنعاء' => 'صنعاء', 'عدن' => 'عدن', 'تعز' => 'تعز', 'الحديدة' => 'الحديدة',
            'المكلا' => 'المكلا', 'إب' => 'إب', 'مارب' => 'مارب', 'سيئون' => 'سيئون',
            'ذمار' => 'ذمار', 'حجة' => 'حجة', 'المهرة' => 'المهرة', 'رياض' => 'الرياض', 'جدة' => 'جدة',
        ];
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{0652}]/u', '', $text) ?? $text;

        return str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $text);
    }

    /** الحقول التي لم تُحدد بعد وتحتاج قرارًا (للمتابعة الذكية). */
    private function unsettledFields(array $filters): array
    {
        $needed = ['transaction_type', 'property_type', 'city', 'district', 'max_price', 'bedrooms_min'];

        return array_values(array_filter($needed, fn ($f) => empty($filters[$f])));
    }

    /** دمج: الجديد يتفوق، مع تجاهل out_of_scope القديمة. */
    private function merge(array $base, array $override): array
    {
        unset($base['out_of_scope'], $override['out_of_scope']);

        return array_merge($base, array_filter($override, fn ($v) => $v !== null && $v !== ''));
    }

    /** تطهير نهائي للمعايير قبل البحث. */
    private function normalizeFilters(array $filters): array
    {
        foreach (['bedrooms_min', 'bedrooms_max', 'bathrooms_min'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = max(0, min(20, (int) $filters[$key]));
            }
        }
        foreach (['min_price', 'max_price', 'min_area', 'max_area'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = max(0, (float) $filters[$key]);
            }
        }
        if (isset($filters['furnished'])) {
            $filters['furnished'] = filter_var($filters['furnished'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($filters['is_new'])) {
            $filters['is_new'] = filter_var($filters['is_new'], FILTER_VALIDATE_BOOLEAN);
        }

        return $filters;
    }
}
