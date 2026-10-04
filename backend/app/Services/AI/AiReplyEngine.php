<?php

namespace App\Services\AI;

/**
 * محرك الردود الحتمي — بديل كامل للنموذج اللغوي.
 *
 * يبني ردودًا عربية طبيعية (كلام يومي) من نتائج البحث الحقيقية وسياق
 * المحادثة فقط. لا يستدعي أي نموذج ولا مزودًا خارجيًا ولا يحمّل أي
 * ملفات: كل جملة تُركَّب من بيانات قاعدة بيانات وجهتك نفسها، لذلك لا
 * يمكن أن "يخترع" ردًا غير موجود أصلًا (بلا هلوسة إطلاقًا).
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

        foreach (array_slice($items, 0, 3) as $index => $item) {
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

    /** رد حوار عام قصير طبيعي (تحية/شكر/مساعدة/إحصاء). */
    public function smallTalkReply(string $message, string $intent, int $variationIndex = 0, array $recentReplies = []): string
    {
        $name = $this->settings->assistantName();

        $variants = [
            'greeting' => [
                "وعليكم السلام ورحمة الله وبركاته. أهلًا بك! أنا {$name}، مساعد وجهتك. كيف أساعدك اليوم؟",
                "وعليكم السلام 🌿. أهلًا بك في وجهتك. ماذا تحب نبدأ به اليوم؟",
                "حياك الله. أنا {$name}، مساعد وجهتك. أخبرني بما تحتاجه وسأتعامل معه بالطريقة المناسبة.",
            ],
            'wellbeing' => [
                'بخير والحمد لله. شكرًا لسؤالك. ماذا تحتاج اليوم؟',
                'الحمد لله بخير. جاهز أساعدك في وجهتك؛ ما الذي تبحث عنه؟',
            ],
            'acknowledgement' => [
                'تمام. أنا معك؛ ما الخطوة التالية؟',
                'تمام 👍. قل لي وش نكمل عليه.',
            ],
        ];

        $base = match ($intent) {
            'greeting' => $variants['greeting'][$variationIndex % count($variants['greeting'])],
            'wellbeing' => $variants['wellbeing'][$variationIndex % count($variants['wellbeing'])],
            'acknowledgement' => $variants['acknowledgement'][$variationIndex % count($variants['acknowledgement'])],
            'thanks' => 'العفو، هذا واجبي. قل لي احتياجك متى ما كان جاهزًا.',
            'capabilities' => "أنا {$name}. أساعدك في البحث عن العقارات المنشورة فعليًا، عرض تفاصيلها الحالية، المقارنة بينها، وشرح استخدام وظائف منصة وجهتك المتاحة.",
            'stats' => $this->statsReply(),
            'weather' => 'لا أملك في مسار المساعد قراءة طقس لحظية موثوقة، لذلك لن أخمّنها. أستطيع مساعدتك في وظائف وجهتك أو البحث العقاري.',
            'joke' => 'أكيد 😄: يبدو أن حتى العقار يحتاج موعدًا قبل أن يخرج من البيت! وإذا أردت نرجع للعقار، أعطني طلبك وسأبحث فعليًا.',
            'casual' => 'أكيد، خذ راحتك. نقدر نتكلم بشكل طبيعي، وعندما تحتاج شيئًا عقاريًا أبحث لك من بيانات وجهتك الحالية.',
            'farewell' => 'في أمان الله. عندما تحتاج بحثًا أو تفاصيل عقار ارجع لي.',
            'emotion' => 'سلامتك. خذ راحتك، وإذا احتجت مساعدة عملية في وجهتك أساعدك من البيانات الفعلية.',
            default => 'أنا مساعد وجهتك. قل لي ما تحتاج وسأتعامل معه بالطريقة المناسبة.',
        };

        // حماية إضافية لو كان النص المقترح مطابقًا لآخر رد.
        if ($recentReplies !== [] && end($recentReplies) === $base && isset($variants[$intent])) {
            return $variants[$intent][($variationIndex + 1) % count($variants[$intent])];
        }

        return $base;
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

    public function investmentClarifyReply(): string
    {
        return 'أقدر أساعدك في البحث عن عقار للاستثمار، لكن لن أفترض عائدًا أو أداءً ماليًا غير موجود في البيانات. اكتب المدينة أو المنطقة والميزانية ونوع العقار إن كان لديك تفضيل.';
    }

    public function platformSupportReply(string $message): string
    {
        $text = mb_strtolower(trim($message));
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text) ?? $text;
        $text = str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $text);

        if (preg_match('/(مفضل|المفضله|مفضلتي|احفظ|حفظ)/u', $text) === 1) {
            return 'للوصول إلى المفضلة افتح قسم «المفضلة». ولحفظ عقار استخدم أيقونة القلب في بطاقة أو تفاصيل العقار.';
        }
        if (preg_match('/(اضيف عقار|اضافة عقار|انشئ عقار|انشاء عقار|عقاراتي)/u', $text) === 1) {
            return 'لإضافة عقار استخدم حساب الوكيل الموثق ثم افتح «عقاراتي» واختر «إضافة عقار»، وبعد إدخال البيانات والصور أرسل العرض وفق حالة الحساب.';
        }
        if (preg_match('/(فلتر|الفلاتر|بحث متقدم|استكشاف|البحث)/u', $text) === 1) {
            return 'يمكنك من «استكشاف» البحث بالكلمات الطبيعية ثم تضييق النتائج بالفلاتر المتاحة مثل المدينة، نوع العقار، السعر وعدد الغرف.';
        }
        if (preg_match('/(معاينه|معاينة|طلب معاينه|الطلبات)/u', $text) === 1) {
            return 'لطلب معاينة، افتح تفاصيل العقار ثم استخدم إجراء المعاينة واختر الموعد المتاح. ستظهر حالة الطلب في القسم المخصص له.';
        }

        return 'اذكر الميزة التي تريد شرحها في وجهتك، مثل المفضلة أو إضافة عقار أو البحث بالفلاتر أو طلب المعاينة.';
    }

    /** رد تعريف المساعد دون أي بحث عقاري. */
    public function identityReply(): string
    {
        return 'أنا مساعد وجهتك. أساعدك في البحث عن العقارات المتاحة، فهم تفاصيل العقار ومواصفاته الحالية، وشرح استخدام ميزات منصة وجهتك المتاحة لحسابك.';
    }

    /** رد معلومات المنصة من مصدر المعرفة المعتمد فقط. */
    public function knowledgeReply(array $items): string
    {
        if ($items === []) {
            return 'لا أملك حاليًا معلومة موثوقة عن هذا الجزء من المنصة. يمكنك تحديد الميزة أو الصفحة التي تريد معرفة طريقة استخدامها.';
        }

        return collect($items)
            ->take(3)
            ->map(fn (array $item) => '• '.($item['content'] ?? ''))
            ->implode("\n");
    }

    /** رد آمن لطلبات الأسرار. */
    public function securityReply(): string
    {
        return 'لا أستطيع الوصول إلى كلمات المرور أو مفاتيح API أو الأسرار أو بيانات المستخدمين الخاصة أو عرضها. لاستعادة كلمة المرور استخدم إجراء الاستعادة في الحساب، ولأي صلاحية إدارية استخدم المسار الإداري المعتمد.';
    }

    public function ambiguousReply(): string
    {
        return 'عذراً، لم أفهم طلبك بوضوح. هل تبحث عن عقار معين أم تحتاج مساعدة في استخدام التطبيق؟';
    }

    public function technicalReply(): string
    {
        return 'أستطيع مساعدتك في الوظائف المتاحة داخل وجهتك. اذكر اسم الصفحة أو المشكلة التي تظهر لك وسأوجهك إلى المسار المدعوم في التطبيق.';
    }

    /** رد الحالة أو السعر أو الموقع من السجل الحي فقط. */
    public function propertyStatusReply(array $property, string $intent): string
    {
        $id = (int) ($property['property_id'] ?? 0);
        $title = (string) ($property['title'] ?? 'العقار');
        $status = (string) ($property['status'] ?? '');

        $label = match ($status) {
            'published' => 'منشور ومتاح للاكتشاف حاليًا',
            'draft' => 'مسودة وغير متاح للاكتشاف',
            'pending' => 'قيد المراجعة وغير متاح للاكتشاف',
            'rejected' => 'مرفوض وغير متاح للاكتشاف',
            'archived' => 'مؤرشف وغير متاح للاكتشاف',
            default => 'حالته الحالية غير معروفة في البيانات المتاحة',
        };

        return match ($intent) {
            'property_availability' => "العقار رقم {$id} «{$title}» حالته الحالية: {$label}.",
            'property_price' => isset($property['price'])
                ? "السعر الحالي للعقار رقم {$id} «{$title}»: ".number_format((float) $property['price']).' '.($property['currency'] ?? '').'.'
                : "لا توجد قيمة سعر مؤكدة حاليًا للعقار رقم {$id}.",
            'property_location' => 'موقع العقار رقم '.$id.' «'.$title.'»: '
                .collect([$property['district'] ?? null, $property['neighborhood'] ?? null, $property['city'] ?? null])->filter()->implode(' - ').'.',
            default => $this->detailsReply($id) ?? 'تعذر جلب بيانات العقار الحالية.',
        };
    }

    /** الرد المفتوح للدخول: نطاق المساعد. */
    public function scopeReply(): string
    {
        return 'أنا مساعد وجهتك، متخصص في العقارات فقط: البحث، الأسعار، المناطق، وترتيب المعاينة. اسألني عن أي عقار وسأعرضه لك ببطاقة حقيقية من المنصة.';
    }

    /** رد عدم توفر نتائج بعد المتابعة الذكية. */
    public function alternativesReply(array $filters, array $relaxations = []): string
    {
        $city = $filters['city'] ?? null;
        $type = $filters['property_type'] ?? null;
        $max = $filters['max_price'] ?? null;

        $criteria = [];
        if ($type) $criteria[] = 'نوع العقار';
        if ($city) $criteria[] = 'المدينة ('.$city.')';
        if ($max !== null) $criteria[] = 'الميزانية';

        $message = 'لم أجد تطابقًا حرفيًا مع '.implode(' و', $criteria ?: ['الشروط']).' حاليًا، لكن وجدت بدائل حقيقية أقرب إلى طلبك.';
        if ($relaxations !== []) {
            $message .= '\n'.implode(' ', array_slice($relaxations, 0, 2));
        }

        return $message;
    }

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

        // لا نذكر معرفًا فارغًا: نستخدم معرف العقار المعروض فعلًا (أو نصيحة عامة).
        $id = $firstPropertyId > 0 ? $firstPropertyId : (int) ($filters['last_property_id'] ?? 0);

        return $id > 0
            ? 'أعجبك هذا؟ اكتب «معلومات عن العقار '.$id.'» لتفاصيل أوسع، أو اطلب عقارًا مشابهًا.'
            : 'أعجبك هذا العقار؟ اطلب «عقار مشابه» وسأعرض لك الأقرب له في نفس المنطقة.';
    }
}
