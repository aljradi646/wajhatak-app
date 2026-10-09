<?php

namespace App\Services\AI;

/**
 * محلل النية — يحول لغة المستخدم الطبيعية إلى معايير بحث منظمة.
 * استراتيجية حتمية 100% (بلا أي نموذج لغوي ولا مزود خارجي): قواعد سريعة
 * (regex) تلتقط الحالات الصريحة موثوقًا — المدينة، النوع، الغرف، السعر،
 * التأثيث، نوع العملية — ثم دمج مع سياق المحادثة والتحقق من النطاق.
 * النتيجة لا تفترض معلومات غير مذكورة (كل حقل غير واضح = null).
 */
class AiIntentService
{
    /** المخطط الذي يلتزم به البحث. */
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
            'bathrooms_max' => ['type' => ['integer', 'null']],
            'min_price' => ['type' => ['number', 'null']],
            'max_price' => ['type' => ['number', 'null']],
            'min_area' => ['type' => ['number', 'null']],
            'max_area' => ['type' => ['number', 'null']],
            'furnished' => ['type' => ['boolean', 'null']],
            'is_new' => ['type' => ['boolean', 'null']],
            'keywords' => ['type' => ['array', 'null'], 'items' => ['type' => 'string']],
            'similar_to' => ['type' => ['integer', 'null']],
            'sort' => ['type' => ['string', 'null'], 'enum' => ['price_asc', 'price_desc', 'area_desc', 'relevance', null]],
            'out_of_scope' => ['type' => ['boolean', 'null']],
        ],
    ];

    public function __construct(
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

        // دمج مع سياق المحادثة: الجديد يتغلب على القديم، والقديم يبقى للمفقود.
        $filters = $this->merge($previousFilters, $filters);

        return [
            'filters' => $this->normalizeFilters($filters),
            'out_of_scope' => (bool) ($filters['out_of_scope'] ?? false),
            'parser' => 'rules',
        ];
    }

    // ------------------------------------------------------------------
    // طبقة القواعد — التقط الصريح دائمًا (أسرع وأدق من النموذج).
    // ------------------------------------------------------------------
    private function ruleBased(string $message): array
    {
        $filters = [];
        $text = $this->normalize($message);

        foreach ($this->transactionMap() as $pattern => $value) {
            if ($this->matches($pattern, $text)) {
                $filters['transaction_type'] = $value;
                break;
            }
        }

        foreach ($this->typeMap() as $pattern => $value) {
            if ($this->matches($pattern, $text)) {
                $filters['property_type'] = $value;
                break;
            }
        }

        // الاستثمار يبقى داخل النطاق العقاري؛ لا نستنتج عائدًا أو أداءً ماليًا.
        if ($this->matches('/(استثمار|استثماري|استثمارية|دخل\\s*استثماري|عائد|roi)/u', $text)) {
            $filters['investment'] = true;
            $filters['transaction_type'] ??= 'sale';
        }

        // الترتيب مقصود: نطاق «من .. إلى» أولًا، ثم الحالات المفردة.
        if ($this->matches('/(ثلاث\s*غرف|3\s*غرف|غرفتين أو ثلاث|غرفتين او ثلاث|2\s*(?:إلى|-)\s*3)/u', $text)) {
            $filters['bedrooms_min'] = 2;
            $filters['bedrooms_max'] = 3;
        } elseif ($this->matches('/(غرفتين|غرفتان|2\s*غرف)/u', $text)) {
            $filters['bedrooms_min'] = 2;
        } elseif ($this->matches('/(غرفة\s*واحدة|غرفة\s*نوم\s*واحدة|1\s*غرفة)/u', $text)) {
            $filters['bedrooms_min'] = 1;
            $filters['bedrooms_max'] = 1;
        } elseif ($this->matches('/(أربع|4)\s*غرف/u', $text)) {
            $filters['bedrooms_min'] = 4;
        } elseif ($this->matches('/(خمس|5)\s*غرف/u', $text)) {
            $filters['bedrooms_min'] = 5;
        }

        // الانتباه للترتيب: «غير مفروش» تحتوي كلمة «مفروش»، لذا يُفحص النفي أولًا.
        if ($this->matches('/(غير\s*مفروش|بدون\s*أثاث|بدون\s*اثاث|غير\s*مؤثث)/u', $text)) {
            $filters['furnished'] = false;
        } elseif ($this->matches('/مفروش/u', $text)) {
            $filters['furnished'] = true;
        }

        if ($this->matches('/جديد|حديث\s*البناء/u', $text)) {
            $filters['is_new'] = true;
        }

        if ($this->matches('/(أرخص|ارخص)/u', $text)) {
            $filters['sort'] = 'price_asc';
        } elseif ($this->matches('/(أغلى|اغلى|الأفخم|الافخم)/u', $text)) {
            $filters['sort'] = 'price_desc';
        }

        // "عقار مشابه لهذا العقار" — يشير لعقار مرجعي في المحادثة (id يُلتقط لاحقًا من السياق).
        if ($this->matches('/(مشابه|مشابهة|مثل|شبيه)/u', $text)
            && preg_match('/(?:عقار|شقة|فيلا|بيت|دور)?\s*(?:رقم|#|المعرف)?\s*(\d{1,10})/u', $text, $m) === 1) {
            $filters['similar_to'] = (int) $m[1];
        }

        // كلمات مفتاحية ناعمة (قريب من الجامعة / هادئ / للعائلة) — بحث نصي + تعزيز في الترتيب.
        $keywords = [];
        foreach ([
            'قريب من الجامعة' => 'جامعة', 'قرب الجامعة' => 'جامعة', 'الجامعة' => 'جامعة',
            'المستشفى' => 'مستشفى', 'قريب من المستشفى' => 'مستشفى',
            'هادئ' => 'هادئ', 'هادئة' => 'هادئ', 'للعائلة' => 'عائلة', 'عائلي' => 'عائلة',
            'قريب من السوق' => 'سوق', 'السوق' => 'سوق', 'قريب من المدرسة' => 'مدرسة',
        ] as $pattern => $keyword) {
            if (mb_strpos($text, $this->normalize($pattern)) !== false) {
                $keywords[$keyword] = true;
            }
        }
        if ($keywords !== []) {
            $filters['keywords'] = array_keys($keywords);
        }

        // المساحة: افصلها عن السعر حتى لا تتحول «1500 متر» إلى ميزانية.
        $filters = array_merge($filters, $this->extractAreaFilters($text));

        // الحمامات: دعم الصيغ الرقمية والكلمات الشائعة في العربية.
        if ($this->matches('/(?:3|ثلاث|ثلاثة)\\s*حمام(?:ات)?/u', $text)) {
            $filters['bathrooms_min'] = 3;
        } elseif ($this->matches('/(?:2|اثنين|اثنتين|حمامين|حمامان)\\s*حمام(?:ات)?|حمامين|حمامان/u', $text)) {
            $filters['bathrooms_min'] = 2;
        } elseif ($this->matches('/(?:1|واحد|واحدة)\\s*حمام|حمام\\s*واحد/u', $text)) {
            $filters['bathrooms_min'] = 1;
        }

        // الأسعار: «أقل من 150 ألف»، «من 50 إلى 100 مليون»، «150K»، والأرقام العربية.
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

        // عند ورود نطاق سعر صريح نضبط الحدين قبل البحث؛ الوحدة على طرف واحد
        // تُورّث للطرف الآخر، وتظل حدود المساحة خارج محلل السعر.
        $priceRange = $this->extractPriceRangeFilters($text);
        if ($priceRange !== []) {
            $filters = array_merge($filters, $priceRange);
        }

        // المساحة: تمرير الحدود الصريحة إلى البحث بدل تركها في النص فقط.
        $filters = array_merge($filters, $this->extractAreaFilters($text));

        // الحمامات: الأرقام العربية/الهندية وصيغ شائعة في العربية اليمنية.
        $bathroomCount = null;
        if ($this->matches('/(حمامين|حمامان|دورتين\\s*مياه)/u', $text)) {
            $bathroomCount = 2;
        } elseif (preg_match(
            $this->normalize('/([\\d٠-٩]+|واحده?|اثنين|اثنتين|اثنان|ثلاث|ثلاثه|اربع|اربعه|خمس|خمسه)\\s*(?:حمامات|حمامين|حمام|دورات\\s*مياه)/u'),
            $text,
            $bathroomMatch,
        ) === 1) {
            $bathroomCount = $this->toCount((string) $bathroomMatch[1]);
        } elseif (preg_match(
            $this->normalize('/(?:حمام|دورة\\s*مياه)\\s*([\\d٠-٩]+|واحده?|اثنين|اثنتين|اثنان|ثلاث|ثلاثه|اربع|اربعه|خمس|خمسه)/u'),
            $text,
            $bathroomMatch,
        ) === 1) {
            $bathroomCount = $this->toCount((string) $bathroomMatch[1]);
        } elseif ($this->matches('/\\bحمام\\b/u', $text)) {
            $bathroomCount = 1;
        }

        if ($bathroomCount !== null) {
            $isMaxBathrooms = $this->matches('/(حتى|حد\\s*اقصى|اقل\\s*من|لا\\s*تزيد\\s*عن|ما\\s*تزيد\\s*عن)/u', $text);
            $isMinBathrooms = $this->matches('/(على\\s*الاقل|لا\\s*تقل\\s*عن|اكثر\\s*من|فوق)/u', $text);
            if ($isMaxBathrooms) {
                $filters['bathrooms_max'] = $this->matches('/اقل\\s*من/u', $text)
                    ? max(0, $bathroomCount - 1)
                    : $bathroomCount;
            } elseif ($isMinBathrooms) {
                $filters['bathrooms_min'] = $this->matches('/اكثر\\s*من/u', $text)
                    ? min(20, $bathroomCount + 1)
                    : $bathroomCount;
            } else {
                $filters['bathrooms_min'] = $bathroomCount;
            }
        }

        // المدن والأحياء المعروفة — مطابقة *بحدود كلمة* لا بالاحتواء النصي.
        // بدون ذلك تُطابَق «إب» داخل «أبحث» و«حدة» داخل «الوحدة»، فيظهر
        // للمستخدم بحث في مدينة لم يذكرها إطلاقًا.
        foreach ($this->cityMap() as $pattern => $city) {
            if ($this->matchesWord($pattern, $text)) {
                $filters['city'] = $city;
                break;
            }
        }

        foreach (['حدة' => 'حدة', 'السافية' => 'الصافية', 'شعوب' => 'شعوب', 'معين' => 'معين', 'آزال' => 'آزال', 'بني الحارث' => 'بني الحارث', 'الثورة' => 'الثورة', 'التحرير' => 'التحرير', 'الوحدة' => 'الوحدة', 'السفارة' => 'السفارة'] as $pattern => $district) {
            if ($this->matchesWord($pattern, $text)) {
                $filters['district'] = $district;
                break;
            }
        }

        return $filters;
    }

    /**
     * استخراج حدود المساحة من العبارات الصريحة فقط.
     *
     * @return array<string, float>
     */
    private function extractAreaFilters(string $text): array
    {
        $rangePattern = $this->normalize('/(?:مساحه\\s*)?(?:من\\s*)?([\\d٠-٩]+(?:[.,][\\d٠-٩]+)?)\\s*(?:الي|-|الى)\\s*([\\d٠-٩]+(?:[.,][\\d٠-٩]+)?)\\s*(?:متر(?:\\s*مربع)?|م2|م²)/u');
        if (preg_match($rangePattern, $text, $match) === 1) {
            $minimum = $this->toNumber((string) $match[1]);
            $maximum = $this->toNumber((string) $match[2]);
            if ($minimum !== null && $maximum !== null) {
                return [
                    'min_area' => min($minimum, $maximum),
                    'max_area' => max($minimum, $maximum),
                ];
            }
        }

        $unit = '(?:متر(?:\\s*مربع)?|م2|م²)';
        $number = '([\\d٠-٩]+(?:[.,][\\d٠-٩]+)?)';
        $patterns = [
            'min_area' => [
                '/مساحه\\s*(?:لا\\s*تقل\\s*عن|على\\s*الاقل|اكبر\\s*من|اكثر\\s*من|فوق)\\s*'.$number.'(?:\\s*'.$unit.')?/u',
                '/(?:لا\\s*تقل\\s*عن|على\\s*الاقل|اكبر\\s*من|اكثر\\s*من|فوق)\\s*'.$number.'\\s*'.$unit.'/u',
            ],
            'max_area' => [
                '/مساحه\\s*(?:اقل\\s*من|حتى|بحد\\s*اقصى|لا\\s*تتجاوز|لا\\s*يزيد\\s*عن)\\s*'.$number.'(?:\\s*'.$unit.')?/u',
                '/(?:اقل\\s*من|حتى|بحد\\s*اقصى|لا\\s*تتجاوز|لا\\s*يزيد\\s*عن)\\s*'.$number.'\\s*'.$unit.'/u',
            ],
        ];

        foreach ($patterns as $key => $alternatives) {
            foreach ($alternatives as $pattern) {
                if (preg_match($this->normalize($pattern), $text, $match) !== 1) {
                    continue;
                }
                $area = $this->toNumber((string) $match[1]);
                if ($area !== null) {
                    return [$key => $area];
                }
            }
        }

        return [];
    }

    private function toCount(string $raw): ?int
    {
        $number = $this->toNumber($raw);
        if ($number !== null) {
            return max(0, min(20, (int) $number));
        }

        return match ($this->normalize(trim($raw))) {
            'واحد', 'واحده' => 1,
            'اثنين', 'اثنتين', 'اثنان' => 2,
            'ثلاث', 'ثلاثه' => 3,
            'اربع', 'اربعه' => 4,
            'خمس', 'خمسه' => 5,
            default => null,
        };
    }

    /**
     * يلتقط نطاق السعر بوحداته حتى لا يتحول «من 2 إلى 3 مليون» إلى حد أعلى فقط.
     * يقبل الوحدة على طرف واحد ويطبّقها على الطرف الآخر، دون اعتبار نطاق المساحة سعرًا.
     *
     * @return array{min_price: float, max_price: float}|array{}
     */
    private function extractPriceRangeFilters(string $text): array
    {
        $number = '([\\d٠-٩]+(?:[.,][\\d٠-٩]+)?)';
        $unit = '(مليونين|مليون|الفين|الف|k\\b|m\\b|ك\\b)';
        $pattern = $this->normalize('/(?:من\\s*)?'.$number.'\\s*'.$unit.'?\\s*(?:إلى|الي|حتى|-)\\s*'.$number.'\\s*'.$unit.'?/u');
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        foreach ($matches as $match) {
            $minUnit = trim((string) ($match[2] ?? ''));
            $maxUnit = trim((string) ($match[4] ?? ''));
            $range = (string) ($match[0] ?? '');
            $position = mb_strpos($text, $range);
            $before = $position !== false ? mb_substr($text, max(0, $position - 24), min(24, $position)) : '';

            // لا نفسّر «غرفتين إلى ثلاث» ولا «100 إلى 150 مترًا» على أنها أسعار.
            // الرقم المجرد يُقبل فقط إذا ارتبطت عبارته القريبة بسياق سعر/ميزانية.
            if ($minUnit === '' && $maxUnit === ''
                && ! $this->matches('/(سعر|ميزانيه|قيمه|تكلفه)/u', $before)) {
                continue;
            }

            $minNumber = $this->toNumber((string) ($match[1] ?? ''));
            $maxNumber = $this->toNumber((string) ($match[3] ?? ''));
            if ($minNumber === null || $maxNumber === null) {
                continue;
            }

            $minScale = $this->priceUnitMultiplier($minUnit !== '' ? $minUnit : $maxUnit);
            $maxScale = $this->priceUnitMultiplier($maxUnit !== '' ? $maxUnit : $minUnit);
            $minimum = $minNumber * $minScale;
            $maximum = $maxNumber * $maxScale;

            return [
                'min_price' => min($minimum, $maximum),
                'max_price' => max($minimum, $maximum),
            ];
        }

        return [];
    }

    private function priceUnitMultiplier(string $unit): float
    {
        return match (true) {
            str_contains($unit, 'مليون') || strtolower($unit) === 'm' => 1_000_000,
            str_contains($unit, 'الف') || strtolower($unit) === 'k' || $unit === 'ك' => 1_000,
            default => 1,
        };
    }

    /**
     * يلتقط النطاق أولًا؛ وحدة الرقم الثاني تُورَّث للأول إذا كتبها المستخدم مرة واحدة.
     *
     * @return list<array{0: float, 1: string}>
     */
    private function extractPriceUnits(string $text): array
    {
        $number = '([\\d٠-٩]+(?:[.,٫][\\d٠-٩]+)?)';
        $unit = '(مليون(?:ين)?|الفين|الف|ك|k|m)?';
        $rangePattern = '/(?:^|\\s)(?:من|بين)\\s*'.$number.'\\s*'.$unit.'\\s*(?:إلى|الى|حتى|و)\\s*'.$number.'\\s*'.$unit.'(?=\\s|$|[،,.])/u';

        if (preg_match($rangePattern, $text, $range, PREG_OFFSET_CAPTURE) === 1) {
            $start = $range[0][1];
            $whole = $range[0][0];
            $after = substr($text, $start + strlen($whole));
            // «من 120 إلى 200 متر» نطاق مساحة وليس نطاق سعر.
            if (preg_match('/^\\s*(?:متر|مربع|م2|م²|sqm)/u', $after) !== 1) {
                $left = $this->toNumber($range[1][0]);
                $leftUnit = (string) ($range[2][0] ?? '');
                $right = $this->toNumber($range[3][0]);
                $rightUnit = (string) ($range[4][0] ?? '');

                if ($left !== null && $right !== null) {
                    $leftUnit = $leftUnit !== '' ? $leftUnit : $rightUnit;
                    $rightUnit = $rightUnit !== '' ? $rightUnit : $leftUnit;
                    $from = $this->applyPriceUnit($left, $leftUnit);
                    $to = $this->applyPriceUnit($right, $rightUnit);

                    if ($from >= 1000 && $to >= 1000 && $to >= $from) {
                        return [[$from, 'min'], [$to, 'max']];
                    }
                }
            }
        }

        $out = [];
        $pattern = '/([\\d٠-٩]+(?:[.,٫][\\d٠-٩]+)?)\\s*(مليون(?:ين)?|الفين|الف|ك\\b|k\\b|m\\b)?/u';
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $num = $this->toNumber($match[1][0]);
                if ($num === null) {
                    continue;
                }

                $unit = (string) ($match[2][0] ?? '');
                $value = $this->applyPriceUnit($num, $unit);
                if ($value < 1000) {
                    continue;
                }

                $offset = $match[0][1];
                $before = substr($text, 0, $offset);
                $after = substr($text, $offset + strlen($match[0][0]));
                // يمنع التقاط قياس المساحة على أنه سعر.
                if (preg_match('/(?:مساحه|متر|مربع|م2|م²|sqm)\\s*$/u', $before) === 1
                    || preg_match('/^\\s*(?:متر|مربع|م2|م²|sqm)/u', $after) === 1) {
                    continue;
                }

                $isMax = preg_match('/(أقل|اقل|حتى|بحدود|ميزانية|دون)/u', $before) === 1;
                $isMin = preg_match('/(فوق|أكثر|اكثر|بداية|يبدأ|يبدا|لا\\s*تقل\\s*عن)/u', $before) === 1;
                $out[] = [$value, $isMax && ! $isMin ? 'max' : ($isMin ? 'min' : 'max')];
            }
        }

        return $out;
    }

    private function applyPriceUnit(float $number, string $unit): float
    {
        return $number * match (true) {
            str_contains($unit, 'مليون') => 1_000_000,
            str_contains($unit, 'الف') || str_contains($unit, 'ألف') || $unit === 'ك' || strtolower($unit) === 'k' => 1_000,
            strtolower($unit) === 'm' => 1_000_000,
            default => 1,
        };
    }

    /**
     * @return array<string, int|float>
     */
    private function extractAreaFilters(string $text): array
    {
        $number = '([\\d٠-٩]+(?:[.,٫][\\d٠-٩]+)?)';
        $unit = '(?:متر(?:\\s*مربع)?|مربع|م2|م²|sqm)';
        $areaPhrase = 'مساحه\\s*(?:(?:من|بين)\\s*)?';

        $rangePattern = '/'.$areaPhrase.$number.'\\s*'.$unit.'?\\s*(?:إلى|الى|و)\\s*'.$number.'\\s*'.$unit.'/u';
        if (preg_match($rangePattern, $text, $range) === 1) {
            $min = $this->toNumber($range[1]);
            $max = $this->toNumber($range[2]);
            if ($min !== null && $max !== null && $min > 0 && $max >= $min) {
                return ['min_area' => $min, 'max_area' => $max];
            }
        }

        // يدعم «مساحة لا تقل عن 120 متر» و«مساحة حتى 200 متر».
        $singlePattern = '/(?:مساحه\\s*(?:(?:لا\\s*تقل\\s*عن|على\\s*الاقل|اكثر\\s*من|فوق|اقل\\s*من|حتى|لا\\s*تزيد\\s*عن|بحد\\s*اقصى)\\s*)?|)'.$number.'\\s*'.$unit.'/u';
        if (preg_match($singlePattern, $text, $single, PREG_OFFSET_CAPTURE) === 1) {
            $area = $this->toNumber($single[1][0]);
            if ($area === null || $area <= 0) {
                return [];
            }

            $prefix = substr($text, 0, $single[0][1] + strlen($single[0][0]) - strlen($single[1][0]));
            if (preg_match('/(?:اقل\\s*من|حتى|لا\\s*تزيد\\s*عن|بحد\\s*اقصى)\\s*$/u', $prefix) === 1) {
                return ['max_area' => $area];
            }

            return ['min_area' => $area];
        }

        return [];
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
            '/(شقة|شقق|شقه|دوبلكس|دوبلكس)/u' => 'apartment',
            '/(فيلا|فلل|فله|فيله|فيله)/u' => 'villa',
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

    /**
     * مطابقة نمط على نص *مُوحَّد*: يجب توحيد النمط أيضًا، وإلا فإن كل نمط
     * مكتوب بـ«ة/أ/إ/آ/ى» لا يطابق شيئًا أبدًا (مثال: «شقة» مقابل «شقه»)
     * — وهو سبب فقدان نوع العقار ومدينة «إب» من كل طلب.
     */
    private function matches(string $pattern, string $normalizedText): bool
    {
        return preg_match($this->normalize($pattern), $normalizedText) === 1;
    }

    /**
     * مطابقة اسم مكان بحدود كلمة مع السماح بأداة التعريف «ال».
     * نستخدم حدود الحروف والأرقام Unicode بدل Script=Arabic كي لا تعتبر
     * علامات الترقيم العربية مثل «،» امتدادًا للكلمة فتسقط المدينة من الطلب.
     */
    private function matchesWord(string $pattern, string $normalizedText): bool
    {
        $needle = preg_quote($this->normalize($pattern), '/');

        return preg_match('/(?<![\p{L}\p{N}])(?:ال)?'.$needle.'(?![\p{L}\p{N}])/u', $normalizedText) === 1;
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
        foreach (['bedrooms_min', 'bedrooms_max', 'bathrooms_min', 'bathrooms_max'] as $key) {
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
        if (isset($filters['similar_to'])) {
            $filters['similar_to'] = max(1, min(2147483647, (int) $filters['similar_to']));
        }
        if (isset($filters['keywords'])) {
            $filters['keywords'] = array_slice(
                array_map(fn ($k) => mb_substr(trim((string) $k), 0, 30), (array) $filters['keywords']),
                0, 4,
            );
        }

        return $filters;
    }
}
