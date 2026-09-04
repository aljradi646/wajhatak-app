<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles');
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $allowed = ['name', 'email', 'created_at', 'updated_at', 'is_active'];
        if (in_array($sort, $allowed, true)) {
            $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        return view('admin.users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => Role::all()->pluck('name', 'name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8'],
            'locale' => ['required', 'in:ar,en'],
            'avatar_path' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['nullable', 'array'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'locale' => $data['locale'] ?? 'ar',
            'avatar_path' => $data['avatar_path'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        ActivityLog::record('user', "تم إنشاء المستخدم «{$user->name}»", $user);

        return redirect()->route('admin.users.index')->with('status', 'تم إنشاء المستخدم بنجاح.');
    }

    public function show(User $user)
    {
        $user->load('roles', 'agentProfile');
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::all()->pluck('name', 'name'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'locale' => ['required', 'in:ar,en'],
            'avatar_path' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['nullable', 'array'],
        ]);

        $wantsInactive = ! $request->boolean('is_active');
        $submittedRoles = $request->input('roles');
        $willRemoveAdminRole = $submittedRoles !== null && ! in_array('admin', (array) $submittedRoles, true);

        if ($user->id === $request->user()->id) {
            if ($wantsInactive) {
                return back()->withErrors(['is_active' => 'لا يمكنك تعطيل حسابك الخاص.']);
            }
            if ($willRemoveAdminRole) {
                return back()->withErrors(['roles' => 'لا يمكنك إزالة دور المشرف من حسابك الخاص.']);
            }
        }

        if ($user->hasRole('admin') && ($wantsInactive || $willRemoveAdminRole)) {
            $otherActiveAdmins = User::role('admin')
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->count();
            if ($otherActiveAdmins === 0) {
                return back()->withErrors(['is_active' => 'لا يمكن تعطيل أو إزالة دور آخر مشرف نشط.']);
            }
        }

        $user->fill([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'locale' => $data['locale'] ?? 'ar',
            'avatar_path' => $data['avatar_path'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles'] ?? []);
        }

        ActivityLog::record('user', "تم تحديث المستخدم «{$user->name}»", $user);

        return redirect()->route('admin.users.index')->with('status', 'تم تحديث المستخدم بنجاح.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['delete' => 'لا يمكنك حذف حسابك الخاص.']);
        }

        if ($user->hasRole('admin') && $user->is_active) {
            $otherActiveAdmins = User::role('admin')
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->count();
            if ($otherActiveAdmins === 0) {
                return back()->withErrors(['delete' => 'لا يمكن حذف آخر مشرف نشط.']);
            }
        }

        $user->delete();
        ActivityLog::record('user', "تم حذف المستخدم «{$user->name}»", $user);
        return redirect()->route('admin.users.index')->with('status', 'تم حذف المستخدم بنجاح.');
    }
}
