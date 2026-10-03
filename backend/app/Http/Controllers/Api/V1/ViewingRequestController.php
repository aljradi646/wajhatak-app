<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PropertyStatus;
use App\Enums\ViewingRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ViewingRequestResource;
use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\ViewingRequest;
use App\Notifications\ViewingRequestCreated;
use App\Notifications\ViewingRequestUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViewingRequestController extends Controller
{
    /** سجل تغييرات طلب المعاينة — نفس جدول الأنشطة المستخدم في اللوحة. */
    private const LOG_NAME = 'viewing_request';

    public function index(Request $request)
    {
        $user = $request->user();
        $query = ViewingRequest::query()->with(['property', 'client', 'agent.user']);
        if ($user->hasRole('agent')) {
            $agentId = $user->agentProfile?->id;
            $query->where('agent_id', $agentId);
        } elseif (! $user->hasRole('admin')) {
            $query->where('client_id', $user->id);
        }

        return ViewingRequestResource::collection($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ViewingRequest::class);

        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $property = Property::query()->with('agent.user')->findOrFail($data['property_id']);
        abort_unless($property->status === PropertyStatus::Published, 404);
        abort_if($property->agent->user_id === $request->user()->id, 422, 'لا يمكن طلب معاينة لعقارك الخاص.');

        $viewingRequest = DB::transaction(fn () => ViewingRequest::query()->create([
            ...$data,
            'client_id' => $request->user()->id,
            'agent_id' => $property->agent_id,
            'status' => ViewingRequestStatus::Pending,
        ]));

        $this->recordHistory($viewingRequest, $request->user()->id, 'created', null, ViewingRequestStatus::Pending->value);

        $viewingRequest->load(['client', 'property']);
        $property->agent->user->notify(new ViewingRequestCreated($viewingRequest));

        return response()->json(['data' => new ViewingRequestResource($viewingRequest->load(['property', 'client', 'agent.user']))], 201);
    }

    /** عرض طلب واحد — محمي بصلاحية العرض. */
    public function show(Request $request, ViewingRequest $viewingRequest): ViewingRequestResource
    {
        $this->authorize('view', $viewingRequest);

        return new ViewingRequestResource($viewingRequest->load(['property', 'client', 'agent.user']));
    }

    /**
     * تعديل الطلب: إما تغيير الحالة، أو تغيير الموعد/الملاحظات.
     * كل تغيير يُسجَّل في سجل التغييرات مع القيمة القديمة والجديدة.
     */
    public function update(Request $request, ViewingRequest $viewingRequest): ViewingRequestResource
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:confirmed,rejected,cancelled,completed'],
            'scheduled_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['sometimes', 'date_format:H:i'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        abort_if($data === [], 422, 'لا يوجد أي تغيير مطلوب.');

        $user = $request->user();
        $isClientOwner = $user->id === $viewingRequest->client_id;
        $isAgentOwner = $user->agentProfile?->id !== null && $user->agentProfile->id === $viewingRequest->agent_id;

        $scheduleChanged = array_key_exists('scheduled_date', $data) || array_key_exists('scheduled_time', $data)
            || array_key_exists('notes', $data);

        if ($scheduleChanged) {
            $this->authorize('reschedule', $viewingRequest);
        }

        if (array_key_exists('status', $data)) {
            $target = $data['status'];
            if (! $viewingRequest->status->canTransitionTo($target)) {
                abort(422, 'لا يمكن تغيير حالة الطلب من «'.$viewingRequest->status->value.'» إلى «'.$target.'».');
            }
            $this->authorize('updateStatus', [$viewingRequest, $target]);
        }

        $oldStatus = $viewingRequest->status->value;
        $oldSchedule = $viewingRequest->scheduled_date?->toDateString().' '.$viewingRequest->scheduled_time;

        DB::transaction(function () use ($viewingRequest, $data, $user, $oldStatus, $oldSchedule, $scheduleChanged): void {
            $viewingRequest->update($data);

            if (array_key_exists('status', $data)) {
                $this->recordHistory($viewingRequest, $user->id, 'status_changed', $oldStatus, $data['status']);
            }

            if ($scheduleChanged) {
                $newSchedule = $viewingRequest->scheduled_date?->toDateString().' '.$viewingRequest->scheduled_time;
                if ($newSchedule !== $oldSchedule) {
                    $this->recordHistory($viewingRequest, $user->id, 'rescheduled', $oldSchedule, $newSchedule);
                }
            }
        });

        // إشعار الطرف الآخر فقط: العميل عند تغيير الوكيل/المشرف للحالة أو الموعد،
        // والعكس عند تغيير العميل.
        $fresh = $viewingRequest->fresh()->load(['client', 'property', 'agent.user']);
        if ($isClientOwner) {
            $fresh->agent->user->notify(new ViewingRequestUpdated($fresh));
        } elseif ($isAgentOwner || $user->hasRole('admin')) {
            $fresh->client->notify(new ViewingRequestUpdated($fresh));
        }

        return new ViewingRequestResource($fresh);
    }

    /** حذف الطلب وفق قواعد النظام (المشرف دائمًا، والعميل لطلبه غير النشط). */
    public function destroy(Request $request, ViewingRequest $viewingRequest): JsonResponse
    {
        $this->authorize('delete', $viewingRequest);

        $this->recordHistory($viewingRequest, $request->user()->id, 'deleted', $viewingRequest->status->value, null);
        $viewingRequest->delete();

        return response()->json(['message' => 'تم حذف طلب المعاينة.']);
    }

    /** سجل تغييرات الطلب — يظهر الحالة والمواعيد القديمة والجديدة. */
    public function history(Request $request, ViewingRequest $viewingRequest): JsonResponse
    {
        $this->authorize('viewHistory', $viewingRequest);

        $entries = ActivityLog::query()
            ->where('log_name', self::LOG_NAME)
            ->where('subject_type', ViewingRequest::class)
            ->where('subject_id', $viewingRequest->id)
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                // مفتاح ثابت للبرمجة + وصف عربي جاهز للعرض.
                'action' => $log->properties_array['action'] ?? $log->log_name,
                'label' => $log->description,
                'from' => $log->properties_array['from'] ?? null,
                'to' => $log->properties_array['to'] ?? null,
                'by' => $log->user ? ['id' => $log->user->id, 'name' => $log->user->name] : null,
                'at' => $log->created_at?->toISOString(),
            ]);

        return response()->json(['data' => $entries]);
    }

    /** تسجيل تغيير في سجل الطلب (بيانات حقيقية قابلة للعرض لاحقًا). */
    private function recordHistory(ViewingRequest $viewingRequest, ?int $userId, string $action, ?string $from, ?string $to): void
    {
        $labels = [
            'created' => 'تم إنشاء الطلب',
            'status_changed' => 'تم تغيير حالة الطلب',
            'rescheduled' => 'تم تغيير موعد المعاينة',
            'deleted' => 'تم حذف الطلب',
        ];

        ActivityLog::record(
            self::LOG_NAME,
            $labels[$action] ?? $action,
            $viewingRequest,
            properties: ['action' => $action, 'from' => $from, 'to' => $to],
            userId: $userId,
        );
    }
}
