<?php

namespace App\Services\AI;

use App\Enums\AiRequestStatus;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * المنسق الرئيسي للمساعد — محرك حتمي 100% على الخادم:
 * ضمان المخطط ← الحواجز ← نية الحوار اليومي ← تحليل البحث ← البحث الحقيقي
 * في القاعدة ← محرك الردود (يبني العربية الطبيعية من البيانات الحقيقية فقط)
 * ← التسجيل.
 *
 * لا يعتمد على أي نموذج لغوي ولا مزود خارجي ولا أي ملفات مُحمّلة:
 * يعمل فورًا على أي استضافة بأصغر موارد، ولا يمكنه اختراع معلومة غير موجودة.
 *
 * مبدأ المتانة الإنتاجي: كل مرحلة معزولة. فشل مرحلة واحدة (إعدادات/سجل/
 * فهرس/تسجيل) لا يُفرغ الرد بالكامل، وكل فشل يُسجَّل باسم مرحلته وسببه
 * الحقيقي في `ai_request_logs.error_code` وسجل الأخطاء — لا خطأ صامت أبدًا.
 */
class AiAssistantService
{
    private const OUT_OF_SCOPE_REPLY = 'أنا مساعد وجهتك، ومتخصص في مساعدتك في البحث عن العقارات واستخدام منصة وجهتك.';

    /** آخر مرحلة فشلت في الطلب الحالي (لتشخيص دقيق بلا كشف تفاصيل داخلية). */
    private ?string $failedStage = null;

    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiGuardrailService $guardrails,
        private readonly AiIntentService $intents,
        private readonly AiPropertySearchService $search,
        private readonly AiReplyEngine $replies,
        private readonly AiConversationService $conversations,
        private readonly AiLoggingService $logging,
        private readonly AiSchemaService $schema,
    ) {}

    /**
     * معالجة رسالة مستخدم كاملة.
     *
     * @param  list<array<string, mixed>>  $history  (اختياري — يُبنى من القاعدة إن لم يُمرر)
     * @return array<string, mixed>
     */
    public function handleChat(
        ?User $user,
        string $message,
        ?AiConversation $conversation = null,
        string $locale = 'ar',
        array $clientContext = [],
    ): array {
        $started = $this->ms();
        $searchMs = 0;
        $toolCalls = [];
        $filters = [];
        $intent = 'chat';
        $stage = 'bootstrap';

        try {
            // 0) ضمان مخطط المساعد (جداول/أعمدة/فهرس) — يمنع فشل كل طلب بسبب
            //    هجرة لم تُنفَّذ على الاستضافة. مرة كل بضع دقائق، وغير قاتل.
            $stage = 'schema';
            $this->attempt($stage, fn () => $this->schema->ensure());

            $stage = 'conversation';
            $conversation = $conversation
                ?? $this->attempt($stage, fn () => $this->conversations->currentFor($user, $locale));
            if (! $conversation instanceof AiConversation) {
                // تعذر فتح/قراءة المحادثة: لا نُسقط الرد — نُكمل بمحادثة مؤقتة
                // غير محفوظة، فيبقى المساعد يجيب من العقارات الحقيقية.
                $conversation = new AiConversation(['locale' => $locale, 'status' => 'active']);
            }

            // 1) حاجز الحماية المسبق (نطاق/حقن/حساسية) — قبل أي معالجة.
            $stage = 'guardrails';
            $guard = $this->attempt($stage, fn () => $this->guardrails->inspect($message), ['blocked' => false, 'reason' => null]);
            if (! empty($guard['blocked'])) {
                $reply = $guard['reason'] === 'out_of_domain'
                    ? $this->outOfScopeReply()
                    : 'عذرًا، لا أستطيع المساعدة في هذا الطلب.';

                $this->attempt('persist', fn () => $this->conversations->addUserMessage($conversation, $message, []));
                $this->out($conversation, $user, 'blocked', 'blocked', [], [], 0, $started, 0, 'guard_'.$guard['reason'], $reply);

                return $this->payload($conversation, $reply, 'blocked', [], $guard['reason']);
            }

            // 2) الحوار اليومي أولاً: تحية/شكر/قدرات/إحصاء — ردود فورية بلا بحث.
            $stage = 'history';
            $history = (array) $this->attempt($stage, fn () => $this->conversations->historyFor($conversation), []);
            $previous = (array) $this->attempt($stage, fn () => $this->conversations->accumulatedFilters($conversation), []);

            $smallTalk = AiChatIntentDetector::detectSmallTalk($message);
            if ($smallTalk !== null) {
                $stage = 'small_talk';
                $reply = $this->attempt($stage, fn () => $this->replies->smallTalkReply($message, $smallTalk));
                if (is_string($reply) && $reply !== '') {
                    $this->attempt('persist', fn () => $this->conversations->addUserMessage($conversation, $message, []));
                    $this->out($conversation, $user, 'small_talk', 'ok', [], [], 0, $started, 0, null, $reply);

                    return $this->payload($conversation, $reply, 'ok', [], null, []);
                }
                // فشل بناء رد الحوار اليومي → نكمل كبحث عادي بدل إظهار خطأ.
            }

            // 2.5) سؤال تفاصيل عن عقار محدد: «معلومات عن العقار 5» — من سجل حقيقي.
            $detailsTarget = AiChatIntentDetector::detectDetailsTarget($message);
            if ($detailsTarget !== null) {
                $stage = 'details';
                $reply = $this->attempt($stage, fn () => $this->replies->detailsReply($detailsTarget));
                if (is_string($reply) && $reply !== '') {
                    $item = $this->attempt($stage, fn () => $this->search->details($detailsTarget));
                    $this->attempt('persist', fn () => $this->conversations->addUserMessage($conversation, $message, ['last_property_id' => $detailsTarget]));
                    $this->out($conversation, $user, 'details', 'ok', [], [], 1, $started, 0, null, $reply, [$detailsTarget]);

                    return $this->payload($conversation, $reply, 'ok', $item !== null ? [$item] : [], null, ['last_property_id' => $detailsTarget]);
                }
                // المعرف غير موجود/غير منشور → نكمل كبحث عادي بلا اختراع.
            }

            // 3) تحليل نية البحث + دمج سياق المحادثة + سياق جهاز العميل (موقعه الحقيقي).
            $stage = 'intent';
            $parsed = $this->attempt(
                $stage,
                fn () => $this->intents->parse($message, $history, $previous),
                ['filters' => [], 'out_of_scope' => false, 'parser' => 'fallback'],
            );
            $filters = is_array($parsed['filters'] ?? null) ? $parsed['filters'] : [];

            foreach (['latitude' => 'client_latitude', 'longitude' => 'client_longitude'] as $key => $target) {
                if (isset($clientContext[$key]) && is_numeric($clientContext[$key])) {
                    $filters[$target] = (float) $clientContext[$key];
                }
            }
            if (isset($filters['client_latitude'], $filters['client_longitude']) && isset($clientContext['radius_km']) && is_numeric($clientContext['radius_km'])) {
                $filters['radius_km'] = (float) $clientContext['radius_km'];
            }
            $intent = ! empty($parsed['out_of_scope']) ? 'out_of_scope' : 'search';

            // إثراء الرد: اسم نوع العقار بالعربية + إحداثيات موقع العميل للبحث القريب.
            $this->attempt($stage, fn () => $this->decorateFilters($message, $filters));

            // حفظ رسالة المستخدم مرة واحدة مع معاييرها (سياق المحادثة).
            $this->attempt('persist', fn () => $this->conversations->addUserMessage($conversation, $message, $filters));

            // 3.5) لا معيار بحث حقيقي بعد (رسالة عامة/مبهمة) → لا نُنفّذ بحثًا
            //      يُظهر كل العقارات خطأً. نسأل سؤال توضيح ضمن سقف أسئلة
            //      المتابعة، ثم نرشد المستخدم — فصل النية عن البحث.
            if (! $this->hasSearchCriteria($filters)) {
                $stage = 'clarify';
                $maxFollowUps = (int) config('ai.limits.max_followups', 2);
                $followUps = (int) $this->attempt('followups', fn () => $this->conversations->consecutiveFollowUps($conversation), $maxFollowUps);
                $reply = $this->attempt('clarify', fn () => $this->replies->clarifyReply($followUps, $maxFollowUps));
                if (! is_string($reply) || $reply === '') {
                    $reply = 'أخبرني ما الذي تبحث عنه: شقة أم بيت أم أرض، وفي أي مدينة وبأي ميزانية تقريبًا؟';
                }
                $this->out($conversation, $user, 'clarify', 'ok', $filters, [], 0, $started, 0, null, $reply);

                return $this->payload($conversation, $reply, 'ok', [], null, $filters);
            }

            // "عقار مشابه لهذا العقار": نستمد الفلاتر من خصائص العقار المرجعي
            // (يجب أن يكون منشورًا فعليًا) ثم نبحث عن الأكثر شبهًا به.
            if (! empty($filters['similar_to'])) {
                $stage = 'similar';
                $similar = (array) $this->attempt(
                    $stage,
                    fn () => $this->search->similar((int) $filters['similar_to'], (int) $this->settings->get('ai_max_results', 6)),
                    [],
                );
                if ($similar !== []) {
                    $reply = $this->attempt($stage, fn () => $this->replies->similarReply($similar), '');
                    if (! is_string($reply) || $reply === '') {
                        $reply = 'هذه أقرب العقارات المشابهة المتوفرة لدينا حاليًا:';
                    }
                    $this->out($conversation, $user, 'similar', 'ok', $filters, [['tool' => 'search_properties', 'ok' => true]], count($similar), $started, 0, null, $reply);

                    return $this->payload($conversation, $reply, 'ok', $similar, null, $filters);
                }
                unset($filters['similar_to']);
            }

            // 4) البحث الفعلي في قاعدة البيانات (المصدر الوحيد للحقيقة).
            $stage = 'search';
            $searchStarted = $this->ms();
            $results = $this->attempt($stage, fn () => $this->search->search($filters), null);
            $searchMs = $this->ms() - $searchStarted;

            if (! is_array($results)) {
                // فشل غير متوقع في البحث: رد صادق ومحدد بدل رسالة الخطأ العامة.
                $this->failedStage ??= $stage;

                return $this->degraded($conversation, $user, $intent, $filters, $started, $searchMs);
            }

            $toolCalls[] = ['tool' => 'search_properties', 'ok' => true];

            // 5) لا نتائج → إما سؤال متابعة أو رد بعدم توفر.
            if ((int) $results['total'] === 0 || $results['items'] === []) {
                if (! empty($results['degraded'])) {
                    return $this->degraded($conversation, $user, $intent, $filters, $started, $searchMs);
                }

                $reply = $this->noResultsReply($filters, $conversation);
                $this->out($conversation, $user, $intent, 'ok', $filters, $toolCalls, 0, $started, $searchMs, null, $reply);

                return $this->payload($conversation, $reply, 'ok', [], null, $filters);
            }

            // 6) بناء الرد الطبيعي من النتائج الحقيقية فقط (محرك حتمي — بلا نموذج).
            $stage = 'reply';
            $reply = $this->attempt(
                $stage,
                fn () => $this->replies->summaryReply($message, $results['items'], $filters, $history),
                null,
            );
            if (! is_string($reply) || $reply === '') {
                // بديل مضمون: سطر لكل عقار حقيقي — البيانات نفسها بلا صياغة.
                $reply = 'وجدت لك '.count($results['items'])." عقارًا مطابقًا 🏡\n"
                    .implode("\n", array_map(
                        fn (array $item) => '• '.($item['title'] ?? 'عقار').' (المعرف '.$item['property_id'].')',
                        array_slice($results['items'], 0, 3),
                    ));
            }

            $assistantMessage = $this->out(
                $conversation, $user, $intent, 'ok', $filters, $toolCalls,
                count($results['items']), $started, $searchMs, null, $reply,
                array_map(fn ($item) => (int) $item['property_id'], $results['items']),
            );

            return [
                ...$this->payload($conversation, $reply, 'ok', $results['items'], null, $filters),
                'message_id' => $assistantMessage?->id,
            ];
        } catch (Throwable $e) {
            // خط الدفاع الأخير — لا ينبغي الوصول إليه بعد عزل المراحل أعلاه،
            // لكن إن وصلنا فالمستخدم يستحق ردًا واضحًا وسجلًا يشرح السبب.
            $this->logFailure($stage, $e);
            $this->failedStage ??= $stage;

            try {
                $this->logging->record(
                    $conversation ?? null, $user?->id, $intent, $filters, $toolCalls, 0,
                    AiRequestStatus::Error->value, $this->ms() - $started, $searchMs, 0, $this->errorCode($stage, $e),
                );

                return $this->payload($conversation ?? new AiConversation, $this->errorReply(), 'error', [], 'ai_unavailable', $filters);
            } catch (Throwable) {
                return [
                    'reply' => $this->errorReply(),
                    'status' => 'error',
                    'error' => 'ai_unavailable',
                    'conversation_id' => null,
                    'session_token' => null,
                    'properties' => [],
                    'filters' => $filters,
                ];
            }
        }
    }

    /** بحث مباشر بالمعايير (POST /ai/search) — بلا توليد نصي. */
    public function handleSearch(array $filters, ?User $user): array
    {
        $started = $this->ms();
        $filters = collect($filters)->only([
            'transaction_type', 'property_type', 'city', 'district', 'neighborhood',
            'bedrooms_min', 'bedrooms_max', 'bathrooms_min', 'min_price', 'max_price',
            'min_area', 'max_area', 'furnished', 'is_new', 'sort', 'q',
        ])->filter(fn ($v) => $v !== null && $v !== '')->all();

        try {
            $results = $this->search->search($filters);
            $this->logging->record(null, $user?->id, 'search', $filters, null, count($results['items']), AiRequestStatus::Ok->value, $this->ms() - $started);

            return [
                'status' => 'ok',
                'filters' => $filters,
                'total' => $results['total'],
                'properties' => $results['items'],
                'degraded' => (bool) ($results['degraded'] ?? false),
            ];
        } catch (Throwable $e) {
            $this->logFailure('search', $e);
            report($e);

            return ['status' => 'error', 'filters' => $filters, 'total' => 0, 'properties' => [], 'error' => 'search_failed'];
        }
    }

    // ------------------------------------------------------------------
    // أدوات المتانة والتشخيص
    // ------------------------------------------------------------------

    /**
     * تنفيذ مرحلة مع عزل كامل: أي استثناء يُسجَّل باسم مرحلته ويُرجع القيمة
     * البديلة بدل إسقاط الطلب كله.
     */
    private function attempt(string $stage, callable $callback, mixed $fallback = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            $this->logFailure($stage, $e);
            $this->failedStage ??= $stage;

            return $fallback;
        }
    }

    /** تسجيل السبب الحقيقي بالتفصيل — لا نكتفي بـ report(). */
    private function logFailure(string $stage, Throwable $e): void
    {
        Log::error('ai.stage_failed', [
            'stage' => $stage,
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]);
    }

    private function errorCode(string $stage, Throwable $e): string
    {
        return mb_substr($stage.'|'.$this->failedStage.'|'.class_basename($e), 0, 60);
    }

    /** رد صادق ومحدد عندما تتعذر قراءة بيانات العقارات (لا رسالة عامة غامضة). */
    private function degraded(
        AiConversation $conversation,
        ?User $user,
        string $intent,
        array $filters,
        int $started,
        int $searchMs,
    ): array {
        $reply = 'تعذر الوصول إلى بيانات العقارات لحظيًا. أعد المحاولة بعد قليل — وإن تكرر الأمر راجع لوحة التحكم (المساعد الذكي ← سجل الطلبات).';

        $this->out($conversation, $user, $intent, 'error', $filters, [], 0, $started, $searchMs, 'search_degraded', $reply);

        return $this->payload($conversation, $reply, 'error', [], 'search_degraded', $filters);
    }

    /** حفظ رد المساعد + تسجيل الطلب — كلاهما غير قاتل. */
    private function out(
        AiConversation $conversation,
        ?User $user,
        string $intent,
        string $status,
        array $filters,
        array $toolCalls,
        int $results,
        int $started,
        int $searchMs,
        ?string $errorCode,
        string $reply,
        array $propertyIds = [],
    ): ?AiMessage {
        $message = $this->attempt('persist', fn () => $this->conversations->addAssistantMessage($conversation, $reply, $propertyIds, $status));

        $this->attempt('logging', fn () => $this->logging->record(
            $conversation, $user?->id, $intent, $filters, $toolCalls, $results,
            $status, $this->ms() - $started, $searchMs, 0, $errorCode ?? $this->failedStage,
        ));

        return $message instanceof AiMessage ? $message : null;
    }

    private function ms(): int
    {
        return (int) (microtime(true) * 1000);
    }

    // ------------------------------------------------------------------

    /** إثراء الفلاتر قبل البحث: اسم النوع بالعربية للردود + إحداثيات العميل للبحث القريب. */
    private function decorateFilters(string $message, array &$filters): void
    {
        // اسم النوع بالعربية لجُمل الرد الطبيعية (يُحسب من الفهرس إن توفّر).
        if (! empty($filters['property_type']) && empty($filters['property_type_name'])) {
            $filters['property_type_name'] = match ($filters['property_type']) {
                'apartment' => 'شقة', 'villa' => 'فيلا', 'floor' => 'دور',
                'townhouse' => 'تاون هاوس', 'land' => 'أرض', 'shop' => 'محل',
                'office' => 'مكتب', 'building' => 'عمارة', 'farm' => 'مزرعة',
                'house' => 'بيت', default => null,
            };
        }

        // «قريب مني»: بحث جغرافي بإحداثيات العميل الحقيقية إن أرسلها التطبيق.
        if (AiChatIntentDetector::wantsNearby($message)
            && isset($filters['client_latitude'], $filters['client_longitude'])
            && ! isset($filters['city'])) {
            $filters['nearby'] = [
                'latitude' => (float) $filters['client_latitude'],
                'longitude' => (float) $filters['client_longitude'],
                'radius_km' => (float) ($filters['radius_km'] ?? $this->settings->get('ai_default_search_radius_km', 10)),
            ];
        }
    }

    /**
     * رد عدم توفر نتائج — يبدأ دائمًا برد صادق صريح («لا توجد…») ثم يضيف
     * سؤال متابعة واحدًا فقط إن كان هناك معيار أساسي ناقص ولم نتجاوز السقف.
     */
    private function noResultsReply(array $filters, AiConversation $conversation): string
    {
        $honest = $this->replies->noResultsReply($filters);

        $maxFollowUps = (int) config('ai.limits.max_followups', 2);
        $allowFollowUps = (bool) $this->attempt('settings', fn () => $this->settings->get('ai_allow_followups', true), true);
        $followUps = (int) $this->attempt('followups', fn () => $this->conversations->consecutiveFollowUps($conversation), $maxFollowUps);

        if (! $allowFollowUps || $followUps >= $maxFollowUps) {
            return $honest;
        }

        $question = null;
        if (empty($filters['city']) && empty($filters['district'])) {
            $question = 'في أي مدينة أو منطقة تفضّل؟ وسأوسّع البحث فورًا.';
        } elseif (empty($filters['transaction_type'])) {
            $question = 'هل تفضّل البيع أم الإيجار؟ هذا يساعدني في عرض الأنسب لك.';
        } elseif (! empty($filters['max_price'])) {
            $question = 'هل تريد أن أرفع سقف الميزانية قليلًا لعرض خيارات أقرب؟';
        }

        return $question === null ? $honest : $honest."\n".$question;
    }

    /**
     * هل تحمل المعايير معيار بحث حقيقيًا يستحق الاستعلام؟
     * رسالة مثل «ألو» أو «أبحث عن عقار» بلا معيار لا يجب أن تُنفّذ بحثًا.
     */
    private function hasSearchCriteria(array $filters): bool
    {
        foreach ([
            'transaction_type', 'property_type', 'city', 'district', 'neighborhood',
            'min_price', 'max_price', 'min_area', 'max_area',
            'bedrooms_min', 'bedrooms_max', 'bathrooms_min', 'furnished', 'is_new',
            'sort', 'similar_to', 'nearby',
        ] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== null) {
                return true;
            }
        }

        return ! empty($filters['keywords']);
    }

    private function outOfScopeReply(): string
    {
        return (string) $this->attempt(
            'settings',
            fn () => $this->settings->get('ai_out_of_scope_response', self::OUT_OF_SCOPE_REPLY),
            self::OUT_OF_SCOPE_REPLY,
        );
    }

    /** نص الخطأ العام الأخير — مع إشارة تشخيص إن كان التطبيق في وضع التصحيح. */
    private function errorReply(): string
    {
        return 'حدث خلل مؤقت أثناء معالجة طلبك. جرّب مرة أخرى بعد لحظات.';
    }

    private function payload(AiConversation $conversation, string $reply, string $status, array $properties, ?string $errorCode, array $filters = []): array
    {
        $payload = [
            'reply' => $reply,
            'status' => $status,
            'error' => $errorCode,
            'conversation_id' => $conversation->exists ? $conversation->id : null,
            'session_token' => $conversation->exists ? $conversation->session_token : null,
            'properties' => $properties,
            'filters' => $filters,
            // اسم المرحلة التي فشلت (إن فشلت) — بلا أي تفاصيل داخلية ولا SQL،
            // ويظهر في لوحة التحكم (سجل الطلبات + صفحة الاختبار) للتشخيص الفوري.
            'failed_stage' => $this->failedStage,
        ];

        return $payload;
    }
}
