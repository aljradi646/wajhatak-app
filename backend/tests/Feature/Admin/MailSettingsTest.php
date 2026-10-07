<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Services\Mail\MailSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin'));

        return $admin;
    }

    public function test_mail_settings_screen_loads_with_templates(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.mail.index'));

        $response->assertOk();
        $response->assertViewIs('admin.mail.index');
        $response->assertViewHas('templates', static fn (array $templates): bool => $templates === MailSettingsService::TEMPLATES);
        $response->assertViewHas('templateValues', static fn (array $values): bool => array_keys($values) === array_keys(MailSettingsService::TEMPLATES));

        foreach (array_keys(MailSettingsService::TEMPLATES) as $key) {
            $response->assertSee("templates[{$key}][subject]", false);
        }
    }

    public function test_template_values_fall_back_to_builtin_defaults(): void
    {
        $this->actingAs($this->admin())->get(route('admin.mail.index'));

        $template = app(MailSettingsService::class)->template('email_verification');

        $this->assertSame(MailSettingsService::TEMPLATES['email_verification']['subject'], $template['subject']);
        $this->assertSame(MailSettingsService::TEMPLATES['email_verification']['body'], $template['body']);
    }

    public function test_templates_can_be_saved_from_the_screen(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.mail.templates'), [
            'templates' => [
                'email_verification' => [
                    'subject' => 'موضوع محفوظ',
                    'body' => 'نص محفوظ {code}',
                ],
            ],
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('settings', ['key' => 'mail_template_email_verification_subject']);
        $this->assertDatabaseHas('settings', ['key' => 'mail_template_email_verification_body']);
        $this->assertSame('موضوع محفوظ', Setting::get('mail_template_email_verification_subject'));

        $rendered = app(MailSettingsService::class)->renderTemplate('email_verification', ['code' => '123456']);
        $this->assertSame('موضوع محفوظ', $rendered['subject']);
        $this->assertSame('نص محفوظ 123456', $rendered['body']);
    }
}
