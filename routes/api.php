<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GtfsController;
use App\Http\Controllers\Api\TrainController;
use App\Http\Controllers\AviavoxController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::post('gtfs/update', [GtfsController::class, 'update']);
    Route::get('gtfs/updates', [GtfsController::class, 'index']);
    Route::get('gtfs/updates/{gtfsUpdate}', [GtfsController::class, 'show']);
    Route::get('/trains/today', [TrainController::class, 'today']);
    Route::put('/aviavox/response', [AviavoxController::class, 'handleResponse']);
});

Route::post('/sanctum/token', [AuthController::class, 'token']);
