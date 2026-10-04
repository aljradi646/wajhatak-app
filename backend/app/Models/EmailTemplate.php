<?php

namespace App\Models;

use App\Services\Mail\EmailTemplateRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'description', 'subject', 'html_content', 'text_content',
        'css_styles', 'variables', 'is_active', 'is_system', 'thumbnail', 'version',
        'status', 'published_version', 'last_edited_by', 'published_at', 'archived_at', 'autosaved_at',
    ];

    protected $casts = [
        'css_styles' => 'array',
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'version' => 'integer',
        'published_version' => 'integer',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'autosaved_at' => 'datetime',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(EmailTemplateVersion::class)->orderByDesc('version');
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function publishedVersion(): ?EmailTemplateVersion
    {
        $version = $this->published_version;
        if (! $version) {
            return null;
        }

        return $this->versions()->where('version', $version)->first();
    }

    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->where('is_active', true)->where('status', 'published')->first();
    }

    public function render(array $variables = []): array
    {
        return app(EmailTemplateRenderer::class)->render($this, $variables);
    }

    public function renderPublished(array $variables = []): ?array
    {
        if ($this->status !== 'published') {
            return null;
        }

        $version = $this->publishedVersion();
        if (! $version) {
            return $this->render($variables);
        }

        return app(EmailTemplateRenderer::class)->renderVersion($version, $variables);
    }

    public function incrementVersion(): void
    {
        $this->increment('version');
    }

    public function canBeDeleted(): bool
    {
        return ! $this->is_system;
    }
}
