<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\TreatmentController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::middleware('center.scope')->group(function () {
        Route::post('appointments/{appointment}/status', [AppointmentController::class, 'changeStatus']);
        Route::apiResource('appointments', AppointmentController::class);
        Route::apiResource('treatments', TreatmentController::class);
        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('machines', MachineController::class);
        Route::apiResource('time-slots', TimeSlotController::class)->parameters(['time-slots' => 'time_slot']);
    });
});
