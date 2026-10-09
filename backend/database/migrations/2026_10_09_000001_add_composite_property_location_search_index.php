<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_locations', function (Blueprint $table): void {
            // city (255) + district (255) fit within MySQL 8's utf8mb4 index limit.
            $table->index(
                ['city', 'district'],
                'property_locations_city_district_search_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('property_locations', function (Blueprint $table): void {
            $table->dropIndex('property_locations_city_district_search_idx');
        });
    }
};
