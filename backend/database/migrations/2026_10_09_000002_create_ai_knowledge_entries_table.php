<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_knowledge_entries')) {
            return;
        }

        Schema::create('ai_knowledge_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('topic', 160);
            $table->text('content');
            $table->json('keywords');
            $table->string('target_screen', 120)->nullable();
            $table->json('roles');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_entries');
    }
};
