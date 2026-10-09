<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_status_exposes_server_clock_and_resend_deadline(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'email_code_sent_at' => now()->subSeconds(10),
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me/email/status');

        $response->assertOk()
            ->assertJsonPath('data.verified', false)
            ->assertJsonStructure([
                'data' => [
                    'verified',
                    'resend_in',
                    'resend_available_at',
                    'server_time',
                ],
            ]);

        $this->assertGreaterThan(0, (int) $response->json('data.resend_in'));
        $this->assertNotNull($response->json('data.resend_available_at'));
        $this->assertNotNull($response->json('data.server_time'));
    }
}
