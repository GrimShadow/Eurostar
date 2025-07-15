<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GtfsController;
use App\Http\Controllers\Api\TrainController;
use App\Http\Controllers\AviavoxController;
use App\Http\Controllers\Api\AviavoxApiController;
use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\AnnouncementApiController;
use App\Http\Controllers\Api\BrokerController;
use App\Http\Controllers\Api\AnnouncementController;

Route::post('/aviavox/response', [AviavoxApiController::class, 'handleResponse']);
Route::get('/aviavox/response', [AviavoxApiController::class, 'handleResponse']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::post('gtfs/update', [GtfsController::class, 'update']);
    Route::get('gtfs/updates', [GtfsController::class, 'index']);
    Route::get('gtfs/updates/{gtfsUpdate}', [GtfsController::class, 'show']);
    Route::get('/trains/today', [TrainController::class, 'today']);
    Route::post('/gtfs/heartbeat', [HeartbeatController::class, 'store']);
    Route::get('/announcements', [AnnouncementApiController::class, 'index']);
    Route::get('/announcements/latest', [AnnouncementController::class, 'getLatestAnnouncements']);
    Route::middleware('auth:sanctum')->prefix('broker')->group(function () {
        Route::get('/pending-announcements', [BrokerController::class, 'getPendingAnnouncements']);
        Route::post('/announcement/{id}/status', [BrokerController::class, 'updateAnnouncementStatus']);
    });
    Route::get('/aviavox/responses', [AviavoxController::class, 'getResponses']);
    Route::post('/gtfs/stop-status', [GtfsController::class, 'updateStopStatus']);
    Route::get('/gtfs/stop-statuses/{tripId}', [GtfsController::class, 'getStopStatuses']);
    Route::post('/gtfs/train-status', [GtfsController::class, 'updateTrainStatus']);
    Route::get('/gtfs/train-status/{tripId}', [GtfsController::class, 'getTrainStatus']);
});

Route::post('/sanctum/token', [AuthController::class, 'token']);
