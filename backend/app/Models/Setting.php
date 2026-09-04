<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type'];

    protected $casts = [
        'value' => 'string',
    ];

    public const TYPES = [
        'string' => 'نص',
        'text' => 'نص طويل',
        'boolean' => 'نعم / لا',
        'url' => 'رابط',
        'email' => 'بريد إلكتروني',
        'phone' => 'هاتف',
        'color' => 'لون',
    ];

    /**
     * Default settings seeded on first run.
     */
    public const DEFAULTS = [
        // General
        'site_name' => ['وجهتك', 'string'],
        'site_tagline' => ['وجهتك إلى العقار المناسب.', 'string'],
        'support_email' => ['support@wajhatak.com', 'email'],
        'support_phone' => ['+967-000-000-000', 'phone'],
        'address' => ['صنعاء، اليمن', 'string'],
        'default_currency' => ['YER', 'string'],
        'maintenance_mode' => [false, 'boolean'],
        'registration_enabled' => [true, 'boolean'],
        'language' => ['ar', 'string'],
    ];

    /**
     * Get a setting value by key with fallback default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting || $setting->value === null || $setting->value === '') {
            return $default;
        }
        if ($setting->type === 'boolean') {
            return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
        }
        return $setting->value;
    }

    /**
     * Set / update a setting value.
     */
    public static function put(string $key, mixed $value, string $type = 'string'): self
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
            $type = 'boolean';
        }
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) || is_numeric($value) ? (string) $value : json_encode($value), 'type' => $type]
        );
    }

    /**
     * Seed any missing defaults.
     */
    public static function seedDefaults(): void
    {
        foreach (static::DEFAULTS as $key => [$value, $type]) {
            if (! static::where('key', $key)->exists()) {
                static::create([
                    'key' => $key,
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => $type,
                ]);
            }
        }
    }
}
