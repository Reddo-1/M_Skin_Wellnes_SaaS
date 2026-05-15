<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientProfileController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SkinEvaluationController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkerAbsenceController;
use App\Http\Controllers\Api\WorkerExtraAvailabilityController;
use App\Http\Controllers\Api\WorkerScheduleController;
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
        Route::apiResource('worker-schedules', WorkerScheduleController::class)->parameters(['worker-schedules' => 'worker_schedule']);
        Route::apiResource('worker-absences', WorkerAbsenceController::class)->parameters(['worker-absences' => 'worker_absence']);
        Route::apiResource('worker-extra-availabilities', WorkerExtraAvailabilityController::class)->parameters(['worker-extra-availabilities' => 'worker_extra_availability']);

        Route::post('users/{user}/activate', [UserController::class, 'activate']);
        Route::post('users/{user}/password', [UserController::class, 'changePassword']);
        Route::post('users/{user}/roles', [UserController::class, 'syncRoles']);
        Route::apiResource('users', UserController::class);

        //no hay destroy: la baja del cliente se hace via DELETE /users/{id}, que arrastra el acceso a sus fichas
        Route::apiResource('client-profiles', ClientProfileController::class)
            ->parameters(['client-profiles' => 'client_profile'])
            ->except(['destroy']);

        //solo store/index/show/update; no hay destroy (las evaluaciones quedan como historico)
        Route::apiResource('skin-evaluations', SkinEvaluationController::class)
            ->parameters(['skin-evaluations' => 'skin_evaluation'])
            ->except(['destroy']);
    });
});
