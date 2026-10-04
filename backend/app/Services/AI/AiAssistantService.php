<?php

namespace App\Services\AI;

use App\Enums\AiRequestStatus;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * المنسق الرئيسي للمساعد الذكي - الإنتاجي AI Agent Service
 */
class AiAssistantService
{
    private const OUT_OF_SCOPE_REPLY = 'أنا مساعد وجهتك، ومتخصص في مساعدتك في البحث عن العقارات واستخدام منصة وجهتك.';

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
        private readonly AiAgentOrchestrator $orchestrator,
    ) {}

    /**
     * معالجة رسالة مستخدم كاملة عبر الـ Agent Orchestrator.
     *
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
            // 0) ضمان مخطط المساعد
            $stage = 'schema';
            $this->attempt($stage, fn () => $this->schema->ensure());

            // 1) إعداد أو جلب المحادثة
            $stage = 'conversation';
            $conversation = $conversation
                ?? $this->attempt($stage, fn () => $this->conversations->currentFor($user, $locale));
            if (!$conversation instanceof AiConversation) {
                $conversation = new AiConversation(['locale' => $locale, 'status' => 'active']);
            }

            // 2) تفويض العملية إلى AiAgentOrchestrator
            $stage = 'orchestrator';
            $orchestratorResult = $this->orchestrator->process($user, $message, $conversation, $locale, $clientContext);
            $contract = AiResponseContract::normalize($orchestratorResult);

            $reply = $contract['reply'] ?? 'تمت معالجة الطلب.';
            $status = $contract['status'] ?? 'ok';
            $properties = $contract['properties'] ?? [];
            $filters = $contract['filters'] ?? [];
            $toolCalls = $contract['tool_calls'] ?? [];
            $intent = $contract['intent'] ?? 'ambiguous_request';
            $failedStage = $contract['failed_stage'] ?? null;

            // 3) تسجيل واستخراج رسالة الرد
            $propertyIds = array_map(fn ($p) => (int) ($p['property_id'] ?? $p['id'] ?? 0), $properties);
            $assistantMessage = $this->out(
                $conversation, $user, $intent, $status, $filters, $toolCalls,
                count($properties), $started, $searchMs, $failedStage, $reply, $propertyIds,
                $contract['response_type'] ?? 'text',
                [
                    'actions' => $contract['actions'] ?? [],
                    'citations' => $contract['citations'] ?? [],
                    'state_updates' => $contract['state_updates'] ?? [],
                    'source' => $contract['source'] ?? [],
                ],
            );

            return [
                'reply' => $reply,
                'status' => $status,
                'error' => $failedStage,
                'conversation_id' => $conversation->exists ? $conversation->id : null,
                'session_token' => $conversation->exists ? $conversation->session_token : null,
                'properties' => $properties,
                'result_mode' => $contract['result_mode'] ?? 'exact',
                'filters' => $filters,
                'tool_calls' => $toolCalls,
                'response_type' => $contract['response_type'] ?? 'text',
                'actions' => $contract['actions'] ?? [],
                'citations' => $contract['citations'] ?? [],
                'source' => $contract['source'] ?? [],
                'ui' => $this->buildUiContract($contract['response_type'] ?? 'text', $properties),
                'failed_stage' => $failedStage,
                'message_id' => $assistantMessage?->id,
            ];
        } catch (Throwable $e) {
            $this->logFailure($stage, $e);
            $this->failedStage ??= $stage;

            try {
                $this->logging->record(
                    $conversation ?? null, $user?->id, $intent, $filters, $toolCalls, 0,
                    AiRequestStatus::Error->value, $this->ms() - $started, $searchMs, 0, $this->errorCode($stage, $e),
                );

                return [
                    'reply' => 'حدث خلل مؤقت أثناء معالجة طلبك. أعد المحاولة بعد قليل.',
                    'status' => 'error',
                    'error' => 'ai_unavailable',
                    'conversation_id' => $conversation?->id,
                    'session_token' => $conversation?->session_token,
                    'properties' => [],
                    'filters' => $filters,
                    'tool_calls' => $toolCalls,
                    'response_type' => 'error',
                    'actions' => [],
                    'citations' => [],
                    'source' => [],
                    'intent' => $intent !== 'chat' ? $intent : 'error',
                    'failed_stage' => $stage,
                ];
            } catch (Throwable) {
                return [
                    'reply' => 'حدث خلل مؤقت أثناء معالجة طلبك.',
                    'status' => 'error',
                    'error' => 'ai_unavailable',
                    'conversation_id' => null,
                    'session_token' => null,
                    'properties' => [],
                    'result_mode' => 'none',
                    'filters' => [],
                    'tool_calls' => [],
                    'response_type' => 'error',
                    'actions' => [],
                    'citations' => [],
                    'source' => [],
                    'intent' => 'error',
                    'failed_stage' => $stage,
                ];
            }
        }
    }

    /** بحث مباشر بالمعايير (POST /ai/search) */
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

            return ['status' => 'error', 'filters' => $filters, 'total' => 0, 'properties' => [], 'error' => 'search_failed'];
        }
    }

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
        string $responseType = 'text',
        array $metadata = [],
    ): ?AiMessage {
        $message = $this->attempt('persist', fn () => $this->conversations->addAssistantMessage(
            $conversation,
            $reply,
            $propertyIds,
            $status,
            $responseType,
            $metadata,
        ));

        $this->attempt('logging', fn () => $this->logging->record(
            $conversation, $user?->id, $intent, $filters, $toolCalls, $results,
            $status, $this->ms() - $started, $searchMs, 0, $errorCode ?? $this->failedStage,
            $responseType,
            is_string($metadata['fallback_reason'] ?? null) ? $metadata['fallback_reason'] : null,
            $this->knowledgeVersion($metadata),
        ));

        return $message instanceof AiMessage ? $message : null;
    }

    private function buildUiContract(string $responseType, array $properties): array
    {
        $isProperty = in_array($responseType, ['property_results', 'property_detail'], true);
        $ids = $isProperty
            ? array_values(array_filter(array_map(
                static fn (array $item): int => (int) ($item['property_id'] ?? 0),
                array_filter($properties, 'is_array'),
            )))
            : [];

        return [
            'response_component' => $isProperty && $ids !== [] ? 'property_results' : 'assistant_message',
            'property_card_component' => 'property_card',
            'property_card_click_action' => 'open_property',
            'property_ids' => $ids,
        ];
    }

    private function knowledgeVersion(array $metadata): ?string
    {
        $source = $metadata['source'] ?? [];
        return is_array($source) && isset($source['version']) ? (string) $source['version'] : null;
    }

    private function ms(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
