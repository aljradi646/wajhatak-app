<?php

namespace App\Services\Mail;

use App\Models\EmailSetting;
use App\Models\EmailTemplate;
use App\Services\Email\EmailDeliverabilityService;
use Illuminate\Support\Str;

class UnifiedMailService
{
    public function __construct(
        private readonly DynamicMailService $smtpService,
        private readonly ResendService $resendService,
        private readonly EmailDeliverabilityService $deliverability,
    ) {}

    public function send(string $to, string $subject, string $body, array $options = []): void
    {
        $settings = EmailSetting::current();
        $this->assertAllowedRecipient($settings, $to);

        $check = $this->deliverability->verify($to);
        if (! $check['deliverable']) {
            throw new \RuntimeException($check['reason'] ?? 'البريد المستهدف غير قابل للتسليم.');
        }

        if ($settings->isResend()) {
            $this->resendService->send(
                $to,
                $subject,
                $options['html'] ?? null,
                $options['text'] ?? $body,
                $options['attachments'] ?? [],
            );
            return;
        }

        $html = $options['html'] ?? null;
        if (is_string($html) && $html !== '') {
            $this->smtpService->sendHtml($to, $subject, $html, (string) ($options['text'] ?? $body), $options['headers'] ?? []);
            return;
        }

        $this->smtpService->send($to, $subject, $body, $options['headers'] ?? []);
    }

    public function sendTemplate(string $to, string $templateKey, array $variables = []): void
    {
        $settings = EmailSetting::current();
        $this->assertAllowedRecipient($settings, $to);

        $template = EmailTemplate::findByKey($templateKey);
        if (! $template) {
            $this->sendTemplateLegacy($to, $templateKey, $variables);
            return;
        }

        $variables = $this->normalizeVariables($variables, $settings);
        $rendered = $template->renderPublished($variables);
        if (! is_array($rendered)) {
            throw new \RuntimeException('القالب المطلوب غير منشور حاليًا.');
        }

        $html = (string) ($rendered['html'] ?? '');
        $text = (string) ($rendered['text'] ?? strip_tags($html));

        $this->send($to, $rendered['subject'], $text, [
            'html' => $html,
            'text' => $text,
        ]);
    }

    private function sendTemplateLegacy(string $to, string $templateKey, array $variables = []): void
    {
        $mailSettings = app(MailSettingsService::class);
        $template = $mailSettings->renderTemplate($templateKey, $variables);

        $this->send($to, $template['subject'], $template['body']);
    }

    private function normalizeVariables(array $variables, EmailSetting $settings): array
    {
        $name = $variables['user.name'] ?? $variables['name'] ?? null;
        $email = $variables['user.email'] ?? $variables['email'] ?? null;
        $property = $variables['property.title'] ?? $variables['property'] ?? null;
        $variables['user.name'] ??= $name;
        $variables['name'] ??= $name;
        $variables['user.email'] ??= $email;
        $variables['email'] ??= $email;
        $variables['property.title'] ??= $property;
        $variables['property'] ??= $property;
        $variables['app.url'] ??= rtrim((string) config('app.url'), '/');
        $variables['app.name'] ??= (string) config('app.name', 'وجهتك');
        $variables['app.logo_url'] ??= $settings->getLogoUrlForEmail();

        return $variables;
    }

    private function assertAllowedRecipient(EmailSetting $settings, string $to): void
    {
        if (! $settings->is_active) {
            throw new \RuntimeException('إرسال البريد معطل حاليًا من لوحة التحكم.');
        }

        if (EmailSetting::isTestEmail($to) || Str::contains($to, ['example.com', 'example.org', 'example.net'])) {
            throw new \RuntimeException('لا يمكن إرسال رسائل إلى عناوين بريد وهمية أو تجريبية.');
        }
    }

    public function canSend(): bool
    {
        return EmailSetting::current()->canSend();
    }

    public function getProvider(): string
    {
        return EmailSetting::current()->provider;
    }
}
