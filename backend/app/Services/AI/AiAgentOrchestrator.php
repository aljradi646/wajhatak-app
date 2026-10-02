<?php

namespace App\Services\AI;

use App\Enums\ViewingRequestStatus;
use App\Models\AiConversation;
use App\Models\Property;
use App\Models\User;
use App\Models\ViewingRequest;

/**
 * المنسق الرئيسي لمنظومة الذكاء الاصطناعي AI Agent Orchestrator
 */
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
    ) {}

    /**
     * معالجة مدخلات المستخدم من خلال دورة حياة الـ AI Agent الكاملة.
     */
    public function process(
        ?User $user,
        string $message,
        AiConversation $conversation,
        string $locale = 'ar',
        array $clientContext = []
    ): array {
        // 1. فحص حواجز الأمان والحماية ضد Prompt Injection
        $guardResult = $this->guardrails->inspect($message);
        if (!empty($guardResult['blocked'])) {
            $reason = $guardResult['reason'] ?? 'blocked';
            $reply = $reason === 'out_of_domain'
                ? 'أنا مساعد وجهتك الذكي، ومتخصص في عقارات المنصة وخدماتها فقط.'
                : 'عذرًا، لا أستطيع المساعدة في هذا الطلب.';

            $this->conversationService->addUserMessage($conversation, $message, []);

            return [
                'reply' => $reply,
                'status' => 'blocked',
                'properties' => [],
                'filters' => [],
                'tool_calls' => [],
                'intent' => 'blocked',
                'failed_stage' => 'guard_' . $reason,
            ];
        }

        // 2. التحقق من الحوار اليومي / التحية
        $smallTalk = AiChatIntentDetector::detectSmallTalk($message);
        if ($smallTalk !== null) {
            $reply = $this->replyEngine->smallTalkReply($message, $smallTalk);
            $this->conversationService->addUserMessage($conversation, $message, []);

            return [
                'reply' => $reply,
                'status' => 'ok',
                'properties' => [],
                'filters' => [],
                'tool_calls' => [],
                'intent' => 'small_talk',
            ];
        }

        // 3. طلب تفاصيل عن عقار محدد
        $detailsTarget = AiChatIntentDetector::detectDetailsTarget($message);
        if ($detailsTarget !== null) {
            $reply = $this->replyEngine->detailsReply($detailsTarget);
            $item = $this->searchService->details($detailsTarget);
            $this->conversationService->addUserMessage($conversation, $message, ['last_property_id' => $detailsTarget]);

            return [
                'reply' => $reply,
                'status' => 'ok',
                'properties' => $item ? [$item] : [],
                'filters' => ['last_property_id' => $detailsTarget],
                'tool_calls' => [['tool' => 'get_property_details', 'ok' => true]],
                'intent' => 'details',
            ];
        }

        // 4. طلب معاينة
        if (preg_match('/(معاينة|حجز|احجز|موعد|أريد\s*معاينة)/u', $message)) {
            preg_match('/(\d+)/u', $message, $m);
            $selectedPropertyId = isset($m[1]) ? (int) $m[1] : ($conversation->messages()->whereNotNull('property_ids')->latest('id')->first()?->property_ids[0] ?? null);

            if ($selectedPropertyId) {
                if (!$user) {
                    $this->conversationService->addUserMessage($conversation, $message, []);
                    return [
                        'reply' => 'لطلب معاينة هذا العقار، يرجى تسجيل الدخول إلى حسابك أولاً.',
                        'status' => 'ok',
                        'properties' => [],
                        'filters' => [],
                        'tool_calls' => [],
                        'intent' => 'viewing_request',
                    ];
                }

                preg_match('/(\d{4}-\d{2}-\d{2})/u', $message, $dateMatch);
                $requestedDate = $dateMatch[1] ?? now()->addDay()->toDateString();
                $property = Property::query()->find($selectedPropertyId);

                if ($property) {
                    ViewingRequest::query()->create([
                        'client_id' => $user->id,
                        'agent_id' => $property->agent_id,
                        'property_id' => $property->id,
                        'scheduled_date' => $requestedDate,
                        'scheduled_time' => '10:00:00',
                        'notes' => 'طلب معاينة من خلال المساعد الذكي',
                        'status' => ViewingRequestStatus::Pending,
                    ]);

                    $this->conversationService->addUserMessage($conversation, $message, ['property_id' => $selectedPropertyId]);

                    return [
                        'reply' => 'تم إرسال طلب المعاينة بنجاح!',
                        'status' => 'ok',
                        'properties' => [$selectedPropertyId],
                        'filters' => ['property_id' => $selectedPropertyId],
                        'tool_calls' => [['tool' => 'create_viewing_request', 'ok' => true]],
                        'intent' => 'viewing_request',
                    ];
                }
            }
        }

        // 5. استرجاع ذاكرة وسياق المستخدم التاريخي والتراكمي
        $userMemories = $this->memoryService->getMemories($user, $message);
        $permissions = $this->permissionGuard->getUserPermissions($user);
        $history = $this->conversationService->historyFor($conversation);
        $previous = $this->conversationService->accumulatedFilters($conversation);

        // 6. تحليل النية واستخراج الكيانات والأداة المطلوبة
        $parsedIntent = $this->intentService->parse($message, $history, $previous);
        $intent = $parsedIntent['intent'] ?? 'search';
        $filters = $parsedIntent['filters'] ?? [];

        // دمقرطة إحداثيات الموقع الحقيقي القادمة من العميل
        if (isset($clientContext['latitude'], $clientContext['longitude'])) {
            $filters['client_latitude'] = (float) $clientContext['latitude'];
            $filters['client_longitude'] = (float) $clientContext['longitude'];
            if (isset($clientContext['radius_km'])) {
                $filters['radius_km'] = (float) $clientContext['radius_km'];
            }
        }

        // حفظ رسالة المستخدم مع المعايير المستخرجة
        $this->conversationService->addUserMessage($conversation, $message, $filters);

        // 7. إذا لم توجد معايير بحث واضحة (رسالة عامة/مبهمة) -> سؤال توضيح
        if (!$this->hasSearchCriteria($filters)) {
            $maxFollowUps = (int) config('ai.limits.max_followups', 2);
            $followUps = $this->conversationService->consecutiveFollowUps($conversation);
            $reply = $this->replyEngine->clarifyReply($followUps, $maxFollowUps);
            if (empty($reply)) {
                $reply = 'أخبرني ما الذي تبحث عنه: شقة أم بيت أم أرض، وفي أي مدينة وبأي ميزانية تقريبًا؟';
            }

            return [
                'reply' => $reply,
                'status' => 'ok',
                'properties' => [],
                'filters' => $filters,
                'tool_calls' => [],
                'intent' => 'clarify',
            ];
        }

        // 8. تنفيذ الأداة الحقيقية للبحث
        $toolResult = $this->toolRegistry->execute('search_properties', [
            'city' => $filters['city'] ?? null,
            'district' => $filters['district'] ?? null,
            'property_type' => $filters['property_type'] ?? null,
            'transaction_type' => $filters['transaction_type'] ?? null,
            'bedrooms' => $filters['bedrooms_min'] ?? null,
            'min_price' => $filters['min_price'] ?? null,
            'max_price' => $filters['max_price'] ?? null,
            'furnished' => $filters['furnished'] ?? null,
        ], $user);

        $properties = $toolResult['properties'] ?? [];
        $toolCallsExecuted = [['tool' => 'search_properties', 'ok' => $toolResult['success']]];

        if (count($properties) > 0) {
            $reply = $this->replyEngine->summaryReply($message, $properties, $filters, $history);
        } else {
            $reply = $this->noResultsReply($filters, $conversation);
        }

        // 9. حفظ وتحديث حالة المحادثة
        $this->stateService->updateState($conversation, $filters, count($properties) > 0 ? $properties[0] : null);

        // 10. حفظ التفضيلات طويلة الأجل
        if ($user && !empty($filters['city'])) {
            $this->memoryService->remember($user, 'preferred_city', (string) $filters['city']);
        }

        return [
            'reply' => $reply,
            'status' => 'ok',
            'properties' => $properties,
            'filters' => $filters,
            'tool_calls' => $toolCallsExecuted,
            'intent' => $intent,
            'memories_used' => array_keys($userMemories),
        ];
    }

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

        return !empty($filters['keywords']);
    }

    private function noResultsReply(array $filters, AiConversation $conversation): string
    {
        $honest = $this->replyEngine->noResultsReply($filters);

        $maxFollowUps = (int) config('ai.limits.max_followups', 2);
        $allowFollowUps = (bool) $this->settingsService->get('ai_allow_followups', true);
        $followUps = $this->conversationService->consecutiveFollowUps($conversation);

        if (!$allowFollowUps || $followUps >= $maxFollowUps) {
            return $honest;
        }

        $question = null;
        if (empty($filters['city']) && empty($filters['district'])) {
            $question = 'في أي مدينة أو منطقة تفضّل؟ وسأوسّع البحث فورًا.';
        } elseif (empty($filters['transaction_type'])) {
            $question = 'هل تفضّل البيع أم الإيجار؟ هذا يساعدني في عرض الأنسب لك.';
        } elseif (!empty($filters['max_price'])) {
            $question = 'هل تريد أن أرفع سقف الميزانية قليلًا لعرض خيارات أقرب؟';
        }

        return $question === null ? $honest : $honest . "\n" . $question;
    }
}
