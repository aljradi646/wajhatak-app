<?php

namespace Tests\Unit\Mail;

use App\Models\EmailSetting;
use App\Services\Mail\ResendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResendServiceTest extends TestCase
{
    use RefreshDatabase;

    private function settings(bool $sandbox = false): EmailSetting
    {
        $settings = EmailSetting::current();
        $settings->update([
            'provider' => 'resend',
            'resend_api_key' => 're_test_key',
            'resend_sandbox' => $sandbox,
            'from_name' => 'وجهتك',
            'from_address' => 'no-reply@example.com',
            'is_active' => true,
        ]);

        return $settings->fresh();
    }

    public function test_successful_send_posts_the_real_resend_payload(): void
    {
        $this->settings(false);

        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'email-test-1'], 200),
        ]);

        $result = app(ResendService::class)->send(
            'customer@gmail.com',
            'رسالة اختبار',
            '<p>مرحبًا {{user.name}}</p>',
            'مرحبًا',
        );

        $this->assertTrue($result['success']);
        $this->assertSame('email-test-1', $result['data']['id']);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.resend.com/emails'
                && ($body['from'] ?? null) === 'وجهتك <no-reply@example.com>'
                && ($body['to'] ?? null) === ['customer@gmail.com']
                && ($body['subject'] ?? null) === 'رسالة اختبار'
                && ($body['html'] ?? null) === '<p>مرحبًا {{user.name}}</p>'
                && ($body['text'] ?? null) === 'مرحبًا';
        });
    }

    public function test_sandbox_403_is_reported_as_a_configuration_problem(): void
    {
        $this->settings(true);

        Http::fake([
            'https://api.resend.com/emails' => Http::response([
                'statusCode' => 403,
                'name' => 'validation_error',
                'message' => 'You can only send testing emails to your own email address (aljradisoft@gmail.com).',
            ], 403),
        ]);

        $result = app(ResendService::class)->send(
            'someone@gmail.com',
            'اختبار',
            '<p>اختبار</p>',
            'اختبار',
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('وضع الاختبار (Sandbox)', $result['message']);
        $this->assertStringNotContainsString('HTTP request returned status code 403', $result['message']);
    }

    public function test_inline_logo_uses_resend_content_id_fields(): void
    {
        $settings = $this->settings(false);
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('email-logos/logo.png', 'PNG');

        $settings->update([
            'logo_path' => 'email-logos/logo.png',
            'logo_url' => 'https://example.test/storage/email-logos/logo.png',
        ]);

        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'email-test-logo'], 200),
        ]);

        $result = app(ResendService::class)->send(
            'customer@gmail.com',
            'شعار',
            '<p><img src="https://example.test/storage/email-logos/logo.png"></p>',
            null,
        );

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $attachment = $body['attachments'][0] ?? [];

            return ($attachment['content_id'] ?? null) === 'logo@wajhatak'
                && ($attachment['content_type'] ?? null) === 'image/png'
                && ! array_key_exists('cid', $attachment)
                && str_contains((string) ($body['html'] ?? ''), 'cid:logo@wajhatak');
        });
    }
}
