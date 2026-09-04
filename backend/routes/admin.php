<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyFeatureController;
use App\Http\Controllers\Admin\PropertyTypeController;
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
    Route::resource('agents', AgentController::class);

    // Properties (includes soft-delete trash/restore/force)
    Route::get('properties/trash', [PropertyController::class, 'trash'])->name('properties.trash');
    Route::post('properties/{property}/restore', [PropertyController::class, 'restore'])->name('properties.restore');
    Route::delete('properties/{property}/force', [PropertyController::class, 'forceDelete'])->name('properties.force-delete');
    Route::post('properties/bulk', [PropertyController::class, 'bulk'])->name('properties.bulk');
    Route::resource('properties', PropertyController::class)->whereNumber('property');

    // Viewing Requests
    Route::resource('viewing-requests', ViewingRequestController::class);

    // Property Types
    Route::resource('property-types', PropertyTypeController::class)->except(['show']);

    // Property Features
    Route::resource('property-features', PropertyFeatureController::class)->except(['show']);

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/quick', [SettingController::class, 'quickUpdate'])->name('settings.quick');
    Route::patch('settings/{setting}', [SettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/{setting}', [SettingController::class, 'destroy'])->name('settings.destroy');
});
