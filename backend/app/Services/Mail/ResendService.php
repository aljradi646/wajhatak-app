<?php

namespace App\Services\Mail;

use App\Models\EmailSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resend API Service for sending emails via HTTPS.
 * 
 * This service handles all interactions with the Resend API including:
 * - Sending emails with HTML/text content
 * - Testing API connectivity
 * - Handling authentication errors
 * - Managing sender verification
 * - Supporting inline images (logo)
 */
class ResendService
{
    private const RESEND_API_URL = 'https://api.resend.com';
    private const SANDBOX_SENDER = 'onboarding@resend.dev';

    private ?EmailSetting $settings = null;

    public function __construct()
    {
        $this->settings = EmailSetting::current();
    }

    /**
     * Send an email via Resend API.
     *
     * @param  string  $to  Recipient email address
     * @param  string  $subject  Email subject
     * @param  string  $html  HTML content (optional)
     * @param  string  $text  Plain text content (optional)
     * @param  array  $attachments  Attachments array (optional)
     * @return array{success: bool, message: string, data?: array}
     */
    public function send(
        string $to,
        string $subject,
        ?string $html = null,
        ?string $text = null,
        array $attachments = []
    ): array {
        // Block test email domains
        if (EmailSetting::isTestEmail($to)) {
            return [
                'success' => false,
                'message' => 'لا يمكن إرسال رسائل إلى عناوين بريد وهمية أو تجريبية.',
            ];
        }

        // Check if email sending is active
        if (!$this->settings->canSend()) {
            return [
                'success' => false,
                'message' => 'إعدادات البريد غير مفعلة أو غير مكتملة.',
            ];
        }

        // Check provider
        if (!$this->settings->isResend()) {
            return [
                'success' => false,
                'message' => 'مزود البريد الحالي ليس Resend.',
            ];
        }

        // Get API key
        $apiKey = $this->settings->resend_api_key;
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'مفتاح Resend API غير موجود.',
            ];
        }

        // Prepare sender
        $sender = $this->settings->getSenderEmail();
        $senderName = $this->settings->from_name ?? 'وجهتك';

        // Prepare payload
        $payload = [
            'from' => "{$senderName} <{$sender}>",
            'to' => [$to],
            'subject' => $subject,
        ];

        // Add content
        if ($html) {
            $payload['html'] = $this->injectLogo($html);
        }
        if ($text) {
            $payload['text'] = $text;
        }

        // Add reply-to if set
        if ($this->settings->reply_to) {
            $payload['reply_to'] = $this->settings->reply_to;
        }

        // Add attachments
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        try {
            $response = $this->httpClient()->post('/emails', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'تم إرسال البريد بنجاح.',
                    'data' => $response->json(),
                ];
            }

            // Handle API errors
            $error = $response->json();
            $errorMessage = $this->parseApiError($error);

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => $error,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطأ في الاتصال بـ Resend API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Resend API connection and credentials.
     *
     * @return array{success: bool, message: string, details?: array}
     */
    public function testConnection(): array
    {
        if (!$this->settings->isResend()) {
            return [
                'success' => false,
                'message' => 'مزود البريد الحالي ليس Resend.',
            ];
        }

        $apiKey = $this->settings->resend_api_key;
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'مفتاح Resend API غير موجود.',
            ];
        }

        try {
            // Test by fetching API domains (requires valid API key)
            $response = $this->httpClient()->get('/domains');

            if ($response->successful()) {
                $domains = $response->json('data', []);
                
                return [
                    'success' => true,
                    'message' => 'اتصال Resend API ناجح ✓',
                    'details' => [
                        'domains_count' => count($domains),
                        'domains' => array_map(fn ($d) => $d['name'] ?? 'unknown', $domains),
                        'sandbox_mode' => $this->settings->resend_sandbox,
                    ],
                ];
            }

            $error = $response->json();
            $errorMessage = $this->parseApiError($error);

            return [
                'success' => false,
                'message' => $errorMessage,
                'details' => $error,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطأ في الاتصال بـ Resend API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a test email to verify configuration.
     *
     * @param  string  $to  Recipient email address
     * @return array{success: bool, message: string}
     */
    public function sendTestEmail(string $to): array
    {
        $html = $this->getTestEmailTemplate();
        $text = 'هذه رسالة تجريبية من منصة وجهتك. إذا وصلتك هذه الرسالة، فإن إعدادات البريد تعمل بشكل صحيح.';

        return $this->send($to, 'رسالة تجريبية من وجهتك ✉️', $html, $text);
    }

    /**
     * Get HTTP client configured for Resend API.
     */
    private function httpClient(): PendingRequest
    {
        return Http::baseUrl(self::RESEND_API_URL)
            ->withToken($this->settings->resend_api_key)
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Parse Resend API error messages into user-friendly Arabic messages.
     */
    private function parseApiError(?array $error): string
    {
        if (!$error) {
            return 'خطأ غير معروف من Resend API.';
        }

        $message = $error['message'] ?? '';
        $name = $error['name'] ?? '';

        // Authentication errors
        if (str_contains(strtolower($message), 'unauthorized') || 
            str_contains(strtolower($name), 'unauthorized')) {
            return 'مفتاح Resend API غير صحيح أو منتهي الصلاحية.';
        }

        // Sender verification errors
        if (str_contains(strtolower($message), 'sender') || 
            str_contains(strtolower($message), 'domain')) {
            return 'عنوان المرسل غير موثق في Resend. يجب توثيق النطاق أولاً.';
        }

        // Rate limiting
        if (str_contains(strtolower($message), 'rate limit') || 
            str_contains(strtolower($name), 'rate limit')) {
            return 'تم تجاوز حد المعدل المسموح به من Resend. حاول مرة أخرى لاحقًا.';
        }

        // Validation errors
        if (str_contains(strtolower($name), 'validation')) {
            return 'بيانات البريد غير صحيحة: ' . $message;
        }

        return 'خطأ من Resend API: ' . ($message ?: $name);
    }

    /**
     * Inject logo into HTML email content.
     */
    private function injectLogo(string $html): string
    {
        $logoUrl = $this->settings->getLogoUrlForEmail();

        if (!$logoUrl) {
            return $html;
        }

        // Replace placeholder or inject at top
        if (str_contains($html, '{{logo}}')) {
            return str_replace('{{logo}}', $logoUrl, $html);
        }

        // Inject at the beginning of body
        $logoHtml = '<div style="text-align: center; margin-bottom: 20px;">' .
                    '<img src="' . $logoUrl . '" alt="وجهتك" style="max-width: 200px; height: auto;">' .
                    '</div>';

        if (str_contains($html, '<body')) {
            return preg_replace('/(<body[^>]*>)/i', '$1' . $logoHtml, $html, 1);
        }

        return $logoHtml . $html;
    }

    /**
     * Get test email HTML template.
     */
    private function getTestEmailTemplate(): string
    {
        $logoUrl = $this->settings->getLogoUrlForEmail();
        $logoHtml = $logoUrl 
            ? '<img src="' . $logoUrl . '" alt="وجهتك" style="max-width: 150px; height: auto; margin-bottom: 20px;">' 
            : '';

        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسالة تجريبية</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="text-align: center; background: linear-gradient(135deg, #075E4A, #0E8A6D); padding: 30px; border-radius: 10px 10px 0 0;">
            {$logoHtml}
            <h1 style="color: white; margin: 0; font-size: 24px;">وجهتك</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0;">منصتك العقارية الموثوقة</p>
        </div>
        
        <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
            <h2 style="color: #075E4A; margin-top: 0;">رسالة تجريبية ✉️</h2>
            <p>أهلًا بك!</p>
            <p>هذه رسالة تجريبية تأكد أن إعدادات البريد في لوحة تحكم وجهتك تعمل بشكل صحيح.</p>
            <p>إن وصلتك هذه الرسالة، فكل رسائل المنصة (رموز التحقق، إشعارات العقارات، التوثيق) ستصل بنفس الطريقة.</p>
            
            <div style="background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0; border-right: 4px solid #075E4A;">
                <strong>تفاصيل الاختبار:</strong>
                <ul style="margin: 10px 0; padding-right: 20px;">
                    <li>المزود: Resend API</li>
                    <li>التاريخ: {$this->formatDate(now())}</li>
                    <li>الحالة: ناجح ✓</li>
                </ul>
            </div>
            
            <p>مع تحيات فريق وجهتك العقارية.</p>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
            <p>هذه رسالة آلية من منصة وجهتك - لا ترد عليها.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Format date for Arabic locale.
     */
    private function formatDate($date): string
    {
        return $date->format('Y-m-d H:i');
    }
}
