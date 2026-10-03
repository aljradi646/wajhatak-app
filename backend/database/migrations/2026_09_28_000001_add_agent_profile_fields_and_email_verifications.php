<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1) أعمدة الوكيل الكاملة — كل ما تحتاجه الإدارة لقبول/توثيق الوكيل.
 * 2) حالة توثيق الوكيل: pending → approved | rejected (بوابة النشر).
 * 3) جدول رموز التحقق من البريد (رمز مُجزّأ + صلاحية + محاولات).
 * 4) تتبع تحقق بريد المستخدم عبر رمز منصتنا.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- ملف الوكيل الكامل (كل ما يلزم الإدارة للقبول والتوثيق) ----
        Schema::table('agents', function (Blueprint $table) {
            $table->string('agency_name')->nullable()->after('bio')->comment('اسم المكتب/الشركة العقارية');
            $table->string('job_title')->nullable()->after('agency_name')->comment('المسمى الوظيفي');
            $table->string('phone')->nullable()->after('job_title')->comment('جوال الوكيل للتواصل');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('city')->nullable()->after('whatsapp')->comment('مدينة العمل الأساسية');
            $table->string('national_id')->nullable()->after('city')->comment('الرقم الوطني / الهوية');
            $table->string('id_document_path')->nullable()->after('national_id')->comment('صورة الهوية (توثيق)');
            $table->string('license_document_path')->nullable()->after('id_document_path')->comment('صورة رخصة الوساطة');
            $table->string('experience_years', 2)->nullable()->after('license_document_path');
            $table->text('address')->nullable()->after('experience_years')->comment('العنوان الوطني للمكتب');
            $table->string('website')->nullable()->after('address');
            $table->string('facebook')->nullable()->after('website');
            $table->string('instagram')->nullable()->after('facebook');
            $table->string('twitter')->nullable()->after('instagram');
            $table->string('photo_path')->nullable()->after('twitter')->comment('صورة الوكيل الشخصية');
            $table->text('rejection_reason')->nullable()->after('photo_path')->comment('سبب الرفض (يُعرض على الوكيل)');

            // بوابة التوثيق: لا نشر عقارات قبل approved من الإدارة.
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending')->index()->after('rejection_reason');
            $table->timestamp('verified_at')->nullable()->after('verification_status');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users', 'id')->nullOnDelete();
        });

        // ---- رموز التحقق من البريد (رمز مُجزّأ 6 أرقام) ----
        Schema::create('email_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index()->comment('البريد المستهدف (صالح ومفحوص MX فقط)');
            $table->string('code_hash')->comment('تجزئة الرمز — لا يُخزن الرمز أبدًا نصًا صريحًا');
            $table->timestamp('expires_at')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['email', 'expires_at']);
        });

        // ---- تتبع تحقق البريد عبر رمز المنصة ----
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_code_sent_at')->nullable()->after('email_verified_at')->comment('آخر إرسال رمز (للحد الزمني)');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_code_sent_at');
        });

        Schema::dropIfExists('email_verification_codes');

        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'agency_name', 'job_title', 'phone', 'whatsapp', 'city', 'national_id',
                'id_document_path', 'license_document_path', 'experience_years', 'address',
                'website', 'facebook', 'instagram', 'twitter', 'photo_path',
                'rejection_reason', 'verification_status', 'verified_at', 'verified_by',
            ]);
        });
    }
};
