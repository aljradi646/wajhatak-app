<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\ReportLog;
use App\Models\User;
use App\Services\Mail\UnifiedMailService;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $query = Agent::query()->with('user')->withCount('properties');
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('license_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }
        // فلتر حالة التوثيق: pending | approved | rejected.
        if ($status = $request->input('verification')) {
            $query->where('verification_status', $status);
        }
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        if (in_array($sort, ['rating', 'reviews_count', 'is_active', 'created_at'], true)) {
            $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        return view('admin.agents.index', [
            'agents' => $query->paginate(15)->withQueryString(),
            'search' => $search,
            'pendingVerification' => Agent::query()->where('verification_status', 'pending')->count(),
            'verificationFilter' => $status,
        ]);
    }

    /** صفحة طلبات التوثيق المعلقة. */
    public function verifications()
    {
        $agents = Agent::query()
            ->with('user')
            ->where('verification_status', 'pending')
            ->latest()
            ->paginate(20);

        return view('admin.agents.verifications', [
            'agents' => $agents,
        ]);
    }

    /** موافقة الإدارة على توثيق الوكيل — تفتح بوابة النشر وتُبلغ بالبريد. */
    public function approve(Request $request, Agent $agent)
    {
        $agent->forceFill([
            'verification_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'rejection_reason' => null,
            'is_active' => true,
        ])->save();

        ActivityLog::record('agent', "تم توثيق الوكيل «{$agent->user->name}»", $agent);

        // إشعار بريدي حقيقي (لا يفشل الطلب إن تعذر البريد).
        try {
            app(UnifiedMailService::class)
                ->sendTemplate($agent->user->email, 'agent_approved', ['name' => $agent->user->name]);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', "تم توثيق الوكيل «{$agent->user->name}» — أصبح بإمكانه نشر العقارات.");
    }

    /** رفض توثيق الوكيل مع سبب يُعرض على الوكيل. */
    public function rejectVerification(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'اذكر سبب الرفض ليعرف الوكيل ما يُصححه.',
        ]);

        $agent->forceFill([
            'verification_status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'is_active' => false,
        ])->save();

        ActivityLog::record('agent', "تم رفض توثيق الوكيل «{$agent->user->name}»", $agent);

        try {
            app(UnifiedMailService::class)
                ->sendTemplate($agent->user->email, 'agent_rejected', [
                    'name' => $agent->user->name,
                    'reason' => $data['reason'],
                ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', 'تم رفض التوثيق وإشعار الوكيل بالسبب.');
    }

    public function create()
    {
        $users = User::query()->doesntHave('agentProfile')->get();

        return view('admin.agents.create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'license_number' => ['nullable', 'string', 'max:191', 'unique:agents,license_number'],
            'bio' => ['nullable', 'string'],
            'rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'reviews_count' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $agent = Agent::create([
            'user_id' => $data['user_id'],
            'license_number' => $data['license_number'] ?? null,
            'bio' => $data['bio'] ?? null,
            'rating' => $data['rating'] ?? 0,
            'reviews_count' => $data['reviews_count'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'verification_status' => $request->boolean('is_active') ? 'approved' : 'pending',
            'verified_at' => $request->boolean('is_active') ? now() : null,
            'verified_by' => $request->boolean('is_active') ? $request->user()->id : null,
        ]);

        ActivityLog::record('agent', "تم إنشاء وكيل «{$agent->user->name}»", $agent);

        return redirect()->route('admin.agents.index')->with('status', 'تم إنشاء الوكيل بنجاح.');
    }

    /**
     * صفحة مراجعة الوكيل الكاملة — كل بياناته الحقيقية في تبويبات منظمة
     * بدل نافذة منبثقة ناقصة.
     */
    public function show(Agent $agent)
    {
        $agent->load([
            'user',
            'properties' => fn ($q) => $q->with(['type', 'location'])->latest(),
        ]);

        $userId = $agent->user_id;

        // عمليات التوثيق المُسجَّلة على هذا الوكيل (قبول/رفض/تحديث).
        $verificationEvents = ActivityLog::query()
            ->with('user:id,name')
            ->where('subject_type', Agent::class)
            ->where('subject_id', $agent->id)
            ->latest()
            ->limit(50)
            ->get();

        // تقارير وُلّدت بواسطة حساب الوكيل نفسه.
        $reportLogs = ReportLog::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit(50)
            ->get();

        // محادثات الوكيل مع العملاء (بحسب الصلاحيات — المشرف فقط يدخل هنا).
        $conversations = Conversation::query()
            ->with(['client:id,name,email', 'property:id,title,reference_code'])
            ->where('agent_id', $userId)
            ->latest('last_message_at')
            ->limit(50)
            ->get();

        $viewingRequests = $agent->viewingRequests()
            ->with(['property:id,title,reference_code', 'client:id,name'])
            ->latest('scheduled_date')
            ->limit(50)
            ->get();

        // سجل أنشطة حساب الوكيل.
        $activities = ActivityLog::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.agents.show', [
            'agent' => $agent,
            'verificationEvents' => $verificationEvents,
            'reportLogs' => $reportLogs,
            'conversations' => $conversations,
            'viewingRequests' => $viewingRequests,
            'activities' => $activities,
        ]);
    }

    public function edit(Agent $agent)
    {
        $users = User::query()->where(function ($q) use ($agent) {
            $q->whereDoesntHave('agentProfile')->orWhereHas('agentProfile', fn ($a) => $a->whereKey($agent->id));
        })->get();

        return view('admin.agents.edit', [
            'agent' => $agent,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'license_number' => ['nullable', 'string', 'max:191', 'unique:agents,license_number,'.$agent->id],
            'bio' => ['nullable', 'string'],
            'rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'reviews_count' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $agent->update([
            'user_id' => $data['user_id'],
            'license_number' => $data['license_number'] ?? null,
            'bio' => $data['bio'] ?? null,
            'rating' => $data['rating'],
            'reviews_count' => $data['reviews_count'],
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::record('agent', "تم تحديث وكيل «{$agent->user->name}»", $agent);

        return redirect()->route('admin.agents.index')->with('status', 'تم تحديث الوكيل بنجاح.');
    }

    public function destroy(Agent $agent)
    {
        if ($agent->properties()->exists()) {
            return back()->with('error', 'لا يمكن حذف وكيل مرتبط بعقارات. احذف العقارات أولًا.');
        }
        $agent->delete();
        ActivityLog::record('agent', "تم حذف وكيل «{$agent->user->name}»", $agent);

        return redirect()->route('admin.agents.index')->with('status', 'تم حذف الوكيل بنجاح.');
    }
}
