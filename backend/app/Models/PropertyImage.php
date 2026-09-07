<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyImage extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'path', 'alt_text', 'sort_order', 'is_cover'];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Virtual accessor for image URL.
     *
     * Returns a working URL that serves the stored file when it physically
     * exists under storage/app/public. If the file is missing — which happens on
     * Railway because the storage disk is ephemeral and is wiped on redeploy
     * while DB rows survive — returns a small inline placeholder so the admin
     * UI never renders a broken image.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->path && \Illuminate\Support\Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

        if (! $this->path) {
            return static::missingPlaceholder();
        }

        $absolute = storage_path('app/public/'.ltrim($this->path, '/'));
        if (is_file($absolute)) {
            return asset('storage/'.ltrim($this->path, '/'));
        }

        return static::missingPlaceholder();
    }

    /**
     * Inline SVG used as a graceful fallback when the underlying file is not
     * present on disk (ephemeral storage) or its path was never written.
     */
    public static function missingPlaceholder(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450">'
            .'<rect width="100%" height="100%" fill="#E7F4EE"/>'
            .'<g fill="#0E8A6D"><circle cx="400" cy="180" r="70" fill="none" stroke="#0E8A6D" stroke-width="8"/>'
            .'<path d="M330 330 L360 240 L400 280 L440 230 L470 330 Z"/></g>'
            .'<text x="400" y="380" font-family="sans-serif" font-size="24" fill="#0E8A6D" text-anchor="middle">'
            .'الصورة غير متوفرة</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Alias for backward compatibility — maps is_primary to is_cover.
     */
    public function getIsPrimaryAttribute(): bool
    {
        return $this->is_cover;
    }
}
