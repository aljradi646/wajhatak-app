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
        $lines = [$this->introLine($count, $filters, $first)];

        if ($count > 1) {
            $lines[] = 'عرضت لك الخيارات في بطاقات تفاعلية. افتح البطاقة التي تهمك للتفاصيل الحالية.';
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

        return 'وجدت لك '.count($items).' عقارًا مشابهًا من البيانات الحالية. افتح أي بطاقة أدناه لمراجعة التفاصيل.';
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
                    "وعليكم السلام ورحمة الله، أهلًا بك! 👋 أنا {$name}، مساعدك في وجهتك. كيف أقدر أخدمك اليوم؟",
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

    /** الرد الموحد للرسائل غير الواضحة أو غير المدعومة. */
    public function unclearRequestReply(): string
    {
        return 'عذراً، لم أفهم طلبك بوضوح. هل تبحث عن عقار معين أم تحتاج مساعدة في استخدام التطبيق؟';
    }

    /** رد توضيحي لمسار الاستثمار قبل تنفيذ بحث واسع. */
    public function investmentClarifyReply(): string
    {
        return 'ممتاز. أقدر أساعدك في البحث عن عقار مناسب للاستثمار، لكن ما راح أفترض هدفك أو العائد. قل لي المدينة أو المنطقة، والميزانية التقريبية، ونوع العقار إن كان لديك تفضيل، وسأبحث في العقارات المنشورة فعليًا.';
    }

    /** رد دعم استخدام المنصة دون عرض أي عقارات. */
    public function platformSupportReply(string $message): string
    {
        $text = mb_strtolower(trim($message));
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text) ?? $text;
        $text = str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $text);

        if (preg_match('/(مفضل|المفضله|مفضلتي|احفظ|حفظ)/u', $text) === 1) {
            return 'للعثور على المفضلة: افتح التطبيق ثم اختر «المفضلة» من شريط التنقل السفلي للمستخدم أو من القائمة الجانبية. لحفظ عقار، افتح بطاقته واضغط أيقونة القلب.';
        }

        if (preg_match('/(اضيف عقار|اضافة عقار|اضافه عقار|انشئ عقار|انشاء عقار|عقاراتي)/u', $text) === 1) {
            return 'لإضافة عقار: سجّل الدخول بحساب وكيل موثّق، ثم افتح «عقاراتي» واضغط «إضافة عقار». أكمل بيانات العقار والسعر والموقع والمزايا والصور، ثم أرسل الطلب للنشر وفق حالة الحساب.';
        }

        if (preg_match('/(وكيل|التواصل مع الوكيل|اتواصل مع الوكيل|هاتف الوكيل|رقم الوكيل)/u', $text) === 1) {
            return 'للتواصل مع الوكيل: افتح تفاصيل العقار ثم استخدم وسيلة الاتصال العامة الظاهرة في البطاقة أو شاشة التفاصيل. لا أعرض بيانات اتصال خاصة غير منشورة.';
        }

        if (preg_match('/(فلتر|الفلاتر|بحث متقدم|البحث المتقدم|استكشاف|البحث)/u', $text) === 1) {
            return 'للبحث بدقة: افتح «استكشاف»، اكتب ما تبحث عنه، ثم استخدم الفلاتر مثل المدينة، نوع العقار، البيع أو الإيجار، السعر، المساحة وعدد الغرف.';
        }

        if (preg_match('/(المعاينه|معاينه|طلب معاينه|الطلبات)/u', $text) === 1) {
            return 'لطلب معاينة: افتح العقار المطلوب ثم اختر المعاينة، حدّد الموعد المتاح وأرسل الطلب. ستجد حالة الطلب لاحقًا من قسم «الطلبات».';
        }

        if (preg_match('/(اشعارات|الاشعارات|رسائل|الرسائل)/u', $text) === 1) {
            return 'للوصول إلى الإشعارات أو الرسائل، افتح القسم المقابل من القائمة أو شريط التنقل. الرسائل خاصة بالمحادثات داخل المنصة.';
        }

        return 'أقدر أشرح لك استخدام التطبيق خطوة بخطوة. أخبرني هل تريد طريقة إضافة عقار، الوصول إلى المفضلة، البحث بالفلاتر، التواصل مع وكيل، أم متابعة طلب معاينة؟';
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

    /** تفاصيل مختصرة من payload حي تم التحقق منه، دون إعادة استعلام قاعدة البيانات. */
    public function detailsReplyFromProperty(array $item): string
    {
        $location = collect([$item['district'] ?? null, $item['neighborhood'] ?? null, $item['city'] ?? null])
            ->filter()
            ->unique()
            ->implode(' - ');

        $parts = [];
        if (! empty($item['bedrooms'])) $parts[] = $item['bedrooms'].' غرف نوم';
        if (! empty($item['bathrooms'])) $parts[] = $item['bathrooms'].' حمام';
        if (($item['area'] ?? null) !== null) $parts[] = round((float) $item['area']).' م²';
        if (array_key_exists('is_furnished', $item)) {
            $parts[] = (bool) $item['is_furnished'] ? 'مفروش' : 'غير مفروش';
        }

        $lines = [
            'تفاصيل العقار رقم '.(int) ($item['property_id'] ?? 0).':',
            '• '.((string) ($item['title'] ?? 'عقار'))
                .(($item['type'] ?? null) ? ' — '.$item['type'] : '')
                .($location !== '' ? ' — '.$location : ''),
        ];

        if (($item['price'] ?? null) !== null) {
            $lines[] = '• السعر: '.number_format((float) $item['price']).' '.((string) ($item['currency'] ?? ''));
        }
        if ($parts !== []) $lines[] = '• المواصفات: '.implode(' · ', $parts);

        if (! empty($item['features']) && is_array($item['features'])) {
            $names = array_values(array_filter(array_map(
                fn ($feature) => is_array($feature) ? (string) ($feature['name_ar'] ?? '') : '',
                $item['features'],
            )));
            if ($names !== []) {
                $lines[] = '• المزايا: '.implode('، ', array_slice($names, 0, 12));
            }
        }

        if (($item['status'] ?? null) !== 'published') {
            $lines[] = '• الحالة الحالية: '.((string) ($item['status'] ?? 'غير معروفة'));
        }

        $description = trim((string) ($item['description'] ?? ''));
        if ($description !== '') {
            $lines[] = '• الوصف: '.mb_substr($description, 0, 180).(mb_strlen($description) > 180 ? '…' : '');
        }

        return implode("\n", $lines);
    }

    public function propertyFeaturesReply(array $item): string
    {
        $features = is_array($item['features'] ?? null) ? $item['features'] : [];
        $names = array_values(array_filter(array_map(
            fn ($feature) => is_array($feature) ? (string) ($feature['name_ar'] ?? '') : '',
            $features,
        )));

        return $names !== []
            ? 'المزايا المسجلة حاليًا للعقار رقم '.(int) ($item['property_id'] ?? 0).': '.implode('، ', array_slice($names, 0, 15)).'.'
            : 'لا أرى في بيانات العقار الحالية مزايا موثقة يمكنني تأكيدها.';
    }

    public function propertyContactReply(array $item): string
    {
        $agent = is_array($item['agent'] ?? null) ? $item['agent'] : [];
        $parts = [];

        if (! empty($agent['name'])) $parts[] = 'الوكيل: '.$agent['name'];
        if (! empty($agent['agency_name'])) $parts[] = 'الجهة: '.$agent['agency_name'];
        if (! empty($agent['phone'])) $parts[] = 'الهاتف: '.$agent['phone'];
        if (! empty($agent['whatsapp'])) $parts[] = 'واتساب: '.$agent['whatsapp'];

        return $parts !== []
            ? implode("\n", $parts)
            : 'لا توجد في بيانات العقار الحالية وسيلة تواصل عامة مؤكدة يمكنني عرضها.';
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
            $message .= "\n".implode(' ', array_slice($relaxations, 0, 2));
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
