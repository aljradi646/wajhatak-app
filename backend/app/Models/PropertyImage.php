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
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->path && str_starts_with_any($this->path, ['http://', 'https://'])) {
            return $this->path;
        }
        return $this->path ? asset('storage/' . $this->path) : '';
    }

    /**
     * Alias for backward compatibility — maps is_primary to is_cover.
     */
    public function getIsPrimaryAttribute(): bool
    {
        return $this->is_cover;
    }
}
