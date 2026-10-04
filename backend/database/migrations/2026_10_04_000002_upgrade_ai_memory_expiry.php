<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_user_memories')) {
            Schema::create('ai_user_memories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('memory_key', 80);
                $table->string('memory_value', 500);
                $table->decimal('confidence', 4, 3)->default(1.000);
                $table->string('source', 30)->default('conversation');
                $table->timestamp('last_used_at')->nullable()->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
                $table->unique(['user_id', 'memory_key']);
                $table->index(['user_id', 'updated_at']);
            });

            return;
        }

        Schema::table('ai_user_memories', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_user_memories', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_user_memories')) {
            return;
        }

        if (Schema::hasColumn('ai_user_memories', 'expires_at')) {
            Schema::table('ai_user_memories', function (Blueprint $table): void {
                $table->dropColumn('expires_at');
            });
        }
    }
};
