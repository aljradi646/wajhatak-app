<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

/**
 * حاجز الحماية المسبق — يعمل على مستوى الكود قبل الوصول للنموذج وبعده:
 * 1. قصر النطاق على العقارات ومنصة وجهتك (request classifier).
 * 2. كشف محاولات حقن التعليمات (Prompt Injection).
 * 3. منع طلب البيانات الحساسة/الخاصة.
 * لا يعتمد على الـ prompt فقط؛ تُطبق قرارات الرفض هنا مباشرة.
 */
class AiGuardrailService
{
    public function __construct(private readonly AiSettingsService $settings) {}

    /** @return array{blocked: bool, reason: ?string} */
    public function inspect(string $text): array
    {
        $normalized = $this->normalize($text);

        if ($normalized === '') {
            return ['blocked' => false, 'reason' => null];
        }

        if ($this->settings->guardActive('prompt_injection') && $this->isPromptInjection($normalized)) {
            return ['blocked' => true, 'reason' => 'prompt_injection'];
        }

        if ($this->settings->guardActive('sensitive_data') && $this->requestsSensitiveData($normalized)) {
            return ['blocked' => true, 'reason' => 'sensitive_data'];
        }

        if ($this->settings->guardActive('domain_restriction') && $this->isOutOfDomain($normalized)) {
            return ['blocked' => true, 'reason' => 'out_of_domain'];
        }

        return ['blocked' => false, 'reason' => null];
    }

    /** هل الرسالة حث صريح على تجاوز التعليمات/استخراج النظام؟ */
    private function isPromptInjection(string $text): bool
    {
        $patterns = [
            'تجاهل\s+(كل\s+)?(تعليمات|التعليمات|الأوامر|قواعد)',
            'ignore\s+(all\s+)?(previous|prior|above|your)\s+(instructions|rules|prompts?)',
            'disregard\s+(all\s+)?(previous|your|the)\s+instructions',
            '(اكشف|أظهر|اعرض|هات|أرسل|عطني|show|reveal|print|output)\s+(لي\s+)?(لي\s*)?(system\s*prompt|برومبت|البرومبت|التعليمات\s*النظامية|تعليماتك|النظام\s*برومبت|prompt)',
            'أنت\s+(الآن|من\s*الآن)\s+(مطور|مبرمج|مسؤول|admin|developer)',
            'act\s+as\s+(a\s+)?(developer|admin|root|system)',
            '(أنت|you\s+are)\s+(خالٍ|خالي|free)\s+(من\s+)?(القيود|قيود)',
            'الوضع\s+(الحر|الملكي|developer\s+mode|DAN)',
            'print\s+your\s+(instructions|prompt|rules)',
            'ما\s+هي\s+تعليماتك\s*(النظامية|السريع)?',
            'اتصل\s+بقاعدة\s+البيانات|اكتب\s+استعلام\s+SQL|SELECT\s+\*\s+FROM',
            'DROP\s+TABLE|UNION\s+SELECT|OR\s+1=1',
        ];

        foreach ($patterns as $pattern) {
            if ($this->matches($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /** طلب كلمات مرور/توكنات/بيانات مستخدمين آخرين/إعدادات داخلية. */
    private function requestsSensitiveData(string $text): bool
    {
        $patterns = [
            // كلمة المرور بأشكالها الشائع (كلمة مرور / كلمه المرور / كلمة السر) وبالإنجليزية.
            '((?:كلمة|كلمه|كلمات)\s*(?:ال)?(?:مرور|سر)|pass(?:word|wd|code))',
            '(التوكن|توكن|token|api[_\s-]?key|مفتاح\s*(الAPI|api)?\s*(السري)?)',
            '(بيانات\s+)?(المستخدمين|مستخدم\s*آخر|مستخدمين\s*آخرين|other\s+users?)',
            '(محادثات\s+)?(الآخرين|مستخدم\s+آخر|other\s+conversations?)',
            '(معلومات\s+)?(الإدارة|المشرف|admin\s+data|الإعدادات\s*الداخلية|الاعدادات\s*الداخليه)',
            '(ا?يميل|بريد|هاتف|جوال|رقم)\s+(الوكيل|المالك|المستخدم|صاحب)\s*(الآخر|الآخرين)?\s*(الخاص)?',
            '(بيانات|معلومات)\s+(مستخدم|عميل|وكيل)\s+(آخر|اخر|الآخرين)',
        ];

        foreach ($patterns as $pattern) {
            if ($this->matches($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /** خارج النطاق العقاري ومنصة وجهتك؟ (بعد استبعاد الكلمات المفتاحية للمجال) */
    private function isOutOfDomain(string $text): bool
    {
        $domainKeywords = [
            'عقار', 'عقارات', 'شقة', 'شقق', 'فيلا', 'فلل', 'بيت', 'بيوت', 'منزل', 'دور', 'أدوار',
            'تاون', 'أرض', 'ارض', 'أراضي', 'مزرعة', 'محل', 'محلات', 'مكتب', 'عمارة', 'غرفة', 'غرف',
            'إيجار', 'ايجار', 'تمليك', 'بيع', 'شراء', 'استئجار', 'سعر', 'أسعار', 'اسعار', 'ريال',
            'متر', 'مساحة', 'مفروش', 'مؤثث', 'حي', 'حارة', 'منطقة', 'مناطق', 'مدينة', 'مدن', 'صنعاء',
            'عدن', 'تعز', 'الحديدة', 'المكلا', 'إب', 'اب', 'مارب', 'سيئون', 'رياض', 'جدة', 'الدمام',
            'جار', 'جيران', 'موقف', 'كراج', 'مصعد', 'حديقة', 'سطح', 'خزان', 'أمن', 'تأمين',
            'وكيل', 'وكلاء', 'معاينة', 'زيارة', 'معرض', 'عرض', 'مفضلة', 'حفظ', 'مشاركة', 'خريطة',
            'وجهتك', 'واجهتك', 'wajhatak', 'المنصة', 'التطبيق', 'المساعد', 'تطبيقكم', 'منصتكم',
            'كيف\s+أ?', 'هل\s+يمكن', 'أريد', 'ابحث', 'بحث', 'أخبرني', 'رشح', 'اقترح', 'قارن', 'مقارنة',
        ];

        foreach ($domainKeywords as $keyword) {
            if (str_contains($keyword, '\\')
                ? $this->matches($keyword, $text)
                : Str::contains($text, $this->normalize($keyword), ignoreCase: true)) {
                // نطاق مقبول (كلمة عقارية أو عبارة استخدام منصة).
                return false;
            }
        }

        // أفعال/موضوعات واضحة خارج النطاق.
        $outOfDomain = [
            'برنامج', 'كود', 'برمجة', 'php', 'flutter', 'python', 'javascript', 'java\b', 'لارافيل', 'laravel',
            'مقالة', 'مقال', 'قصيدة', 'شعر', 'رواية', 'قصة', 'ترجمة', 'تلخيص',
            'طبخ', 'وصفة', 'رياضة', 'كرة', 'أغنية', 'ألعاب', 'فيزياء', 'كيمياء', 'رياضيات',
            'طقس\s*اليوم', 'أخبار', 'سياسة', 'تاريخ\s+التحويل', 'دكتور\s+فصل', 'واجب\s+الجامعة',
        ];

        foreach ($outOfDomain as $pattern) {
            if ($this->matches($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * مطابقة نمط على نص *مُوحَّد*: لا بد من توحيد النمط نفسه أيضًا، وإلا فإن
     * كل نمط مكتوب بـ«ة/أ/إ/آ/ى» (قصيدة، مقالة، أظهر، أنت...) لا يطابق شيئًا
     * أبدًا، فيمرّ الطلب خارج النطاق بدل حجبه.
     */
    private function matches(string $pattern, string $normalizedText): bool
    {
        return preg_match('/'.$this->normalize($pattern).'/iu', $normalizedText) === 1;
    }

    /** توحيد النص: إزالة التشكيل وتوحيد الألف والتاء المربوطة والهمزات. */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text) ?? $text; // تشكيل
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $text = str_replace(['ة'], 'ه', $text);
        $text = str_replace(['ى'], 'ي', $text);

        return $text;
    }
}
