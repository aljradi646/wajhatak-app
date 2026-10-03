<?php

namespace App\Services\Mail;

use App\Models\EmailSetting;
use App\Models\EmailTemplate;
use App\Services\Email\EmailDeliverabilityService;

/**
 * Unified mail service that routes to either SMTP or Resend based on settings.
 * This is the main entry point for all email sending in the application.
 */
class UnifiedMailService
{
    public function __construct(
        private readonly DynamicMailService $smtpService,
        private readonly ResendService $resendService,
        private readonly EmailDeliverabilityService $deliverability,
    ) {}

    /**
     * Send an email using the configured provider.
     *
     * @param  string  $to  Recipient email address
     * @param  string  $subject  Email subject
     * @param  string  $body  Email body (text or HTML)
     * @param  array  $options  Additional options (html, attachments, etc.)
     * @return void
     */
    public function send(string $to, string $subject, string $body, array $options = []): void
    {
        $settings = EmailSetting::current();

        // Block sending if not active
        if (!$settings->is_active) {
            throw new \RuntimeException('إرسال البريد معطل حاليًا من لوحة التحكم.');
        }

        // Block test email domains
        if (EmailSetting::isTestEmail($to)) {
            throw new \RuntimeException('لا يمكن إرسال رسائل إلى عناوين بريد وهمية أو تجريبية.');
        }

        // Check deliverability
        $check = $this->deliverability->verify($to);
        if (!$check['deliverable']) {
            throw new \RuntimeException($check['reason'] ?? 'البريد المستهدف غير قابل للتسليم.');
        }

        // Route to appropriate provider
        if ($settings->isResend()) {
            $this->resendService->send(
                $to,
                $subject,
                $options['html'] ?? null,
                $options['text'] ?? $body,
                $options['attachments'] ?? [],
            );
        } else {
            // Use legacy SMTP service
            $this->smtpService->send($to, $subject, $body, $options['headers'] ?? []);
        }
    }

    /**
     * Send a template email using the configured provider.
     *
     * @param  string  $to  Recipient email address
     * @param  string  $templateKey  Template key from EmailTemplate
     * @param  array  $variables  Variables to replace in template
     * @return void
     */
    public function sendTemplate(string $to, string $templateKey, array $variables = []): void
    {
        $settings = EmailSetting::current();

        // Block sending if not active
        if (!$settings->is_active) {
            throw new \RuntimeException('إرسال البريد معطل حاليًا من لوحة التحكم.');
        }

        // Block test email domains
        if (EmailSetting::isTestEmail($to)) {
            throw new \RuntimeException('لا يمكن إرسال رسائل إلى عناوين بريد وهمية أو تجريبية.');
        }

        // Check deliverability
        $check = $this->deliverability->verify($to);
        if (!$check['deliverable']) {
            throw new \RuntimeException($check['reason'] ?? 'البريد المستهدف غير قابل للتسليم.');
        }

        // Get template from new system
        $template = EmailTemplate::findByKey($templateKey);
        
        // Fallback to legacy system if template not found
        if (!$template) {
            $this->sendTemplateLegacy($to, $templateKey, $variables);
            return;
        }

        // Render template with variables
        $rendered = $template->render($variables);

        // For SMTP: replace {{logo}} with URL or remove it
        // For Resend: keep {{logo}} placeholder, ResendService will handle CID
        if (!$settings->isResend()) {
            $logoUrl = $settings->getLogoUrlForEmail();
            if ($logoUrl) {
                $rendered['html'] = str_replace('{{logo}}', '<img src="' . $logoUrl . '" alt="وجهتك" style="max-width: 150px; height: auto;">', $rendered['html']);
            } else {
                $rendered['html'] = str_replace('{{logo}}', '', $rendered['html']);
            }
        }

        // Route to appropriate provider
        if ($settings->isResend()) {
            $this->resendService->send(
                $to,
                $rendered['subject'],
                $rendered['html'],
                $rendered['text'],
            );
        } else {
            $html = (string) ($rendered['html'] ?? '');
            $text = $rendered['text'] ?: strip_tags($html);
            if ($html !== '') {
                $this->smtpService->sendHtml($to, $rendered['subject'], $html, $text);
            } else {
                $this->smtpService->send($to, $rendered['subject'], $text);
            }
        }
    }

    /**
     * Fallback to legacy template system for backward compatibility.
     */
    private function sendTemplateLegacy(string $to, string $templateKey, array $variables = []): void
    {
        $settings = EmailSetting::current();
        $mailSettings = app(\App\Services\Mail\MailSettingsService::class);
        
        $template = $mailSettings->renderTemplate($templateKey, $variables);

        if ($settings->isResend()) {
            $html = $this->convertToHtml($template['body'], $templateKey, $variables);
            $this->resendService->send($to, $template['subject'], $html, $template['body']);
        } else {
            $this->smtpService->send($to, $template['subject'], $template['body']);
        }
    }

    /**
     * Convert plain text template to basic HTML for Resend (legacy fallback).
     */
    private function convertToHtml(string $text, string $templateKey, array $variables): string
    {
        $settings = EmailSetting::current();
        $logoUrl = $settings->getLogoUrlForEmail();
        
        $logoHtml = $logoUrl 
            ? '<div style="text-align: center; margin-bottom: 20px;">' .
              '<img src="' . $logoUrl . '" alt="وجهتك" style="max-width: 150px; height: auto;">' .
              '</div>' 
            : '';

        $body = nl2br(e($text));

        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسالة من وجهتك</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f5f5f5;">
    <div style="max-width: 600px; margin: 40px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="background: linear-gradient(135deg, #075E4A, #0E8A6D); padding: 30px; text-align: center;">
            {$logoHtml}
            <h1 style="color: white; margin: 0; font-size: 24px;">وجهتك</h1>
        </div>
        
        <div style="padding: 30px;">
            {$body}
        </div>
        
        <div style="background: #f9f9f9; padding: 20px; text-align: center; border-top: 1px solid #eee;">
            <p style="margin: 0; color: #666; font-size: 12px;">
                تم إرسال هذه الرسالة من منصة وجهتك العقارية
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Check if email sending is configured and active.
     */
    public function canSend(): bool
    {
        $settings = EmailSetting::current();
        return $settings->canSend();
    }

    /**
     * Get the current provider.
     */
    public function getProvider(): string
    {
        return EmailSetting::current()->provider;
    }
}
