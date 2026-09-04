<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $accountType = $validated['account_type'] ?? 'client';
        $roleName = $accountType === 'agent' ? UserRole::Agent->value : UserRole::User->value;

        $user = DB::transaction(function () use ($validated, $accountType, $roleName): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'locale' => $validated['locale'] ?? 'ar',
                'password' => Hash::make($validated['password']),
            ]);

            Role::findOrCreate($roleName);
            $user->assignRole($roleName);

            if ($accountType === 'agent') {
                Agent::query()->create([
                    'user_id' => $user->id,
                    'license_number' => $validated['license_number'] ?? null,
                    'bio' => $validated['bio'] ?? null,
                    'is_active' => true,
                ]);
            }

            return $user;
        });

        $token = $user->createToken($request->header('X-Device-Name', 'mobile'))->plainTextToken;

        return response()->json(['data' => ['user' => new UserResource($user->load('roles', 'permissions')), 'token' => $token]], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->lower()->toString())->first();

        if (! $user || ! $user->is_active || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 422);
        }

        $token = $user->createToken($request->string('device_name')->toString())->plainTextToken;

        return response()->json(['data' => ['user' => new UserResource($user->load('roles', 'permissions')), 'token' => $token]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(status: 204);
    }
}
