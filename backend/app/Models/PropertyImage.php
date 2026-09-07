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
     * Priority:
     *   1. Stored absolute http(s) URL is returned as-is.
     *   2. The local file under storage/app/public is served when present.
     *   3. Otherwise (ephemeral Railway disk wiped on redeploy, or the file was
     *      never downloaded) a REAL photo URL from the internet is returned — a
     *      deterministic Unsplash photo chosen by reference code + slot — so the
     *      UI always shows a genuine property photo, never a broken image or a
     *      generic placeholder.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->path && \Illuminate\Support\Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

        $slot = (int) $this->sort_order;
        if (! $this->path) {
            return $this->internetFallbackUrl($slot);
        }

        $absolute = storage_path('app/public/'.ltrim($this->path, '/'));
        if (is_file($absolute)) {
            return asset('storage/'.ltrim($this->path, '/'));
        }

        return $this->internetFallbackUrl($slot);
    }

    /**
     * Deterministic real-photo URL used when the local file is unavailable.
     * Mirrors the seeder's mapping: the reference code tail (e.g. SN-2026-005)
     * or the property id picks a cover; sort_order>0 picks interior photos.
     */
    private function internetFallbackUrl(int $slot): string
    {
        static $covers = [
            '1580587771525-78b9dba3b914', '1568605114967-8130f3a36994',
            '1570129477492-45c003edd2be', '1600596542815-ffad4c1539a9',
            '1600585154340-be6161a56a0c', '1600566753190-17f0baa2a6c3',
            '1600047509807-ba8f99d2cdde', '1613490493576-7fde63acd811',
            '1545324418-cc1a3fa10c00',  '1512917774080-9991f1c4c750',
            '1575517111478-7f6afd0973db', '1502005229762-cf1b2da7c5d6',
            '1470770841072-f978cf4d019e', '1523217582562-09d0def993a6',
            '1570126618953-d437176e8c79',
        ];
        static $interiors = [
            '1600607687939-ce8a6c25118c', '1600210492486-724fe5c67fb0',
            '1605348532760-6753d2c43329', '1493809842364-78817add7ffb',
            '1522708323590-d24dbb6b0267', '1502672260266-1c1ef2d93688',
            '1484154218962-a197022b5858', '1554995207-c18c203602cb',
            '1560448204-e02f11c3d0e2',  '1522444195799-478538b28823',
            '1460317442991-0ec209397118', '1605276374104-dee2a0ed3cd6',
            '1512453979798-5ea266f8880c', '1560518883-ce09059eeffa',
            '1486406146926-c627a92ad1ab',
        ];

        $key = $this->fallbackKey();
        if ($slot <= 0) {
            $id = $covers[$key % count($covers)];
        } elseif ($slot === 1) {
            $id = $interiors[$key % count($interiors)];
        } else {
            $id = $interiors[($key + 5) % count($interiors)];
        }

        return 'https://images.unsplash.com/photo-'.$id.'?auto=format&fit=crop&w=1280&q=80';
    }

    /**
     * Numeric photo key derived from the property reference code — e.g.
     * SN-2026-005 -> 005 -> index 4 — preferring the related property's
     * reference_code (always exact) and falling back to the last standalone
     * numeric group inside the stored path, then the property id.
     */
    private function fallbackKey(): int
    {
        $property = null;
        if ($this->relationLoaded('property')) {
            $property = $this->property;
        } elseif ($this->property_id) {
            $property = Property::query()->whereKey($this->property_id)->first();
        }

        if ($property?->reference_code && preg_match('/(\d+)\s*$/', (string) $property->reference_code, $m)) {
            $tail = (int) $m[1];
            return $tail > 0 ? $tail - 1 : 0;
        }

        // Fallback: last standalone numeric group in the path (e.g. the 001 of
        // properties/real/SN-2026-001/0.jpg — not the concatenation of all
        // digits). Only groups that end a path segment count as candidates, so
        // we take the group preceding a slash or the trailing group.
        if ($this->path) {
            preg_match_all('#/(\d+)(?=/|$)#', '/'.ltrim($this->path, '/'), $m);
            $groups = array_map('intval', $m[1] ?? []);
            if ($groups !== []) {
                $tail = $groups[0] % 1000;
                return $tail > 0 ? $tail - 1 : 0;
            }
            if (preg_match('/(\d+)$/', $this->path, $m)) {
                $tail = ((int) $m[1]) % 1000;
                return $tail > 0 ? $tail - 1 : 0;
            }
        }

        return max(0, (int) $this->property_id - 1);
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
