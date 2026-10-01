<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            
            // Template identification
            $table->string('key')->unique(); // e.g., 'email_verification', 'agent_approved'
            $table->string('name'); // Display name in Arabic
            $table->text('description')->nullable();
            
            // Template content
            $table->string('subject')->default('');
            $table->longText('html_content')->nullable(); // HTML template
            $table->longText('text_content')->nullable(); // Plain text fallback
            $table->json('css_styles')->nullable(); // Custom CSS as JSON
            
            // Template configuration
            $table->json('variables')->nullable(); // Available variables as JSON
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System templates cannot be deleted
            
            // Preview and versioning
            $table->string('thumbnail')->nullable(); // Preview image URL
            $table->integer('version')->default(1);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
