<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_knowledge_articles')) {
            return;
        }

        Schema::create('ai_knowledge_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('topic', 160);
            $table->longText('content');
            $table->json('keywords');
            $table->json('roles');
            $table->string('target_screen', 100)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['is_active', 'priority', 'updated_at'], 'ai_knowledge_active_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_articles');
    }
};
