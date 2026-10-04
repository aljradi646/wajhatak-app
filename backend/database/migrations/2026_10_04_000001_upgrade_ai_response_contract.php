<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_messages')) {
            Schema::table('ai_messages', function (Blueprint $table): void {
                if (! Schema::hasColumn('ai_messages', 'response_type')) {
                    $table->string('response_type', 30)->default('text')->after('status')->index();
                }
                if (! Schema::hasColumn('ai_messages', 'metadata')) {
                    $table->json('metadata')->nullable()->after('response_type');
                }
            });
        }

        if (Schema::hasTable('ai_request_logs')) {
            Schema::table('ai_request_logs', function (Blueprint $table): void {
                if (! Schema::hasColumn('ai_request_logs', 'response_type')) {
                    $table->string('response_type', 30)->nullable()->after('status')->index();
                }
                if (! Schema::hasColumn('ai_request_logs', 'fallback_reason')) {
                    $table->string('fallback_reason', 120)->nullable()->after('error_code');
                }
                if (! Schema::hasColumn('ai_request_logs', 'knowledge_version')) {
                    $table->string('knowledge_version', 40)->nullable()->after('fallback_reason');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_messages')) {
            Schema::table('ai_messages', function (Blueprint $table): void {
                $columns = [];
                foreach (['metadata', 'response_type'] as $column) {
                    if (Schema::hasColumn('ai_messages', $column)) {
                        $columns[] = $column;
                    }
                }
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('ai_request_logs')) {
            Schema::table('ai_request_logs', function (Blueprint $table): void {
                $columns = [];
                foreach (['knowledge_version', 'fallback_reason', 'response_type'] as $column) {
                    if (Schema::hasColumn('ai_request_logs', $column)) {
                        $columns[] = $column;
                    }
                }
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
