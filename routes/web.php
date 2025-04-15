<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RulesAndTriggersController;
use App\Http\Controllers\AviavoxController;
use App\Http\Controllers\GtfsController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\SelectorController;
use App\Http\Controllers\AdminSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/selector', [SelectorController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('selector');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'update-activity'])->group(function () {
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::post('/announcements/make-audio', [AnnouncementController::class, 'makeAudioAnnouncement'])->name('announcements.makeAudio');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
   
    // Aviavox settings
    Route::get('/settings/aviavox', [AviavoxController::class, 'viewAviavox'])->name('settings.aviavox');
    Route::post('/settings/aviavox', [AviavoxController::class, 'updateAviavox'])->name('settings.aviavox.update');
    Route::post('/settings/aviavox/test-connection', [AviavoxController::class, 'testConnection'])->name('settings.aviavox.test');
    Route::post('/settings/aviavox/store-announcement', [AviavoxController::class, 'storeAnnouncement'])->name('settings.aviavox.storeAnnouncement');
    Route::post('/settings/aviavox/checkin-aware-fault', [AviavoxController::class, 'sendCheckinAwareFault'])
        ->name('settings.aviavox.checkin-aware-fault');
    Route::post('/settings/aviavox/messages', [AviavoxController::class, 'storeMessage'])
        ->name('settings.aviavox.storeMessage');
    Route::post('/settings/aviavox/custom', [AviavoxController::class, 'sendCustomAnnouncement'])
        ->name('settings.aviavox.custom');
    Route::post('/settings/aviavox/template', [AviavoxController::class, 'storeTemplate'])->name('settings.aviavox.storeTemplate');

    // GTFS settings
    Route::get('/settings/gtfs', [GtfsController::class, 'viewGtfs'])->name('settings.gtfs');
    Route::post('/settings/gtfs', [GtfsController::class, 'updateGtfsUrl'])->name('settings.gtfs.update');
    Route::get('/settings/gtfs/download', [GtfsController::class, 'downloadGtfs'])->name('settings.gtfs.download');

    // Rules and triggers
    Route::get('/settings/rules', [RulesAndTriggersController::class, 'viewRulesAndTriggers'])->name('settings.rules');

    // User management
    Route::get('/settings/users', [UserManagementController::class, 'viewUsers'])->name('settings.users');
    
    // Logs
    Route::get('/settings/logs', [LogsController::class, 'viewLogs'])->name('settings.logs');
    Route::get('/settings/logs/export', [LogsController::class, 'exportLogs'])->name('settings.logs.export');

    // Admin settings
    

});

Route::middleware(['auth', 'update-activity'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/tokens', [TokenController::class, 'create'])->name('token.create');
    Route::delete('/tokens/{token}', [TokenController::class, 'destroy'])->name('token.destroy');
});

Route::middleware(['auth', 'admin', 'update-activity'])->group(function () {
    // Admin settings
    Route::get('/settings/admin', [AdminSettingsController::class, 'index'])->name('settings.admin');
    
    // Aviavox settings
    Route::get('/settings/aviavox', [AviavoxController::class, 'viewAviavox'])->name('settings.aviavox');
    Route::post('/settings/aviavox', [AviavoxController::class, 'updateAviavox'])->name('settings.aviavox.update');
    Route::post('/settings/aviavox/test-connection', [AviavoxController::class, 'testConnection'])->name('settings.aviavox.test');
    Route::post('/settings/aviavox/store-announcement', [AviavoxController::class, 'storeAnnouncement'])->name('settings.aviavox.storeAnnouncement');
    Route::delete('/settings/aviavox/announcements/{announcement}', [AviavoxController::class, 'deleteAnnouncement'])
        ->name('settings.aviavox.deleteAnnouncement');

    // GTFS settings
    Route::get('/settings/gtfs', [GtfsController::class, 'viewGtfs'])->name('settings.gtfs');
    Route::post('/settings/gtfs', [GtfsController::class, 'updateGtfsUrl'])->name('settings.gtfs.update');
    Route::get('/settings/gtfs/download', [GtfsController::class, 'downloadGtfs'])->name('settings.gtfs.download');
    Route::post('/settings/gtfs/clear', [GtfsController::class, 'clearGtfsData'])->name('settings.gtfs.clear');

    // Rules and triggers
    Route::get('/settings/rules', [RulesAndTriggersController::class, 'viewRulesAndTriggers'])->name('settings.rules');

    // User management
    Route::get('/settings/users', [UserManagementController::class, 'viewUsers'])->name('settings.users');
    
    // Logs
    Route::get('/settings/logs', [LogsController::class, 'viewLogs'])->name('settings.logs');
    Route::get('/settings/logs/export', [LogsController::class, 'exportLogs'])->name('settings.logs.export');
});

Route::delete('/settings/aviavox/template/{template}', [AviavoxController::class, 'deleteTemplate'])->name('settings.aviavox.deleteTemplate');

require __DIR__.'/auth.php';
