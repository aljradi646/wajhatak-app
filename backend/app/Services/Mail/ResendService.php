<?php

namespace App\Services\Mail;

use App\Models\EmailSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Resend API integration used by the production mail pipeline.
 *
 * Important:
 * - Sandbox/testing restrictions are imposed by Resend and cannot be bypassed by the app.
 * - Production sending must use a verified sender domain and resend_sandbox=false.
 * - API keys are read only from the encrypted EmailSetting model.
 */
final class ResendService
{
    private const RESEND_API_URL = 'https://api.resend.com';
    private const SANDBOX_SENDER = 'onboarding@resend.dev';

    /**
     * @return array{success:bool,message:string,data?:array<string,mixed>}
     */
    public function send(
        string $to,
        string $subject,
        ?string $html = null,
        ?string $text = null,
        array $attachments = []
    ): array {
        $settings = EmailSetting::current();

        if (EmailSetting::isTestEmail($to)) {
            return [
                'success' => false,
                'message' => 'لا يمكن إرسال رسائل إلى نطاقات بريد تجريبية أو وهمية.',
            ];
        }

        if (! $settings->canSend()) {
            return [
                'success' => false,
                'message' => 'إعدادات البريد غير مفعلة أو غير مكتملة. تحقق من المزود، مفتاح API، وعنوان From.',
            ];
        }

        if (! $settings->isResend()) {
            return [
                'success' => false,
                'message' => 'مزود البريد الحالي ليس Resend.',
            ];
        }

        $apiKey = trim((string) $settings->resend_api_key);
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'مفتاح Resend API غير موجود.',
            ];
        }

        $sender = trim($settings->getSenderEmail());
        $senderName = trim((string) ($settings->from_name ?: 'وجهتك'));

        if ($sender === '') {
            return [
                'success' => false,
                'message' => 'عنوان المرسل From غير مضبوط.',
            ];
        }

        $payload = [
            'from' => $senderName !== '' ? $senderName.' <'.$sender.'>' : $sender,
            'to' => [$to],
            'subject' => $subject,
        ];

        $logoAttachment = $this->prepareLogoAttachment($settings);

        if ($html !== null && trim($html) !== '') {
            $payload['html'] = $this->injectLogo($html, $logoAttachment['cid'] ?? null, $settings);
        }

        if ($text !== null && trim($text) !== '') {
            $payload['text'] = $text;
        }

        if ($settings->reply_to) {
            $payload['reply_to'] = $settings->reply_to;
        }
        if ($logoAttachment) {
            $payload['attachments'] = array_merge([$logoAttachment['attachment']], $attachments);
        } elseif ($attachments !== []) {
            $payload['attachments'] = $attachments;
        }

        try {
            $response = $this->httpClient($apiKey)->post('/emails', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'تم إرسال البريد بنجاح عبر Resend.',
                    'data' => is_array($response->json()) ? $response->json() : [],
                ];
            }

            $error = $response->json();
            if (! is_array($error)) {
                $error = [];
            }

            return [
                'success' => false,
                'message' => $this->parseApiError($error, $response->status(), $settings),
                'data' => $error,
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'تعذر الاتصال بخدمة Resend: '.$e->getMessage(),
            ];
        }
    }

    /**
     * This is an actual email send through POST /emails, not a fake connectivity check.
     *
     * @return array{success:bool,message:string,details?:array<string,mixed>}
     */
    public function testConnection(string $to): array
    {
        $settings = EmailSetting::current();

        if (! $settings->isResend()) {
            return [
                'success' => false,
                'message' => 'مزود البريد الحالي ليس Resend.',
            ];
        }

        if (trim((string) $settings->resend_api_key) === '') {
            return [
                'success' => false,
                'message' => 'مفتاح Resend API غير موجود.',
            ];
        }

        if (EmailSetting::isTestEmail($to)) {
            return [
                'success' => false,
                'message' => 'استخدم بريدًا حقيقيًا للاختبار، وليس نطاقًا تجريبيًا.',
            ];
        }

        $result = $this->sendTestEmail($to);

        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'details' => array_merge(
                [
                    'recipient' => $to,
                    'sandbox_mode' => (bool) $settings->resend_sandbox,
                    'sender' => $settings->getSenderEmail(),
                ],
                $result['data']['id'] ?? false ? ['resend_id' => $result['data']['id']] : [],
            ),
        ];
    }

    /** @return array{success:bool,message:string,data?:array<string,mixed>} */
    public function sendTestEmail(string $to): array
    {
        $html = $this->getTestEmailTemplate();
        $text = 'هذه رسالة اختبار حقيقية من منصة وجهتك عبر Resend.';

        return $this->send($to, 'اختبار بريد وجهتك عبر Resend', $html, $text);
    }

    private function httpClient(string $apiKey): PendingRequest
    {
        return Http::baseUrl(self::RESEND_API_URL)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'User-Agent' => 'Wajhatak-Mail/1.0',
            ])
            ->timeout(30)
            ->retry(2, 250, null, false);
    }

    private function parseApiError(array $error, int $status, EmailSetting $settings): string
    {
        $message = trim((string) ($error['message'] ?? ''));
        $name = trim((string) ($error['name'] ?? ''));
        $lower = strtolower($message.' '.$name);

        if ($status === 403 && str_contains($lower, 'only send testing emails to your own email address')) {
            return 'Resend في وضع الاختبار (Sandbox)، ولذلك يسمح بالإرسال فقط إلى بريد حساب Resend نفسه. لتعطيل هذا القيد: عطّل Sandbox من إعدادات وجهتك، وثبّت نطاق الإرسال في Resend، ثم استخدم عنوان From من النطاق الموثق.';
        }

        if ($status === 401 || str_contains($lower, 'unauthorized') || str_contains($lower, 'invalid api key')) {
            return 'مفتاح Resend API غير صحيح أو غير مصرح به. أنشئ مفتاحًا جديدًا من لوحة Resend ثم احفظه من إعدادات البريد.';
        }

        if ($status === 403 && (str_contains($lower, 'domain') || str_contains($lower, 'sender') || str_contains($lower, 'from'))) {
            return 'Resend رفض عنوان المرسل. وثّق النطاق في Resend واستخدم بريدًا من النطاق الموثق، أو فعّل Sandbox للاختبار المحدود.';
        }

        if ($status === 422 || str_contains($lower, 'validation')) {
            return 'Resend رفض بيانات الرسالة: '.($message !== '' ? $message : 'تحقق من From وTo وSubject ومحتوى البريد.');
        }

        if ($status === 429 || str_contains($lower, 'rate limit')) {
            return 'تم تجاوز حد الإرسال في Resend. حاول بعد فترة قصيرة أو تحقق من حدود الحساب.';
        }

        if ($status >= 500) {
            return 'خدمة Resend أعادت خطأ خادم (HTTP '.$status.'). أعد المحاولة وتحقق من حالة الخدمة.';
        }

        if ($message !== '') {
            return 'خطأ من Resend (HTTP '.$status.'): '.$message;
        }

        return 'فشل طلب Resend (HTTP '.$status.').';
    }

    /**
     * @return array{attachment:array<string,mixed>,cid:string}|null
     */
    private function prepareLogoAttachment(EmailSetting $settings): ?array
    {
        $logoPath = $settings->logo_path;
        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        try {
            $content = Storage::disk('public')->get($logoPath);
            $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
            $filename = basename($logoPath);
            $cid = 'logo@wajhatak';

            return [
                'cid' => $cid,
                'attachment' => [
                    'content' => base64_encode($content),
                    'filename' => $filename,
                    'content_type' => $mime,
                    'content_id' => $cid,
                ],
            ];
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function injectLogo(string $html, ?string $cid, EmailSetting $settings): string
    {
        $logoUrl = $settings->getLogoUrlForEmail();
        $logoSrc = $cid ? 'cid:'.$cid : $logoUrl;

        if (! $logoSrc) {
            return $html;
        }

        $html = str_replace('{{logo}}', $logoSrc, $html);

        if ($cid && $logoUrl) {
            $html = str_replace($logoUrl, $logoSrc, $html);
        }

        return $html;
    }

    private function getTestEmailTemplate(): string
    {
        $settings = EmailSetting::current();
        $logoUrl = $settings->getLogoUrlForEmail();
        $logo = $logoUrl
            ? '<img src="'.e($logoUrl).'" alt="وجهتك" width="150" style="display:block;width:150px;max-width:150px;height:auto;margin:0 auto 18px;">'
            : '';

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#f5f7f6;"><tr><td align="center" style="padding:32px 14px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;border-collapse:collapse;background:#ffffff;border-radius:18px;overflow:hidden;"><tr><td style="padding:36px 30px;text-align:center;background:#075e4a;">'.$logo.'<h1 style="margin:0;color:#ffffff;font:700 26px/1.35 Arial,sans-serif;">وجهتك</h1><p style="margin:8px 0 0;color:#dceee9;font:400 15px/1.7 Arial,sans-serif;">اختبار إرسال البريد الإلكتروني</p></td></tr><tr><td style="padding:32px;text-align:right;font:400 16px/1.8 Arial,sans-serif;color:#22312d;"><h2 style="margin:0 0 14px;color:#075e4a;font:700 22px/1.5 Arial,sans-serif;">تم تشغيل اختبار Resend</h2><p style="margin:0 0 12px;">إذا وصلت هذه الرسالة، فالاتصال بـ Resend وعنوان المرسل يعملان فعليًا.</p><p style="margin:0;">راجع لوحة Resend للتأكد من حالة الرسالة والتسليم.</p></td></tr><tr><td style="padding:18px 30px;text-align:center;background:#f7faf9;color:#73817d;font:400 12px/1.6 Arial,sans-serif;">رسالة آلية من منصة وجهتك</td></tr></table></td></tr></table>';
    }
}
