<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AiAssistantController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MailSettingsController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyFeatureController;
use App\Http\Controllers\Admin\PropertyTypeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ViewingRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', UserController::class);

    // Activity Log (read-only)
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Locations (Country/Region/City/Area)
    Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
    Route::post('locations/countries', [LocationController::class, 'storeCountry'])->name('locations.country.store');
    Route::put('locations/countries/{country}', [LocationController::class, 'updateCountry'])->name('locations.country.update');
    Route::delete('locations/countries/{country}', [LocationController::class, 'destroyCountry'])->name('locations.country.destroy');
    Route::post('locations/regions', [LocationController::class, 'storeRegion'])->name('locations.region.store');
    Route::put('locations/regions/{region}', [LocationController::class, 'updateRegion'])->name('locations.region.update');
    Route::delete('locations/regions/{region}', [LocationController::class, 'destroyRegion'])->name('locations.region.destroy');
    Route::post('locations/cities', [LocationController::class, 'storeCity'])->name('locations.city.store');
    Route::put('locations/cities/{city}', [LocationController::class, 'updateCity'])->name('locations.city.update');
    Route::delete('locations/cities/{city}', [LocationController::class, 'destroyCity'])->name('locations.city.destroy');
    Route::post('locations/areas', [LocationController::class, 'storeArea'])->name('locations.area.store');
    Route::put('locations/areas/{area}', [LocationController::class, 'updateArea'])->name('locations.area.update');
    Route::delete('locations/areas/{area}', [LocationController::class, 'destroyArea'])->name('locations.area.destroy');
    // Cascade JSON for property forms
    Route::get('locations/cascade/regions/{country}', [LocationController::class, 'regionsFor'])->name('locations.regions-for');
    Route::get('locations/cascade/cities/{region}', [LocationController::class, 'citiesFor'])->name('locations.cities-for');
    Route::get('locations/cascade/areas/{city}', [LocationController::class, 'areasFor'])->name('locations.areas-for');

    // Agents
    Route::get('agents/verifications', [AgentController::class, 'verifications'])->name('agents.verifications');
    Route::post('agents/{agent}/approve', [AgentController::class, 'approve'])->name('agents.approve');
    Route::post('agents/{agent}/reject-verification', [AgentController::class, 'rejectVerification'])->name('agents.reject-verification');
    Route::resource('agents', AgentController::class);

    // Properties (includes soft-delete trash/restore/force)
    Route::get('properties/trash', [PropertyController::class, 'trash'])->name('properties.trash');
    Route::post('properties/{property}/restore', [PropertyController::class, 'restore'])->name('properties.restore');
    Route::delete('properties/{property}/force', [PropertyController::class, 'forceDelete'])->name('properties.force-delete');
    Route::post('properties/bulk', [PropertyController::class, 'bulk'])->name('properties.bulk');

    // Unapproved (pending) properties + approve/reject actions
    Route::get('properties/pending', [PropertyController::class, 'pending'])->name('properties.pending');
    Route::post('properties/{property}/approve', [PropertyController::class, 'approve'])->name('properties.approve');
    Route::post('properties/{property}/reject', [PropertyController::class, 'reject'])->name('properties.reject');

    Route::resource('properties', PropertyController::class)->whereNumber('property');

    // Viewing Requests
    Route::resource('viewing-requests', ViewingRequestController::class);

    // Property Types
    Route::resource('property-types', PropertyTypeController::class)->except(['show']);

    // Property Features
    Route::resource('property-features', PropertyFeatureController::class)->except(['show']);

    // AI Assistant — كل قسم صفحة مستقلة (لا نموذج ضخم واحد).
    Route::get('ai', [AiAssistantController::class, 'index'])->name('ai.index');
    Route::get('ai/settings/{section}', [AiAssistantController::class, 'settings'])->name('ai.settings');
    Route::post('ai/settings/{section}', [AiAssistantController::class, 'updateSection'])->name('ai.settings.update');
    Route::get('ai/monitoring', [AiAssistantController::class, 'monitoring'])->name('ai.monitoring');
    Route::get('ai/stats', [AiAssistantController::class, 'stats'])->name('ai.stats');
    Route::get('ai/logs', [AiAssistantController::class, 'logs'])->name('ai.logs');
    Route::post('ai/reindex', [AiAssistantController::class, 'reindex'])->name('ai.reindex');
    // إصلاح ذاتي لمخطط المساعد (جداول/أعمدة ناقصة + فهرس فارغ) من اللوحة.
    Route::post('ai/repair', [AiAssistantController::class, 'repair'])->name('ai.repair');
    Route::get('ai/playground', [AiAssistantController::class, 'playground'])->name('ai.playground');
    Route::post('ai/playground/send', [AiAssistantController::class, 'playgroundSend'])->name('ai.playground.send');
    Route::post('ai/playground/clear', [AiAssistantController::class, 'playgroundClear'])->name('ai.playground.clear');

    // إعدادات البريد الإلكتروني (SMTP + Resend + قوالب الرسائل + شعار)
    Route::get('mail', [MailSettingsController::class, 'index'])->name('mail.index');
    Route::post('mail', [MailSettingsController::class, 'update'])->name('mail.update');
    Route::post('mail/logo', [MailSettingsController::class, 'uploadLogo'])->name('mail.logo');
    Route::delete('mail/logo', [MailSettingsController::class, 'deleteLogo'])->name('mail.logo.delete');
    Route::post('mail/test-smtp', [MailSettingsController::class, 'testSmtpConnection'])->name('mail.test-smtp');
    Route::post('mail/test-resend', [MailSettingsController::class, 'testResendConnection'])->name('mail.test-resend');
    Route::post('mail/send-test', [MailSettingsController::class, 'sendTest'])->name('mail.send-test');
    Route::post('mail/templates', [MailSettingsController::class, 'updateTemplates'])->name('mail.templates');
    Route::post('mail/preview', [MailSettingsController::class, 'previewEmail'])->name('mail.preview');

    // قوالب البريد الإلكتروني (HTML/CSS قابل للتخصيص)
    Route::resource('email-templates', EmailTemplateController::class)->parameters(['email-templates' => 'emailTemplate']);
    Route::post('email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
    Route::post('email-templates/{emailTemplate}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('email-templates.duplicate');

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/quick', [SettingController::class, 'quickUpdate'])->name('settings.quick');
    Route::post('settings/identity', [SettingController::class, 'updateIdentity'])->name('settings.identity');
    Route::patch('settings/{setting}', [SettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/{setting}', [SettingController::class, 'destroy'])->name('settings.destroy');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    // سجل التقارير — يجب تعريفه قبل reports/{type} لتفادي التعارض.
    Route::get('reports/logs', [ReportController::class, 'logs'])->name('reports.logs');
    Route::get('reports/logs/{reportLog}/download', [ReportController::class, 'download'])->name('reports.logs.download');
    Route::get('reports/{type}', [ReportController::class, 'show'])
        ->name('reports.show')
        ->whereIn('type', ['agents', 'properties', 'requests', 'users']);
});
