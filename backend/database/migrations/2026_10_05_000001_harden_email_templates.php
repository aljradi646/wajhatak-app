<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        // Existing active templates were already treated as live by the application.
        // Preserve that behavior while creating a real immutable published snapshot.
        $now = now();
        $templates = DB::table('email_templates')->where('is_active', true)->get();
        foreach ($templates as $template) {
            $html = str_replace(
                '{{logo}}',
                '<img src="{{app.logo_url}}" alt="وجهتك" style="max-width:150px;height:auto;">',
                (string) ($template->html_content ?? ''),
            );

            DB::table('email_templates')->where('id', $template->id)->update([
                'status' => 'published',
                'published_version' => (int) $template->version,
                'published_at' => $now,
                'html_content' => $html,
            ]);

            DB::table('email_template_versions')->updateOrInsert(
                ['email_template_id' => $template->id, 'version' => (int) $template->version],
                [
                    'subject' => (string) $template->subject,
                    'html_content' => $html,
                    'text_content' => $template->text_content,
                    'css_styles' => $template->css_styles,
                    'variables' => $template->variables,
                    'created_by' => null,
                    'change_note' => 'ترحيل الإصدار المنشور إلى نظام النسخ',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        DB::table('email_templates')
            ->where('is_active', false)
            ->update(['status' => 'archived', 'archived_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->dropForeign(['last_edited_by']);
            $table->dropIndex(['status', 'is_active']);
            $table->dropIndex(['published_version']);
            $table->dropColumn(['status', 'published_version', 'last_edited_by', 'published_at', 'archived_at', 'autosaved_at']);
        });
    }
};
