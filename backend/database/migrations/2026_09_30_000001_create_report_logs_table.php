<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type', 50)->index();
            // الصيغة الفعلية التي طلبها المستخدم: html | pdf | excel | csv | json
            $table->string('format', 20)->index();
            // المرشحات المستخدمة عند التوليد — لازمة لإعادة توليد نفس التقرير لاحقًا.
            $table->json('filters')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->string('file_name', 191)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_logs');
    }
};
