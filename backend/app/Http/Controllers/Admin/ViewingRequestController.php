<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ViewingRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Property;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Http\Request;

class ViewingRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ViewingRequest::query()->with(['property', 'client', 'agent'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('property', fn ($p) => $p->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('client', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        return view('admin.viewing_requests.index', [
            'viewingRequests' => $query->latest()->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin.viewing_requests.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $viewingRequest = ViewingRequest::create($data);
        ActivityLog::record('viewing_request', "تم إنشاء طلب معاينة #{$viewingRequest->id}", $viewingRequest);
        return redirect()->route('admin.viewing-requests.index')->with('status', 'تم إنشاء طلب المعاينة بنجاح.');
    }

    public function show(ViewingRequest $viewingRequest)
    {
        $viewingRequest->load(['property', 'client', 'agent']);
        return view('admin.viewing_requests.show', compact('viewingRequest'));
    }

    public function edit(ViewingRequest $viewingRequest)
    {
        return view('admin.viewing_requests.edit', array_merge($this->formData(), ['viewingRequest' => $viewingRequest]));
    }

    public function update(Request $request, ViewingRequest $viewingRequest)
    {
        $data = $this->validateData($request);
        $viewingRequest->update($data);
        ActivityLog::record('viewing_request', "تم تحديث طلب معاينة #{$viewingRequest->id}", $viewingRequest);
        return redirect()->route('admin.viewing-requests.index')->with('status', 'تم تحديث طلب المعاينة بنجاح.');
    }

    public function destroy(ViewingRequest $viewingRequest)
    {
        $viewingRequest->delete();
        ActivityLog::record('viewing_request', "تم حذف طلب معاينة #{$viewingRequest->id}", $viewingRequest);
        return redirect()->route('admin.viewing-requests.index')->with('status', 'تم حذف طلب المعاينة بنجاح.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'client_id' => ['required', 'exists:users,id'],
            'agent_id' => ['required', 'exists:agents,id'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,confirmed,rejected,cancelled,completed'],
        ]);
    }

    private function formData(): array
    {
        return [
            'properties' => Property::query()->whereNull('deleted_at')->get(),
            'clients' => User::query()->orderBy('name')->get(),
            'agents' => Agent::query()->with('user')->get(),
            'statuses' => ViewingRequestStatus::cases(),
        ];
    }
}
