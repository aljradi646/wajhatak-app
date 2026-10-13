<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->nullable();
            $table->timestamp('two_factor_enabled_at')->nullable();
        });

        Schema::create('failed_login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->char('email_hash', 64)->index();
            $table->string('ip_address', 45)->index();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamp('attempted_at')->index();
            $table->index(['ip_address', 'attempted_at']);
        });

        Schema::create('audit_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120)->index();
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['created_at', 'action']);
        });

        Schema::create('property_moderation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 20)->index();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['property_id', 'created_at']);
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->timestamp('delivered_at')->nullable()->after('read_at');
            $table->string('ref_type', 32)->nullable()->after('data');
            $table->unsignedBigInteger('ref_id')->nullable()->after('ref_type');
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'],
                'notifications_owner_read_created_idx'
            );
            $table->index(['ref_type', 'ref_id'], 'notifications_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['two_factor_secret', 'two_factor_enabled_at']);
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_owner_read_created_idx');
            $table->dropIndex('notifications_ref_idx');
            $table->dropColumn(['delivered_at', 'ref_type', 'ref_id']);
        });

        Schema::dropIfExists('property_moderation_logs');
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('failed_login_attempts');
    }
};
