<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\User;
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
        ]);
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
        ]);

        ActivityLog::record('agent', "تم إنشاء وكيل «{$agent->user->name}»", $agent);

        return redirect()->route('admin.agents.index')->with('status', 'تم إنشاء الوكيل بنجاح.');
    }

    public function show(Agent $agent)
    {
        $agent->load('user', 'properties');
        return view('admin.agents.show', compact('agent'));
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
