<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->index();
            $table->string('name_en')->index();
            $table->string('code', 3)->nullable()->index();
            $table->string('currency_code', 3)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar')->index();
            $table->string('name_en')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar')->index();
            $table->string('name_en')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // Link the existing flat property_locations to the reference hierarchy (additive, API-safe)
        Schema::table('property_locations', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('city')->constrained('countries')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('property_locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropConstrainedForeignId('country_id');
        });

        Schema::dropIfExists('areas');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('countries');
    }
};
