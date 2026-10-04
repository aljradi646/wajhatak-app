<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->string('status', 20)->default('draft')->after('is_system');
            $table->unsignedInteger('published_version')->nullable()->after('version');
            $table->foreignId('last_edited_by')->nullable()->after('published_version')->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('last_edited_by');
            $table->timestamp('archived_at')->nullable()->after('published_at');
            $table->timestamp('autosaved_at')->nullable()->after('archived_at');

            $table->index(['status', 'is_active']);
            $table->index('published_version');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->dropForeign(['last_edited_by']);
            $table->dropIndex(['status', 'is_active']);
            $table->dropIndex(['published_version']);
            $table->dropColumn([
                'status',
                'published_version',
                'last_edited_by',
                'published_at',
                'archived_at',
                'autosaved_at',
            ]);
        });
    }
};
