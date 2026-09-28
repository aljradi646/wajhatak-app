<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Mail\DynamicMailService;
use App\Services\Mail\MailSettingsService;
use Illuminate\Http\Request;

/**
 * قسم «إعدادات البريد الإلكتروني» في لوحة التحكم:
 * بيانات SMTP التي يدخلها المدير + زر اختبار اتصال حقيقي
 * + قوالب الرسائل القابلة للتعديل + إرسال رسالة تجريبية.
 */
class MailSettingsController extends Controller
{
    public function __construct(
        private readonly MailSettingsService $mailSettings,
        private readonly DynamicMailService $mailer,
    ) {}

    public function index()
    {
        return view('admin.mail.index', [
            'values' => $this->mailSettings->all(),
            'configured' => $this->mailSettings->isConfigured(),
            'templates' => MailSettingsService::TEMPLATES,
            'templateValues' => collect(array_keys(MailSettingsService::TEMPLATES))
                ->mapWithKeys(fn ($key) => [$key => $this->mailSettings->template($key)])
                ->all(),
        ]);
    }

    /** حفظ بيانات SMTP. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'mail_host' => ['nullable', 'string', 'max:190'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'mail_username' => ['nullable', 'string', 'max:190'],
            'mail_password' => ['nullable', 'string', 'max:190'],
            'mail_timeout' => ['nullable', 'integer', 'min:3', 'max:60'],
            'mail_from_address' => ['nullable', 'email', 'max:190'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
            'mail_reply_to' => ['nullable', 'email', 'max:190'],
            'mail_verification_code_ttl' => ['nullable', 'integer', 'min:5', 'max:120'],
            'mail_verification_max_attempts' => ['nullable', 'integer', 'min:3', 'max:10'],
            'mail_verification_resend_seconds' => ['nullable', 'integer', 'min:15', 'max:600'],
            'mail_require_mx_check' => ['nullable', 'boolean'],
        ], [
            'mail_host.max' => 'عنوان الخادم طويل جدًا.',
            'mail_port.integer' => 'المنفذ يجب أن يكون رقمًا.',
            'mail_encryption.in' => 'التشفير يجب أن يكون tls أو ssl أو none.',
            'mail_from_address.email' => 'بريد المُرسل غير صحيح.',
            'mail_reply_to.email' => 'بريد الرد غير صحيح.',
        ]);

        $this->mailSettings->putMany($data);
        ActivityLog::record('mail', 'تم تحديث إعدادات البريد الإلكتروني');

        return back()->with('status', 'تم حفظ إعدادات البريد بنجاح. لا تنسَ اختبار الاتصال.');
    }

    /** POST اختبار الاتصال الحقيقي: EHLO + AUTH + (RCPT اختياري). */
    public function testConnection(Request $request)
    {
        $data = $request->validate([
            'test_recipient' => ['nullable', 'email', 'max:190'],
        ]);

        // حفظ القيم المدخلة أولًا لاختبار ما كتبه المدير فعليًا (كلمة المرور مضمّنة).
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

    /** POST إرسال رسالة تجريبية حقيقية إلى بريد المدير. */
    public function sendTest(Request $request)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:190'],
        ], [
            'test_email.required' => 'أدخل بريدًا لإرسال الرسالة التجريبية.',
            'test_email.email' => 'البريد الإلكتروني غير صحيح.',
        ]);

        try {
            $this->mailer->send(
                $data['test_email'],
                'رسالة تجريبية من وجهتك ✉️',
                "أهلًا!\n\nهذه رسالة تجريبية تأكد أن إعدادات البريد في لوحة تحكم وجهتك تعمل بشكل صحيح.\n\nإن وصلتك هذه الرسالة فكل رسائل المنصة (رموز التحقق، إشعارات العقارات، التوثيق) ستصل بنفس الطريقة.\n\nفريق وجهتك.",
            );
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
}
