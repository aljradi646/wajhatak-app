<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public_contact_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('name', 120);
            $table->string('email', 190)->index();
            $table->string('phone', 40)->nullable();
            $table->string('category', 32)->default('general')->index();
            $table->string('subject', 190);
            $table->text('message');
            $table->string('status', 24)->default('new')->index();
            $table->text('admin_reply')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            $table->string('email_status', 24)->default('not_sent');
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['category', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_contact_submissions');
    }
};
