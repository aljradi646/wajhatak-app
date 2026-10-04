<?php

namespace App\Services\AI;

/**
 * بوابة النية الحتمية قبل أي نموذج أو أداة.
 *
 * مسؤوليتها الوحيدة: تحديد نوع الدور الحالي، وتحديد ما إذا كان الطلب
 * يعتمد منطقيًا على حالة البحث السابقة. لا تنفذ أي بحث ولا تستدعي أدوات.
 */
class AiIntentRouter
{
    public function __construct(
        private readonly AiIntentService $intentService,
    ) {}

    /**
     * @return array{
     *   intent:string,
     *   filters:array<string,mixed>,
     *   use_search_context:bool,
     *   reset_search:bool,
     *   property_reference_ids:list<int>
     * }
     */
    public function route(string $message, array $history = [], array $previousFilters = [], array $previousPropertyIds = []): array
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return $this->result('ambiguous_request');
        }

        $directId = AiChatIntentDetector::detectDetailsTarget($message);
        if ($directId !== null) {
            if ($this->isAvailabilityQuestion($normalized)) {
                return $this->result('property_availability', ['property_reference_ids' => [$directId]]);
            }
            if ($this->isPriceQuestion($normalized)) {
                return $this->result('property_price', ['property_reference_ids' => [$directId]]);
            }
            if ($this->isLocationQuestion($normalized)) {
                return $this->result('property_location', ['property_reference_ids' => [$directId]]);
            }
            if ($this->isFeaturesQuestion($normalized)) {
                return $this->result('property_features', ['property_reference_ids' => [$directId]]);
            }
            if ($this->isAgentQuestion($normalized)) {
                return $this->result('property_agent/contact', ['property_reference_ids' => [$directId]]);
            }
            return $this->result('property_detail', ['property_reference_ids' => [$directId]]);
        }

        if ($this->isSearchReset($normalized)) {
            return $this->result('search_reset', ['reset_search' => true]);
        }

        if ($this->isSecuritySensitive($normalized)) {
            return $this->result('security_sensitive_request');
        }

        $smallTalk = AiChatIntentDetector::detectSmallTalk($message);
        if ($smallTalk !== null) {
            return $this->result(
                match ($smallTalk) {
                    'capabilities' => 'capability',
                    default => 'small_talk',
                }
            , ['sub_intent' => $smallTalk]);
        }

        if ($this->isIdentity($normalized)) {
            return $this->result('identity');
        }

        if ($this->isPlatformHowTo($normalized)) {
            return $this->result('platform_how_to');
        }

        if ($this->isPlatformInformation($normalized)) {
            return $this->result('platform_information');
        }

        if (AiChatIntentDetector::wantsNearby($message)) {
            return $this->result('nearest_property', [
                'use_search_context' => $this->containsExplicitPropertyCriteria($message),
            ]);
        }

        if ($this->isComparison($normalized)) {
            return $this->result('compare_properties', [
                'property_reference_ids' => $this->resolveReferences($normalized, $history, $previousPropertyIds),
            ]);
        }

        $referencedId = $this->extractSingleReference($normalized, $previousPropertyIds);
        if ($referencedId !== null && $this->isPropertyReferencePhrase($normalized)) {
            return $this->result('property_detail', ['property_reference_ids' => [$referencedId]]);
        }

        $parsed = $this->intentService->parse($message, $history, []);
        $filters = (array) ($parsed['filters'] ?? []);
        $hasExplicitProperty = $this->containsExplicitPropertyCriteria($message)
            || $filters !== [];

        if ($hasExplicitProperty) {
            $propertyId = $this->extractSingleReference($normalized, $previousPropertyIds);
            if ($this->isAvailabilityQuestion($normalized) && $propertyId !== null) {
                return $this->result('property_availability', [
                    'property_reference_ids' => [$propertyId],
                ]);
            }

            if ($this->isPriceQuestion($normalized) && $propertyId !== null) {
                return $this->result('property_price', [
                    'property_reference_ids' => [$propertyId],
                ]);
            }

            if ($this->isLocationQuestion($normalized) && $propertyId !== null) {
                return $this->result('property_location', [
                    'property_reference_ids' => [$propertyId],
                ]);
            }

            if ($this->isFeaturesQuestion($normalized) && $propertyId !== null) {
                return $this->result('property_features', [
                    'property_reference_ids' => [$propertyId],
                ]);
            }

            if ($this->isAgentQuestion($normalized) && $propertyId !== null) {
                return $this->result('property_agent/contact', [
                    'property_reference_ids' => [$propertyId],
                ]);
            }

            $useContext = $this->shouldReuseSearchContext($normalized, $filters, $previousFilters);

            return $this->result(
                $useContext ? 'search_refinement' : 'property_search',
                [
                    'filters' => $useContext
                        ? $this->mergeFilters($previousFilters, $filters)
                        : $filters,
                    'use_search_context' => $useContext,
                ]
            );
        }

        if ($this->isAccountHelp($normalized)) {
            return $this->result('account_help');
        }

        if ($this->isTechnicalHelp($normalized)) {
            return $this->result('technical_help');
        }

        if ($this->isOutOfScope($normalized)) {
            return $this->result('out_of_scope');
        }

        return $this->result('ambiguous_request');
    }

    /** @return array<string,mixed> */
    private function result(string $intent, array $overrides = []): array
    {
        return array_merge([
            'intent' => $intent,
            'filters' => [],
            'use_search_context' => false,
            'reset_search' => false,
            'property_reference_ids' => [],
        ], $overrides);
    }

    private function shouldReuseSearchContext(string $text, array $filters, array $previous): bool
    {
        if ($previous === []) {
            return false;
        }

        if ($filters !== [] && $this->isFreshSearchPhrase($text)) {
            return false;
        }

        return $this->isContextRefinement($text)
            || $this->isCorrectionPhrase($text)
            || ($filters !== [] && $this->isShortPropertyCorrection($text));
    }

    private function isFreshSearchPhrase(string $text): bool
    {
        return preg_match('/^(ابحث|دور|ابغى|ابي|أريد|اريد|بغيت|ابحث لي|هات لي)\\b/u', $text) === 1
            && preg_match('/(شقه|فيلا|فله|فيله|بيت|منزل|ارض|محل|مكتب|عماره|عقار)/u', $text) === 1;
    }

    private function isContextRefinement(string $text): bool
    {
        return preg_match('/(ارخص|الأرخص|اغلى|الأغلى|اقرب|الأقرب|اكبر|اصغر|غرف اكثر|غرف اقل|اقل من|اكثر من|حتى|بدل|خلها|خليها|غيرها|غيره|غير|لا،|لا )/u', $text) === 1;
    }

    private function isCorrectionPhrase(string $text): bool
    {
        return preg_match('/^(لا|بدل|غير|خل|خلي|غيرها|غيره)\\b/u', $text) === 1
            || preg_match('/\\bبدل\\b.*(شقه|فيلا|فله|فيله|بيت|منزل|ارض|محل)/u', $text) === 1;
    }

    private function isShortPropertyCorrection(string $text): bool
    {
        return mb_strlen($text) <= 30
            && preg_match('/(شقه|فيلا|فله|فيله|بيت|منزل|ارض|محل|مكتب|عماره)/u', $text) === 1;
    }

    private function isPropertyReferencePhrase(string $text): bool
    {
        return preg_match('/^(الاول|الأول|الثاني|الثانيه|الثانية|هذا|هذه|نفسه|نفسها|اللي قبل|العقار السابق|الشقه السابقه|الفيلا السابقه)$/u', $text) === 1;
    }

    private function containsExplicitPropertyCriteria(string $message): bool
    {
        $text = $this->normalize($message);

        return preg_match('/(عقار|عقارات|شقه|شقق|فيلا|فلل|فله|فيله|بيت|بيوت|منزل|منازل|دور|ارض|اراضي|مزرعه|محل|محلات|مكتب|عماره|تاون|شراء|للبيع|ايجار|للإيجار|استئجار|غرفه|غرف|مفروش|ميزانيه|سعر|ريال)/u', $text) === 1;
    }

    private function isPlatformInformation(string $text): bool
    {
        return preg_match('/(ما هي وجهتك|وش هي وجهتك|ايش هي وجهتك|ما هي المنصه|وش هي المنصه|ايش هي المنصه|معلومات عن وجهتك|معلومات عن المنصه|خدمات وجهتك|خدماتكم|عن التطبيق|عن المنصه)/u', $text) === 1;
    }

    private function isPlatformHowTo(string $text): bool
    {
        return preg_match('/(كيف استخدم|كيف ا?(?:بحث|ابحث)|كيف.*الفلاتر|كيف.*المفضله|كيف.*احفظ|كيف.*اضيف عقار|كيف.*تسجيل الدخول|كيف.*انشئ حساب|كيف.*اتواصل|طريقة استخدام|طريقه استخدام)/u', $text) === 1
            || preg_match('/(الفلاتر|المفضله|تسجيل الدخول|انشاء الحساب|طلب معاينه)/u', $text) === 1 && str_contains($text, 'كيف');
    }

    private function isIdentity(string $text): bool
    {
        return preg_match('/^(من انت|مين انت|من هو المساعد|وش انت|ايش انت|ما وظيفتك|وش وظيفتك|ما عملك|وش عملك)\\b/u', $text) === 1;
    }

    private function isComparison(string $text): bool
    {
        return preg_match('/(قارن|مقارن|بين الاول والثاني|بين اول وثاني|قارنهم|قارنهما|بينهم)/u', $text) === 1;
    }

    private function isAvailabilityQuestion(string $text): bool
    {
        return preg_match('/(متاح|متوفر|انبيع|انباع|انباع|انأجر|تأجر|تأجر|باعوه|اتأجر)/u', $text) === 1;
    }

    private function isPriceQuestion(string $text): bool
    {
        return preg_match('/(كم سعر|السعر كم|بكم|سعره|سعر هذا|تكلفته)/u', $text) === 1;
    }

    private function isLocationQuestion(string $text): bool
    {
        return preg_match('/(وين موقع|اين موقع|موقعه|مكانه|وينه)/u', $text) === 1;
    }

    private function isFeaturesQuestion(string $text): bool
    {
        return preg_match('/(تفاصيل|مواصفات|غرفه|حمام|مساحه|مفروش|موقف|مصعد|حديقه)/u', $text) === 1;
    }

    private function isAgentQuestion(string $text): bool
    {
        return preg_match('/(الوكيل|المعلن|صاحب العقار|رقم الوكيل|تواصل مع الوكيل|هاتف الوكيل)/u', $text) === 1;
    }

    private function isAccountHelp(string $text): bool
    {
        return preg_match('/(الحساب|حسابي|الملف الشخصي|نسيت كلمة المرور|استعاده كلمة المرور|اعادة كلمة المرور)/u', $text) === 1;
    }

    private function isTechnicalHelp(string $text): bool
    {
        return preg_match('/(خطا|خطأ|ما يفتح|لا يعمل|مشكله|مشكلة|تعذر|عطل)/u', $text) === 1;
    }

    private function isOutOfScope(string $text): bool
    {
        return preg_match('/(برمجه|برمجة|flutter|python|php|javascript|java|لارافيل|قصيده|شعر|طبخ|وصفه|رياضه|سياسه|اخبار|فيزياء|كيمياء|رياضيات)/u', $text) === 1;
    }

    private function isSecuritySensitive(string $text): bool
    {
        return preg_match('/(كلمه? المرور|كلمات السر|كلمة السر|password|token|api key|مفتاح سري|بيانات المستخدمين|قاعدة البيانات كاملة|system prompt|تعليماتك النظاميه|تعليماتك النظامية)/u', $text) === 1;
    }

    private function isSearchReset(string $text): bool
    {
        return preg_match('/(ابدأ من جديد|ابدا من جديد|انس الطلب السابق|انسى الطلب السابق|بحث جديد|بحث ثاني|خلنا نبحث عن شيء ثاني|من البداية|من البدايه)/u', $text) === 1;
    }

    /** @return list<int> */
    private function resolveReferences(string $text, array $history, array $previousPropertyIds): array
    {
        if (preg_match_all('/(?:العقار|شقه|فيلا|فله|رقم|#)\\s*(?:رقم|#)?\\s*(\\d{1,10})/u', $text, $m) > 0) {
            return array_values(array_unique(array_map('intval', $m[1])));
        }

        if (preg_match('/(الاول|الأول)/u', $text) === 1 && isset($previousPropertyIds[0])) {
            return [(int) $previousPropertyIds[0]];
        }

        if (preg_match('/(الثاني|ثاني|2)/u', $text) === 1 && isset($previousPropertyIds[1])) {
            return [(int) $previousPropertyIds[1]];
        }

        return [];
    }

    private function extractSingleReference(string $text, array $previousPropertyIds): ?int
    {
        $resolved = $this->resolveReferences($text, [], $previousPropertyIds);

        return count($resolved) === 1 ? $resolved[0] : null;
    }

    /** @return array<string,mixed> */
    private function mergeFilters(array $previous, array $current): array
    {
        $merged = array_merge($previous, $current);

        if (isset($current['property_type'])) {
            $merged['property_type'] = $current['property_type'];
        }
        if (isset($current['transaction_type'])) {
            $merged['transaction_type'] = $current['transaction_type'];
        }

        return $merged;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\\x{064B}-\\x{0652}\\x{0670}]/u', '', $text) ?? $text;
        return str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $text);
    }
}
