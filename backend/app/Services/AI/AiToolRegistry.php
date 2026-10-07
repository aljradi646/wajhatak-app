<?php

namespace App\Services\AI;

use App\Enums\ViewingRequestStatus;
use App\Models\Property;
use App\Models\User;
use App\Models\ViewingRequest;
use Throwable;

/**
 * سجل الأدوات الحقيقي للمساعد الذكي AI Agent Tool Registry
 * يوفر التعريفات والتنفيذ الفعلي للأدوات فوق قاعدة البيانات والخدمات الحقيقية.
 */
class AiToolRegistry
{
    public function __construct(
        private readonly AiPropertySearchService $searchService,
        private readonly AiPermissionGuard $permissionGuard,
        private readonly AiMemoryService $memoryService,
        private readonly AiKnowledgeService $knowledgeService,
    ) {}

    /**
     * قائمة الأدوات المتاحة مع تعريف الشبه schema والوصف.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getToolsSchema(?User $user = null, ?array $allowedTools = null): array
    {
        $schemas = [
            'search_properties' => [
                'name' => 'search_properties',
                'description' => 'بحث حي عن العقارات المتاحة حاليًا وفق معايير محددة.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'city' => ['type' => 'string'],
                        'district' => ['type' => 'string'],
                        'property_type' => ['type' => 'string'],
                        'transaction_type' => ['type' => 'string', 'enum' => ['sale', 'rent']],
                        'bedrooms' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 20],
                        'min_price' => ['type' => 'number', 'minimum' => 0],
                        'max_price' => ['type' => 'number', 'minimum' => 0],
                        'furnished' => ['type' => 'boolean'],
                    ],
                ],
            ],
            'get_property_availability' => [
                'name' => 'get_property_availability',
                'description' => 'جلب حالة توفر العقار الحالية فقط من المصدر التشغيلي. لا يعيد بيانات خاصة.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['property_id'],
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'minimum' => 1],
                    ],
                ],
            ],
            'get_property_details' => [
                'name' => 'get_property_details',
                'description' => 'جلب سجل عقار محدد من بيانات التطبيق الحالية، بما في ذلك حالته الحالية.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['property_id'],
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'minimum' => 1],
                    ],
                ],
            ],
            'search_nearby_properties' => [
                'name' => 'search_nearby_properties',
                'description' => 'بحث حي عن العقارات المنشورة القريبة من إحداثيات المستخدم المصرح بها.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['latitude', 'longitude'],
                    'properties' => [
                        'latitude' => ['type' => 'number', 'minimum' => -90, 'maximum' => 90],
                        'longitude' => ['type' => 'number', 'minimum' => -180, 'maximum' => 180],
                        'radius_km' => ['type' => 'number', 'minimum' => 0.5, 'maximum' => 100],
                    ],
                ],
            ],
            'create_viewing_request' => [
                'name' => 'create_viewing_request',
                'description' => 'إنشاء طلب معاينة حقيقي لعقار منشور للمستخدم المسجل الدخول.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['property_id'],
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'minimum' => 1],
                        'scheduled_date' => ['type' => 'string', 'maxLength' => 10],
                        'scheduled_time' => ['type' => 'string', 'maxLength' => 8],
                        'notes' => ['type' => 'string', 'maxLength' => 500],
                        'confirmed' => ['type' => 'boolean'],
                    ],
                ],
            ],
            'find_similar_properties' => [
                'name' => 'find_similar_properties',
                'description' => 'البحث عن عقارات منشورة مشابهة لعقار حقيقي محدد.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['property_id'],
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'minimum' => 1],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 8],
                    ],
                ],
            ],
            'get_app_knowledge' => [
                'name' => 'get_app_knowledge',
                'description' => 'استرجاع معرفة منصة وجهتك الحالية فقط؛ ليست مصدرًا لبيانات العقارات التشغيلية.',
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['query'],
                    'properties' => [
                        'query' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
                    ],
                ],
            ],
        ];

        if ($allowedTools === null) {
            return $schemas;
        }

        return array_intersect_key($schemas, array_flip($allowedTools));
    }

    /**
     * الأدوات المسموح للنموذج رؤيتها حسب النية.
     *
     * @return list<string>
     */
    public function allowedToolsForIntent(string $intent): array
    {
        return match ($intent) {
            'property_search', 'search_refinement', 'search_correction' => ['search_properties'],
            'nearest_property' => ['search_nearby_properties'],
            'property_detail', 'property_price', 'property_location',
            'property_features', 'property_agent/contact' => ['get_property_details'],
            'property_availability' => ['get_property_availability'],
            'property_recommendation' => ['find_similar_properties'],
            'viewing_request' => ['create_viewing_request'],
            'platform_information', 'platform_how_to' => ['get_app_knowledge'],
            default => [],
        };
    }

    /**
     * تنفيذ أداة محددة مع التحقق الصارم من الصلاحيات والتحقق من المدخلات.
     */
    public function execute(string $toolName, array $arguments, ?User $user = null): array
    {
        $validation = $this->validateArguments($toolName, $arguments);
        if ($validation !== null) {
            return [
                'success' => false,
                'error' => 'INVALID_TOOL_ARGUMENTS',
                'message' => $validation,
            ];
        }
        // 1. التحقق من الصلاحية
        if (!$this->permissionGuard->canExecuteTool($user, $toolName, $arguments)) {
            return [
                'success' => false,
                'error' => 'PERMISSION_DENIED',
                'message' => 'عذرًا، لا تملك الصلاحية الكافية لتنفيذ هذه العملية.',
            ];
        }

        try {
            return match ($toolName) {
                'search_properties' => $this->executeSearchProperties($arguments),
                'get_property_details' => $this->executeGetPropertyDetails($user, $arguments),
                'get_property_availability' => $this->executeGetPropertyAvailability($arguments),
                'find_similar_properties' => $this->executeFindSimilarProperties($arguments),
                'search_nearby_properties' => $this->executeSearchNearbyProperties($arguments),
                'create_viewing_request' => $this->executeCreateViewingRequest($user, $arguments),
                'get_agent_info' => $this->executeGetAgentInfo($arguments),
                'get_user_profile' => $this->executeGetUserProfile($user),
                'cancel_viewing_request' => $this->executeCancelViewingRequest($user, $arguments),
                'get_app_knowledge' => $this->executeGetAppKnowledge($user, $arguments),
                default => [
                    'success' => false,
                    'error' => 'UNKNOWN_TOOL',
                    'message' => "الأداة المحددة {$toolName} غير مدعومة.",
                ],
            };
        } catch (Throwable $e) {
            report($e);
            return [
                'success' => false,
                'error' => 'EXECUTION_FAILED',
                'message' => 'تعذر تنفيذ الأداة حاليًا. تم تسجيل الخطأ داخليًا.',
            ];
        }
    }

    private function validateArguments(string $toolName, array $args): ?string
    {
        $allowed = match ($toolName) {
            'search_properties' => ['city','district','property_type','transaction_type','bedrooms','min_price','max_price','furnished'],
            'get_property_details' => ['property_id'],
            'get_property_availability' => ['property_id'],
            'find_similar_properties' => ['property_id','limit'],
            'search_nearby_properties' => ['latitude','longitude','radius_km'],
            'get_app_knowledge' => ['query'],
            'create_viewing_request' => ['property_id','scheduled_date','scheduled_time','notes','confirmed'],
            'cancel_viewing_request' => ['viewing_id','confirmed'],
            default => null,
        };

        if ($allowed !== null) {
            $unknown = array_diff(array_keys($args), $allowed);
            if ($unknown !== []) {
                return 'توجد معاملات غير مسموح بها للأداة.';
            }
        }

        if (isset($args['property_id']) && (int) $args['property_id'] <= 0) return 'معرف العقار غير صالح.';
        if (isset($args['bedrooms']) && ((int) $args['bedrooms'] < 0 || (int) $args['bedrooms'] > 20)) return 'عدد الغرف غير صالح.';
        if (isset($args['transaction_type']) && ! in_array($args['transaction_type'], ['sale','rent'], true)) return 'نوع العملية غير صالح.';
        if (isset($args['min_price']) && (float) $args['min_price'] < 0) return 'الحد الأدنى للسعر غير صالح.';
        if (isset($args['max_price']) && (float) $args['max_price'] < 0) return 'الحد الأعلى للسعر غير صالح.';
        if ($toolName === 'get_app_knowledge' && mb_strlen((string) ($args['query'] ?? '')) > 200) return 'الاستعلام طويل جدًا.';
        if (isset($args['query']) && mb_strlen((string) $args['query']) > 200) return 'الاستعلام طويل جدًا.';

        if ($toolName === 'search_nearby_properties') {
            if (! isset($args['latitude'], $args['longitude'])) return 'إحداثيات الموقع مطلوبة.';
            if ((float) $args['latitude'] < -90 || (float) $args['latitude'] > 90) return 'خط العرض غير صالح.';
            if ((float) $args['longitude'] < -180 || (float) $args['longitude'] > 180) return 'خط الطول غير صالح.';
        }

        return null;
    }

    private function executeSearchProperties(array $args): array
    {
        $filters = [];
        if (!empty($args['city'])) $filters['city'] = (string) $args['city'];
        if (!empty($args['district'])) $filters['district'] = (string) $args['district'];
        if (!empty($args['property_type'])) $filters['property_type'] = (string) $args['property_type'];
        if (!empty($args['transaction_type'])) $filters['transaction_type'] = (string) $args['transaction_type'];
        if (!empty($args['bedrooms'])) $filters['bedrooms_min'] = (int) $args['bedrooms'];
        if (!empty($args['min_price'])) $filters['min_price'] = (float) $args['min_price'];
        if (!empty($args['max_price'])) $filters['max_price'] = (float) $args['max_price'];
        if (isset($args['furnished'])) $filters['furnished'] = (bool) $args['furnished'];

        $results = $this->searchService->search($filters);

        return [
            'success' => ! ($results['degraded'] ?? false),
            'total' => $results['total'] ?? 0,
            'properties' => $results['items'] ?? [],
            'filters' => $filters,
            'result_mode' => $results['result_mode'] ?? 'exact',
            'relaxations' => $results['relaxations'] ?? [],
            'degraded' => (bool) ($results['degraded'] ?? false),
        ];
    }

    private function executeGetPropertyAvailability(array $args): array
    {
        $propertyId = (int) ($args['property_id'] ?? 0);
        $property = Property::query()->select(['id', 'status'])->find($propertyId);

        if (! $property) {
            return [
                'success' => false,
                'error' => 'PROPERTY_NOT_FOUND',
                'message' => "لم يتم العثور على عقار برقم {$propertyId}.",
            ];
        }

        $status = $property->status instanceof \BackedEnum
            ? $property->status->value
            : (string) $property->status;

        return [
            'success' => true,
            'property_id' => $propertyId,
            'status' => $status,
            'available' => $status === \App\Enums\PropertyStatus::Published->value,
            'source' => [
                'type' => 'live_property',
                'source_id' => $propertyId,
                'retrieved_at' => now()->toISOString(),
            ],
        ];
    }

    private function executeGetPropertyDetails(?User $user, array $args): array
    {
        $propertyId = (int) ($args['property_id'] ?? 0);
        $property = Property::query()->with(['agent.user'])->find($propertyId);

        if (! $property) {
            return [
                'success' => false,
                'error' => 'PROPERTY_NOT_FOUND',
                'message' => "لم يتم العثور على عقار برقم {$propertyId}.",
            ];
        }

        if (! app(\Illuminate\Contracts\Auth\Access\Gate::class)->forUser($user)->allows('view', $property)) {
            return [
                'success' => false,
                'error' => 'PROPERTY_FORBIDDEN',
                'message' => 'هذا العقار غير متاح لك وفق صلاحيات المنصة.',
            ];
        }

        $details = $this->searchService->details($propertyId);
        if (! $details) {
            return [
                'success' => false,
                'error' => 'PROPERTY_UNAVAILABLE',
                'message' => 'تعذر تحميل بيانات العقار الحالية.',
            ];
        }

        return [
            'success' => true,
            'property' => $details,
        ];
    }

    private function executeFindSimilarProperties(array $args): array
    {
        $propertyId = (int) ($args['property_id'] ?? 0);
        $limit = max(1, min(8, (int) ($args['limit'] ?? 4)));
        $properties = $this->searchService->similar($propertyId, $limit);

        return [
            'success' => true,
            'total' => count($properties),
            'properties' => $properties,
            'source' => [
                'type' => 'live_property',
                'reference_property_id' => $propertyId,
                'retrieved_at' => now()->toISOString(),
            ],
        ];
    }

    private function executeSearchNearbyProperties(array $args): array
    {
        $lat = (float) ($args['latitude'] ?? 0);
        $lng = (float) ($args['longitude'] ?? 0);
        $radius = (float) ($args['radius_km'] ?? 10.0);

        $filters = [
            'nearby' => [
                'latitude' => $lat,
                'longitude' => $lng,
                'radius_km' => $radius,
            ],
        ];

        $results = $this->searchService->search($filters);

        return [
            'success' => true,
            'total' => $results['total'] ?? 0,
            'properties' => $results['items'] ?? [],
            'radius_km' => $radius,
        ];
    }

    private function executeCreateViewingRequest(?User $user, array $args): array
    {
        if (!$user || ! $user->is_active) {
            return [
                'success' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'يلزم تسجيل الدخول بحساب نشط لإنشاء طلب معاينة.',
            ];
        }

        $propertyId = (int) ($args['property_id'] ?? 0);
        $property = Property::query()->find($propertyId);

        if (! $property) {
            return [
                'success' => false,
                'message' => 'العقار المحدد غير موجود.',
            ];
        }

        $status = $property->status instanceof \BackedEnum
            ? $property->status->value
            : (string) $property->status;

        if ($status !== \App\Enums\PropertyStatus::Published->value) {
            return [
                'success' => false,
                'error' => 'PROPERTY_NOT_DISCOVERABLE',
                'message' => 'لا يمكن طلب معاينة لعقار غير منشور حاليًا.',
            ];
        }

        $viewing = ViewingRequest::query()->create([
            'client_id' => $user->id,
            'agent_id' => $property->agent_id,
            'property_id' => $property->id,
            'scheduled_date' => $args['scheduled_date'] ?? now()->addDay()->toDateString(),
            'scheduled_time' => $args['scheduled_time'] ?? '10:00:00',
            'notes' => $args['notes'] ?? 'تم إنشاء الطلب عبر المساعد الذكي',
            'status' => ViewingRequestStatus::Pending,
        ]);

        return [
            'success' => true,
            'viewing_id' => $viewing->id,
            'message' => "تم إنشاء طلب المعاينة بنجاح للعقار #{$propertyId} بتاريخ {$viewing->scheduled_date}.",
        ];
    }

    private function executeGetAgentInfo(array $args): array
    {
        $agentId = (int) ($args['agent_id'] ?? 0);
        $agent = User::query()->where('role', 'agent')->find($agentId);

        if (!$agent) {
            return [
                'success' => false,
                'message' => 'لم يتم العثور على الوكيل المحدد.',
            ];
        }

        return [
            'success' => true,
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'phone' => $agent->phone,
                'email' => $agent->email,
            ],
        ];
    }

    private function executeGetUserProfile(?User $user): array
    {
        if (!$user) {
            return ['success' => false, 'message' => 'المستخدم غير مسجل الدخول.'];
        }

        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ];
    }

    private function executeCancelViewingRequest(?User $user,array $args): array
    {
        if(!$user) return ['success'=>false,'error'=>'UNAUTHENTICATED','message'=>'يلزم تسجيل الدخول.'];
        $id=(int)($args['viewing_id']??0);
        $viewing=ViewingRequest::query()->find($id);
        if(!$viewing||$viewing->client_id!==$user->id) return ['success'=>false,'error'=>'FORBIDDEN','message'=>'طلب المعاينة غير موجود أو لا تملكه.'];
        if(!($viewing->status?->isOpen())) return ['success'=>false,'message'=>'لا يمكن إلغاء طلب المعاينة في حالته الحالية.'];
        if(!($args['confirmed']??false)) return ['success'=>false,'confirmation_required'=>true,'message'=>'يلزم تأكيد المستخدم قبل إلغاء طلب المعاينة.'];
        $viewing->status=ViewingRequestStatus::Cancelled;
        $viewing->save();
        return ['success'=>true,'viewing_id'=>$viewing->id,'message'=>'تم إلغاء طلب المعاينة بنجاح.'];
    }

    private function executeGetAppKnowledge(?User $user, array $args): array
    {
        $query = (string) ($args['query'] ?? '');
        $knowledge = $this->knowledgeService->searchKnowledge($query, $user?->role ?? 'client');

        return [
            'success' => true,
            'results' => $knowledge,
        ];
    }
}
