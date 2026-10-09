<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class EmailSetting extends Model
{
    protected $fillable = [
        'provider',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'smtp_timeout',
        'resend_api_key',
        'resend_sandbox',
        'from_name',
        'from_address',
        'reply_to',
        'logo_path',
        'logo_url',
        'is_active',
        'last_tested_at',
        'last_test_result',
        'last_test_error',
    ];

    protected $casts = [
        'smtp_port' => 'integer',
        'smtp_timeout' => 'integer',
        'resend_sandbox' => 'boolean',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Get the single email settings record (singleton pattern).
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'provider' => 'smtp',
            'from_name' => 'وجهتك',
            'is_active' => false,
        ]);
    }

    /**
     * Encrypt the Resend API key before saving.
     */
    public function setResendApiKeyAttribute(?string $value): void
    {
        $this->attributes['resend_api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt the Resend API key when retrieving.
     */
    public function getResendApiKeyAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Get masked API key for display (first 8 chars + ****).
     */
    public function getMaskedApiKeyAttribute(): string
    {
        if (!$this->resend_api_key) {
            return '';
        }
        return substr($this->resend_api_key, 0, 8) . '****';
    }

    /**
     * Check if the provider is Resend.
     */
    public function isResend(): bool
    {
        return $this->provider === 'resend';
    }

    /**
     * Check if the provider is SMTP.
     */
    public function isSmtp(): bool
    {
        return $this->provider === 'smtp';
    }

    /**
     * Check if email sending is active and configured.
     */
    public function canSend(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isResend()) {
            return !empty($this->resend_api_key) && !empty($this->from_address);
        }

        if ($this->isSmtp()) {
            return !empty($this->smtp_host) && !empty($this->smtp_username) && !empty($this->from_address);
        }

        return false;
    }

    /**
     * Get the sender email address.
     */
    public function getSenderEmail(): string
    {
        if ($this->isResend() && $this->resend_sandbox) {
            return 'onboarding@resend.dev';
        }

        return $this->from_address ?? '';
    }

    /**
     * Get the logo URL for emails (public URL or null).
     */
    public function getLogoUrlForEmail(): ?string
    {
        $url = $this->logo_url;

        if (! $url && $this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            $url = Storage::disk('public')->url($this->logo_path);
        }

        if (! $url) {
            return null;
        }

        // Email clients require a publicly reachable absolute URL.
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }

    /**
     * Update test result status.
     */
    public function recordTestResult(bool $success, ?string $error = null): void
    {
        $this->update([
            'last_tested_at' => now(),
            'last_test_result' => $success ? 'success' : 'failed',
            'last_test_error' => $error,
        ]);
    }

    /**
     * Block test email domains (example.com, test.com, etc.).
     */
    public static function isTestEmail(string $email): bool
    {
        $blockedDomains = [
            'example.com',
            'example.org',
            'example.net',
            'test.com',
            'test.org',
            'test.net',
            'fake.com',
            'dummy.com',
            'tempmail.com',
            'throwaway.email',
            'guerrillamail.com',
            'mailinator.com',
            '10minutemail.com',
        ];

        $domain = strtolower(substr(strrchr($email, '@'), 1));

        return in_array($domain, $blockedDomains, true);
    }
}
