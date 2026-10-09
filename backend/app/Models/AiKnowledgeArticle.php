<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiKnowledgeArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'topic',
        'content',
        'keywords',
        'roles',
        'target_screen',
        'is_active',
        'priority',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'roles' => 'array',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'version' => 'integer',
        ];
    }
}
