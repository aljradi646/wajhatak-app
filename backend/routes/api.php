<?php

use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\TaxonomyController;
use App\Http\Controllers\Api\V1\ViewingRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->as('api.v1.')->group(function (): void {
    Route::middleware('throttle:6,1')->group(function (): void {
        Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
    Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::get('property-types', [TaxonomyController::class, 'propertyTypes'])->name('property-types.index');
    Route::get('features', [TaxonomyController::class, 'features'])->name('features.index');
    Route::get('countries', [TaxonomyController::class, 'countries'])->name('countries.index');
    Route::get('regions', [TaxonomyController::class, 'regions'])->name('regions.index');
    Route::get('cities', [TaxonomyController::class, 'cities'])->name('cities.index');
    Route::get('areas', [TaxonomyController::class, 'areas'])->name('areas.index');
    Route::get('currencies', [TaxonomyController::class, 'currencies'])->name('currencies.index');

    Route::middleware('inject.sanctum.token')->middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [MeController::class, 'show'])->name('me.show');
        Route::patch('me', [MeController::class, 'update'])->name('me.update');
        Route::post('me/avatar', [MeController::class, 'uploadAvatar'])->name('me.avatar.store');
        Route::get('me/notification-preferences', [NotificationPreferenceController::class, 'show'])->name('me.notification-preferences.show');
        Route::patch('me/notification-preferences', [NotificationPreferenceController::class, 'update'])->name('me.notification-preferences.update');
        Route::post('me/devices', [DeviceController::class, 'store'])->name('me.devices.store');
        Route::delete('me/devices/{deviceId}', [DeviceController::class, 'destroy'])->name('me.devices.destroy');

        Route::post('properties', [PropertyController::class, 'store'])->name('properties.store');
        Route::get('agent/properties', [PropertyController::class, 'mine'])->name('agent.properties.index');
        Route::patch('properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
        Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');
        Route::post('properties/{property}/images', [PropertyController::class, 'uploadImage'])->name('properties.images.store');
        Route::delete('properties/{property}/images/{imageId}', [PropertyController::class, 'destroyImage'])->name('properties.images.destroy');
        Route::post('properties/{property}/images/{imageId}/cover', [PropertyController::class, 'setCover'])->name('properties.images.cover');

        Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
        Route::post('favorites', [FavoriteController::class, 'store'])->name('favorites.store');
        Route::delete('favorites/{property}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

        Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('conversations', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('conversations/{conversation}/messages', [ConversationController::class, 'messages'])->name('conversations.messages.index');
        Route::post('conversations/{conversation}/messages', [ConversationController::class, 'sendMessage'])->name('conversations.messages.store');

        Route::get('viewing-requests', [ViewingRequestController::class, 'index'])->name('viewing-requests.index');
        Route::post('viewing-requests', [ViewingRequestController::class, 'store'])->name('viewing-requests.store');
        Route::patch('viewing-requests/{viewingRequest}', [ViewingRequestController::class, 'update'])->name('viewing-requests.update');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notificationId}/read', [NotificationController::class, 'read'])->name('notifications.read');
    });
});
