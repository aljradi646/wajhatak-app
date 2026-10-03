<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiAgentOrchestrator
{
    public function __construct(
        private readonly AiIntentService $intentService,
        private readonly AiToolRegistry $toolRegistry,
        private readonly AiPermissionGuard $permissionGuard,
        private readonly AiMemoryService $memoryService,
        private readonly AiKnowledgeService $knowledgeService,
        private readonly AiConversationStateService $stateService,
        private readonly AiReplyEngine $replyEngine,
        private readonly AiGuardrailService $guardrails,
        private readonly AiPropertySearchService $searchService,
        private readonly AiConversationService $conversationService,
        private readonly AiSettingsService $settingsService,
        private readonly AiLlmClient $llm,
        private readonly AiAgentPromptBuilder $promptBuilder,
    ) {}

    public function process(
        ?User $user,
        string $message,
        AiConversation $conversation,
        string $locale = 'ar',
        array $clientContext = []
    ): array {
        $guard = $this->guardrails->inspect($message);

        if (!empty($guard['blocked'])) {
            return $this->blocked($conversation, $message, $guard['reason'] ?? 'blocked');
        }

        $pending = $this->stateService->pendingAction($conversation);
        if ($pending && $this->isConfirmation($message)) {
            $arguments = $pending['arguments'];
            $arguments['confirmed'] = true;
            $result = $this->toolRegistry->execute($pending['tool'], $arguments, $user);

            $this->stateService->setPendingAction($conversation, null);
            $this->conversationService->addUserMessage($conversation, $message, []);

            return [
                'reply' => ($result['success'] ?? false)
                    ? ($result['message'] ?? 'تم تنفيذ العملية بنجاح.')
                    : ($result['message'] ?? 'تعذر تنفيذ العملية.'),
                'status' => ($result['success'] ?? false) ? 'ok' : 'error',
                'properties' => [],
                'filters' => [],
                'tool_calls' => [[
                    'tool' => $pending['tool'],
                    'ok' => (bool) ($result['success'] ?? false),
                ]],
                'intent' => 'confirmed_action',
            ];
        }

        $this->conversationService->addUserMessage($conversation, $message, []);

        if ($this->llm->configured()) {
            try {
                return $this->processWithLlm($user, $message, $conversation, $locale, $clientContext);
            } catch (Throwable $e) {
                Log::warning('ai.llm_agent_failed_using_fallback', [
                    'exception' => class_basename($e),
                ]);

                if (!(bool) config('ai.allow_rule_fallback', true)) {
                    throw $e;
                }
            }
        }

        return $this->processWithRules($user, $message, $conversation, $locale, $clientContext);
    }

    private function processWithLlm(
        ?User $user,
        string $message,
        AiConversation $conversation,
        string $locale,
        array $clientContext
    ): array {
        $state = $this->stateService->state($conversation);
        $memories = $this->memoryService->getMemories($user);
        $knowledge = $this->knowledgeService->searchKnowledge(
            $message,
            $user?->role ?? 'client'
        );
        $history = $this->conversationService->historyFor($conversation);

        if (
            $history !== []
            && (($last = end($history))['role'] ?? null) === 'user'
            && (($last['content'] ?? null) === $message)
        ) {
            array_pop($history);
        }

        $messages = [[
            'role' => 'system',
            'content' => $this->promptBuilder->system(
                $user,
                $locale,
                $state,
                $memories,
                $knowledge
            ),
        ]];

        foreach ($history as $item) {
            $messages[] = [
                'role' => $item['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $item['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        if (
            isset($clientContext['latitude'], $clientContext['longitude'])
            && $clientContext['latitude'] !== null
            && $clientContext['longitude'] !== null
        ) {
            $messages[] = [
                'role' => 'system',
                'content' => 'موقع المستخدم متاح لأداة البحث القريب فقط: latitude='
                    . (float) $clientContext['latitude']
                    . ', longitude=' . (float) $clientContext['longitude']
                    . ', radius_km=' . (float) ($clientContext['radius_km'] ?? 10) . '.',
            ];
        }

        $tools = $this->openAiTools($user);
        $toolCalls = [];
        $properties = [];
        $filters = [];
        $rounds = 0;

        while ($rounds++ < (int) config('ai.llm.max_tool_rounds', 5)) {
            $response = $this->llm->chat($messages, $tools);
            $assistant = $response['message'];
            $messages[] = $assistant;
            $calls = $assistant['tool_calls'] ?? [];

            if ($calls === []) {
                $reply = trim((string) ($assistant['content'] ?? ''));
                if ($reply === '') {
                    $reply = 'لم أتمكن من صياغة رد مفيد على الطلب.';
                }

                $this->stateService->updateState(
                    $conversation,
                    $filters,
                    $properties[0] ?? null
                );

                if ($user && !empty($filters['city'])) {
                    $this->memoryService->remember(
                        $user,
                        'preferred_city',
                        (string) $filters['city']
                    );
                }

                return [
                    'reply' => $reply,
                    'status' => 'ok',
                    'properties' => $properties,
                    'filters' => $filters,
                    'tool_calls' => $toolCalls,
                    'intent' => 'llm_agent',
                    'memories_used' => array_keys($memories),
                ];
            }

            foreach ($calls as $call) {
                $name = (string) data_get($call, 'function.name');
                $raw = (string) data_get($call, 'function.arguments', '{}');
                $args = json_decode($raw, true);
                $args = is_array($args) ? $args : [];

                $allowedToolNames = array_keys($this->toolRegistry->getToolsSchema($user));

                if (!in_array($name, $allowedToolNames, true)) {
                    $result = [
                        'success' => false,
                        'error' => 'UNKNOWN_TOOL',
                        'message' => 'الأداة المطلوبة غير متاحة.',
                    ];
                } elseif (
                    in_array($name, ['create_viewing_request', 'cancel_viewing_request'], true)
                    && !($args['confirmed'] ?? false)
                ) {
                    $this->stateService->setPendingAction($conversation, $name, $args);
                    return [
                        'reply' => $this->confirmationPrompt($name, $args),
                        'status' => 'ok',
                        'properties' => [],
                        'filters' => $args['property_id'] ?? null ? ['property_id' => (int) $args['property_id']] : [],
                        'tool_calls' => [[
                            'tool' => $name,
                            'ok' => false,
                            'confirmation_required' => true,
                        ]],
                        'intent' => 'confirmation_required',
                    ];
                } else {
                    $result = $this->toolRegistry->execute($name, $args, $user);

                    if (in_array($name, [
                        'search_properties',
                        'search_nearby_properties',
                        'search_similar_properties',
                    ], true)) {
                        $properties = $result['properties'] ?? [];
                        $filters = array_merge($filters, $result['filters'] ?? []);
                    } elseif (
                        $name === 'get_property_details'
                        && isset($result['property'])
                        && is_array($result['property'])
                    ) {
                        $properties = [$result['property']];
                        $filters['last_property_id'] = $result['property']['property_id'] ?? null;
                    }
                }

                $toolCalls[] = [
                    'tool' => $name,
                    'ok' => (bool) ($result['success'] ?? false),
                    'confirmation_required' => (bool) ($result['confirmation_required'] ?? false),
                ];

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) ($call['id'] ?? uniqid('tool_', false)),
                    'name' => $name,
                    'content' => json_encode(
                        $result,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                ];
            }
        }

        throw new \RuntimeException('Maximum LLM tool rounds exceeded.');
    }

    private function processWithRules(
        ?User $user,
        string $message,
        AiConversation $conversation,
        string $locale,
        array $clientContext
    ): array {
        $smallTalk = AiChatIntentDetector::detectSmallTalk($message);
        if ($smallTalk !== null) {
            return [
                'reply' => $this->replyEngine->smallTalkReply($message, $smallTalk),
                'status' => 'ok',
                'properties' => [],
                'filters' => [],
                'tool_calls' => [],
                'intent' => 'small_talk',
            ];
        }

        $detailsTarget = AiChatIntentDetector::detectDetailsTarget($message);
        if ($detailsTarget !== null) {
            $item = $this->searchService->details($detailsTarget);

            if (!$item) {
                return [
                    'reply' => 'لم أجد عقارًا منشورًا بهذا الرقم.',
                    'status' => 'ok',
                    'properties' => [],
                    'filters' => [],
                    'tool_calls' => [[
                        'tool' => 'get_property_details',
                        'ok' => false,
                    ]],
                    'intent' => 'details',
                ];
            }

            $this->stateService->updateState($conversation, [
                'last_property_id' => $detailsTarget,
            ], $item);

            return [
                'reply' => $this->replyEngine->detailsReply($detailsTarget) ?? 'تعذر تحميل تفاصيل العقار.',
                'status' => 'ok',
                'properties' => [$item],
                'filters' => ['last_property_id' => $detailsTarget],
                'tool_calls' => [['tool' => 'get_property_details', 'ok' => true]],
                'intent' => 'details',
            ];
        }

        $history = $this->conversationService->historyFor($conversation);
        $previous = $this->conversationService->accumulatedFilters($conversation);
        $parsed = $this->intentService->parse($message, $history, $previous);
        $filters = $parsed['filters'] ?? [];

        if (!empty($filters['similar_to'])) {
            $items = $this->searchService->similar((int) $filters['similar_to'], 6);
            $this->stateService->updateState(
                $conversation,
                ['similar_to' => (int) $filters['similar_to']],
                $items[0] ?? null
            );

            return [
                'reply' => $this->replyEngine->similarReply($items),
                'status' => 'ok',
                'properties' => $items,
                'filters' => $filters,
                'tool_calls' => [['tool' => 'search_similar_properties', 'ok' => true]],
                'intent' => 'similar',
            ];
        }

        $hasLocation = isset($clientContext['latitude'], $clientContext['longitude'])
            && $clientContext['latitude'] !== null
            && $clientContext['longitude'] !== null;

        if ($hasLocation && AiChatIntentDetector::wantsNearby($message)) {
            $result = $this->toolRegistry->execute('search_nearby_properties', [
                'latitude' => (float) $clientContext['latitude'],
                'longitude' => (float) $clientContext['longitude'],
                'radius_km' => (float) ($clientContext['radius_km'] ?? 10),
                'property_type' => $filters['property_type'] ?? null,
                'transaction_type' => $filters['transaction_type'] ?? null,
                'max_price' => $filters['max_price'] ?? null,
                'bedrooms_min' => $filters['bedrooms_min'] ?? null,
            ], $user);

            $properties = $result['properties'] ?? [];
            $this->stateService->updateState($conversation, $filters, $properties[0] ?? null);

            return [
                'reply' => $properties !== []
                    ? $this->replyEngine->summaryReply($message, $properties, $filters, $history)
                    : $this->replyEngine->noResultsReply($filters),
                'status' => 'ok',
                'properties' => $properties,
                'filters' => $filters,
                'tool_calls' => [[
                    'tool' => 'search_nearby_properties',
                    'ok' => (bool) ($result['success'] ?? false),
                ]],
                'intent' => 'nearby',
            ];
        }

        if (preg_match('/(?:معاينه|معاينة|حجز|احجز|موعد|زيارة)/u', $message)) {
            $propertyId = null;

            if (preg_match('/(?:عقار|شقه|فيلا|بيت|ارض|محل)?\s*(?:رقم|#)?\s*(\d{1,10})/u', $message, $m)) {
                $propertyId = (int) $m[1];
            }

            $pendingProperty = $conversation->messages()
                ->whereNotNull('structured_filters')
                ->latest('id')
                ->first()?->structured_filters['last_property_id'] ?? null;

            $propertyId ??= $pendingProperty;

            if (!$user) {
                return [
                    'reply' => 'لطلب المعاينة، سجّل الدخول إلى حسابك أولًا.',
                    'status' => 'ok',
                    'properties' => [],
                    'filters' => [],
                    'tool_calls' => [],
                    'intent' => 'viewing_request',
                ];
            }

            if (!$propertyId) {
                return [
                    'reply' => 'حدّد رقم العقار الذي تريد معاينته أولًا.',
                    'status' => 'ok',
                    'properties' => [],
                    'filters' => [],
                    'tool_calls' => [],
                    'intent' => 'viewing_request',
                ];
            }

            $date = null;
            if (preg_match('/(\d{4}-\d{2}-\d{2})/u', $message, $dateMatch)) {
                $date = $dateMatch[1];
            }

            $args = [
                'property_id' => $propertyId,
                'scheduled_date' => $date ?? now()->addDay()->toDateString(),
                'scheduled_time' => '10:00:00',
                'notes' => 'طلب معاينة من خلال المساعد الذكي',
            ];

            $this->stateService->setPendingAction(
                $conversation,
                'create_viewing_request',
                $args
            );

            return [
                'reply' => 'سأرسل طلب معاينة للعقار #'.$propertyId.' بتاريخ '.$args['scheduled_date'].' الساعة '.$args['scheduled_time'].'. هل تؤكد؟',
                'status' => 'ok',
                'properties' => [],
                'filters' => ['property_id' => $propertyId],
                'tool_calls' => [['tool' => 'create_viewing_request', 'ok' => false, 'confirmation_required' => true]],
                'intent' => 'viewing_request_confirmation',
            ];
        }

        if (!$this->hasSearchCriteria($filters)) {
            $reply = $this->replyEngine->clarifyReply(
                $this->conversationService->consecutiveFollowUps($conversation),
                (int) config('ai.limits.max_followups', 2)
            );

            return [
                'reply' => $reply ?: 'أخبرني ما الذي تبحث عنه: شقة أم بيت أم أرض، وفي أي مدينة وبأي ميزانية تقريبًا؟',
                'status' => 'ok',
                'properties' => [],
                'filters' => $filters,
                'tool_calls' => [],
                'intent' => 'clarify',
            ];
        }

        $result = $this->toolRegistry->execute('search_properties', [
            'city' => $filters['city'] ?? null,
            'district' => $filters['district'] ?? null,
            'neighborhood' => $filters['neighborhood'] ?? null,
            'property_type' => $filters['property_type'] ?? null,
            'transaction_type' => $filters['transaction_type'] ?? null,
            'bedrooms_min' => $filters['bedrooms_min'] ?? null,
            'bedrooms_max' => $filters['bedrooms_max'] ?? null,
            'bathrooms_min' => $filters['bathrooms_min'] ?? null,
            'min_price' => $filters['min_price'] ?? null,
            'max_price' => $filters['max_price'] ?? null,
            'min_area' => $filters['min_area'] ?? null,
            'max_area' => $filters['max_area'] ?? null,
            'furnished' => $filters['furnished'] ?? null,
            'is_new' => $filters['is_new'] ?? null,
            'sort' => $filters['sort'] ?? null,
            'q' => $filters['q'] ?? null,
        ], $user);

        $properties = $result['properties'] ?? [];
        $this->stateService->updateState($conversation, $filters, $properties[0] ?? null);

        if ($user && !empty($filters['city'])) {
            $this->memoryService->remember($user, 'preferred_city', (string) $filters['city']);
        }

        return [
            'reply' => $properties !== []
                ? $this->replyEngine->summaryReply($message, $properties, $filters, $history)
                : $this->replyEngine->noResultsReply($filters),
            'status' => 'ok',
            'properties' => $properties,
            'filters' => $filters,
            'tool_calls' => [['tool' => 'search_properties', 'ok' => (bool) ($result['success'] ?? false)]],
            'intent' => $parsed['intent'] ?? 'search',
        ];
    }

    private function confirmationPrompt(string $tool, array $args): string
    {
        return match ($tool) {
            'create_viewing_request' => 'سأرسل طلب معاينة للعقار #'.((int) ($args['property_id'] ?? 0))
                .' بتاريخ '.((string) ($args['scheduled_date'] ?? now()->addDay()->toDateString()))
                .' الساعة '.((string) ($args['scheduled_time'] ?? '10:00')).'. هل تؤكد؟',
            'cancel_viewing_request' => 'سألغي طلب المعاينة #'.((int) ($args['viewing_id'] ?? 0)).'. هل تؤكد؟',
            default => 'هذه العملية تحتاج تأكيدك. هل تؤكد؟',
        };
    }

    private function openAiTools(?User $user): array
    {
        $tools = [];
        foreach ($this->toolRegistry->getToolsSchema($user) as $name => $schema) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'description' => $schema['description'],
                    'parameters' => $schema['parameters'],
                ],
            ];
        }

        return $tools;
    }

    private function blocked(
        AiConversation $conversation,
        string $message,
        string $reason
    ): array {
        $this->conversationService->addUserMessage($conversation, $message, []);

        return [
            'reply' => $reason === 'out_of_domain'
                ? 'أنا مساعد وجهتك الذكي، ومتخصص في عقارات المنصة وخدماتها فقط.'
                : 'عذرًا، لا أستطيع المساعدة في هذا الطلب.',
            'status' => 'blocked',
            'properties' => [],
            'filters' => [],
            'tool_calls' => [],
            'intent' => 'blocked',
            'failed_stage' => 'guard_'.$reason,
        ];
    }

    private function isConfirmation(string $message): bool
    {
        return (bool) preg_match(
            '/^(?:نعم|نعم موافق|موافق|أوافق|بالتأكيد|أكيد|اكيد|أكيد موافق|ايوه|أيوه|ايوا|نعم، موافق|confirm|yes|ok|okay)$/iu',
            trim($message)
        );
    }

    private function hasSearchCriteria(array $filters): bool
    {
        foreach ([
            'transaction_type','property_type','city','district','neighborhood',
            'min_price','max_price','min_area','max_area','bedrooms_min',
            'bedrooms_max','bathrooms_min','furnished','is_new','sort',
            'similar_to','nearby','q',
        ] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== null) {
                return true;
            }
        }

        return !empty($filters['keywords']);
    }
}
