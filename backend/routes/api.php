<?php

use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\AgentReportController;
use App\Http\Controllers\Api\V1\AiAssistantController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\AgentProfileController;
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
    Route::get('agents/{agent}/properties', [AgentController::class, 'properties'])->name('agents.properties');
    Route::get('property-types', [TaxonomyController::class, 'propertyTypes'])->name('property-types.index');
    Route::get('features', [TaxonomyController::class, 'features'])->name('features.index');
    Route::get('countries', [TaxonomyController::class, 'countries'])->name('countries.index');
    Route::get('regions', [TaxonomyController::class, 'regions'])->name('regions.index');
    Route::get('cities', [TaxonomyController::class, 'cities'])->name('cities.index');
    Route::get('areas', [TaxonomyController::class, 'areas'])->name('areas.index');
    Route::get('currencies', [TaxonomyController::class, 'currencies'])->name('currencies.index');

    // -------------------------------------------------------------------
    // المساعد العقاري الذكي — Flutter يتصل بـ Laravel فقط، وLaravel وحده
    // يتصل بخادم النموذج المحلي (لا مسار مباشر من العميل إلى النموذج).
    // -------------------------------------------------------------------
    Route::get('ai/bootstrap', [AiAssistantController::class, 'bootstrap'])->name('ai.bootstrap');
    Route::get('ai/health', [AiAssistantController::class, 'health'])->name('ai.health');
    Route::post('ai/chat', [AiAssistantController::class, 'chat'])->name('ai.chat');
    Route::post('ai/search', [AiAssistantController::class, 'search'])->name('ai.search');

    Route::middleware('inject.sanctum.token')->middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [MeController::class, 'show'])->name('me.show');
        Route::patch('me', [MeController::class, 'update'])->name('me.update');
        Route::post('me/avatar', [MeController::class, 'uploadAvatar'])->name('me.avatar.store');

        // التحقق الحقيقي من البريد الإلكتروني برمز مُرسل إلى الصندوق الفعلي.
        Route::get('me/email/status', [EmailVerificationController::class, 'status'])->name('me.email.status');
        Route::post('me/email/verification-code', [EmailVerificationController::class, 'send'])->name('me.email.send');
        Route::post('me/email/verify', [EmailVerificationController::class, 'verify'])->name('me.email.verify');

        // ملف الوكيل الكامل (بيانات التوثيق + المستندات).
        Route::get('me/agent-profile', [AgentProfileController::class, 'show'])->name('me.agent-profile.show');
        Route::post('me/agent-profile', [AgentProfileController::class, 'store'])->name('me.agent-profile.store');
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

        // تقارير الوكيل داخل التطبيق — مقيدة ببيانات الوكيل نفسه.
        Route::get('agent/reports', [AgentReportController::class, 'index'])->name('agent.reports.index');
        Route::get('agent/reports/{type}', [AgentReportController::class, 'show'])->name('agent.reports.show');
        Route::get('agent/reports/history/{reportLog}/download', [AgentReportController::class, 'download'])->name('agent.reports.download');

        Route::get('viewing-requests', [ViewingRequestController::class, 'index'])->name('viewing-requests.index');
        Route::post('viewing-requests', [ViewingRequestController::class, 'store'])->name('viewing-requests.store');
        Route::get('viewing-requests/{viewingRequest}', [ViewingRequestController::class, 'show'])->name('viewing-requests.show');
        Route::get('viewing-requests/{viewingRequest}/history', [ViewingRequestController::class, 'history'])->name('viewing-requests.history');
        Route::patch('viewing-requests/{viewingRequest}', [ViewingRequestController::class, 'update'])->name('viewing-requests.update');
        Route::delete('viewing-requests/{viewingRequest}', [ViewingRequestController::class, 'destroy'])->name('viewing-requests.destroy');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notificationId}/read', [NotificationController::class, 'read'])->name('notifications.read');

        // محادثات المساعد (تتطلب حسابًا) — عزل ملكية كامل داخل المتحكم.
        Route::get('ai/conversations', [AiAssistantController::class, 'conversations'])->name('ai.conversations.index');
        Route::get('ai/conversations/{conversation}', [AiAssistantController::class, 'show'])->whereNumber('conversation')->name('ai.conversations.show');
        Route::delete('ai/conversations/{conversation}', [AiAssistantController::class, 'destroy'])->whereNumber('conversation')->name('ai.conversations.destroy');
    });
});
