<?php

namespace Tests\Feature;

use App\Models\PublicContactSubmission;
use App\Models\SitePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PublicContactSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SitePage::query()->updateOrCreate(
            ['slug' => 'contact'],
            [
                'title' => 'اتصل بنا',
                'summary' => 'تواصل مع فريق وجهتك.',
                'content' => 'أرسل رسالتك إلى فريق وجهتك.',
                'category' => 'info',
                'is_published' => true,
                'sort_order' => 100,
            ]
        );
    }

    public function test_public_contact_page_renders_real_form_and_helpful_copy(): void
    {
        $this->get('/pages/contact')
            ->assertOk()
            ->assertSee('أرسل رسالة إلى فريق وجهتك')
            ->assertSee('بلاغ عن عقار أو إعلان')
            ->assertSee(route('public.contact.store'), false);
    }

    public function test_public_contact_submission_is_persisted_with_follow_up_reference(): void
    {
        $this->from('/pages/contact')->post('/contact', [
            'name' => 'مستخدم للاختبار',
            'email' => 'contact-test@example.com',
            'phone' => '+967700000000',
            'category' => 'abuse',
            'subject' => 'بلاغ عن إعلان مضلل',
            'message' => 'أرغب في الإبلاغ عن إعلان يحتوي على معلومات غير صحيحة.',
            'consent' => '1',
        ])->assertRedirect(route('public.pages.show', 'contact'))
            ->assertSessionHas('status')
            ->assertSessionHas('contact_reference');

        $this->assertDatabaseHas('public_contact_submissions', [
            'name' => 'مستخدم للاختبار',
            'email' => 'contact-test@example.com',
            'category' => 'abuse',
            'subject' => 'بلاغ عن إعلان مضلل',
            'status' => 'new',
        ]);

        $submission = PublicContactSubmission::query()->firstOrFail();
        $this->assertStringStartsWith('WJ-C-', $submission->reference);
    }

    public function test_admin_can_view_and_reply_to_public_contact_submission(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $submission = PublicContactSubmission::query()->create([
            'reference' => 'WJ-C-261012-TEST01',
            'name' => 'صاحب الرسالة',
            'email' => 'owner@example.com',
            'category' => 'technical',
            'subject' => 'مشكلة في عرض العقار',
            'message' => 'لا تظهر صور العقار عند فتح تفاصيل الإعلان.',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contact-submissions.show', $submission))
            ->assertOk()
            ->assertSee('صاحب الرسالة')
            ->assertSee('لا تظهر صور العقار عند فتح تفاصيل الإعلان.');

        $this->actingAs($admin)
            ->post(route('admin.contact-submissions.reply', $submission), [
                'admin_reply' => 'شكرًا لإبلاغنا، أحلنا المشكلة إلى الفريق التقني للمراجعة.',
                'status' => 'replied',
            ])
            ->assertRedirect(route('admin.contact-submissions.show', $submission))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('public_contact_submissions', [
            'id' => $submission->id,
            'status' => 'replied',
            'admin_reply' => 'شكرًا لإبلاغنا، أحلنا المشكلة إلى الفريق التقني للمراجعة.',
            'replied_by' => $admin->id,
            'email_status' => 'not_sent',
        ]);
    }

    public function test_contact_form_requires_privacy_consent_and_valid_message(): void
    {
        $this->from('/pages/contact')->post('/contact', [
            'name' => 'مستخدم للاختبار',
            'email' => 'contact-test@example.com',
            'category' => 'general',
            'subject' => 'استفسار عن المنصة',
            'message' => 'رسالة صحيحة للاختبار لكنها لا توافق على سياسة الخصوصية.',
        ])->assertSessionHasErrors(['consent']);

        $this->assertDatabaseCount('public_contact_submissions', 0);
    }
}
