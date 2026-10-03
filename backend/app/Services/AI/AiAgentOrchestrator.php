<?php

namespace AppServicesAI;

use AppModelsAiConversation;
use AppModelsUser;
use IlluminateSupportFacadesLog;
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
                'reply' => $result['success'] ?? false
                    ? ($result['message'] ?? 'تم تنفيذ العملية بنجاح.')
                    : ($result['message'] ?? 'تعذر تنفيذ العملية.'),
                'status' => $result['success'] ?? false ? 'ok' : 'error',
                'properties' => [],
                'filters' => [],
                'tool_calls' => [['tool' => $pending['tool'], 'ok' => (bool) ($result['success'] ?? false)]],
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
        $knowledge = $this->knowledgeService->searchKnowledge($message, $user?->role ?? 'client');
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
            'content' => $this->promptBuilder->system($user, $locale, $state, $memories, $knowledge),
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
                'content' => 'موقع المستخدم متاح لأداة البحث القريب. استخدمه فقط عند طلب القرب من المستخدم: latitude='
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

                $this->stateService->updateState($conversation, $filters, $properties[0] ?? null);
                if ($user && !empty($filters['city'])) {
                    $this->memoryService->remember($user, 'preferred_city', (string) $filters['city']);
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
                    $result = [
                        'success' => false,
                        'confirmation_required' => true,
                        'message' => 'يلزم تأكيد المستخدم قبل تنفيذ العملية.',
                    ];
                    $this->stateService->setPendingAction($conversation, $name, $args);
                } else {
                    $result = $this->toolRegistry->execute($name, $args, $user);

                    if ($name === 'search_properties' || $name === 'search_nearby_properties') {
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

        throw new RuntimeException('Maximum LLM tool rounds exceeded.');
    }

    private function processWithRules(
        ?User $user,
        string $message,
        AiConversation $conversation,
        string $locale,
        array $clientContext
    ): array {
        $history = $this->conversationService->historyFor($conversation);
        $previous = $this->conversationService->accumulatedFilters($conversation);
        $parsed = $this->intentService->parse($message, $history, $previous);
        $filters = $parsed['filters'] ?? [];

        $hasLocation = isset($clientContext['latitude'], $clientContext['longitude'])
            && $clientContext['latitude'] !== null
            && $clientContext['longitude'] !== null;

        if ($hasLocation && ($filters['nearby'] ?? false)) {
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
                'tool_calls' => [['tool' => 'search_nearby_properties', 'ok' => (bool) ($result['success'] ?? false)]],
                'intent' => $parsed['intent'] ?? 'nearby',
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
            'failed_stage' => 'guard_' . $reason,
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
