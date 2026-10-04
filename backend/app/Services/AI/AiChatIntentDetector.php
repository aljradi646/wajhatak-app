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

    /** تصنيف الكلام اليومي: تحية/شكر/قدرات/إحصاءات/وداع/حالة/موافقة أو null. */
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
            && preg_match('/(شكرا|ممتن|يعطيك العافيه|تسلم|ربي يحفظك|جزاك الله|thank|thx)/u', $chatText) === 1
            && ! self::looksLikePropertyRequest($chatText)) {
            return 'thanks';
        }

        if (mb_strlen($chatText) <= 120 && self::isPlatformSupport($chatText)) {
            return 'platform_support';
        }

        if (mb_strlen($chatText) <= 100
            && preg_match('/(مين انت|من انت|وش تقدر|ايش تقدر|شن تقدر|كيف تساعد|ساعدني|ممكن تساعدني|احتاج مساعده|وش تسوي|ايش تسوي|قدراتك|مميزاتك|من انت بالضبط|who are you|what can you|كيف استخدم|كيف ابدأ|كيف ابداء|ماذا تستطيع)/u', $chatText) === 1
            && ! self::looksLikeSearchRequest($chatText)) {
            return 'capabilities';
        }

        if (mb_strlen($chatText) <= 70
            && preg_match('/(كم عقار|كم شقه|ما المتوفر|وش عندكم|ايش عندكم|شن عندكم|كم العدد|احصائيات|عدد العقارات|عدد العقارات المتوفره)/u', $chatText) === 1) {
            return 'stats';
        }

        if (mb_strlen($chatText) <= 120
            && preg_match('/(الجو|حاله الطقس|حالة الطقس|الطقس|طقس|درجة الحراره|درجه الحراره|حر اليوم|برد اليوم|مطر اليوم|weather)/u', $chatText) === 1
            && ! self::hasStrongPropertySignal($chatText)) {
            return 'weather';
        }

        if (mb_strlen($chatText) <= 100
            && preg_match('/(نكتة|نكته|نكتني|مزحه|مزحة|ضحكني|قول لي نكته|قول لي نكتة|joke)/u', $chatText) === 1
            && ! self::hasStrongPropertySignal($chatText)) {
            return 'joke';
        }

        if (mb_strlen($chatText) <= 100
            && preg_match('/(نتكلم|نتكلم شوي|نسولف|سوالف|دردشه|دردشة|خلنا نتكلم|كيف كان يومك|وش تسوي|ايش تسوي|كيف الحياة|كيف الحياه)/u', $chatText) === 1
            && ! self::hasStrongPropertySignal($chatText)) {
            return 'casual';
        }

        if (mb_strlen($chatText) <= 50
            && preg_match('/(وداعا|الى اللقاء|مع السلامه|تصبح على خير|تصبحي على خير|باي|باي باي|bye|goodbye)/u', $chatText) === 1) {
            return 'farewell';
        }

        if (mb_strlen($chatText) <= 90
            && preg_match('/(كيفك+|كيف حالك|كيف امورك|كيف الامور|كيف احوالك|كيف الدنيا|كيف يومك|طمني عليك|طمنيني عليك|اخبارك|شخبارك|وش اخبارك|ايش اخبارك|شن اخبارك|ايش الاخبار|وش الاخبار|ما الاخبار|ما الاخبار اليوم|what(?:up| is up)|how are you)/u', $chatText) === 1
            && ! self::looksLikeSearchRequest($chatText)) {
            return 'wellbeing';
        }

        if (mb_strlen($chatText) <= 100
            && preg_match('/(تعبان|تعبانه|متضايق|متضايقه|مضايق|مضايقه|زعلان|زعلانه|حزين|حزينه|مبسوط|مبسوطه|فرحان|فرحانه|متوتر|متوتره|قلقان|قلقانه)/u', $chatText) === 1
            && ! self::looksLikeSearchRequest($chatText)) {
            return 'emotion';
        }


        if (mb_strlen($chatText) <= 35
            && preg_match('/^(تمام|تماما|طيب|كويس|ممتاز|حلو|جميل|رائع|اوكي|اوك|يس|yes|ok|okay|thanks)$/u', $chatText) === 1
            && ! self::looksLikeSearchRequest($chatText)) {
            return 'acknowledgement';
        }

        return null;
    }

    /**
     * كشف التحية/السلام — يقبل صيغ الدردشة الشائعة وإطالة الحروف.
     */
    /** أسئلة دعم استخدام التطبيق: التنقل، الإضافة، المفضلة، البحث والتواصل. */
    private static function isPlatformSupport(string $text): bool
    {
        return preg_match('/(كيف (استخدم|اخل|اعمل|اسوي)|وين (الاق|اجد|الاقي)|اين (اجد|الاقي)|طريقة|طريقه|اضيف عقار|اضافة عقار|اضافه عقار|انشئ عقار|انشاء عقار|عقاراتي|المفضله|مفضلتي|احفظ عقار|حفظ عقار|اتواصل مع الوكيل|التواصل مع الوكيل|الفلاتر|فلتر|البحث المتقدم|استكشاف|الحساب|الملف الشخصي|الطلبات|طلب معاينه|الاشعارات|الرسائل|تسجيل الدخول|تسجيل حساب)/u', $text) === 1;
    }

    private static function isGreeting(string $normalized): bool
    {
        $pattern = '^(?:'
            .'السلام(?:\s+عليكم(?:\s+ورحمه\s*الله)?)?'
            .'|سلام(?:\s+عليكم)?'
            .'|الو+|هلا+'
            .'|مرحبا|مرحبتين|هلا(?:\s+والله)?|يا\s+هلا|هاي|hello|hi|hey'
            .'|اهلا(?:\s+وسهلا)?|اهلين|حياك(?:م)?(?:\s+الله)?'
            .'|صباح\s*(?:الخير|النور)|مساء\s*(?:الخير|النور)'
            .'|كيف(?:ك|\s+حالك|\s+الحال|\s+امورك)|شلونك|شخبارك|اخبارك|عساك\s*بخير|ازيك'
            .'|منور(?:ه)?|نورت'
            .')(?:[\s،,.!؟?~]|$)';

        return preg_match('/'.$pattern.'/u', $normalized) === 1;
    }

    /** منع «تمام شقة...» ونحوها من أن تتحول إلى حوار عام. */
    public static function looksLikePropertyRequest(string $text): bool
    {
        $normalized = self::collapseRepeatedCharacters(self::normalize($text));

        return preg_match(
            '/(عقار|عقارات|شقه|شقق|فيلا|فلل|بيت|بيوت|منزل|منازل|ارض|اراضي|محل|محلات|مكتب|مكاتب|عماره|عمارات|برج|دور|ادوار|تاون\s*هاوس|للبيع|بيع|ايجار|للايجار|شراء|اشتري|تمليك|استئجار|استثمار|استثماري|دخل استثماري|عائد|roi|ابحث|بحث|دور لي|اعرض|وريني|ميزانيه|غرف|حمام|متر|مساحه|سعر|اسعار|ريال|مليون|الف|ك\b|قريب مني|قريبه مني|بالقرب مني|مشابه|شبيه|المفضله|مفضلتي|معاينه|حجز|زيارة|عقار رقم|صنعاء|عدن|تعز|الحديدة|المكلا|إب|اب|مارب|سيئون|ذمار|حجة|المهرة)/u',
            $normalized
        ) === 1;
    }

    /** تقدير بسيط لأسلوب المستخدم لتكييف نبرة الرد دون الاعتماد على نموذج خارجي. */
    public static function isFormal(string $text): bool
    {
        $normalized = self::normalize($text);

        return preg_match('/(كيف حالك|هل يمكنك|من فضلك|لو سمحت|اود|ارغب|ارجو|حضرتك|هل بالامكان|اسال حضرتك)/u', $normalized) === 1;
    }

    /** هل الرسالة تحتوي إشارة عقارية صريحة وليست مجرد اسم مدينة/سياق؟ */
    private static function hasStrongPropertySignal(string $text): bool
    {
        $normalized = self::collapseRepeatedCharacters(self::normalize($text));

        return preg_match(
            '/(عقار|عقارات|شقه|شقق|فيلا|فلل|بيت|بيوت|منزل|منازل|ارض|اراضي|محل|محلات|مكتب|مكاتب|عماره|عمارات|برج|دور|ادوار|تاون\s*هاوس|للبيع|بيع|ايجار|للايجار|شراء|اشتري|تمليك|استئجار|ابحث|بحث|دور لي|اعرض|وريني|ميزانيه|غرف|حمام|متر|مساحه|سعر|اسعار|ريال|مليون|الف|استثمار|استثماري|عائد|roi|قريب مني|قريبه مني|بالقرب مني|مشابه|شبيه|المفضله|مفضلتي|معاينه|حجز|زيارة)/u',
            $normalized
        ) === 1;
    }

    /** إبقاء الدالة الداخلية للتصنيف مع نفس العقد السابق. */
    private static function looksLikeSearchRequest(string $normalized): bool
    {
        return self::looksLikePropertyRequest($normalized);
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
        // نعالج الإطالة الواضحة فقط، حتى لا نفسد كلمات عربية تحتوي حرفين متتاليين مثل «الله».
        return preg_replace('/(.)\1{2,}/us', '$1', $text) ?? $text;
    }
}
