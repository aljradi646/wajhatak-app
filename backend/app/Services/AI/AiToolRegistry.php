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
    public function getToolsSchema(?User $user = null): array
    {
        return [
            'search_properties' => [
                'name' => 'search_properties',
                'description' => 'البحث عن عقارات في قاعدة البيانات وفق معايير محددة',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'city' => ['type' => 'string', 'description' => 'اسم المدينة (مثل صنعاء، عدن)'],
                        'district' => ['type' => 'string', 'description' => 'اسم الحي/المنطقة (مثل حدة، الروضة)'],
                        'property_type' => ['type' => 'string', 'description' => 'نوع العقار (apartment, villa, land, office, house)'],
                        'transaction_type' => ['type' => 'string', 'description' => 'نوع العملية (rent, sale)'],
                        'bedrooms' => ['type' => 'integer', 'description' => 'عدد الغرف'],
                        'min_price' => ['type' => 'number', 'description' => 'الحد الأدنى للسعر'],
                        'max_price' => ['type' => 'number', 'description' => 'الحد الأقصى للسعر'],
                        'furnished' => ['type' => 'boolean', 'description' => 'مفروشة أم لا'],
                    ],
                ],
            ],
            'get_property_details' => [
                'name' => 'get_property_details',
                'description' => 'جلب تفاصيل عقار محدد برقم ID الحقيقي',
                'parameters' => [
                    'type' => 'object',
                    'required' => ['property_id'],
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'description' => 'رقم ID العقار'],
                    ],
                ],
            ],
            'search_nearby_properties' => [
                'name' => 'search_nearby_properties',
                'description' => 'البحث عن عقارات قريبة برقم الإحداثيات الجغرافية الحقيقية للمستخدم',
                'parameters' => [
                    'type' => 'object',
                    'required' => ['latitude', 'longitude'],
                    'properties' => [
                        'latitude' => ['type' => 'number', 'description' => 'خط العرض'],
                        'longitude' => ['type' => 'number', 'description' => 'خط الطول'],
                        'radius_km' => ['type' => 'number', 'description' => 'نصف قطر البحث بالكم'],
                    ],
                ],
            ],
            'create_viewing_request' => [
                'name' => 'create_viewing_request',
                'description' => 'إنشاء طلب معاينة عقار محدد',
                'parameters' => [
                    'type' => 'object',
                    'required' => ['property_id', 'scheduled_date'],
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'description' => 'رقم العقار'],
                        'scheduled_date' => ['type' => 'string', 'description' => 'تاريخ المعاينة YYYY-MM-DD'],
                        'scheduled_time' => ['type' => 'string', 'description' => 'وقت المعاينة HH:MM:SS'],
                        'notes' => ['type' => 'string', 'description' => 'ملاحظات المعاينة'],
                    ],
                ],
            ],
            'get_agent_info' => [
                'name' => 'get_agent_info',
                'description' => 'جلب معلومات وكيل عقاري أو مالك عقار',
                'parameters' => [
                    'type' => 'object',
                    'required' => ['agent_id'],
                    'properties' => [
                        'agent_id' => ['type' => 'integer', 'description' => 'رقم الوكيل'],
                    ],
                ],
            ],
            'get_user_profile' => [
                'name' => 'get_user_profile',
                'description' => 'جلب معلومات الملف الشخصي للمستخدم الحالي',
                'parameters' => ['type' => 'object', 'properties' => []],
            ],
            'cancel_viewing_request' => [
                'name' => 'cancel_viewing_request',
                'description' => 'إلغاء طلب معاينة يملكه المستخدم الحالي بعد تأكيده',
                'parameters' => [
                    'type'=>'object',
                    'required'=>['viewing_id'],
                    'properties'=>['viewing_id'=>['type'=>'integer','description'=>'رقم طلب المعاينة']],
                ],
            ],
            'get_app_knowledge' => [
                'name' => 'get_app_knowledge',
                'description' => 'الاستعلام عن كيفية استخدام التطبيق وصفحاته وسياساته',
                'parameters' => [
                    'type' => 'object',
                    'required' => ['query'],
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'سؤال أو كلمة دلالية عن التطبيق'],
                    ],
                ],
            ],
        ];
    }

    /**
     * تنفيذ أداة محددة مع التحقق الصارم من الصلاحيات والتحقق من المدخلات.
     */
    public function execute(string $toolName, array $arguments, ?User $user = null): array
    {
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
                'get_property_details' => $this->executeGetPropertyDetails($arguments),
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
            'success' => true,
            'total' => $results['total'] ?? 0,
            'properties' => $results['items'] ?? [],
            'filters' => $filters,
        ];
    }

    private function executeGetPropertyDetails(array $args): array
    {
        $propertyId = (int) ($args['property_id'] ?? 0);
        $details = $this->searchService->details($propertyId);

        if (!$details) {
            return [
                'success' => false,
                'message' => "لم يتم العثور على عقار برقم {$propertyId}، أو قد لا يكون منشورًا.",
            ];
        }

        return [
            'success' => true,
            'property' => $details,
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
        if (!$user) {
            return [
                'success' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'يلزم تسجيل الدخول لإنشاء طلب معاينة.',
            ];
        }

        $propertyId = (int) ($args['property_id'] ?? 0);
        $property = Property::query()->find($propertyId);

        if (!$property) {
            return [
                'success' => false,
                'message' => 'العقار المحدد غير موجود.',
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
