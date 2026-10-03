<?php

namespace App\Services\AI;

/**
 * محرك الردود الأساسي والاحتياطي.
 *
 * يبني الردود من بيانات وجهتك الحقيقية ويضمن مسارًا آمنًا عند تعذر خادم
 * النموذج الصغير. في وضع LLM grounded يعمل كطبقة حقيقة قبل إعادة الصياغة.
 */
class AiReplyEngine
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiPropertySearchService $search,
    ) {}

    /** رد طبيعي فوق قائمة نتائج حقيقية. */
    public function summaryReply(string $message, array $items, array $filters, array $history = []): string
    {
        $count = count($items);
        if ($count === 0) {
            return 'لم أجد عقارات مطابقة لطلبك الآن، جرّب توسيع الميزانية أو تغيير المنطقة وأنا أبحث لك من جديد.';
        }

        $first = $items[0];
        $lines = [];

        $lines[] = $this->introLine($count, $filters, $first);

        foreach (array_slice($items, 0, 3) as $item) {
            $lines[] = $this->cardLine($item);
        }

        if ($count > 3) {
            $lines[] = 'وهناك '.($count - 3).' عقارًا آخر مطابقًا ظهرت لك بطاقاته في المحادثة 👇';
        }

        $lines[] = $this->nextStepLine($filters, $count, (int) ($first['property_id'] ?? 0));

        return implode("\n", $lines);
    }

    /** رد "عقار مشابه لهذا العقار". */
    public function similarReply(array $items): string
    {
        if ($items === []) {
            return 'لم أجد عقارات مشابهة كافية حتى الآن، لكن عقارات جديدة تُنشر باستمرار — جرّب لاحقًا أو اسألني عن منطقة معينة.';
        }

        $lines = ['هذه أقرب العقارات المشابهة المتوفرة لدينا حاليًا:'];
        foreach (array_slice($items, 0, 3) as $item) {
            $lines[] = $this->cardLine($item);
        }

        return implode("\n", $lines);
    }

    /**
     * رد سؤال تفاصيل عن عقار محدد بالمعرف ("معلومات عن العقار 5").
     * البيانات كلها من سجل الفهرس الحقيقي — لا شيء يُخترع.
     */
    public function detailsReply(int $propertyId): ?string
    {
        $item = $this->search->details($propertyId);
        if ($item === null) {
            return null;
        }

        $location = collect([$item['district'] ?? null, $item['neighborhood'] ?? null, $item['city'] ?? null])
            ->filter()
            ->implode(' - ');
        $price = number_format((float) $item['price']).' '.($item['currency'] ?? '');
        $parts = [];
        if (! empty($item['bedrooms'])) {
            $parts[] = $item['bedrooms'].' غرف نوم';
        }
        if (! empty($item['bathrooms'])) {
            $parts[] = $item['bathrooms'].' حمام';
        }
        if (! empty($item['area'])) {
            $parts[] = round((float) $item['area']).' م²';
        }
        $parts[] = ($item['is_furnished'] ?? false) ? 'مفروش' : 'غير مفروش';

        $lines = [
            'تفاصيل العقار رقم '.$item['property_id'].':',
            '• '.($item['title'] ?? '').' — '.($item['type'] ?? '').' '.$location,
            '• السعر: '.$price,
            '• المواصفات: '.implode(' · ', $parts),
        ];

        $description = trim((string) ($item['description'] ?? ''));
        if ($description !== '') {
            $lines[] = '• الوصف: '.mb_substr($description, 0, 180).(mb_strlen($description) > 180 ? '…' : '');
        }

        $lines[] = 'افتح بطاقة العقار في الأعلى لترى الصور والموقع على الخريطة والتواصل مع الوكيل مباشرة.';

        return implode("\n", $lines);
    }

    /** رد حوار عام قصير طبيعي. */
    public function smallTalkReply(string $message, string $intent): string
    {
        $name = $this->settings->assistantName();

        return match ($intent) {
            'greeting' => "أهلًا وسهلًا! 🌟 أنا {$name}، مساعدك العقاري في منصة وجهتك.\nأقدر أساعدك بالبحث عن عقار بالكلام الطبيعي، وأعرض لك النتائج والبطاقات الحقيقية من المنصة.",
            'wellbeing' => "بخير والحمد لله 😄 وجاهز أساعدك. قل لي ما الذي تبحث عنه، أو تحدث معي بشكل طبيعي وسأفهم طلبك.",
            'thanks' => 'العفو! 🌿 أنا هنا لمساعدتك في العثور على العقار المناسب واستخدام وجهتك.',
            'capabilities' => "أنا {$name}. أقدر أفهم وصفك الطبيعي للعقار، مثل المدينة والحي والسعر والغرف، ثم أبحث في العقارات المنشورة فعليًا. أقدر أيضًا أجيب عن تفاصيل عقار، أبحث عن عقار مشابه، وأساعد في طلب المعاينة.",
            'stats' => $this->statsReply(),
            'farewell' => 'مع السلامة! 👋 ارجع لي متى ما احتجت البحث عن عقار أو تفاصيل عن أحد العقارات.',
            'acknowledgement' => 'تمام 👍 أنا معك. اكتب طلبك بالطريقة التي تريحك، مثل «أبغى شقة غرفتين في صنعاء»، وسأفهمه وأبحث مباشرة.',
            default => 'أنا مساعد وجهتك العقاري. تحدث معي بالطريقة التي تريدها، وسأتعامل مع طلبك بحسب محتواه.',
        };
    }

    /**
     * رد احتياطي لرسالة محادثية لم تُصنّف كتحية أو طلب عقاري.
     * لا يحتوي على بيانات عقارات ولا يعيد استخدام نتائج محادثة سابقة.
     */
    public function conversationReply(string $message): string
    {
        $text = trim($message);

        if ($text === '') {
            return 'أنا معك. اكتب ما تريد معرفته وسأساعدك في حدود خدمات وجهتك.';
        }

        return 'فهمتك. أنا معك، وأقدر أتحدث معك بشكل طبيعي أو أساعدك في أي شيء متعلق بعقارات وجهتك. عندما تريد البحث، اكتب طلبك كما تتكلم عادةً وسأحوّله إلى بحث فعلي.';
    }

    /** رد إحصاءات حقيقية من الفهرس (كم عقارًا متوفرًا). */
    public function statsReply(): string
    {
        $rows = $this->search->findLocations('', 12);
        $total = array_sum(array_map(fn ($r) => (int) $r['properties_count'], $rows));

        if ($total === 0) {
            return 'لا توجد عقارات منشورة لحظة الرسالة، لكن تُضاف عقارات جديدة باستمرار — تابعنا أو استخدم البحث التقليدي.';
        }

        usort($rows, fn ($a, $b) => $b['properties_count'] <=> $a['properties_count']);
        $lines = ['حاليًا لدينا '.$total.' عقارًا منشورًا، وأبرز المناطق:'];
        foreach (array_slice($rows, 0, 4) as $row) {
            $place = collect([$row['city'], $row['district']])->filter()->implode(' - ');
            $min = $row['min_price'] !== null ? number_format((float) $row['min_price']) : null;
            $lines[] = '• '.$place.': '.$row['properties_count'].' عقار'.($min !== null ? ' (يبدأ من '.$min.')' : '');
        }

        return implode("\n", $lines);
    }

    /**
     * رد توضيحي لرسالة عامة/مبهمة لا تحمل أي معيار بحث حقيقي.
     *
     * مهم: لا يُنفّذ بحث عند غياب المعايير إطلاقًا — وإلا عُرضت كل العقارات
     * لرسالة مثل «ألو» أو «أبحث عن عقار». نسأل سؤالًا واحدًا ضمن سقف أسئلة
     * المتابعة، وبعد بلوغ السقف نرشد المستخدم بلا سؤال آخر.
     */
    public function clarifyReply(int $followUps, int $maxFollowUps): string
    {
        $questions = [
            'يسعدني مساعدتك! أي نوع عقار تبحث عنه: شقة، بيت، أرض، محل…؟ وفي أي مدينة؟',
            'وفي أي مدينة أو منطقة تحديدًا؟ وما ميزانيتك التقريبية؟',
        ];

        if ($followUps < $maxFollowUps && isset($questions[$followUps])) {
            return $questions[$followUps];
        }

        return "لأعرض لك نتائج دقيقة، اكتب طلبك في رسالة واحدة تتضمّن:"."\n"
            .'• نوع العقار (شقة، بيت، أرض، محل…)'."\n"
            .'• المدينة أو المنطقة'."\n"
            .'• الميزانية التقريبية أو عدد الغرف'."\n"
            .'مثال: «شقة ثلاث غرف في صنعاء أقل من 100 ألف» — وسأعرض لك البطاقات الحقيقية فورًا. 🌿';
    }

    /** الرد المفتوح للدخول: نطاق المساعد. */
    public function scopeReply(): string
    {
        return 'أنا مساعد وجهتك، متخصص في العقارات فقط: البحث، الأسعار، المناطق، وترتيب المعاينة. اسألني عن أي عقار وسأعرضه لك ببطاقة حقيقية من المنصة.';
    }

    /** رد عدم توفر نتائج بعد المتابعة الذكية. */
    public function noResultsReply(array $filters): string
    {
        $city = $filters['city'] ?? null;
        $type = $filters['property_type'] ?? null;
        $max = $filters['max_price'] ?? null;

        $parts = [];
        if ($type !== null) {
            $parts[] = 'نوع العقار';
        }
        if ($city !== null) {
            $parts[] = 'المدينة ('.$city.')';
        }
        if ($max !== null) {
            $parts[] = 'الميزانية';
        }
        $criteria = implode(' و', $parts);

        return 'لا توجد حاليًا عقارات مطابقة'.($criteria !== '' ? ' حسب '.$criteria : '').' في قاعدة بيانات وجهتك.'
            ."\nجرّب رفع الميزانية قليلًا أو توسيع المناطق، أو اسألني: «ما المتوفر في صنعاء؟» وسأعرض لك أحدث البطاقات.";
    }

    // ------------------------------------------------------------------

    /** جملة الافتح: تذكر طلب المستخدم بلغته (المدينة/النوع/الميزانية). */
    private function introLine(int $count, array $filters, array $first): string
    {
        $transaction = ($filters['transaction_type'] ?? null) === 'rent' ? 'للإيجار' : (($filters['transaction_type'] ?? null) === 'sale' ? 'للبيع' : null);
        $typeName = $filters['property_type_name'] ?? null;
        $city = $filters['city'] ?? null;
        $maxPrice = isset($filters['max_price']) ? number_format((float) $filters['max_price']) : null;
        $minPrice = isset($filters['min_price']) ? number_format((float) $filters['min_price']) : null;

        $what = trim(($transaction ?? 'مطابقًا لطلبك').' '.($typeName ?? ''));
        $where = $city !== null ? ' في '.$city : '';
        $budget = $minPrice !== null && $maxPrice !== null
            ? ' بميزانية من '.$minPrice.' إلى '.$maxPrice
            : ($maxPrice !== null ? ' بميزانية حتى '.$maxPrice : ($minPrice !== null ? ' بميزانية تبدأ من '.$minPrice : ''));

        $best = $count > 1 ? ' — وأفضلها يظهر أولًا حسب أقرب تطابق' : '';

        return "وجدت لك {$count} عقار{$what}{$where}{$budget}{$best} 🏡";
    }

    /** سطر بطاقة: عنوان + موقع + سعر + أبرز مواصفة، من بيانات حقيقية. */
    private function cardLine(array $item): string
    {
        $place = collect([$item['district'] ?? null, $item['neighborhood'] ?? null, $item['city'] ?? null])
            ->filter()
            ->unique()
            ->implode(' - ');

        $traits = [];
        if (! empty($item['bedrooms'])) {
            $traits[] = $item['bedrooms'].' غرف';
        }
        if (! empty($item['area'])) {
            $traits[] = round((float) $item['area']).' م²';
        }
        if (! empty($item['is_furnished'])) {
            $traits[] = 'مفروش';
        }

        $line = '• '.($item['title'] ?? 'عقار')
            .($place !== '' ? ' — '.$place : '')
            .' | '.number_format((float) $item['price']).' '.($item['currency'] ?? '');
        if ($traits !== []) {
            $line .= ' | '.implode('، ', $traits);
        }
        $line .= ' (المعرف '.$item['property_id'].')';

        return $line;
    }

    /** جملة الخطوة التالية حسب ما ناقص في الطلب. */
    private function nextStepLine(array $filters, int $count, int $firstPropertyId = 0): string
    {
        if (empty($filters['transaction_type'])) {
            return 'هل تفضّل البيع أم الإيجار؟ أخبرني وأضيّق البحث لك أكثر.';
        }
        if (empty($filters['city'])) {
            return 'في أي مدينة تفضّل؟ (صنعاء، عدن، تعز…) وسأعرض لك الأنسب.';
        }
        if ($count > 1) {
            return 'هل تريد ترتيبها من الأرخص، أو مقارنة اثنين منها؟';
        }

        $id = $firstPropertyId > 0 ? $firstPropertyId : (int) ($filters['last_property_id'] ?? 0);

        return $id > 0
            ? 'أعجبك هذا؟ اكتب «معلومات عن العقار '.$id.'» لتفاصيل أوسع، أو اطلب عقارًا مشابهًا.'
            : 'أعجبك هذا العقار؟ اطلب «عقار مشابه» وسأعرض لك الأقرب له في نفس المنطقة.';
    }
}
