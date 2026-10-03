<?php

namespace App\Services\Mail;

use App\Services\Email\EmailDeliverabilityService;
use Illuminate\Support\Facades\Mail;

/**
 * مُرسل البريد الديناميكي — يبني mailer SMTP لحظيًا من إعدادات قاعدة
 * البيانات (ما كتبه المدير من اللوحة) بدون إعادة نشر ولا مس cache عام.
 * كل رسالة تمر عبر فحص قابلية التسليم أولًا: لا إرسال إلى بريد وهمي
 * أو نطاق بلا سجلات MX أبدًا (متطلب إلزامي).
 */
class DynamicMailService
{
    public function __construct(
        private readonly MailSettingsService $mailSettings,
        private readonly EmailDeliverabilityService $deliverability,
    ) {}

    /**
     * إرسال رسالة نصية/HTML عبر SMTP المُعد من اللوحة.
     * يرمي RuntimeException عند فشل التهيئة أو الإرسال — المتصل يعرض الخطأ.
     *
     * @param  array<string, string>  $headers  رؤوس إضافية اختيارية
     */
    public function send(string $toEmail, string $subject, string $body, array $headers = []): void
    {
        $this->assertConfigured();

        // لا بريد إلى عناوين غير قابلة للتسليم — إطلاقًا.
        $check = $this->deliverability->verify($toEmail);
        if (! $check['deliverable']) {
            throw new \RuntimeException($check['reason'] ?? 'البريد المستهدف غير قابل للتسليم.');
        }

        $from = $this->mailSettings->all();
        $replyTo = $from['mail_reply_to'] !== '' ? $from['mail_reply_to'] : $from['mail_from_address'];

        // بناء mailer SMTP لحظيًا من إعدادات اللوحة (بدون config cache):
        // نكتب الإعداد ثم نمسح النسخة المحلولة كي يلتقط Laravel القيم الجديدة فورًا.
        config()->set('mail.mailers.dynamic_smtp', [
            'transport' => 'smtp',
            'host' => $from['mail_host'],
            'port' => $from['mail_port'],
            'encryption' => $from['mail_encryption'] === 'none' ? null : $from['mail_encryption'],
            'username' => $from['mail_username'],
            'password' => $from['mail_password'],
            'timeout' => $from['mail_timeout'],
            'local_domain' => config('mail.mailers.smtp.local_domain', 'localhost'),
        ]);
        Mail::purge('dynamic_smtp');

        Mail::mailer('dynamic_smtp')->raw($body, function ($message) use ($toEmail, $subject, $from, $replyTo, $headers) {
            $message->to($toEmail)
                ->from($from['mail_from_address'], $from['mail_from_name'])
                ->replyTo($replyTo)
                ->subject($subject);

            foreach ($headers as $name => $value) {
                $message->getHeaders()->addTextHeader($name, $value);
            }
        });
    }

    public function sendHtml(string $toEmail,string $subject,string $html,?string $text=null,array $headers=[]): void
    {
        $this->assertConfigured();
        $check=$this->deliverability->verify($toEmail);
        if(!$check['deliverable']) throw new \RuntimeException($check['reason']??'البريد المستهدف غير قابل للتسليم.');
        $from=$this->mailSettings->all();
        $replyTo=$from['mail_reply_to']!==''?$from['mail_reply_to']:$from['mail_from_address'];
        config()->set('mail.mailers.dynamic_smtp',[
            'transport'=>'smtp','host'=>$from['mail_host'],'port'=>$from['mail_port'],
            'encryption'=>$from['mail_encryption']==='none'?null:$from['mail_encryption'],
            'username'=>$from['mail_username'],'password'=>$from['mail_password'],'timeout'=>$from['mail_timeout'],
            'local_domain'=>config('mail.mailers.smtp.local_domain','localhost'),
        ]);
        Mail::purge('dynamic_smtp');
        Mail::mailer('dynamic_smtp')->html($html,function($message)use($toEmail,$subject,$from,$replyTo,$headers,$text){
            $message->to($toEmail)->from($from['mail_from_address'],$from['mail_from_name'])->replyTo($replyTo)->subject($subject);
            foreach($headers as $name=>$value) $message->getHeaders()->addTextHeader($name,$value);
            if($text!==null&&method_exists($message,'text')) $message->text($text);
        });
    }

    /** إرسال قالب من اللوحة بعد رندرة المتغيرات. */
    public function sendTemplate(string $toEmail, string $templateKey, array $variables = []): void
    {
        $rendered = $this->mailSettings->renderTemplate($templateKey, $variables);
        $this->send($toEmail, $rendered['subject'], $rendered['body']);
    }

    /**
     * اختبار اتصال SMTP حقيقي: فتح مقبس + EHLO + STARTTLS/ AUTH LOGIN.
     * يُستخدم من زر «اختبار الاتصال» في لوحة التحكم. لا يرسل بريدًا.
     *
     * @return array{ok: bool, message: string, details: array<string, mixed>}
     */
    public function testConnection(?string $testRecipient = null): array
    {
        $values = $this->mailSettings->all();

        if ($values['mail_host'] === '') {
            return ['ok' => false, 'message' => 'أدخل عنوان خادم SMTP أولًا.', 'details' => []];
        }

        $host = $values['mail_host'];
        $port = $values['mail_port'];
        $encryption = $values['mail_encryption'];
        $timeout = min(30, max(3, $values['mail_timeout']));

        $transport = match ($encryption) {
            'ssl' => 'ssl://',
            default => '',
        };

        // 1) فتح الاتصال.
        $socket = @fsockopen($transport.$host, $port, $errno, $errstr, $timeout);
        if ($socket === false) {
            return ['ok' => false, 'message' => "تعذر الاتصال بـ {$host}:{$port} — {$errstr} ({$errno})", 'details' => ['step' => 'connect']];
        }
        stream_set_timeout($socket, $timeout);

        try {
            // 2) تحية الخادم.
            $banner = $this->readResponse($socket);
            if (! isset($banner['code']) || $banner['code'] >= 400) {
                return ['ok' => false, 'message' => 'الخادم لم يرحب بالاتصال: '.($banner['line'] ?? 'لا استجابة'), 'details' => ['step' => 'banner']];
            }

            $ehloHost = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
            $dialog = [];

            $response = $this->command($socket, "EHLO {$ehloHost}");
            $dialog[] = $response['raw'];
            if ($response['code'] >= 400) {
                return ['ok' => false, 'message' => 'فشل EHLO: '.$response['raw'], 'details' => ['step' => 'ehlo']];
            }

            $capabilities = strtolower($response['raw']);

            // 3) STARTTLS للاتصال غير المشفر.
            if ($encryption === 'tls' && str_contains($capabilities, 'starttls')) {
                $response = $this->command($socket, 'STARTTLS');
                $dialog[] = $response['raw'];
                if ($response['code'] >= 400) {
                    return ['ok' => false, 'message' => 'فشل بدء STARTTLS: '.$response['raw'], 'details' => ['step' => 'starttls']];
                }
                if (! @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    return ['ok' => false, 'message' => 'فشل تفعيل تشفير TLS مع الخادم.', 'details' => ['step' => 'tls']];
                }
                // EHLO ثانية بعد التشفير.
                $response = $this->command($socket, "EHLO {$ehloHost}");
                $dialog[] = $response['raw'];
                $capabilities = strtolower($response['raw']);
            } elseif ($encryption === 'tls') {
                return ['ok' => false, 'message' => 'الخادم لا يدعم STARTTLS — اختر ssl أو none.', 'details' => ['step' => 'starttls-unsupported']];
            }

            // 4) المصادقة.
            if ($values['mail_username'] !== '') {
                if (! str_contains($capabilities, 'auth')) {
                    return ['ok' => false, 'message' => 'الخادم لا يعلن عن دعم المصادقة AUTH.', 'details' => ['step' => 'auth-unsupported']];
                }
                $response = $this->command($socket, 'AUTH LOGIN');
                $dialog[] = $response['raw'];
                if ($response['code'] !== 334) {
                    return ['ok' => false, 'message' => 'الخادم رفض بدء AUTH LOGIN: '.$response['raw'], 'details' => ['step' => 'auth-login']];
                }

                $response = $this->command($socket, base64_encode($values['mail_username']));
                $dialog[] = $response['raw'];
                if ($response['code'] !== 334) {
                    return ['ok' => false, 'message' => 'اسم مستخدم SMTP مرفوض: '.$response['raw'], 'details' => ['step' => 'auth-user']];
                }

                $response = $this->command($socket, base64_encode($values['mail_password']));
                $dialog[] = $response['raw'];
                if ($response['code'] !== 235) {
                    return ['ok' => false, 'message' => 'فشل تسجيل الدخول إلى SMTP — تحقق من اسم المستخدم وكلمة المرور: '.$response['raw'], 'details' => ['step' => 'auth-password']];
                }
            }

            // 5) اختبار المرسل + المستلم بـ VRFY خفيف (MAIL FROM / RCPT TO) دون إرسال.
            if ($testRecipient !== null && $testRecipient !== '') {
                $response = $this->command($socket, 'MAIL FROM:<'.$values['mail_from_address'].'>');
                $dialog[] = $response['raw'];
                if ($response['code'] >= 400) {
                    return ['ok' => false, 'message' => 'الخادم رفض عنوان المرسل: '.$response['raw'], 'details' => ['step' => 'mail-from']];
                }

                $response = $this->command($socket, 'RCPT TO:<'.$testRecipient.'>');
                $dialog[] = $response['raw'];
                if ($response['code'] >= 400) {
                    return ['ok' => false, 'message' => 'الخادم رفض المستلم التجريبي (قد يكون العنوان غير موجود): '.$response['raw'], 'details' => ['step' => 'rcpt-to']];
                }

                $this->command($socket, 'RSET');
            }

            $this->command($socket, 'QUIT');

            return ['ok' => true, 'message' => 'نجح الاتصال والمصادقة مع خادم SMTP ✓', 'details' => [
                'host' => $host.':'.$port,
                'encryption' => $encryption,
                'authenticated' => $values['mail_username'] !== '',
                'dialog' => array_slice($dialog, -4),
            ]];
        } finally {
            fclose($socket);
        }
    }

    /** رفع الاستثناء عند غياب الإعداد الأساسي. */
    private function assertConfigured(): void
    {
        if (! $this->mailSettings->isConfigured()) {
            throw new \RuntimeException('إعدادات البريد غير مكتملة — أكملها من لوحة التحكم (قسم إعدادات البريد).');
        }
    }

    /** قراءة استجابة SMTP متعددة الأسطر. @return array{code: ?int, raw: string, line: ?string} */
    private function readResponse($socket): array
    {
        $raw = '';
        $code = null;
        $lastLine = null;

        while (($line = fgets($socket, 1024)) !== false) {
            $raw .= $line;
            $lastLine = trim($line);
            if (isset($line[3]) && $line[3] === ' ') {
                $code = (int) substr($line, 0, 3);
                break;
            }
        }

        return ['code' => $code, 'raw' => $raw, 'line' => $lastLine];
    }

    /** إرسال أمر وقراءة استجابته. @return array{code: ?int, raw: string} */
    private function command($socket, string $command): array
    {
        fwrite($socket, $command."\r\n");
        $response = $this->readResponse($socket);

        return ['code' => $response['code'], 'raw' => trim($response['raw'])];
    }
}
