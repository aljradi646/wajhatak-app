<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('message_type', 20)->default('text')->after('body');
            $table->foreignId('property_id')->nullable()->constrained()->cascadeOnDelete()->after('message_type');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropColumn(['message_type', 'property_id']);
        });
    }
};
