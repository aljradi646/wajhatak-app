<?php

namespace App\Services\AI;

use App\Enums\AiRequestStatus;
use App\Models\AiConversation;
use App\Models\User;
use Throwable;

/**
 * المنسق الرئيسي للمساعد — محرك حتمي 100% على الخادم:
 * الحواجز ← نية الحوار اليومي ← تحليل البحث ← البحث الحقيقي في القاعدة ←
 * محرك الردود (يبني العربية الطبيعية من البيانات الحقيقية فقط) ← التسجيل.
 *
 * لا يعتمد على أي نموذج لغوي ولا مزود خارجي ولا أي ملفات مُحمّلة:
 * يعمل فورًا على أي استضافة بأصغر موارد، ولا يمكنه اختراع معلومة غير موجودة.
 */
class AiAssistantService
{
    private const OUT_OF_SCOPE_REPLY = 'أنا مساعد وجهتك، ومتخصص في مساعدتك في البحث عن العقارات واستخدام منصة وجهتك.';

    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiGuardrailService $guardrails,
        private readonly AiIntentService $intents,
        private readonly AiPropertySearchService $search,
        private readonly AiReplyEngine $replies,
        private readonly AiConversationService $conversations,
        private readonly AiLoggingService $logging,
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
        $started = (int) (microtime(true) * 1000);
        $conversation = $conversation ?? $this->conversations->currentFor($user, $locale);
        $searchMs = 0;
        $toolCalls = [];
        $filters = [];
        $intent = 'chat';

        try {
            // 1) حاجز الحماية المسبق (نطاق/حقن/حساسية) — قبل أي استدعاء للنموذج.
            $guard = $this->guardrails->inspect($message);
            if ($guard['blocked']) {
                $reply = $guard['reason'] === 'out_of_domain'
                    ? $this->outOfScopeReply()
                    : 'عذرًا، لا أستطيع المساعدة في هذا الطلب.';

                $this->logging->record(
                    $conversation, $user?->id, 'blocked', [], [], 0, 'blocked',
                    (int) (microtime(true) * 1000) - $started, 0, 0, 'guard_'.$guard['reason'],
                );
                $this->conversations->addUserMessage($conversation, $message, []);
                $this->conversations->addAssistantMessage($conversation, $reply, [], 'blocked');

                return $this->payload($conversation, $reply, 'blocked', [], $guard['reason']);
            }

            // 2) الحوار اليومي أولاً: تحية/شكر/قدرات/إحصاء — ردود فورية بلا بحث.
            $history = $this->conversations->historyFor($conversation);
            $previous = $this->conversations->accumulatedFilters($conversation);

            $smallTalk = AiChatIntentDetector::detectSmallTalk($message);
            if ($smallTalk !== null) {
                $reply = $this->replies->smallTalkReply($message, $smallTalk);
                $this->conversations->addUserMessage($conversation, $message, []);
                $this->conversations->addAssistantMessage($conversation, $reply, []);
                $this->finish($conversation, $user, AiRequestStatus::Ok->value, 'small_talk', [], [], 0, $started, 0, null, $reply, []);

                return $this->payload($conversation, $reply, 'ok', [], null, []);
            }

            // 2.5) سؤال تفاصيل عن عقار محدد: «معلومات عن العقار 5» — من سجل حقيقي.
            $detailsTarget = AiChatIntentDetector::detectDetailsTarget($message);
            if ($detailsTarget !== null) {
                $reply = $this->replies->detailsReply($detailsTarget);
                if ($reply !== null) {
                    $this->conversations->addUserMessage($conversation, $message, ['last_property_id' => $detailsTarget]);
                    $this->conversations->addAssistantMessage($conversation, $reply, [$detailsTarget]);
                    $this->finish($conversation, $user, AiRequestStatus::Ok->value, 'details', [], [], 1, $started, 0, null, $reply, [$detailsTarget]);

                    $item = $this->search->details($detailsTarget);

                    return $this->payload($conversation, $reply, 'ok', $item !== null ? [$item] : [], null, ['last_property_id' => $detailsTarget]);
                }
                // المعرف غير موجود/غير منشور → نكمل كبحث عادي بلا اختراع.
            }

            // 3) تحليل نية البحث + دمج سياق المحادثة + سياق جهاز العميل (موقعه الحقيقي).
            $parsed = $this->intents->parse($message, $history, $previous);
            $filters = $parsed['filters'];
            if (isset($clientContext['latitude'], $clientContext['longitude'])) {
                $filters['client_latitude'] = (float) $clientContext['latitude'];
                $filters['client_longitude'] = (float) $clientContext['longitude'];
                if (isset($clientContext['radius_km'])) {
                    $filters['radius_km'] = (float) $clientContext['radius_km'];
                }
            }
            $intent = $parsed['out_of_scope'] ? 'out_of_scope' : 'search';

            // إثراء الرد: اسم نوع العقار بالعربية + إحداثيات موقع العميل للبحث القريب.
            $this->decorateFilters($message, $filters);

            // "عقار مشابه لهذا العقار": نستمد الفلاتر من خصائص العقار المرجعي
            // (يجب أن يكون منشورًا فعليًا) ثم نبحث عن الأكثر شبهًا به.
            if (! empty($filters['similar_to'])) {
                $similar = $this->search->similar((int) $filters['similar_to'], (int) $this->settings->get('ai_max_results', 6));
                if ($similar !== []) {
                    $this->conversations->addUserMessage($conversation, $message, $filters);
                    $this->logging->record(
                        $conversation, $user?->id, 'similar', $filters,
                        [['tool' => 'search_properties', 'ok' => true]],
                        count($similar), 'ok', (int) (microtime(true) * 1000) - $started, 0,
                    );
                    $reply = $this->replies->similarReply($similar);

                    return $this->payload($conversation, $reply, 'ok', $similar, null, $filters);
                }
                // العقار المرجعي غير موجود/غير منشور → نكمل كبحث عادي بلا اختراع.
                unset($filters['similar_to']);
            }

            // 4) البحث الفعلي في قاعدة البيانات (المصدر الوحيد للحقيقة).
            $searchStarted = (int) (microtime(true) * 1000);
            $results = $this->search->search($filters);
            $searchMs = (int) (microtime(true) * 1000) - $searchStarted;

            $toolCalls[] = ['tool' => 'search_properties', 'ok' => true];

            $this->conversations->addUserMessage($conversation, $message, $filters);

            // 5) لا نتائج → إما سؤال متابعة أو رد بعدم توفر.
            if ($results['total'] === 0) {
                $reply = $this->noResultsReply($filters, $conversation);
                $this->finish($conversation, $user, AiRequestStatus::Ok->value, $intent, $filters, $toolCalls, 0, $started, $searchMs, null, $reply, []);

                return $this->payload($conversation, $reply, 'ok', [], null, $filters);
            }

            // 6) بناء الرد الطبيعي من النتائج الحقيقية فقط (محرك حتمي — بلا نموذج).
            $reply = $this->replies->summaryReply($message, $results['items'], $filters, $history);

            $assistantMessage = $this->conversations->addAssistantMessage(
                $conversation,
                $reply,
                array_map(fn ($item) => (int) $item['property_id'], $results['items']),
            );

            $this->finish($conversation, $user, AiRequestStatus::Ok->value, $intent, $filters, $toolCalls, count($results['items']), $started, $searchMs, null, $reply, []);

            return [
                ...$this->payload($conversation, $reply, 'ok', $results['items'], null, $filters),
                'message_id' => $assistantMessage->id,
            ];
        } catch (Throwable $e) {
            report($e);

            // Fallback لطيف — التطبيق يبقى صالحًا بدون AI.
            try {
                $this->logging->record(
                    $conversation, $user?->id, $intent, $filters, $toolCalls, 0,
                    AiRequestStatus::Error->value, (int) (microtime(true) * 1000) - $started, $searchMs, 0, class_basename($e),
                );

                return $this->payload($conversation, 'حدث خلل مؤقت أثناء معالجة طلبك. جرّب مرة أخرى بعد لحظات.', 'error', [], 'ai_unavailable', $filters);
            } catch (Throwable) {
                return [
                    'reply' => 'حدث خلل مؤقت أثناء معالجة طلبك. جرّب مرة أخرى بعد لحظات.',
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
        $started = (int) (microtime(true) * 1000);
        $filters = collect($filters)->only([
            'transaction_type', 'property_type', 'city', 'district', 'neighborhood',
            'bedrooms_min', 'bedrooms_max', 'bathrooms_min', 'min_price', 'max_price',
            'min_area', 'max_area', 'furnished', 'is_new', 'sort', 'q',
        ])->filter(fn ($v) => $v !== null && $v !== '')->all();

        try {
            $results = $this->search->search($filters);
            $this->logging->record(null, $user?->id, 'search', $filters, null, count($results['items']), AiRequestStatus::Ok->value, (int) (microtime(true) * 1000) - $started);

            return ['status' => 'ok', 'filters' => $filters, 'total' => $results['total'], 'properties' => $results['items']];
        } catch (Throwable $e) {
            report($e);

            return ['status' => 'error', 'filters' => $filters, 'total' => 0, 'properties' => [], 'error' => 'search_failed'];
        }
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

    private function noResultsReply(array $filters, AiConversation $conversation): string
    {
        $maxFollowups = (int) config('ai.limits.max_followups', 3);

        // سؤال متابعة ذكي فقط إذا كانت المعلومات الأساسية ناقصة ولم نتجاوز الحد.
        if ($this->settings->get('ai_allow_followups', true)
            && $this->conversations->consecutiveFollowUps($conversation) < $maxFollowups) {
            if (empty($filters['transaction_type'])) {
                return 'هل تريد شراء العقار أم استئجاره؟ هذا يساعدني في عرض الأنسب لك.';
            }
            if (empty($filters['city'])) {
                return 'لم أجد نتائج مطابقة بعد. في أي مدينة تبحث؟ (صنعاء، عدن، تعز...)';
            }
        }

        return $this->replies->noResultsReply($filters);
    }

    private function outOfScopeReply(): string
    {
        return (string) $this->settings->get('ai_out_of_scope_response', self::OUT_OF_SCOPE_REPLY);
    }

    private function finish(
        AiConversation $conversation,
        ?User $user,
        string $status,
        string $intent,
        array $filters,
        array $toolCalls,
        int $results,
        int $started,
        int $searchMs,
        ?string $errorCode,
        string $reply,
        array $propertyIds,
    ): void {
        $latency = (int) (microtime(true) * 1000) - $started;

        $this->logging->record(
            $conversation, $user?->id, $intent, $filters, $toolCalls, $results,
            $status, $latency, $searchMs, 0, $errorCode,
        );
    }

    private function payload(AiConversation $conversation, string $reply, string $status, array $properties, ?string $errorCode, array $filters = []): array
    {
        return [
            'reply' => $reply,
            'status' => $status,
            'error' => $errorCode,
            'conversation_id' => $conversation->id,
            'session_token' => $conversation->session_token,
            'properties' => $properties,
            'filters' => $filters,
        ];
    }
}
