<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::post('/announcements/make-audio', [AnnouncementController::class, 'makeAudioAnnouncement'])->name('announcements.makeAudio');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/aviavox', [SettingsController::class, 'updateAviavox'])->name('settings.aviavox.update');
    Route::post('/settings/aviavox/test-connection', [SettingsController::class, 'testConnection'])->name('settings.aviavox.test');
    Route::get('/settings/logs', [SettingsController::class, 'viewLogs'])->name('settings.logs');
    Route::get('/settings/logs/export', [SettingsController::class, 'exportLogs'])->name('settings.logs.export');
    Route::post('/settings/aviavox/store-announcement', [SettingsController::class, 'storeAnnouncement'])->name('settings.aviavox.storeAnnouncement');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
