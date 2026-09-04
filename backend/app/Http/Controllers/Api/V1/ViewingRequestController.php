<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PropertyStatus;
use App\Enums\ViewingRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ViewingRequestResource;
use App\Models\Property;
use App\Models\ViewingRequest;
use App\Notifications\ViewingRequestCreated;
use App\Notifications\ViewingRequestUpdated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViewingRequestController extends Controller
{
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
        $viewingRequest->load(['client', 'property']);
        $property->agent->user->notify(new ViewingRequestCreated($viewingRequest));

        return response()->json(['data' => new ViewingRequestResource($viewingRequest->load(['property', 'client', 'agent.user']))], 201);
    }

    public function update(Request $request, ViewingRequest $viewingRequest): ViewingRequestResource
    {
        $data = $request->validate(['status' => ['required', 'in:confirmed,rejected,cancelled,completed']]);
        $user = $request->user();
        $isAgentOwner = $user->agentProfile?->id === $viewingRequest->agent_id;
        $isClientOwner = $user->id === $viewingRequest->client_id;
        abort_unless($user->hasRole('admin') || $isAgentOwner || $isClientOwner, 403);
        if ($isClientOwner) {
            abort_unless($data['status'] === ViewingRequestStatus::Cancelled->value, 403);
        }
        if ($isAgentOwner) {
            abort_unless(in_array($data['status'], [ViewingRequestStatus::Confirmed->value, ViewingRequestStatus::Rejected->value, ViewingRequestStatus::Completed->value], true), 403);
        }

        $viewingRequest->update(['status' => $data['status']]);

        // إشعار الطرف الآخر فقط: العميل عند تغيير الوكيل/المدير للحالة،
        // والوكيل عندما يلغي العميل الطلب.
        $fresh = $viewingRequest->fresh()->load(['client', 'property', 'agent.user']);
        if ($isClientOwner) {
            $fresh->agent->user->notify(new ViewingRequestUpdated($fresh));
        } elseif ($isAgentOwner || $user->hasRole('admin')) {
            $fresh->client->notify(new ViewingRequestUpdated($fresh));
        }

        return new ViewingRequestResource($fresh);
    }
}
