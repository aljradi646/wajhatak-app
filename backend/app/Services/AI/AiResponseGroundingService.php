<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

/**
 * التحقق الأرضي (Grounding) — طبقة ما بعد التوليد، إلزامية دائمًا:
 * 1. يتأكد أن كل معرف عقار ذُكر في رد النموذج موجود فعلًا في نتائج البحث.
 * 2. يزيل أي أرقام أسعار/معرفات لم ترد في البيانات (منع اختراع الأرقام).
 * 3. يمنع تسرب أسرار (توكنات/مفاتيح/SQL) إن ظهرت في المخرجات.
 * عند اكتشاف تلف جوهري يستبدل الرد برد آمن بدل عرض معلومات مفبركة.
 */
class AiResponseGroundingService
{
    public function __construct(private readonly AiSettingsService $settings) {}

    /**
     * @param  list<array<string, mixed>>  $candidates  نتائج البحث الحقيقية.
     * @return array{content: string, replaced: bool, removed_ids: list<int>, violations: list<string>}
     */
    public function validate(string $content, array $candidates): array
    {
        $violations = [];
        $candidateIds = array_map(fn ($c) => (int) ($c['property_id'] ?? 0), $candidates);
        $candidateIds = array_values(array_filter($candidateIds));

        // 1) معرفات العقارات المذكورة في الرد.
        $mentionedIds = $this->extractPropertyIds($content);
        $removed = [];
        foreach ($mentionedIds as $id) {
            if ($id > 0 && ! in_array($id, $candidateIds, true)) {
                $removed[] = $id;
            }
        }

        if ($removed !== []) {
            $violations[] = 'hallucinated_property_ids';
        }

        // 2) الأسعار المخترعة: أي رقم كبير في الرد (≥ 100,000 بصيغة رقمية أو مع فاصل)
        //    يجب أن يطابق سعرًا حقيقيًا في نتائج البحث — وإلا يُحذف سطره.
        $realPrices = array_map(
            fn ($c) => (string) (int) round((float) ($c['price'] ?? 0)),
            $candidates,
        );
        $removedLines = [];
        foreach (preg_split('/\n/u', $content) ?: [] as $line) {
            if (preg_match_all('/(?:\d{1,3}(?:[,،]\d{3})+|\d{6,})/u', $line, $m)) {
                foreach ($m[0] as $raw) {
                    $price = (string) (int) str_replace([',', '،'], '', $raw);
                    if ((int) $price >= 100000 && ! in_array($price, $realPrices, true)) {
                        $violations[] = 'hallucinated_price';
                        $removedLines[] = $line;
                        break;
                    }
                }
            }
        }

        // 3) كشف تسرب أسرار أو SQL.
        if ($this->leaksSecrets($content)) {
            $violations[] = 'sensitive_leak';
        }

        $replaced = false;
        if ($this->settings->guardActive('hallucination') && ($removed !== [] || $removedLines !== [])) {
            // إزالة الأسطر التي تسوّق العقار المخترع أو السعر المخترع.
            foreach ($removed as $id) {
                $content = preg_replace('/^.*\b'.preg_quote((string) $id, '/').'\b.*$/mu', '', $content) ?? $content;
            }
            foreach (array_unique($removedLines) as $line) {
                $escaped = preg_quote($line, '/');
                $content = preg_replace('/^'.str_replace(['\n', '\r'], '', $escaped).'$/mu', '', $content) ?? $content;
            }
            $content = trim(preg_replace("/\n{3,}/", "\n\n", $content) ?? $content);
        }

        if ($this->leaksSecrets($content)) {
            // تسرب قسري: يُستبدل الرد بالكامل — لا تفاوض.
            $content = 'عذرًا، تعذر إعداد الإجابة بشكل آمن. أعد صياغة طلبك من فضلك.';
            $replaced = true;
        } elseif ($removed !== [] && trim($content) === '') {
            $content = 'لم أعثر على نتائج مطابقة موثوقة لطلبك حاليًا. جرّب توسيع البحث قليلًا (مدينة أو ميزانية أوسع).';
            $replaced = true;
        }

        return [
            'content' => $content,
            'replaced' => $replaced,
            'removed_ids' => $removed,
            'violations' => $violations,
        ];
    }

    /** استخراج كل الأرقام التي تبدو معرفات عقارات في الرد. */
    private function extractPropertyIds(string $content): array
    {
        $ids = [];

        // النمط: «المعرف 12» / «رقم 12» / «#12» / «(12)» بعد كلمات عرض عقار.
        if (preg_match_all('/(?:المعرف|رقم\s*العقار|العقار\s*رقم|#)\s*:?\s*(\d{1,10})/u', $content, $m)) {
            foreach ($m[1] as $id) {
                $ids[] = (int) $id;
            }
        }

        // أرقام مرجعية بأسلوب «ID: 12» اللاتينية.
        if (preg_match_all('/\b(?:id|ID)\s*:\s*(\d{1,10})/u', $content, $m)) {
            foreach ($m[1] as $id) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /** هل يتضمن الرد أسرارًا أو مفاتيح أو SQL؟ */
    private function leaksSecrets(string $content): bool
    {
        return (bool) preg_match(
            '/(api[_\s-]?key|bearer\s+[a-z0-9._-]{15,}|sk-[a-z0-9]{10,}|DB_PASSWORD|APP_KEY|SELECT\s+\*\s+FROM|DROP\s+TABLE|authorization:\s)/i',
            $content
        );
    }
}
