<?php

namespace App\Services\Email;

use App\Services\Mail\MailSettingsService;
use Illuminate\Support\Facades\Cache;

/**
 * فاحص قابلية تسليم البريد — يمنع الإرسال إلى عناوين وهمية أو نطاقات
 * لا تستقبل بريدًا. طبقات الفحص (بالتسلسل):
 *   1) صيغة RFC صحيحة + حرف محلي غير فارغ.
 *   2) لا كلمات مرتبطة بالبريد المؤقت/المرمي (disposable).
 *   3) النطاق لديه سجلات MX (أو A كبديل مقبول) — أي نطاق لا يستقبل بريدًا يُرفض.
 *   4) (اختياري خفيف) فحص رمز SMTP: RCPT TO بدون DATA — يكشف العناوين
 *      غير الموجودة على الخوادم التي تكشفها، ويتجاهل الخوادم المتساهلة
 *      (greylist/accept-all) بدون رفض خاطئ.
 */
class EmailDeliverabilityService
{
    public function __construct(private readonly MailSettingsService $mailSettings) {}

    /**
     * فحص شامل لبريد مستلم محتمل.
     *
     * @return array{valid_format: bool, deliverable: bool, reason: ?string, mx: ?string}
     */
    public function verify(string $email): array
    {
        $email = trim(mb_strtolower($email));

        // 1) الصيغة.
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid_format' => false, 'deliverable' => false, 'reason' => 'صيغة البريد الإلكتروني غير صحيحة.', 'mx' => null];
        }

        [$local, $domain] = explode('@', $email, 2);
        if ($local === '' || $domain === '' || ! str_contains($domain, '.')) {
            return ['valid_format' => false, 'deliverable' => false, 'reason' => 'صيغة البريد الإلكتروني غير صحيحة.', 'mx' => null];
        }

        // 2) بريد مؤقت/مرمي.
        if ($this->isDisposable($domain)) {
            return ['valid_format' => true, 'deliverable' => false, 'reason' => 'لا نقبل عناوين البريد المؤقتة/المرمية — استخدم بريدك الحقيقي.', 'mx' => null];
        }

        // 3) MX الحقيقي للنطاق (كاش 6 ساعات لتخفيف DNS).
        $mx = $this->bestMx($domain);
        if ($mx === null && ! (bool) $this->mailSettings->get('mail_require_mx_check', true)) {
            $mx = $domain; // السماح الاعتيادي بفحص MX — يُدار من اللوحة.
        }
        if ($mx === null) {
            return ['valid_format' => true, 'deliverable' => false, 'reason' => "النطاق {$domain} لا يستقبل بريدًا إلكترونيًا (لا سجلات MX).", 'mx' => null];
        }

        return ['valid_format' => true, 'deliverable' => true, 'reason' => null, 'mx' => $mx];
    }

    /** اختصار مريح: صالح للتسليم فقط؟ */
    public function isDeliverable(string $email): bool
    {
        return $this->verify($email)['deliverable'];
    }

    /**
     * فحص رمز SMTP خفيف (MAIL FROM + RCPT TO بدون DATA) لكشف العنوان
     * غير الموجود. يُستخدم فقط في مسار إرسال رمز التحقق، ولا يُفشل
     * الإرسال إلا عند رفض صريح من الخادم (550/551/553) — أما 250/251/450
     * (greylisting/accept-all) فتُعتبر مقبولة كي لا نرفض بريدًا حقيقيًا.
     */
    public function smtpProbe(string $email): ?string
    {
        $values = $this->mailSettings->all();
        if ($values['mail_host'] === '' || $values['mail_username'] === '') {
            return null; // بلا SMTP مضبوط: نتجاوز الفحص الرمزي.
        }

        $mx = $this->bestMx(explode('@', $email, 2)[1]);
        if ($mx === null) {
            return null;
        }

        $transport = $values['mail_encryption'] === 'ssl' ? 'ssl://' : '';
        $timeout = min(15, max(3, $values['mail_timeout']));
        $socket = @fsockopen($transport.$mx, $values['mail_port'], $errno, $errstr, $timeout);
        if ($socket === false) {
            return null; // تعذر الفحص — لا نمنع الإرسال بسببه.
        }
        stream_set_timeout($socket, $timeout);

        try {
            $this->readAll($socket);
            $ehloHost = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
            $this->cmd($socket, "EHLO {$ehloHost}");

            if ($values['mail_encryption'] === 'tls' && str_contains(strtolower($this->lastRaw), 'starttls')) {
                if ($this->cmd($socket, 'STARTTLS')['code'] === 220) {
                    if (@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                        $this->cmd($socket, "EHLO {$ehloHost}");
                    }
                }
            }

            if ($values['mail_username'] !== '' && $values['mail_password'] !== '') {
                if ($this->cmd($socket, 'AUTH LOGIN')['code'] === 334) {
                    $this->cmd($socket, base64_encode($values['mail_username']));
                    $this->cmd($socket, base64_encode($values['mail_password']));
                }
            }

            $from = $this->cmd($socket, 'MAIL FROM:<'.$values['mail_from_address'].'>');
            if ($from['code'] >= 400) {
                return null;
            }

            $rcpt = $this->cmd($socket, 'RCPT TO:<'.$email.'>');
            $this->cmd($socket, 'RSET');
            $this->cmd($socket, 'QUIT');

            // رفض صريح = العنوان غير موجود على الخادم.
            if (in_array($rcpt['code'], [550, 551, 553], true)) {
                return 'خادم البريد يرفض هذا العنوان — يبدو أنه غير موجود.';
            }

            return null;
        } catch (\Throwable) {
            return null; // أي خلل في الفحص لا يمنع الإرسال.
        } finally {
            fclose($socket);
        }
    }

    // ------------------------------------------------------------------

    /** أفضل سجل MX للنطاق مع كاش، أو null. */
    private function bestMx(string $domain): ?string
    {
        $key = 'wajhatak_mx_'.md5($domain);

        return Cache::remember($key, now()->addHours(6), function () use ($domain) {
            $hosts = [];
            if (function_exists('getmxrr') && @getmxrr($domain, $mxHosts, $mxWeights)) {
                foreach ($mxHosts as $i => $host) {
                    $hosts[$host] = $mxWeights[$i] ?? 10;
                }
                asort($hosts);
                $best = array_key_first($hosts);
            } else {
                // بديل مقبول في RFC 5321: A/AAAA مباشر إن لم يوجد MX.
                $a = @dns_get_record($domain, DNS_A);
                $aaaa = @dns_get_record($domain, DNS_AAAA);
                $best = (! empty($a) || ! empty($aaaa)) ? $domain : null;
            }

            return $best ?? '';
        }) ?: null;
    }

    /** نطاقات البريد المؤقت المعروفة (قائمة مركزة من أشهر الخدمات). */
    private function isDisposable(string $domain): bool
    {
        $disposable = [
            'mailinator.com', '10minutemail.com', 'guerrillamail.com', 'yopmail.com',
            'tempmail.com', 'temp-mail.org', 'throwawaymail.com', 'getnada.com',
            'trashmail.com', 'sharklasers.com', 'grr.la', 'dispostable.com',
            'maildrop.cc', 'mailnesia.com', 'mintemail.com', 'mohmal.com',
            'tempail.com', 'tempr.email', '1secmail.com', '1secmail.org',
            'emailondeck.com', 'fakeinbox.com', 'jetable.org', 'mailcatch.com',
            'spam4.me', 'tempinbox.com', 'discard.email', 'mailsac.com',
            'inboxbear.com', 'tmpmail.org', 'emailtemporanea.net', 'mytemp.email',
        ];

        foreach ($disposable as $bad) {
            if ($domain === $bad || str_ends_with($domain, '.'.$bad)) {
                return true;
            }
        }

        return false;
    }

    private string $lastRaw = '';

    private function readAll($socket): string
    {
        $raw = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $raw .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $this->lastRaw = $raw;

        return $raw;
    }

    /** @return array{code: ?int} */
    private function cmd($socket, string $command): array
    {
        fwrite($socket, $command."\r\n");
        $raw = $this->readAll($socket);
        $code = strlen($raw) >= 3 ? (int) substr($raw, 0, 3) : null;

        return ['code' => $code];
    }
}
