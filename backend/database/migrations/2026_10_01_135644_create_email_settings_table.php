<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            
            // Provider selection
            $table->enum('provider', ['smtp', 'resend'])->default('smtp');
            
            // SMTP settings (for backward compatibility)
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->enum('smtp_encryption', ['tls', 'ssl', 'none'])->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->integer('smtp_timeout')->nullable()->default(15);
            
            // Resend API settings
            $table->text('resend_api_key')->nullable(); // Encrypted
            $table->boolean('resend_sandbox')->default(false); // Use onboarding@resend.dev
            
            // Common sender settings
            $table->string('from_name')->default('وجهتك');
            $table->string('from_address')->nullable();
            $table->string('reply_to')->nullable();
            
            // Logo settings
            $table->string('logo_path')->nullable();
            $table->string('logo_url')->nullable(); // Public URL for emails
            
            // Status and testing
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_result')->nullable();
            $table->text('last_test_error')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
