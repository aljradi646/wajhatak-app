<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->unique()->after('email');
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('locale', 5)->default('ar')->after('avatar_path');
            $table->boolean('is_active')->default(true)->index()->after('locale');
        });

        Schema::create('property_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('property_features', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('property_locations', function (Blueprint $table) {
            $table->id();
            $table->string('city')->index();
            $table->string('district')->nullable()->index();
            $table->string('neighborhood')->nullable()->index();
            $table->string('address');
            $table->decimal('latitude', 10, 7)->nullable()->index();
            $table->decimal('longitude', 10, 7)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('license_number')->nullable()->unique();
            $table->text('bio')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->restrictOnDelete();
            $table->foreignId('property_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('property_location_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('reference_code')->unique();
            $table->mediumText('description');
            $table->enum('transaction_type', ['sale', 'rent'])->index();
            $table->enum('status', ['draft', 'pending', 'published', 'rejected', 'archived'])->default('draft')->index();
            $table->decimal('price', 15, 2)->index();
            $table->char('currency', 3)->default('SAR');
            $table->decimal('area', 10, 2)->nullable()->index();
            $table->unsignedSmallInteger('bedrooms')->nullable()->index();
            $table->unsignedSmallInteger('bathrooms')->nullable()->index();
            $table->unsignedSmallInteger('parking_spaces')->nullable()->index();
            $table->boolean('is_furnished')->default(false)->index();
            $table->boolean('is_new')->default(false)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'transaction_type', 'price']);
            $table->index(['property_type_id', 'status']);
        });

        Schema::create('property_feature', function (Blueprint $table) {
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_feature_id')->constrained()->cascadeOnDelete();
            $table->primary(['property_id', 'property_feature_id']);
        });

        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('is_cover')->default(false)->index();
            $table->timestamps();
            $table->index(['property_id', 'sort_order']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'property_id']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['property_id', 'client_id', 'agent_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('viewing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->date('scheduled_date')->index();
            $table->time('scheduled_time');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'cancelled', 'completed'])->default('pending')->index();
            $table->timestamps();
            $table->index(['agent_id', 'status', 'scheduled_date']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('filters');
            $table->boolean('notifications_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('viewing_requests');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('property_feature');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('property_locations');
        Schema::dropIfExists('property_features');
        Schema::dropIfExists('property_types');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'avatar_path', 'locale', 'is_active']);
        });
    }
};
