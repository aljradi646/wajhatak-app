<?php

namespace App\Services\Mail;

use App\Models\EmailTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class MailSettingsService
{
    private const CACHE_KEY = 'wajhatak_mail_settings_v2';
    private const CACHE_TTL = 300;

    public const DEFAULTS = [
        'mail_host' => ['', 'string'],
        'mail_port' => ['587', 'string'],
        'mail_encryption' => ['tls', 'string'],
        'mail_username' => ['', 'string'],
        'mail_password' => ['', 'string'],
        'mail_timeout' => ['15', 'string'],
        'mail_from_address' => ['', 'string'],
        'mail_from_name' => ['وجهتك', 'string'],
        'mail_reply_to' => ['', 'string'],
        'mail_verification_code_ttl' => ['15', 'string'],
        'mail_verification_max_attempts' => ['5', 'string'],
        'mail_verification_resend_seconds' => ['60', 'string'],
        'mail_require_mx_check' => ['1', 'boolean'],
    ];

    public function all(): array
    {
        $cached = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->readRaw());
        $values = [];
        foreach (self::DEFAULTS as $key => [$default, $type]) {
            $raw = $cached[$key] ?? $default;
            if ($type === 'boolean') {
                $values[$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
            } elseif (in_array($key, ['mail_port','mail_timeout','mail_verification_code_ttl','mail_verification_max_attempts','mail_verification_resend_seconds'], true)) {
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

    public function putMany(array $input): void
    {
        foreach (self::DEFAULTS as $key => [$default, $type]) {
            if (! array_key_exists($key, $input)) continue;
            $value = $input[$key];
            if ($type === 'boolean') $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            if ($key === 'mail_password' && ($value === '' || $value === null)) continue;
            Setting::put($key, (string) $value, $type);
        }
        $this->flush();
    }

    public function isConfigured(): bool
    {
        $values = $this->all();
        return $values['mail_host'] !== '' && $values['mail_from_address'] !== '' && $values['mail_username'] !== '';
    }

    public function template(string $key): array
    {
        $published = EmailTemplate::findByKey($key);
        if ($published) {
            $rendered = $published->renderPublished();
            if (is_array($rendered)) {
                return [
                    'subject' => $rendered['subject'],
                    'body' => $rendered['text'] ?? strip_tags((string) ($rendered['html'] ?? '')),
                ];
            }
        }

        $subject = Setting::get('mail_template_'.$key.'_subject', '');
        $body = Setting::get('mail_template_'.$key.'_body', '');
        return ['subject' => (string) $subject, 'body' => (string) $body];
    }

    public function saveTemplate(string $key, string $subject, string $body): void
    {
        Setting::put('mail_template_'.$key.'_subject', $subject, 'text');
        Setting::put('mail_template_'.$key.'_body', $body, 'text');
        $this->flush();
    }

    public function renderTemplate(string $key, array $variables = []): array
    {
        $published = EmailTemplate::findByKey($key);
        if ($published) {
            $rendered = $published->renderPublished($variables);
            if (is_array($rendered)) {
                return [
                    'subject' => $rendered['subject'],
                    'body' => $rendered['text'] ?? strip_tags((string) ($rendered['html'] ?? '')),
                ];
            }
        }

        $template = $this->template($key);
        $render = static fn (string $text): string => str_replace(
            array_map(fn ($k) => '{'.$k.'}', array_keys($variables)),
            array_map(static fn ($v): string => (string) $v, array_values($variables)),
            $text,
        );

        return ['subject' => $render($template['subject']), 'body' => $render($template['body'])];
    }

    public function flush(): void { Cache::forget(self::CACHE_KEY); }

    private function readRaw(): array
    {
        $keys = array_keys(self::DEFAULTS);
        $rows = Setting::query()->whereIn('key', $keys)->get();
        return $rows->mapWithKeys(fn (Setting $s) => [$s->key => (string) $s->value])->all();
    }
}
