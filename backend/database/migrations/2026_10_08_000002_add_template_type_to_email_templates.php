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
            $table->string('template_type', 40)->default('custom')->after('description');
            $table->index('template_type');
        });

        $map = [
            'email_verification' => 'verification',
            'agent_approved' => 'agent',
            'agent_rejected' => 'agent',
            'property_published' => 'property',
            'property_rejected' => 'property',
        ];

        foreach ($map as $key => $type) {
            DB::table('email_templates')->where('key', $key)->update(['template_type' => $type]);
        }
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->dropIndex(['template_type']);
            $table->dropColumn('template_type');
        });
    }
};