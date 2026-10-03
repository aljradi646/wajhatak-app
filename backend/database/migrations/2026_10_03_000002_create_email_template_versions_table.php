<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('email_template_versions',function(Blueprint $table): void {
            $table->id();
            $table->foreignId('email_template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('subject',190);
            $table->longText('html_content')->nullable();
            $table->longText('text_content')->nullable();
            $table->json('css_styles')->nullable();
            $table->json('variables')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_note',500)->nullable();
            $table->timestamps();
            $table->unique(['email_template_id','version']);
        });
    }
    public function down(): void { Schema::dropIfExists('email_template_versions'); }
};
