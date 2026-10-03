<?php

namespace App\Services\AI;

/**
 * كاشف نية الحوار اليومي — حتمي بالكامل.
 *
 * يعمل قبل محلل البحث حتى لا تتحول الرسائل المحادثية إلى استعلامات عقارية،
 * خصوصًا داخل محادثة لديها معايير بحث محفوظة من رسالة سابقة.
 */
class AiChatIntentDetector
{
    /** نية «معلومات عن العقار N» — يعيد المعرف إن وُجد. */
    public static function detectDetailsTarget(string $text): ?int
    {
        $normalized = self::normalize($text);

        if (preg_match('/(معلومات|تفاصيل|وصف|اعرض لي|هات|اخبرني عن)/u', $normalized) === 1
            && preg_match('/(?:عقار|شقه|فيلا|بيت|ارض|محل|دور|المعرف|رقم)\s*(?:رقم|#)?\s*(\d{1,10})/u', $normalized, $m) === 1) {
            return (int) $m[1];
        }

        if (mb_strlen($normalized) <= 25
            && preg_match('/^(?:عقار|شقه|فيلا|بيت|ارض|محل|دور)\s*(?:رقم|#)?\s*(\d{1,10})$/u', $normalized, $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }

    /** تصنيف الكلام اليومي: greeting/thanks/capabilities/stats/farewell أو null. */
    public static function detectSmallTalk(string $text): ?string
    {
        $normalized = self::normalize($text);

        if ($normalized === '') {
            return null;
        }

        // عالج إطالة الحروف الشائعة في الدردشة مثل: «الووو»، «هلااا»، «كيفكك».
        $chatText = self::collapseRepeatedCharacters($normalized);

        if (mb_strlen($chatText) <= 80 && self::isGreeting($chatText)) {
            return 'greeting';
        }

        if (mb_strlen($chatText) <= 100
            && preg_match('/(شكرا|ممتن|يعطيك العافيه|تسلم|ربي يحفظك|جزاك الله|thank|thx)/u', $chatText) === 1) {
            return 'thanks';
        }

        if (mb_strlen($chatText) <= 100
            && preg_match('/(مين انت|من انت|وش تقدر|ايش تقدر|شن تقدر|كيف تساعد|ساعدني|ممكن تساعدني|احتاج مساعده|أحتاج مساعدة|وش تسوي|ايش تسوي|قدراتك|مميزاتك|من انت بالضبط|who are you|what can you|كيف استخدم|كيف ابدأ|كيف ابداء|ماذا تستطيع)/u', $chatText) === 1
            && ! self::looksLikePropertyRequest($chatText)) {
            return 'capabilities';
        }

        if (mb_strlen($chatText) <= 70
            && preg_match('/(كم عقار|كم شقه|ما المتوفر|وش عندكم|ايش عندكم|شن عندكم|كم العدد|احصائيات|عدد العقارات|عدد العقارات المتوفره)/u', $chatText) === 1) {
            return 'stats';
        }

        if (mb_strlen($chatText) <= 50
            && preg_match('/(وداعا|الى اللقاء|مع السلامه|تصبح على خير|تصبحي على خير|باي|باي باي|bye|goodbye)/u', $chatText) === 1) {
            return 'farewell';
        }

        if (mb_strlen($chatText) <= 90
            && preg_match('/(كيفك|كيف حالك|كيف امورك|كيف الامور|كيف احوالك|كيف الدنيا|طمني عليك|طمنيني عليك|اخبارك|شخبارك|وش اخبارك|ايش اخبارك|شن اخبارك|ايش الاخبار|وش الاخبار|ما الاخبار|ما الاخبار اليوم|what(?:up| is up)|how are you)/u', $chatText) === 1
            && ! self::looksLikePropertyRequest($chatText)) {
            return 'wellbeing';
        }

        if (mb_strlen($chatText) <= 60
            && preg_match('/^(تمام|تماما|طيب|كويس|ممتاز|حلو|جميل|رائع|اوكي|اوك|يس|yes|ok|okay|thanks|تمام شكرا)$/u', $chatText) === 1
            && ! self::looksLikePropertyRequest($chatText)) {
            return 'acknowledgement';
        }

        return null;
    }

    /**
     * هل الرسالة تحتوي مؤشرات فعلية على طلب عقاري أو متابعة مباشرة للبحث؟
     *
     * هذه الدالة تُستخدم لمنع إعادة تطبيق فلاتر محادثة قديمة على رسالة
     * اجتماعية/عامة لا علاقة لها بالبحث.
     */
    public static function looksLikePropertyRequest(string $text): bool
    {
        $normalized = self::collapseRepeatedCharacters(self::normalize($text));

        return preg_match(
            '/(عقار|عقارات|شقه|شقق|فيلا|فلل|بيت|بيوت|منزل|منازل|ارض|اراضي|محل|محلات|مكتب|مكاتب|عماره|عمارات|برج|دور|ادوار|تاون\s*هاوس|للبيع|بيع|ايجار|للايجار|شراء|اشتري|تمليك|استئجار|ابحث|بحث|دور لي|اعرض|وريني|اريد|ابغى|ابغا|احتاج|ميزانيه|غرف|حمام|متر|مساحه|سعر|اسعار|ريال|مليون|الف|ك\b|قريب مني|قريبه مني|بالقرب مني|مشابه|شبيه|المفضله|مفضلتي|معاينه|معاينة|حجز|زيارة|عقار رقم|في صنعاء|في عدن|في تعز|في الحديدة|في المكلا|في إب|في مارب|في سيئون|في ذمار|في حجة)/u',
            $normalized
        ) === 1;
    }

    /**
     * كشف التحية/السلام — يقبل صيغ الدردشة الشائعة وإطالة الحروف.
     */
    private static function isGreeting(string $normalized): bool
    {
        $pattern = '^(?:'
            .'السلام(?:\s+عليكم(?:\s+ورحمه\s*الله)?)?'
            .'|سلام(?:\s+عليكم)?'
            .'|الو'
            .'|مرحبا|مرحبتين|هلا(?:\s+والله)?|يا\s+هلا|هاي|hello|hi|hey'
            .'|اهلا(?:\s+وسهلا)?|اهلين|حياك(?:م)?(?:\s+الله)?'
            .'|صباح\s*(?:الخير|النور)|مساء\s*(?:الخير|النور)'
            .'|كيفك+|كيف(?:\s+حالك|\s+الحال|\s+امورك)|شلونك|شخبارك|اخبارك|عساك\s*بخير|ازيك'
            .'|منور(?:ه)?|نورت'
            .')(?:[\s،,.!؟?~]|$)';

        return preg_match('/'.$pattern.'/u', $normalized) === 1;
    }

    /** «وين موقعي» / «قريب مني» — يحتاج إحداثيات العميل الحقيقية. */
    public static function wantsNearby(string $text): bool
    {
        $normalized = self::normalize($text);

        return preg_match('/(قريب مني|قريبه مني|بالقرب مني|حولي|على بعد|من موقعي|موقعي الحالي|وين انا|وين موقعي|nearby|near me|بعيد كم|المسافه مني)/u', $normalized) === 1;
    }

    /** توحيد النص العربي وإزالة التشكيل والتكرار الشكلي في الدردشة. */
    private static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text) ?? $text;
        $text = str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /** ضغط إطالة الحروف المتكررة المستخدمة في الدردشة غير الرسمية. */
    private static function collapseRepeatedCharacters(string $text): string
    {
        return preg_replace('/(.)\1+/us', '$1', $text) ?? $text;
    }
}
