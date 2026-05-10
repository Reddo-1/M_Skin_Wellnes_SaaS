<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::middleware('center.scope')->group(function () {
        Route::middleware('permission:appointments.view')->group(function () {
            Route::get('appointments', [AppointmentController::class, 'index']);
            Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
        });

        Route::middleware('permission:appointments.create')->group(function () {
            Route::post('appointments', [AppointmentController::class, 'store']);
        });

        Route::middleware('permission:appointments.update')->group(function () {
            Route::put('appointments/{appointment}',   [AppointmentController::class, 'update']);
            Route::patch('appointments/{appointment}', [AppointmentController::class, 'update']);
        });

        Route::middleware('permission:appointments.delete')->group(function () {
            Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy']);
        });

        Route::middleware('permission:appointments.change_status')->group(function () {
            Route::post('appointments/{appointment}/status', [AppointmentController::class, 'changeStatus']);
        });
    });
});
