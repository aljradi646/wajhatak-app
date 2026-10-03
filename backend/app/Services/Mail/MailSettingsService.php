<?php

namespace App\Services\Mail;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * إعدادات البريد الإلكتروني القابلة للإدارة من لوحة التحكم.
 * تُخزن في جدول settings بمفاتيح mail_* وتُقرأ عبر هذه الخدمة فقط.
 * الخدمة هي مصدر الحقيقة الوحيد — لا يقارن أي مكان بقيمها صراحة.
 */
class MailSettingsService
{
    private const CACHE_KEY = 'wajhatak_mail_settings_v1';
    private const CACHE_TTL = 300;

    /** المفاتيح المدعومة وقيمها الافتراضية. أي مفتاح آخر يُتجاهل. */
    public const DEFAULTS = [
        // خادم SMTP
        'mail_host' => ['', 'string'],
        'mail_port' => ['587', 'string'],
        'mail_encryption' => ['tls', 'string'],   // tls | ssl | none
        'mail_username' => ['', 'string'],
        'mail_password' => ['', 'string'],
        'mail_timeout' => ['15', 'string'],
        // المُرسل
        'mail_from_address' => ['', 'string'],
        'mail_from_name' => ['وجهتك', 'string'],
        'mail_reply_to' => ['', 'string'],
        // التحقق من البريد
        'mail_verification_code_ttl' => ['15', 'string'],     // دقائق
        'mail_verification_max_attempts' => ['5', 'string'],  // محاولات إدخال الرمز
        'mail_verification_resend_seconds' => ['60', 'string'],
        'mail_require_mx_check' => ['1', 'boolean'],          // رفض النطاقات بلا سجلات MX
    ];

    /** قوالب الرسائل (subject + body) — قابلة للتعديل من اللوحة. */
    public const TEMPLATES = [
        'email_verification' => [
            'label' => 'رمز التحقق من البريد',
            'subject' => 'رمز التحقق من بريدك — وجهتك',
            'body' => "أهلًا {name} 👋\n\nرمز التحقق الخاص بك في منصة وجهتك هو:\n\n{code}\n\nصالح لمدة {ttl} دقيقة. إن لم تطلب هذا الرمز فتجاهل هذه الرسالة.\n\nمع تحيات فريق وجهتك العقارية.",
        ],
        'agent_approved' => [
            'label' => 'قبول توثيق الوكيل',
            'subject' => 'تم توثيق حسابك كوكيل عقاري — وجهتك 🎉',
            'body' => "مبارك {name}! 🎉\n\nتمت الموافقة على توثيق حسابك كوكيل عقاري في منصة وجهتك.\nيمكنك الآن إضافة عقاراتك ونشرها للباحثين مباشرة.\n\nنتمنى لك تجارة موفقة!\nفريق وجهتك.",
        ],
        'agent_rejected' => [
            'label' => 'رفض توثيق الوكيل',
            'subject' => 'بخصوص طلب توثيق حسابك — وجهتك',
            'body' => "مرحبًا {name}،\n\nنأسف لإبلاغك بأنه لم يتم قبول طلب توثيق حسابك كوكيل بعد.\n\nالسبب: {reason}\n\nيمكنك تحديث بياناتك والتقدم مرة أخرى من خلال إدارة حسابك.\n\nفريق وجهتك.",
        ],
        'property_approved' => [
            'label' => 'قبول عقار للنشر',
            'subject' => 'تم نشر عقارك — وجهتك ✅',
            'body' => "مرحبًا {name}،\n\nتمت الموافقة على نشر عقارك: «{property}».\nأصبح ظاهرًا الآن لجميع الباحثين في المنصة.\n\nفريق وجهتك.",
        ],
        'property_rejected' => [
            'label' => 'رفض عقار',
            'subject' => 'بخصوص عقارك المقدم للمراجعة — وجهتك',
            'body' => "مرحبًا {name}،\n\nلم يتم اعتماد عقارك «{property}» للنشر.\n\nالسبب: {reason}\n\nيمكنك تعديل بياناته وإعادة إرساله للمراجعة.\n\nفريق وجهتك.",
        ],
    ];

    /** @return array<string, mixed> كل الإعدادات محوّلة الأنواع. */
    public function all(): array
    {
        $cached = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->readRaw());

        $values = [];
        foreach (self::DEFAULTS as $key => [$default, $type]) {
            $raw = $cached[$key] ?? $default;
            if ($type === 'boolean') {
                $values[$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
            } elseif (in_array($key, ['mail_port', 'mail_timeout', 'mail_verification_code_ttl', 'mail_verification_max_attempts', 'mail_verification_resend_seconds'], true)) {
                $values[$key] = (int) ($raw === '' ? $default : $raw);
            } else {
                $values[$key] = (string) $raw;
            }
        }

        return $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** حفظ دفعي (مفاتيح معروفة فقط) مع كشف كلمة المرور الفارغة. */
    public function putMany(array $input): void
    {
        foreach (self::DEFAULTS as $key => [$default, $type]) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];
            if ($type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }
            // كلمة مرور فارغة = الإبقاء على القيمة القديمة (لا نمسحها بالخطأ).
            if ($key === 'mail_password' && ($value === '' || $value === null)) {
                continue;
            }
            Setting::put($key, (string) $value, $type);
        }
        $this->flush();
    }

    /** هل الرمز يُرسل فعليًا عبر SMTP (وليس log/array)؟ */
    public function isConfigured(): bool
    {
        $values = $this->all();

        return $values['mail_host'] !== ''
            && $values['mail_from_address'] !== ''
            && $values['mail_username'] !== '';
    }

    /** قالب رسالة (subject, body) — قيم اللوحة أو الافتراضي. */
    public function template(string $key): array
    {
        $subject = Setting::get('mail_template_'.$key.'_subject', self::TEMPLATES[$key]['subject'] ?? '');
        $body = Setting::get('mail_template_'.$key.'_body', self::TEMPLATES[$key]['body'] ?? '');

        return ['subject' => (string) $subject, 'body' => (string) $body];
    }

    public function saveTemplate(string $key, string $subject, string $body): void
    {
        Setting::put('mail_template_'.$key.'_subject', $subject, 'text');
        Setting::put('mail_template_'.$key.'_body', $body, 'text');
        $this->flush();
    }

    /** استبدال المتغيرات {name} {code} {ttl} {reason} {property}... */
    public function renderTemplate(string $key, array $variables = []): array
    {
        $template = $this->template($key);

        $render = static fn (string $text): string => str_replace(
            array_map(fn ($k) => '{'.$k.'}', array_keys($variables)),
            array_values($variables),
            $text,
        );

        return [
            'subject' => $render($template['subject']),
            'body' => $render($template['body']),
        ];
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, string> قيم خام من القاعدة للكاش. */
    private function readRaw(): array
    {
        $keys = array_merge(
            array_keys(self::DEFAULTS),
            array_map(fn ($k) => 'mail_template_'.$k.'_subject', array_keys(self::TEMPLATES)),
            array_map(fn ($k) => 'mail_template_'.$k.'_body', array_keys(self::TEMPLATES)),
        );

        $rows = Setting::query()->whereIn('key', $keys)->get();

        return $rows->mapWithKeys(fn (Setting $s) => [$s->key => (string) $s->value])->all();
    }
}
