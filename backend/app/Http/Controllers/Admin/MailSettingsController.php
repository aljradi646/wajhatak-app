<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EmailSetting;
use App\Services\Mail\MailSettingsService;
use App\Services\Mail\ResendService;
use App\Services\Mail\UnifiedMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * قسم «إعدادات البريد الإلكتروني» في لوحة التحكم:
 * - دعم SMTP و Resend API
 * - رفع شعار البريد
 * - اختبار الاتصال والإرسال
 * - قوالب الرسائل القابلة للتعديل
 */
class MailSettingsController extends Controller
{
    public function __construct(
        private readonly MailSettingsService $mailSettings,
        private readonly UnifiedMailService $mailer,
        private readonly ResendService $resendService,
    ) {}

    public function index()
    {
        $settings = EmailSetting::current();

        return view('admin.mail.index', [
            'settings' => $settings,
            'legacyValues' => $this->mailSettings->all(),
            'legacyConfigured' => $this->mailSettings->isConfigured(),
            'templates' => MailSettingsService::TEMPLATES,
            'templateValues' => collect(array_keys(MailSettingsService::TEMPLATES))
                ->mapWithKeys(fn ($key) => [$key => $this->mailSettings->template($key)])
                ->all(),
        ]);
    }

    /** حفظ إعدادات البريد (SMTP أو Resend). */
    public function update(Request $request)
    {
        $settings = EmailSetting::current();

        $data = $request->validate([
            'provider' => ['required', 'in:smtp,resend'],
            
            // SMTP settings
            'smtp_host' => ['nullable', 'string', 'max:190', 'required_if:provider,smtp'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535', 'required_if:provider,smtp'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl,none', 'required_if:provider,smtp'],
            'smtp_username' => ['nullable', 'string', 'max:190', 'required_if:provider,smtp'],
            'smtp_password' => ['nullable', 'string', 'max:190'],
            'smtp_timeout' => ['nullable', 'integer', 'min:3', 'max:60'],
            
            // Resend settings
            'resend_api_key' => ['nullable', 'string', 'max:255', 'required_if:provider,resend'],
            'resend_sandbox' => ['nullable', 'boolean'],
            
            // Common settings
            'from_name' => ['required', 'string', 'max:120'],
            'from_address' => ['required', 'email', 'max:190'],
            'reply_to' => ['nullable', 'email', 'max:190'],
            
            // Activation
            'is_active' => ['nullable', 'boolean'],
        ], [
            'provider.required' => 'اختر مزود البريد.',
            'smtp_host.required_if' => 'عنوان خادم SMTP مطلوب.',
            'smtp_port.required_if' => 'منفذ SMTP مطلوب.',
            'smtp_encryption.required_if' => 'طريقة التشفير مطلوبة.',
            'smtp_username.required_if' => 'اسم مستخدم SMTP مطلوب.',
            'resend_api_key.required_if' => 'مفتاح Resend API مطلوب.',
            'from_name.required' => 'اسم المرسل مطلوب.',
            'from_address.required' => 'بريد المرسل مطلوب.',
            'from_address.email' => 'بريد المرسل غير صحيح.',
            'reply_to.email' => 'بريد الرد غير صحيح.',
        ]);

        // Update settings
        $settings->update([
            'provider' => $data['provider'],
            'smtp_host' => $data['smtp_host'] ?? null,
            'smtp_port' => $data['smtp_port'] ?? null,
            'smtp_encryption' => $data['smtp_encryption'] ?? null,
            'smtp_username' => $data['smtp_username'] ?? null,
            // Only update password if provided
            'smtp_password' => !empty($data['smtp_password']) ? $data['smtp_password'] : $settings->smtp_password,
            'smtp_timeout' => $data['smtp_timeout'] ?? 15,
            'resend_api_key' => !empty($data['resend_api_key']) ? $data['resend_api_key'] : $settings->resend_api_key,
            'resend_sandbox' => $data['resend_sandbox'] ?? false,
            'from_name' => $data['from_name'],
            'from_address' => $data['from_address'],
            'reply_to' => $data['reply_to'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        ActivityLog::record('mail', 'تم تحديث إعدادات البريد الإلكتروني');

        return back()->with('status', 'تم حفظ إعدادات البريد بنجاح.');
    }

    /** رفع شعار البريد. */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg', 'max:512'],
        ], [
            'logo.required' => 'اختر صورة الشعار.',
            'logo.image' => 'الملف يجب أن يكون صورة.',
            'logo.mimes' => 'الصيغ المدعومة: PNG, JPG, JPEG, SVG.',
            'logo.max' => 'حجم الصورة يجب أن يكون أقل من 512KB.',
        ]);

        $settings = EmailSetting::current();

        // Delete old logo
        if ($settings->logo_path && Storage::disk('public')->exists($settings->logo_path)) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        // Upload new logo
        $path = $request->file('logo')->store('email-logos', 'public');
        $url = Storage::disk('public')->url($path);

        $settings->update([
            'logo_path' => $path,
            'logo_url' => $url,
        ]);

        ActivityLog::record('mail', 'تم تحديث شعار البريد');

        return back()->with('status', 'تم رفع الشعار بنجاح.');
    }

    /** حذف شعار البريد. */
    public function deleteLogo()
    {
        $settings = EmailSetting::current();

        if ($settings->logo_path && Storage::disk('public')->exists($settings->logo_path)) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        $settings->update([
            'logo_path' => null,
            'logo_url' => null,
        ]);

        ActivityLog::record('mail', 'تم حذف شعار البريد');

        return back()->with('status', 'تم حذف الشعار.');
    }

    /** اختبار اتصال Resend API عن طريق إرسال رسالة اختبار. */
    public function testResendConnection(Request $request)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:190'],
        ], [
            'test_email.required' => 'أدخل بريدًا لاختبار الاتصال.',
            'test_email.email' => 'البريد الإلكتروني غير صحيح.',
        ]);

        $settings = EmailSetting::current();

        if (!$settings->isResend()) {
            return back()->with('error', 'مزود البريد الحالي ليس Resend.');
        }

        // Block test emails
        if (EmailSetting::isTestEmail($data['test_email'])) {
            return back()->with('error', 'لا يمكن اختبار الاتصال باستخدام بريد وهمي أو تجريبي. استخدم بريد حقيقي.');
        }

        $result = $this->resendService->testConnection($data['test_email']);

        $settings->recordTestResult($result['success'], $result['success'] ? null : $result['message']);

        ActivityLog::record('mail', 'اختبار اتصال Resend: '.($result['success'] ? 'ناجح' : 'فاشل'));

        return back()->with(
            $result['success'] ? 'status' : 'error',
            $result['message'],
        );
    }

    /** اختبار اتصال SMTP (legacy). */
    public function testSmtpConnection(Request $request)
    {
        $data = $request->validate([
            'test_recipient' => ['nullable', 'email', 'max:190'],
        ]);

        // حفظ القيم المدخلة أولًا
        $this->mailSettings->putMany($request->only(array_keys(MailSettingsService::DEFAULTS)));

        $result = $this->mailer->testConnection($data['test_recipient'] ?? null);

        ActivityLog::record('mail', 'اختبار اتصال SMTP: '.($result['ok'] ? 'ناجح' : 'فاشل'));

        if ($request->boolean('ajax')) {
            return response()->json(['data' => $result]);
        }

        return back()->with(
            $result['ok'] ? 'status' : 'error',
            $result['message'],
        );
    }

    /** إرسال رسالة تجريبية. */
    public function sendTest(Request $request)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:190'],
        ], [
            'test_email.required' => 'أدخل بريدًا لإرسال الرسالة التجريبية.',
            'test_email.email' => 'البريد الإلكتروني غير صحيح.',
        ]);

        $settings = EmailSetting::current();

        // Block test emails
        if (EmailSetting::isTestEmail($data['test_email'])) {
            return back()->with('error', 'لا يمكن إرسال رسائل إلى عناوين بريد وهمية أو تجريبية.');
        }

        try {
            if ($settings->isResend()) {
                $result = $this->resendService->sendTestEmail($data['test_email']);
                
                if (!$result['success']) {
                    return back()->with('error', $result['message']);
                }
            } else {
                // For SMTP, use the legacy DynamicMailService directly for test
                app(\App\Services\Mail\DynamicMailService::class)->send(
                    $data['test_email'],
                    'رسالة تجريبية من وجهتك ✉️',
                    "أهلًا!\n\nهذه رسالة تجريبية تأكد أن إعدادات البريد في لوحة تحكم وجهتك تعمل بشكل صحيح.\n\nإن وصلتك هذه الرسالة فكل رسائل المنصة (رموز التحقق، إشعارات العقارات، التوثيق) ستصل بنفس الطريقة.\n\nفريق وجهتك.",
                );
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'فشل إرسال الرسالة التجريبية: '.$e->getMessage());
        }

        ActivityLog::record('mail', "رسالة تجريبية أُرسلت إلى {$data['test_email']}");

        return back()->with('status', "تم إرسال رسالة تجريبية إلى {$data['test_email']} — افحص صندوق الوارد.");
    }

    /** حفظ قوالب الرسائل. */
    public function updateTemplates(Request $request)
    {
        foreach (MailSettingsService::TEMPLATES as $key => $meta) {
            $subject = trim((string) $request->input("templates.{$key}.subject", ''));
            $body = trim((string) $request->input("templates.{$key}.body", ''));

            if ($subject !== '' && $body !== '') {
                $this->mailSettings->saveTemplate($key, $subject, $body);
            }
        }

        ActivityLog::record('mail', 'تم تحديث قوالب رسائل البريد');

        return back()->with('status', 'تم حفظ قوالب الرسائل بنجاح — تُستخدم في كل الإرسالات القادمة.');
    }

    /** معاينة رسالة البريد. */
    public function previewEmail(Request $request)
    {
        $data = $request->validate([
            'template_key' => ['required', 'string'],
        ]);

        $settings = EmailSetting::current();

        // Sample data for preview
        $sampleData = [
            'name' => 'أحمد محمد',
            'code' => '123456',
            'ttl' => '15',
            'reason' => 'سبب الرفض',
            'property' => 'شقة في الرياض',
            'email' => 'ahmed@example.com',
        ];

        // Try new template system first
        $template = \App\Models\EmailTemplate::findByKey($data['template_key']);
        
        if ($template) {
            $rendered = $template->render($sampleData);
            $html = $rendered['html'];
            
            // Inject logo for preview
            $logoUrl = $settings->getLogoUrlForEmail();
            if ($logoUrl) {
                $html = str_replace('{{logo}}', '<img src="' . $logoUrl . '" alt="وجهتك" style="max-width: 150px; height: auto;">', $html);
            } else {
                $html = str_replace('{{logo}}', '', $html);
            }
        } else {
            // Fallback to legacy system
            $legacyTemplate = $this->mailSettings->renderTemplate($data['template_key'], $sampleData);
            $html = $this->convertToHtml($legacyTemplate['body'], $settings);
        }

        return response()->json([
            'html' => $html,
        ]);
    }

    /**
     * Convert plain text to HTML for preview (legacy fallback).
     */
    private function convertToHtml(string $text, EmailSetting $settings): string
    {
        $logoUrl = $settings->getLogoUrlForEmail();
        $logoHtml = $logoUrl 
            ? '<img src="' . $logoUrl . '" alt="وجهتك" style="max-width: 150px; height: auto; margin-bottom: 20px;">' 
            : '';

        $body = nl2br(e($text));

        return <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة الرسالة</title>
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
                معاينة رسالة من منصة وجهتك العقارية
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
