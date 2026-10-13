<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('properties', 'is_demo')) {
            Schema::table('properties', function (Blueprint $table): void {
                $table->boolean('is_demo')->default(false)->index();
            });
        }

        if (! Schema::hasColumn('agents', 'is_demo')) {
            Schema::table('agents', function (Blueprint $table): void {
                $table->boolean('is_demo')->default(false)->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('properties', 'is_demo')) {
            Schema::table('properties', function (Blueprint $table): void {
                $table->dropIndex(['is_demo']);
                $table->dropColumn('is_demo');
            });
        }

        if (Schema::hasColumn('agents', 'is_demo')) {
            Schema::table('agents', function (Blueprint $table): void {
                $table->dropIndex(['is_demo']);
                $table->dropColumn('is_demo');
            });
        }
    }
};
