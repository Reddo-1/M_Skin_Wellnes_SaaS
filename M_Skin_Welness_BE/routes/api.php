<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
    });
});

Route::prefix('public')->group(function () {
    Route::get('/center', function (\Illuminate\Http\Request $request) {
        $center = \App\Support\CurrentCenter::get($request);
        if ($center !== null) {
            $center->loadMissing('plan');
        }

        return response()->json([
            'center' => $center,
        ]);
    })->middleware('plan:allows_public_page');
});
