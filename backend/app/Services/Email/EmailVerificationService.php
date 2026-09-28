<?php

namespace App\Services\Email;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\Mail\DynamicMailService;
use App\Services\Mail\MailSettingsService;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * التحقق الحقيقي من البريد الإلكتروني برمز مُرسل إلى الصندوق الفعلي:
 *   1) فحص قابلية التسليم (صيغة + MX + رفض البريد المؤقت) — لا رمز لبريد وهمي.
 *   2) حد زمني بين الإرسالات + حد محاولات الإدخال.
 *   3) الرمز 6 أرقام يُخزن مجزّأً (Hash) فقط ويصل إلى البريد الحقيقي.
 *   4) عند التأكيد الصحيح يُعلَّم user->email_verified_at فورًا.
 */
class EmailVerificationService
{
    public function __construct(
        private readonly MailSettingsService $mailSettings,
        private readonly DynamicMailService $mailer,
        private readonly EmailDeliverabilityService $deliverability,
    ) {}

    /**
     * إرسال رمز جديد إلى البريد (بعد فحص التسليم والحد الزمني).
     *
     * @return array{ok: bool, message: string, resend_in: int}
     */
    public function sendCode(User $user): array
    {
        $email = mb_strtolower(trim($user->email));
        $ttl = max(5, (int) $this->mailSettings->get('mail_verification_code_ttl', 15));
        $resendSeconds = max(15, (int) $this->mailSettings->get('mail_verification_resend_seconds', 60));

        // 1) البريد يجب أن يكون صالحًا وقابلًا للتسليم فعليًا.
        $check = $this->deliverability->verify($email);
        if (! $check['deliverable']) {
            return ['ok' => false, 'message' => $check['reason'] ?? 'البريد غير قابل للتسليم.', 'resend_in' => 0];
        }

        // 2) حد الإرسالات الزمني.
        $last = $user->email_code_sent_at;
        if ($last !== null && $last->diffInSeconds(now()) < $resendSeconds) {
            $remaining = $resendSeconds - (int) $last->diffInSeconds(now());

            return [
                'ok' => false,
                'message' => 'انتظر '.max(1, $remaining).' ثانية قبل إعادة إرسال الرمز.',
                'resend_in' => max(1, $remaining),
            ];
        }

        // 3) توليد الرمز وتخزينه مجزّأً.
        $code = (string) random_int(100000, 999999);
        EmailVerificationCode::query()->create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl),
        ]);

        // 4) الإرسال الفعلي عبر SMTP المُعد من اللوحة — أي فشل يظهر للمستخدم.
        try {
            $this->mailer->sendTemplate($email, 'email_verification', [
                'name' => $user->name,
                'code' => $code,
                'ttl' => (string) $ttl,
            ]);
        } catch (Throwable $e) {
            report($e);

            return [
                'ok' => false,
                'message' => 'تعذر إرسال البريد الآن: '.$e->getMessage(),
                'resend_in' => 0,
            ];
        }

        $user->forceFill(['email_code_sent_at' => now()])->save();

        return ['ok' => true, 'message' => 'تم إرسال رمز التحقق إلى بريدك — افحص صندوق الوارد.', 'resend_in' => $resendSeconds];
    }

    /**
     * تأكيد الرمز وتفعيل تحقق البريد.
     *
     * @return array{ok: bool, message: string}
     */
    public function confirm(User $user, string $code): array
    {
        $email = mb_strtolower(trim($user->email));
        $maxAttempts = max(3, (int) $this->mailSettings->get('mail_verification_max_attempts', 5));

        $record = EmailVerificationCode::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($record === null) {
            return ['ok' => false, 'message' => 'لا يوجد رمز صالح — اطلب رمزًا جديدًا.'];
        }

        if ($record->attempts >= $maxAttempts) {
            $record->update(['consumed_at' => now()]);

            return ['ok' => false, 'message' => 'تجاوزت عدد المحاولات المسموح — اطلب رمزًا جديدًا.'];
        }

        $record->increment('attempts');

        if (! Hash::check(trim($code), $record->code_hash)) {
            return ['ok' => false, 'message' => 'الرمز غير صحيح — تحقق من الرسالة وأعد المحاولة.'];
        }

        $record->update(['consumed_at' => now()]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return ['ok' => true, 'message' => 'تم توثيق بريدك الإلكتروني بنجاح ✓'];
    }

    /** ثوانٍ متبقية قبل السماح بإعادة الإرسال (للواجهة). */
    public function resendIn(User $user): int
    {
        $resendSeconds = max(15, (int) $this->mailSettings->get('mail_verification_resend_seconds', 60));
        $last = $user->email_code_sent_at;
        if ($last === null) {
            return 0;
        }

        return max(0, $resendSeconds - (int) $last->diffInSeconds(now()));
    }
}
