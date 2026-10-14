<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class SupportTicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_policy_pages_are_served_from_database(): void
    {
        $this->getJson('/api/v1/pages')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'privacy'])
            ->assertJsonFragment(['slug' => 'terms'])
            ->assertJsonFragment(['slug' => 'usage-policy'])
            ->assertJsonFragment(['slug' => 'refund-cancellation'])
            ->assertJsonFragment(['slug' => 'cookies'])
            ->assertJsonFragment(['slug' => 'faq']);

        $this->getJson('/api/v1/pages/privacy')
            ->assertOk()
            ->assertJsonPath('data.slug', 'privacy')
            ->assertJsonPath('data.title', 'سياسة الخصوصية');
    }

    public function test_admin_reply_creates_database_notification_for_ticket_owner(): void
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $user = User::factory()->create();
        $ticket = SupportTicket::query()->create([
            'reference' => 'WJ-261011-NOTIFY1',
            'user_id' => $user->id,
            'subject' => 'بلاغ لاختبار الإشعار',
            'category' => 'technical',
            'status' => 'open',
            'priority' => 'normal',
        ]);
        $ticket->messages()->create([
            'user_id' => $user->id,
            'sender_role' => 'user',
            'body' => 'أواجه مشكلة تقنية في التطبيق.',
        ]);

        $this->actingAs($admin)->post('/admin/support-tickets/'.$ticket->id.'/reply', [
            'message' => 'تم استلام البلاغ وسيتم متابعته.',
            'status' => 'waiting_user',
            'send_email' => '0',
        ])->assertRedirect(route('admin.support-tickets.show', $ticket));

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'sender_role' => 'admin',
            'body' => 'تم استلام البلاغ وسيتم متابعته.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => 'support_ticket_reply',
        ]);

        $notification = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('type', 'support_ticket_reply')
            ->first();
        $payload = json_decode((string) $notification->data, true);
        $this->assertSame($ticket->id, (int) $payload['support_ticket_id']);
        $this->assertSame('تم استلام البلاغ وسيتم متابعته.', $payload['message']);
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

    public function test_ticket_attachments_are_private_and_only_the_owner_can_download_them(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/support-tickets', [
            'subject' => 'بلاغ مع صورة توضيحية',
            'category' => 'technical',
            'message' => 'أرفقت صورة توضح الخطأ الذي يظهر عند استخدام التطبيق.',
            'attachments' => [UploadedFile::fake()->image('evidence.png')->size(128)],
        ]);

        $response->assertCreated();
        $ticketId = (int) $response->json('data.id');
        $attachmentId = (int) $response->json('data.attachments.0.id');
        $this->assertGreaterThan(0, $attachmentId);
        $this->assertDatabaseHas('support_ticket_attachments', [
            'id' => $attachmentId,
            'support_ticket_id' => $ticketId,
            'uploaded_by' => $user->id,
            'original_name' => 'evidence.png',
        ]);

        $this->actingAs($user, 'sanctum')
            ->get('/api/v1/support-tickets/'.$ticketId.'/attachments/'.$attachmentId)
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->actingAs($other, 'sanctum')
            ->getJson('/api/v1/support-tickets/'.$ticketId.'/attachments/'.$attachmentId)
            ->assertNotFound();
    }

}
