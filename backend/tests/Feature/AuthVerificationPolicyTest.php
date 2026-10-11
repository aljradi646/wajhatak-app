<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AuthVerificationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_can_login_when_email_verification_is_enabled(): void
    {
        Setting::put('email_verification_enabled', true, 'boolean');

        $user = User::factory()->unverified()->create([
            'email' => 'unverified-client@example.test',
            'password' => 'SecretPass123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'SecretPass123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email_verified', false)
            ->assertJsonPath('data.user.email_verification_enabled', true)
            ->assertJsonPath('data.email_verification_required', false);
    }

    public function test_unverified_agent_can_login_when_email_verification_is_enabled(): void
    {
        Setting::put('email_verification_enabled', true, 'boolean');
        $user = User::factory()->unverified()->create([
            'email' => 'unverified-agent@example.test',
            'password' => 'SecretPass123',
        ]);
        Role::findOrCreate('agent');
        $user->assignRole('agent');

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'SecretPass123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email_verified', false)
            ->assertJsonPath('data.email_verification_required', false);
    }

    public function test_registration_returns_verification_policy_when_enabled(): void
    {
        Setting::put('email_verification_enabled', true, 'boolean');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Client Test',
            'email' => 'enabled-client@example.test',
            'password' => 'SecretPass123',
            'password_confirmation' => 'SecretPass123',
            'account_type' => 'client',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email_verified', false)
            ->assertJsonPath('data.user.email_verification_enabled', true)
            ->assertJsonPath('data.email_verification_required', true);
    }

    public function test_registration_does_not_require_email_verification_when_disabled(): void
    {
        Setting::put('email_verification_enabled', false, 'boolean');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Client Test',
            'email' => 'disabled-client@example.test',
            'password' => 'SecretPass123',
            'password_confirmation' => 'SecretPass123',
            'account_type' => 'client',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email_verified', false)
            ->assertJsonPath('data.user.email_verification_enabled', false)
            ->assertJsonPath('data.email_verification_required', false);
    }

    public function test_verification_endpoints_stop_when_platform_policy_is_disabled(): void
    {
        Setting::put('email_verification_enabled', false, 'boolean');
        $user = User::factory()->unverified()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/email/status')
            ->assertStatus(409)
            ->assertJsonPath('data.enabled', false);
    }
}
