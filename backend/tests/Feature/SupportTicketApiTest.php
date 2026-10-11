<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupportTicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_policy_pages_are_served_from_database(): void
    {
        $this->getJson('/api/v1/pages')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'privacy'])
            ->assertJsonFragment(['slug' => 'terms']);

        $this->getJson('/api/v1/pages/privacy')
            ->assertOk()
            ->assertJsonPath('data.slug', 'privacy')
            ->assertJsonPath('data.title', 'سياسة الخصوصية');
    }

    public function test_user_can_submit_and_retrieve_only_their_own_ticket(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/support-tickets', [
            'subject' => 'مشكلة تسجيل الدخول',
            'category' => 'account',
            'message' => 'لا أستطيع الدخول إلى حسابي رغم صحة كلمة المرور.',
        ])->assertCreated()->assertJsonPath('data.status', 'open');

        $id = (int) $response->json('data.id');

        $this->assertDatabaseHas('support_tickets', ['id' => $id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('support_ticket_messages', ['support_ticket_id' => $id, 'sender_role' => 'user']);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/support-tickets/'.$id)->assertOk();
        $this->actingAs($other, 'sanctum')->getJson('/api/v1/support-tickets/'.$id)->assertNotFound();
    }
}
