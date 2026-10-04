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

        // البطاقات تُرسل كبنية بيانات منفصلة إلى الواجهة؛ لا نكررها كسطور نصية.
        // هذا يمنع ظهور قائمة نصية مكررة بجانب Property Cards التفاعلية.
        $lines[] = $this->introLine($count, $filters, $first);

        if ($count > 3) {
            $lines[] = 'عرضت لك أفضل النتائج في بطاقات تفاعلية أدناه، وبإمكانك فتح أي بطاقة للتفاصيل.';
        }

        $lines[] = $this->nextStepLine($filters, $count, (int) ($first['property_id'] ?? 0));

        return implode("\n", $lines);
    }

    /** رد "عقار مشابه لهذا العقار" — التفاصيل تعرضها البطاقات التفاعلية. */
    public function similarReply(array $items): string
    {
        if ($items === []) {
            return 'لم أجد عقارات مشابهة كافية حتى الآن، لكن أقدر أوسّع البحث إلى مدينة أو ميزانية مختلفة.';
        }

        return 'وجدت لك '.count($items).' خيارات مشابهة من العقارات المنشورة فعليًا. البطاقات أدناه تحتوي الصور والتفاصيل؛ افتح أي بطاقة لاختيار الخطوة التالية.';
    }

    /**
     * رد صريح عند غياب التطابق الحرفي مع عرض البدائل القريبة في البطاقات.
     *
     * @param list<array<string,mixed>> $items
     * @param list<string> $relaxations
     */
    public function alternativesReply(array $items, array $relaxations = []): string
    {
        if ($items === []) {
            return 'لم أجد تطابقًا حرفيًا ولا بديلًا قريبًا من المعايير الحالية. يمكننا تعديل معيار واحد فقط ثم أعيد البحث.';
        }

        $message = 'لم أجد تطابقًا حرفيًا لكل الشروط، لذلك عرضت لك أقرب البدائل المتاحة فعليًا في البطاقات أدناه.';

        if ($relaxations !== []) {
            $message .= "\nخففت ".implode('، ', array_slice($relaxations, 0, 3)).' فقط، مع الحفاظ على بيانات العقارات الحقيقية.';
        }

        return $message."\nاختر أي بطاقة للتفاصيل، أو قل لي ما المعيار الذي تريد تضييقه.";
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

    /** رد حوار طبيعي متنوع؛ variationIndex يمنع تكرار نفس الصياغة في المحادثة. */
    public function smallTalkReply(
        string $message,
        string $intent,
        int $variationIndex = 0,
        array $recentReplies = [],
    ): string
    {
        $name = $this->settings->assistantName();
        $pick = static function (array $options) use ($variationIndex, $recentReplies): string {
            $count = count($options);
            $start = $count > 0 ? $variationIndex % $count : 0;

            for ($offset = 0; $offset < $count; $offset++) {
                $candidate = $options[($start + $offset) % $count];
                if (! in_array($candidate, $recentReplies, true)) {
                    return $candidate;
                }
            }

            if ($count === 0) {
                return '';
            }

            $candidate = $options[$start];
            $suffixes = [
                ' وأنا معك للخطوة التالية.',
                ' وخذ راحتك في الكلام.',
                ' وقل لي ما يدور في بالك.',
                ' وأنا جاهز نكمل معك.',
                ' ونقدر نبدأ من أي نقطة تحب.',
                ' وأخبرني بما تحتاج الآن.',
            ];

            return $candidate.($recentReplies !== []
                ? $suffixes[$variationIndex % count($suffixes)]
                : '');
        };
        $formal = AiChatIntentDetector::isFormal($message);

        return match ($intent) {
            'greeting' => $formal
                ? $pick([
                    "مرحبًا بك. أنا {$name}، ويسعدني مساعدتك اليوم. كيف يمكنني خدمتك؟",
                    "أهلًا وسهلًا بكم. أنا {$name}، ويمكنني مساعدتكم في البحث عن العقار المناسب. ما احتياجكم اليوم؟",
                    "تحية طيبة. أنا {$name}. اذكروا ما تبحثون عنه وسأساعدكم في الوصول إلى الخيارات المناسبة.",
                ])
                : $pick([
                    "وعليكم السلام ورحمة الله، أهلًا بك! 👋 أنا {$name}. كيف أقدر أخدمك اليوم؟",
                    "يا هلا والله! 🌿 نورت. أنا {$name}، قل لي وش تحتاج اليوم وأنا معك.",
                    "أهلًا وسهلًا! 😄 سعيد بوجودك. وش اللي في بالك اليوم؟",
                    "هلا بك! أتمنى يومك يكون طيب. أخبرني، نبدأ بالبحث عن عقار أم عندك شيء ثاني؟",
                    "مرحبًا بك! أنا {$name}. خذ راحتك بالكلام، وإذا احتجت عقارًا نبحث عنه سويًا.",
                ]),
            'wellbeing' => $formal
                ? $pick([
                    "بخير والحمد لله، وأشكركم على السؤال. كيف حالكم اليوم؟ وهل ترغبون في البحث عن عقار معين؟",
                    "أنا بخير، شكرًا لسؤالكم. كيف يمكنني مساعدتكم اليوم في احتياجكم العقاري؟",
                ])
                : $pick([
                    'بخير والحمد لله 😄 وسعيد بالسؤال. وأنت كيف أمورك؟ وبعدها قل لي وش نبحث عنه اليوم.',
                    'أنا تمام ولله الحمد 🌿 وجاهز لك. كيف يومك إلى الآن؟ وهل في عقار معين على بالك؟',
                    'الحمد لله بخير! 😊 طمني عنك أنت، وإذا خلصنا سوالفنا نبدأ ندوّر لك على العقار المناسب.',
                    'بأفضل حال وجاهز للمساعدة 😄. كيف الدنيا معك؟ وهل عندك مواصفات لعقارك القادم؟',
                ]),
            'thanks' => $pick([
                'العفو، هذا واجبي 🌿. أنا معك متى ما احتجت، وش نبحث لك اليوم؟',
                'ولا يهمك! 🙌 يسعدني أساعدك. عندك طلب عقاري نبدأ فيه؟',
                'على الرحب والسعة 😊. قل لي احتياجك وأنا أتكفل بالبحث الفعلي.',
            ]),
            'capabilities' => $pick([
                "أنا {$name}. أفهم كلامك الطبيعي عن العقار، وأبحث في العقارات المنشورة فعليًا، وأعرضها لك كبطاقات تفاعلية، وأساعدك في المقارنة والتفاصيل والمعاينة. وش الخدمة التي نبدأ بها؟",
                "تقدر تتكلم معي بدون نماذج معقدة: قل المدينة، الميزانية، النوع، الغرف أو أي وصف تعرفه، وأنا أحوّله لبحث حقيقي. ماذا تريد أن نجد؟",
                "أقدر أبحث، أقارن، أشرح تفاصيل عقار، أجيب عن المتوفر، وأتابع شروط بحثك داخل نفس المحادثة بدون خلطها مع كلامنا العادي. وش هدفك الآن؟",
            ]),
            'stats' => $this->statsReply(),
            'farewell' => $pick([
                'في أمان الله 👋. متى رجعت، نكمل من حيث تحب.',
                'مع السلامة 🌿، وبالتوفيق. وإذا احتجت عقارًا مناسبًا أنا موجود.',
                'نلتقي على خير 👋. أي وقت تحتاج بحثًا أو مقارنة عقارات، ارجع لي.',
            ]),
            'acknowledgement' => $pick([
                'تمام 👍 أنا معك. قل لي الخطوة التالية.',
                'أبشر 👍. وش نعمل الآن؟',
                'ممتاز، اتفقنا 🌿. أعطني طلبك التالي.',
            ]),
            'emotion' => $pick([
                'الله يعينك. خذ راحتك، وإذا حبيت تغيّر الجو أو تتكلم أكثر أنا معك. وعندما تحتاج شيئًا من وجهتك نرتبه سويًا.',
                'أفهمك. أتمنى أن تتحسن الأمور معك 🤍. خذ وقتك، وإذا أردت نركز على شيء عملي في وجهتك فأنا جاهز.',
                'سلامتك. لا تضغط على نفسك؛ قل لي ما تحتاجه الآن، سواء دردشة خفيفة أو مساعدة في البحث عن عقار.',
            ]),
            'weather' => $pick([
                'الجو دائمًا موضوع حلو 😄. إذا تقصد حالة الطقس اللحظية، ما عندي قراءة مباشرة لها هنا؛ لكن أقدر أبقى معك في أي سؤال يخص وجهتك أو نبدأ بحث العقار.',
                'خلّنا نغيّر الجو شوي 😊. ما أقدر أؤكد حالة الطقس الحالية من داخل المساعد، لكن قل لي إن كنت تريد دردشة قصيرة أو نرجع للبحث العقاري.',
                'سؤال لطيف 🌤️. ما عندي بيانات طقس لحظية موثوقة في هذا المسار، لذلك ما راح أخمّن. طيب، ماذا تبحث اليوم في وجهتك؟',
            ]),
            'joke' => $pick([
                'أكيد 😄: واحد قال لصاحبه «أريد بيتًا هادئًا جدًا»، قال له: «دور لك على بيت ما فيه جيران يتابعون كل شيء!» 😅
وبالمناسبة، هل تبحث عن بيت فعلًا؟',
                'هذه واحدة خفيفة 😄: عقار اتأخر على موعده، قالوا له «ليش؟» قال: «الزحمة كانت في الحي كله!» 🏠😂
طيب، نرجع للأهم: وش العقار اللي في بالك؟',
                'خذها 😄: واحد قرر يشتري أرضًا لأنه يحب المساحات الواسعة… ثم اكتشف أن مساحة المحفظة أضيق منها! 😂
هل عندك ميزانية معينة نبحث ضمنها؟',
            ]),
            'casual' => $pick([
                'أكيد، خذ راحتك 😄. أنا معك، نتكلم شوي أو نحول الكلام مباشرة لبحث عقاري متى ما تحب.',
                'من عيوني 🌿. خلّنا نسولف براحتنا؛ وإذا جاء طاري العقار، أنا جاهز أبحث لك من البيانات الحقيقية.',
                'تمام، نقدر نتكلم بشكل طبيعي 😊. وبعدها قل لي وش تحتاج في العقارات وأنا أرتبها لك.',
            ]),
            default => $pick([
                'أنا معك. خذ راحتك في الكلام، وعندما تحتاج عقارًا نبحث عنه فعليًا من بيانات وجهتك.',
                'سمعتك 👍. قل لي أكثر، وإذا صار الموضوع عقاريًا سأتعامل معه مباشرة.',
            ]),
        };
    }

    /**
     * رد آمن لرسالة عامة غير مصنفة كبحث عقاري.
     * لا يعتمد على فلاتر أو نتائج محفوظة من رسائل سابقة.
     */
    public function conversationReply(
        string $message,
        int $variationIndex = 0,
        array $recentReplies = [],
    ): string {
        $options = [
            'فهمتك. أنا معك، ويمكنك التحدث معي بشكل طبيعي. وعندما تريد خدمة عقارية، اكتب طلبك بطريقتك المعتادة وسأحوّله إلى بحث فعلي في عقارات وجهتك.',
            'تمام، خذ راحتك بالكلام 😊. وإذا احتجت أي شيء متعلق بعقارات وجهتك، قل لي ما عندك وسأتعامل معه مباشرة.',
            'أكيد، نقدر نتكلم بشكل طبيعي. وعندما يكون عندك احتياج عقاري، لا تحتاج لصيغة محددة؛ اشرح لي بطريقتك وأنا أرتب البحث.',
            'أنا حاضر معك. احكِ لي ما تريد، وإذا كان فيه جزء عقاري سأحوّله إلى خطوة عملية وبحث حقيقي.',
            'سمعتك. نقدر نكمل الكلام بشكل طبيعي، وعندما يظهر احتياج عقاري سأحوّله إلى بحث فعلي بدل ما أفترض عنك شيئًا.',
        ];
        $count = count($options);
        if ($count === 0) {
            return '';
        }

        $start = $variationIndex % $count;
        for ($offset = 0; $offset < $count; $offset++) {
            $candidate = $options[($start + $offset) % $count];
            if (! in_array($candidate, $recentReplies, true)) {
                return $candidate;
            }
        }

        return $options[$start];
    }

    /** رد توضيحي لمسار الاستثمار قبل تنفيذ بحث واسع. */
    public function investmentClarifyReply(): string
    {
        return 'ممتاز. أقدر أساعدك في البحث عن عقار مناسب للاستثمار، لكن ما راح أفترض هدفك أو العائد. قل لي المدينة أو المنطقة، والميزانية التقريبية، ونوع العقار إن كان لديك تفضيل، وسأبحث في العقارات المنشورة فعليًا.';
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
