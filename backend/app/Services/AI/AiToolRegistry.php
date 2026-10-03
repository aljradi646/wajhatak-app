<?php

namespace App\Services\AI;

use App\Enums\ViewingRequestStatus;
use App\Models\Agent;
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
                        'city' => ['type' => 'string', 'description' => 'اسم المدينة'],
                        'district' => ['type' => 'string', 'description' => 'اسم الحي أو المنطقة'],
                        'neighborhood' => ['type' => 'string', 'description' => 'اسم الحي الفرعي أو الشارع'],
                        'property_type' => ['type' => 'string', 'description' => 'نوع العقار مثل apartment أو villa أو land'],
                        'transaction_type' => ['type' => 'string', 'description' => 'sale أو rent'],
                        'bedrooms_min' => ['type' => 'integer', 'description' => 'الحد الأدنى لعدد غرف النوم'],
                        'bedrooms_max' => ['type' => 'integer', 'description' => 'الحد الأعلى لعدد غرف النوم'],
                        'bathrooms_min' => ['type' => 'integer', 'description' => 'الحد الأدنى للحمامات'],
                        'min_price' => ['type' => 'number', 'description' => 'الحد الأدنى للسعر'],
                        'max_price' => ['type' => 'number', 'description' => 'الحد الأعلى للسعر'],
                        'min_area' => ['type' => 'number', 'description' => 'الحد الأدنى للمساحة بالمتر المربع'],
                        'max_area' => ['type' => 'number', 'description' => 'الحد الأعلى للمساحة بالمتر المربع'],
                        'furnished' => ['type' => 'boolean', 'description' => 'هل العقار مفروش'],
                        'is_new' => ['type' => 'boolean', 'description' => 'هل العقار جديد'],
                        'sort' => ['type' => 'string', 'enum' => ['relevance','price_asc','price_desc','area_desc'], 'description' => 'ترتيب النتائج'],
                        'q' => ['type' => 'string', 'description' => 'عبارات إضافية للبحث النصي'],
                    ],
                ],
            ],
            'search_similar_properties' => [
                'name' => 'search_similar_properties',
                'description' => 'العثور على عقارات مشابهة لعقار محدد',
                'parameters' => [
                    'type' => 'object',
                    'required' => ['property_id'],
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'description' => 'رقم العقار المرجعي'],
                        'limit' => ['type' => 'integer', 'description' => 'عدد النتائج من 1 إلى 6'],
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
                        'property_type' => ['type' => 'string', 'description' => 'نوع العقار اختياريًا'],
                        'transaction_type' => ['type' => 'string', 'description' => 'sale أو rent اختياريًا'],
                        'max_price' => ['type' => 'number', 'description' => 'أقصى سعر اختياريًا'],
                        'bedrooms_min' => ['type' => 'integer', 'description' => 'أقل عدد غرف اختياريًا'],
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
                'description' => 'جلب معلومات الاتصال العامة لوكيل محدد أو لوكيل عقار محدد بواسطة property_id',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'agent_id' => ['type' => 'integer', 'description' => 'معرف الوكيل'],
                        'property_id' => ['type' => 'integer', 'description' => 'معرف العقار لاستخراج وكيله'],
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
                'search_similar_properties' => $this->executeSearchSimilarProperties($arguments),
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
        foreach (['neighborhood','sort','q'] as $key) if (!empty($args[$key])) $filters[$key]=(string)$args[$key];
        foreach (['bedrooms_min','bedrooms_max','bathrooms_min'] as $key) if (isset($args[$key])) $filters[$key]=(int)$args[$key];
        foreach (['min_price','max_price','min_area','max_area'] as $key) if (isset($args[$key])) $filters[$key]=(float)$args[$key];
        foreach (['furnished','is_new'] as $key) if (isset($args[$key])) $filters[$key]=(bool)$args[$key];

        $results = $this->searchService->search($filters);

        return [
            'success' => true,
            'total' => $results['total'] ?? 0,
            'properties' => $results['items'] ?? [],
            'filters' => $filters,
        ];
    }

    private function executeSearchSimilarProperties(array $args): array
    {
        $propertyId=(int)($args['property_id']??0);
        $limit=max(1,min(6,(int)($args['limit']??4)));
        $items=$this->searchService->similar($propertyId,$limit);
        return ['success'=>true,'property_id'=>$propertyId,'properties'=>$items,'total'=>count($items)];
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
        foreach (['property_type','transaction_type'] as $key) if (!empty($args[$key])) $filters[$key]=(string)$args[$key];
        if(isset($args['max_price'])) $filters['max_price']=(float)$args['max_price'];
        if(isset($args['bedrooms_min'])) $filters['bedrooms_min']=(int)$args['bedrooms_min'];

        $results = $this->searchService->search($filters);

        return [
            'success' => true,
            'total' => $results['total'] ?? 0,
            'properties' => $results['items'] ?? [],
            'filters' => $filters,
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
        $propertyId=(int)($args['property_id']??0);
        $agentId=(int)($args['agent_id']??0);
        $agent=$propertyId>0
            ? Property::query()->with('agent.user')->find($propertyId)?->agent
            : Agent::query()->with('user')->find($agentId);

        if (!$agent) {
            return [
                'success' => false,
                'message' => 'لم يتم العثور على الوكيل المحدد.',
            ];
        }

        return [
            'success'=>true,
            'agent'=>[
                'id'=>$agent->id,
                'user_id'=>$agent->user?->id,
                'name'=>$agent->user?->name,
                'agency_name'=>$agent->agency_name,
                'job_title'=>$agent->job_title,
                'phone'=>$agent->phone,
                'whatsapp'=>$agent->whatsapp,
                'city'=>$agent->city,
                'verified'=>$agent->isApproved(),
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