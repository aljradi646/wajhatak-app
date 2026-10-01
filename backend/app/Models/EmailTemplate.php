<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'subject',
        'html_content',
        'text_content',
        'css_styles',
        'variables',
        'is_active',
        'is_system',
        'thumbnail',
        'version',
    ];

    protected $casts = [
        'css_styles' => 'array',
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'version' => 'integer',
    ];

    /**
     * Get template by key.
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->where('is_active', true)->first();
    }

    /**
     * Render template with variables.
     */
    public function render(array $variables = []): array
    {
        $subject = $this->replaceVariables($this->subject, $variables);
        $html = $this->html_content ? $this->replaceVariables($this->html_content, $variables) : null;
        $text = $this->text_content ? $this->replaceVariables($this->text_content, $variables) : null;

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
        ];
    }

    /**
     * Replace variables in content.
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }

        return $content;
    }

    /**
     * Increment version and save.
     */
    public function incrementVersion(): void
    {
        $this->increment('version');
    }

    /**
     * Check if template can be deleted (not system).
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_system;
    }
}
