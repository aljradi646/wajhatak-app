<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أعمدة الإحداثيات الحقيقية في فهرس بحث المساعد — للبحث الجغرافي «قريب مني».
 * هجرة منفصلة تعمل على قواعد الإنتاج التي أُنشئت قبل إضافة الأعمدة إلى
 * هجرة الإنشاء الأصلية (Laravel يسجل الهجرات المنفذة فقط، فلا تكرار).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_search_index', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_search_index', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->index()->after('published_at');
            }
            if (! Schema::hasColumn('ai_search_index', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->index()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_search_index', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
