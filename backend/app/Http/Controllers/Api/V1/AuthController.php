<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Agent;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['email'] = mb_strtolower(trim($validated['email']));
        $accountType = $validated['account_type'] ?? 'client';
        $emailVerificationEnabled = (bool) Setting::get('email_verification_enabled', true);

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
                // ملف الوكيل يبدأ pending — لا نشر عقارات قبل توثيق الإدارة.
                Agent::query()->create([
                    'user_id' => $user->id,
                    'license_number' => $validated['license_number'] ?? null,
                    'bio' => $validated['bio'] ?? null,
                    'agency_name' => $validated['agency_name'] ?? null,
                    'job_title' => $validated['job_title'] ?? null,
                    'phone' => $validated['agent_phone'] ?? null,
                    'whatsapp' => $validated['whatsapp'] ?? null,
                    'city' => $validated['agent_city'] ?? null,
                    'national_id' => $validated['national_id'] ?? null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'is_active' => false,
                    'verification_status' => 'pending',
                ]);
            }

            return $user;
        });

        $token = $user->createToken($request->header('X-Device-Name', 'mobile'))->plainTextToken;

        return response()->json(['data' => [
            'user' => new UserResource($user->load('roles', 'permissions')),
            'token' => $token,
            'email_verification_required' => $emailVerificationEnabled,
        ]], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $email = mb_strtolower(trim($request->string('email')->toString()));
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $user->is_active || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 422);
        }

        $token = $user->createToken($request->string('device_name')->toString())->plainTextToken;

        return response()->json(['data' => [
            'user' => new UserResource($user->load('roles', 'permissions')),
            'token' => $token,
            'email_verification_required' => false,
        ]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $plainTextToken = $request->bearerToken();

        if (is_string($plainTextToken) && $plainTextToken !== '') {
            // Resolve the token explicitly. This also covers environments where
            // Sanctum's currentAccessToken is a transient session token.
            $accessToken = PersonalAccessToken::findToken($plainTextToken);

            if (
                $accessToken !== null
                && (string) $accessToken->tokenable_id === (string) $user->getKey()
                && $accessToken->tokenable_type === $user->getMorphClass()
            ) {
                $accessToken->delete();
            }
        } else {
            $currentAccessToken = $user->currentAccessToken();

            if ($currentAccessToken instanceof PersonalAccessToken) {
                $currentAccessToken->delete();
            }
        }

        return response()->json(status: 204);
    }
}
