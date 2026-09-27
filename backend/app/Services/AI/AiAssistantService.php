<?php

namespace App\Services\AI;

use App\Enums\AiRequestStatus;
use App\Models\AiConversation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * المنسق الرئيسي للمساعد — يربط: الحواجز ← تحليل النية ← البحث ← النموذج
 * المحلي ← التحقق الأرضي ← التسجيل. أي فشل في النموذج ينتج رد fallback
 * لطيف ويبقى التطبيق يعمل بدون AI تمامًا (متطلب إلزامي).
 *
 * المسار: Flutter → Laravel → AI Service → Local Model → Tools → DB →
 *         Grounding → Flutter. لا يتصل Flutter بخادم النموذج مطلقًا.
 */
class AiAssistantService
{
    private const OUT_OF_SCOPE_REPLY = 'أنا مساعد وجهتك، ومتخصص في مساعدتك في البحث عن العقارات واستخدام منصة وجهتك.';
    private const UNAVAILABLE_REPLY = 'المساعد غير متاح حاليًا، لكن يمكنك استخدام البحث العقاري التقليدي.';

    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiGuardrailService $guardrails,
        private readonly AiIntentService $intents,
        private readonly AiPropertySearchService $search,
        private readonly AiToolService $tools,
        private readonly AiResponseGroundingService $grounding,
        private readonly AiConversationService $conversations,
        private readonly AiPromptService $prompts,
        private readonly AiProviderManager $providers,
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

                $this->finish($conversation, $user, 'blocked', $intent, $filters, $toolCalls, 0, $started, 0, 'guard_'.$guard['reason'], $reply, []);

                return $this->payload($conversation, $reply, 'blocked', [], $guard['reason']);
            }

            // 2) تحليل النية + دمج سياق المحادثة.
            $history = $this->conversations->historyFor($conversation);
            $previous = $this->conversations->accumulatedFilters($conversation);
            $parsed = $this->intents->parse($message, $history, $previous);
            $filters = $parsed['filters'];
            $intent = $parsed['out_of_scope'] ? 'out_of_scope' : 'search';

            // 3) البحث الفعلي في قاعدة البيانات (المصدر الوحيد للحقيقة).
            $searchStarted = (int) (microtime(true) * 1000);
            $results = $this->search->search($filters);
            $searchMs = (int) (microtime(true) * 1000) - $searchStarted;

            $toolCalls[] = ['tool' => 'search_properties', 'ok' => true];

            $this->conversations->addUserMessage($conversation, $message, $filters);

            // 4) لا نتائج → إما سؤال متابعة أو رد بعدم توفر.
            if ($results['total'] === 0) {
                $reply = $this->noResultsReply($filters, $conversation);
                $this->finish($conversation, $user, AiRequestStatus::Ok->value, $intent, $filters, $toolCalls, 0, $started, $searchMs, null, $reply, []);

                return $this->payload($conversation, $reply, 'ok', [], null, $filters);
            }

            // 5) توليد الرد من النموذج المحلي مع قطع DATA موثوقة فقط.
            $modelReply = $this->generateReply($message, $history, $results['items']);

            // 6) التحقق الأرضي — إلزامي دائمًا.
            $grounded = $this->grounding->validate($modelReply, $results['items']);
            if ($grounded['violations'] !== []) {
                Log::warning('ai.grounding.violation', ['violations' => $grounded['violations']]);
            }

            $assistantMessage = $this->conversations->addAssistantMessage(
                $conversation,
                $grounded['content'],
                array_map(fn ($item) => (int) $item['property_id'], $results['items']),
            );

            $this->finish($conversation, $user, AiRequestStatus::Ok->value, $intent, $filters, $toolCalls, count($results['items']), $started, $searchMs, null, $grounded['content'], []);

            return [
                ...$this->payload($conversation, $grounded['content'], 'ok', $results['items'], null, $filters),
                'message_id' => $assistantMessage->id,
                'replaced' => $grounded['replaced'],
            ];
        } catch (Throwable $e) {
            report($e);

            // Fallback لطيف — التطبيق يبقى صالحًا بدون AI.
            try {
                $this->finish($conversation, $user, AiRequestStatus::Error->value, $intent, $filters, $toolCalls, 0, $started, $searchMs, class_basename($e), self::UNAVAILABLE_REPLY, []);

                return $this->payload($conversation, self::UNAVAILABLE_REPLY, 'error', [], 'ai_unavailable', $filters);
            } catch (Throwable) {
                return $this->payload(null, self::UNAVAILABLE_REPLY, 'error', [], 'ai_unavailable', $filters);
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

    /** استدعاء النموذج المحلي لتوليد الرد النهائي من قطع DATA فقط. */
    private function generateReply(string $message, array $history, array $candidates): string
    {
        $dataLines = [];
        foreach ($candidates as $i => $property) {
            $dataLines[] = sprintf(
                "[ID: %d] %s | %s | %s %s | %s | %.0f %s | %s | %.0f م² | غرف: %s | حمامات: %s | مفروش: %s | الحالة: %s | تطابق: %.0f%%",
                $property['property_id'],
                $property['title'],
                $property['type'] ?? '-',
                $property['transaction_type'],
                $property['city'] ?? '-',
                collect([$property['district'], $property['neighborhood']])->filter()->implode('، '),
                $property['price'] ?? 0,
                $property['currency'] ?? '',
                $property['is_furnished'] ? 'نعم' : 'لا',
                $property['area'] ?? 0,
                $property['bedrooms'] ?? 'غير محدد',
                $property['bathrooms'] ?? 'غير محدد',
                $property['status'],
                ($property['match_score'] ?? 0) * 100,
            );
        }

        $dataBlock = $this->prompts->dataBlockHeader()."\n".implode("\n", $dataLines);

        $messages = [
            ['role' => 'system', 'content' => $this->prompts->responseSystemPrompt()],
            ...$history,
            ['role' => 'user', 'content' => $message],
            ['role' => 'user', 'content' => $dataBlock],
        ];

        $response = $this->providers->provider()->chat($messages, [
            'temperature' => (float) $this->settings->get('ai_temperature', 0.3),
            'max_tokens' => (int) $this->settings->get('ai_max_tokens', 700),
        ]);

        return trim($response->content);
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

        return 'لا توجد لدي حاليًا عقارات مطابقة لطلبك في قاعدة بيانات وجهتك. جرّب تعديل الميزانية أو توسيع الموقع، أو استخدم البحث العقاري التقليدي.';
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

        // حفظ رد المساعد في المحادثة (ما عدا الرسائل المحجوبة).
    }

    private function payload(?AiConversation $conversation, string $reply, string $status, array $properties, ?string $errorCode, array $filters = []): array
    {
        return [
            'reply' => $reply,
            'status' => $status,
            'error' => $errorCode,
            'conversation_id' => $conversation?->exists ? $conversation->id : null,
            'properties' => $properties,
            'filters' => $filters,
        ];
    }
}
