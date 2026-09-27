<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------------------
        // محادثات المساعد الذكي — منفصلة تمامًا عن محادثات الوكلاء (conversations)
        // -------------------------------------------------------------------
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()
                ->comment('null = زائر غير مسجل');
            // مفتاح ملكية للزوار (غير المسجلين) — عشوائي غير قابل للتخمين.
            $table->string('session_token', 64)->nullable()->unique();
            $table->string('locale', 5)->default('ar');
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            // user | assistant
            $table->string('role', 12)->index();
            $table->longText('content');
            // معايير البحث المستخلصة من رسالة المستخدم (JSON) — للسياق والتحليل.
            $table->json('structured_filters')->nullable();
            // معرفات العقارات المعروضة في الرد (JSON array) — تُستخدم للتحقق من الأرضية.
            $table->json('property_ids')->nullable();
            // blocked | ok | error | fallback
            $table->string('status', 20)->default('ok');
            $table->timestamps();
            $table->index(['ai_conversation_id', 'created_at']);
        });

        // -------------------------------------------------------------------
        // سجل الطلبات — للمراقبة والتدقيق فقط (بلا أسرار ولا محتوى حساس).
        // -------------------------------------------------------------------
        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('request_id', 64)->index();
            $table->string('intent', 40)->nullable()->index();
            $table->json('structured_filters')->nullable();
            $table->json('tool_calls')->nullable();
            $table->unsignedInteger('results_count')->default(0);
            // ok | blocked | error | fallback
            $table->string('status', 20)->default('ok')->index();
            $table->string('error_code', 60)->nullable();
            $table->unsignedInteger('latency_ms')->default(0);
            $table->unsignedInteger('search_ms')->default(0);
            $table->unsignedInteger('tokens_used')->default(0);
            $table->timestamps();
            $table->index(['created_at', 'status']);
        });

        // -------------------------------------------------------------------
        // فهرس بحث AI — مزامنة فورية مع تغييرات العقارات (source of truth
        // تبقى جدول properties، هذا جدول مُعد للبحث النصي والمطابقة السريعة).
        // -------------------------------------------------------------------
        Schema::create('ai_search_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('transaction_type', 10)->nullable()->index();
            $table->string('status', 20)->index();
            $table->string('type_slug', 60)->nullable()->index();
            $table->string('type_name_ar', 60)->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->string('district', 100)->nullable()->index();
            $table->string('neighborhood', 100)->nullable()->index();
            $table->decimal('price', 15, 2)->nullable()->index();
            $table->string('currency', 3)->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable()->index();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->boolean('is_furnished')->default(false)->index();
            $table->boolean('is_new')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            // نص بحث حر مُعد مسبقًا (title + location + features + description).
            $table->text('search_text')->nullable();
            // تجزئة محتوى العقار لكشف التعديلات (تزامن رخيص).
            $table->string('content_hash', 64)->nullable()->index();
            $table->timestamps();
        });

        // بحث نصي كامل (MySQL FULLTEXT؛ على SQLite يبقى عمودًا عاديًا والبحث LIKE).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('ai_search_index', function (Blueprint $table) {
                $table->fullText('search_text');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_search_index');
        Schema::dropIfExists('ai_request_logs');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
