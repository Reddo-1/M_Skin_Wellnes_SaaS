<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AppointmentProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CenterController;
use App\Http\Controllers\Api\CenterFileController;
use App\Http\Controllers\Api\CenterRegistrationController;
use App\Http\Controllers\Api\ClientConsentController;
use App\Http\Controllers\Api\ConsentWizardController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ClientProfileController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductStockController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SkinEvaluationController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\TreatmentConsentController;
use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserFileController;
use App\Http\Controllers\Api\WorkerAbsenceController;
use App\Http\Controllers\Api\WorkerExtraAvailabilityController;
use App\Http\Controllers\Api\WorkerScheduleController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

//verificacion de email: link firmado del correo + endpoint publico para reenviar
Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:6,1');

//alta de centro nuevo: crea checkout session de stripe; el webhook crea center+admin al cobrar
Route::post('centers/register', [CenterRegistrationController::class, 'register']);

//catalogo de planes publico para el alta de centro (el detalle sigue autenticado)
Route::get('plans', [PlanController::class, 'index']);

Route::get('user-files/{user_file}/file', [UserFileController::class, 'file'])
    ->middleware('signed')
    ->name('user-files.file');

//branding del centro (logo): disco privado servido por URL firmada, igual que los user-files
Route::get('center-files/{center_file}/file', [CenterFileController::class, 'file'])
    ->middleware('signed')
    ->name('center-files.file');

Route::middleware(['auth:sanctum','verified'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    //planes: el index es publico (alta de centro); aqui solo el detalle autenticado
    Route::apiResource('plans', PlanController::class)->only(['show']);

    //lookups globales: catalogo, fuera de center.scope
    Route::get('lookups', [LookupController::class, 'index']);

    //centros: el admin solo puede leer y actualizar el suyo. Index/store/destroy viven en Blade del superadmin
    Route::apiResource('centers', CenterController::class)->only(['show', 'update']);

    //suscripcion del centro: solo accesible por el billing_user (el admin que dio de alta y paga)
    Route::get('subscription', [SubscriptionController::class, 'show']);
    Route::get('subscription/invoices', [SubscriptionController::class, 'invoices']);
    Route::get('subscription/invoices/{invoiceId}/pdf', [SubscriptionController::class, 'invoicePdf']);
    Route::post('subscription/portal', [SubscriptionController::class, 'portal']);

    Route::middleware('center.scope')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::post('appointments/{appointment}/status', [AppointmentController::class, 'changeStatus']);
        Route::get('appointments/{appointment}/products', [AppointmentProductController::class, 'index']);
        Route::post('appointments/{appointment}/products', [AppointmentProductController::class, 'store']);
        Route::delete('appointments/{appointment}/products/{product}', [AppointmentProductController::class, 'destroy']);
        Route::apiResource('appointments', AppointmentController::class);

        //branding del centro (logo/header/avatar por defecto); sin update, se sube nuevo y reemplaza
        Route::apiResource('center-files', CenterFileController::class)
            ->parameters(['center-files' => 'center_file'])
            ->only(['index', 'show', 'store', 'destroy']);

        Route::apiResource('treatments', TreatmentController::class);
        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('machines', MachineController::class);
        Route::apiResource('products', ProductController::class);

        //stock solo lectura; las modificaciones pasan por StockMovement
        Route::post('product-stocks/{product_stock}/adjust', [ProductStockController::class, 'adjust']);
        Route::apiResource('product-stocks', ProductStockController::class)
            ->parameters(['product-stocks' => 'product_stock'])
            ->only(['index', 'show']);

        //movimientos de stock: solo entradas y devoluciones manuales; salidas se generan internamente desde ventas/sesiones
        Route::apiResource('stock-movements', StockMovementController::class)
            ->parameters(['stock-movements' => 'stock_movement'])
            ->only(['index', 'show', 'store']);

        //ventas presenciales: sin update (las lineas son inmutables) ni destroy (queda para auditoria, se cancela con changeStatus)
        Route::post('sales/{sale}/status', [SaleController::class, 'changeStatus']);
        Route::apiResource('sales', SaleController::class)
            ->only(['index', 'show', 'store']);

        //facturas: solo lectura; las emite automaticamente el SaleService al cobrar
        Route::apiResource('invoices', InvoiceController::class)
            ->only(['index', 'show']);
        Route::apiResource('time-slots', TimeSlotController::class)->parameters(['time-slots' => 'time_slot']);
        Route::apiResource('worker-schedules', WorkerScheduleController::class)->parameters(['worker-schedules' => 'worker_schedule']);
        Route::apiResource('worker-absences', WorkerAbsenceController::class)->parameters(['worker-absences' => 'worker_absence']);
        Route::apiResource('worker-extra-availabilities', WorkerExtraAvailabilityController::class)->parameters(['worker-extra-availabilities' => 'worker_extra_availability']);

        Route::post('users/{user}/activate', [UserController::class, 'activate']);
        Route::post('users/{user}/online-access', [UserController::class, 'activateOnlineAccess']);
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

        //index: historico de consent por tratamiento (alta solo via wizard). update: el diagnosticador fija la aptitud desde la ficha
        Route::apiResource('treatment-consents', TreatmentConsentController::class)
            ->parameters(['treatment-consents' => 'treatment_consent'])
            ->only(['index', 'update']);

        //historico de consents RGPD del paciente; alta solo a traves del wizard, sin update porque es registro legal
        Route::apiResource('client-consents', ClientConsentController::class)
            ->parameters(['client-consents' => 'client_consent'])
            ->only(['index']);

        //wizard: crea client_consent + N treatment_consents + PDF firmado en una sola transaccion
        Route::post('consents/wizard', [ConsentWizardController::class, 'store']);

        //fotos clinicas y avatars; no hay update (se borra y se sube de nuevo)
        Route::apiResource('user-files', UserFileController::class)
            ->parameters(['user-files' => 'user_file'])
            ->except(['update']);
    });
});
