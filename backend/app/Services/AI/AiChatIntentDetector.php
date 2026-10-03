<?php

namespace App\Services\AI;

/**
 * كاشف نية الحوار اليومي — حتمي بالكامل (قوائم كلمات + أنماط).
 * يعمل قبل محلل البحث: يحوّل "أي كلام عادي" إلى نية واضحة بلا أي نموذج.
 */
class AiChatIntentDetector
{
    /** نية «معلومات عن العقار N» — يعيد المعرف إن وُجد. */
    public static function detectDetailsTarget(string $text): ?int
    {
        $normalized = self::normalize($text);

        // التفاصيل تتطلب سياق عقار صريح: كلمة عقارية + رقم.
        if (preg_match('/(معلومات|تفاصيل|وصف|اعرض لي|هات|أخبرني عن)/u', $normalized) === 1
            && preg_match('/(?:عقار|شقه|فيلا|بيت|ارض|محل|دور|المعرف|رقم)\s*(?:رقم|#)?\s*(\d{1,10})/u', $normalized, $m) === 1) {
            return (int) $m[1];
        }

        // «العقار 5» / «شقة 12» كطلب تفاصيل مباشر قصير.
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

        // تحية في بداية الرسالة القصيرة (بحدود كلمة دقيقة كي لا تلتقط كلمات
        // مثل «الوحدة» أو «مرحبا بكم في موقعنا العقاري» كتحية فقط).
        if (mb_strlen($normalized) <= 40 && self::isGreeting($normalized)) {
            return 'greeting';
        }

        // شكر في رسالة قصيرة.
        if (mb_strlen($normalized) <= 80
            && preg_match('/(شكرا|ممتن|يعطيك العافيه|تسلم|ربي يحفظك|جزاك الله|thank)/u', $normalized) === 1) {
            return 'thanks';
        }

        // «وش تقدر تسوي» / «مين انت» / «كيف تساعدني».
        if (mb_strlen($normalized) <= 60
            && preg_match('/(مين انت|من انت|وش تقدر|ايش تقدر|شن تقدر|كيف تساعد|وش تسوي|ايش تسوي|قدراتك|مميزاتك|من انت بالضبط|who are you|what can you)/u', $normalized) === 1) {
            return 'capabilities';
        }

        // «كم عقار عندكم» / «ما المتوفر».
        if (mb_strlen($normalized) <= 60
            && preg_match('/(كم عقار|كم شقه|ما المتوفر|وش عندكم|ايش عندكم|شن عندكم|كم العدد|احصائيات|عدد العقارات|عدد العقارات المتوفره)/u', $normalized) === 1) {
            return 'stats';
        }

        // وداع.
        if (mb_strlen($normalized) <= 30
            && preg_match('/^(باي|مع السلامه|الى اللقاء|تصبح على خير|وداعا|bye)/u', $normalized) === 1) {
            return 'farewell';
        }

        return null;
    }

    /**
     * كشف التحية/السلام — نمط حتمي بحدود كلمة، يقبل الأشكال الشائعة:
     * ألو/الو، هلا/يا هلا/هلا والله، السلام عليكم، مرحبا، اهلين، صباح/مساء الخير،
     * كيفك/كيف الحال/شلونك/شخبارك…
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
            .'|كيف(?:ك|ك\s*الحال|\s*حالك|\s*الحال)|شلونك|شخبارك|اخبارك|عساك\s*بخير|ازيك'
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

    /** توحيد النص العربي (تشكيل + همزات + تاء مربوطة) للمطابقة. */
    private static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text) ?? $text;

        return str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $text);
    }
}
