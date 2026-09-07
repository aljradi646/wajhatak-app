<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type'];

    protected $casts = [
        'value' => 'string',
    ];

    /** قيم الطلب الحالي فقط — تمنع تكرار استعلامات القاعدة داخل نفس الطلب. */
    private static array $bag = [];

    private const CACHE_PREFIX = 'setting:';

    private const CACHE_TTL_SECONDS = 86400;

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
     *
     * Reads from a per-request bag then the shared cache, and only falls back
     * to the database on a cold miss — so repeated calls within a request and
     * across page loads never hammer the ``settings`` table.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$bag)) {
            return static::$bag[$key] ?? $default;
        }

        $value = static::cachedValue($key);
        static::$bag[$key] = $value;

        return $value ?? $default;
    }

    /**
     * Set / update a setting value, invalidating its cached copy.
     */
    public static function put(string $key, mixed $value, string $type = 'string'): self
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
            $type = 'boolean';
        }
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) || is_numeric($value) ? (string) $value : json_encode($value), 'type' => $type]
        );
        static::forget($key);

        return $setting;
    }

    /**
     * Invalidate the cached copy of a setting (also clears the request bag).
     * Call this after any direct write/delete outside of put().
     */
    public static function forget(string $key): void
    {
        unset(static::$bag[$key]);
        Cache::forget(static::CACHE_PREFIX.$key);
    }

    private static function cachedValue(string $key): mixed
    {
        $cacheKey = static::CACHE_PREFIX.$key;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $setting = static::where('key', $key)->first();
        if (! $setting || $setting->value === null || $setting->value === '') {
            return null;
        }

        $value = $setting->type === 'boolean'
            ? filter_var($setting->value, FILTER_VALIDATE_BOOLEAN)
            : $setting->value;

        Cache::put($cacheKey, $value, static::CACHE_TTL_SECONDS);

        return $value;
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
