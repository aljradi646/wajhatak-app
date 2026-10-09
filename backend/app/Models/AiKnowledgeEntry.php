<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKnowledgeEntry extends Model
{
    protected $fillable = [
        'slug',
        'topic',
        'content',
        'keywords',
        'target_screen',
        'roles',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'roles' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
