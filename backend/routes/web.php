<?php

use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DocumentEmailController;
use App\Http\Controllers\Admin\InvoicePaymentController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Ops\DashboardController as OpsDashboardController;
use App\Http\Controllers\Ops\FlightLogController;
use App\Http\Controllers\Ops\ManualController;
use App\Http\Controllers\Ops\OperationController;
use App\Http\Controllers\Ops\PilotController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::get('/site-assessment', [AssessmentController::class, 'create'])->name('assessment.create');
Route::post('/site-assessment', [AssessmentController::class, 'store'])->middleware('throttle:5,1')->name('assessment.store');
Route::view('/admin/login', 'login')->middleware('guest')->name('login');
Route::post('/admin/login', [AuthController::class, 'store'])->middleware(['guest', 'throttle:admin-login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin'])->group(function (): void {
    Route::redirect('/', '/admin/visits');
    Route::resource('visits', VisitController::class)->only(['index', 'show', 'update']);
    Route::post('/documents/{document}/payments', [InvoicePaymentController::class, 'store'])->name('documents.payments.store');
    Route::post('/documents/{document}/email', DocumentEmailController::class)->name('documents.email');
    Route::resource('documents', DocumentController::class)->except(['destroy']);
    Route::patch('/documents/{document}/status', [DocumentController::class, 'status'])->name('documents.status');
});

Route::prefix('ops')->name('ops.')->middleware(['auth', 'can:operations'])->group(function (): void {
    Route::get('/', OpsDashboardController::class)->name('dashboard');
    Route::get('/operations/create', [OperationController::class, 'create'])->middleware('can:manage-operations-users')->name('operations.create');
    Route::post('/operations', [OperationController::class, 'store'])->middleware('can:manage-operations-users')->name('operations.store');
    Route::get('/operations/{operation}', [OperationController::class, 'show'])->name('operations.show');
    Route::put('/operations/{operation}', [OperationController::class, 'update'])->middleware('can:manage-operations-users')->name('operations.update');
    Route::put('/operations/{operation}/risk-assessment', [OperationController::class, 'risk'])->name('operations.risk');
    Route::put('/operations/{operation}/flight-plan', [OperationController::class, 'flightPlan'])->name('operations.flight-plan');
    Route::put('/operations/{operation}/preflight', [OperationController::class, 'preflight'])->name('operations.preflight');
    Route::put('/operations/{operation}/authorisation', [OperationController::class, 'authorisation'])->name('operations.authorisation');
    Route::patch('/operations/{operation}/status', [OperationController::class, 'status'])->middleware('can:manage-operations-users')->name('operations.status');
    Route::get('/operations/{operation}/agreement', [OperationController::class, 'downloadAgreement'])->name('operations.agreement');
    Route::post('/operations/{operation}/flight-logs', [FlightLogController::class, 'store'])->name('operations.flight-logs.store');
    Route::delete('/operations/{operation}/flight-logs/{flightLog}', [FlightLogController::class, 'destroy'])->name('operations.flight-logs.destroy');
    Route::get('/manual', [ManualController::class, 'show'])->name('manual');
    Route::get('/manual/download', [ManualController::class, 'download'])->name('manual.download');
    Route::get('/authorisation-template', [ManualController::class, 'agreementTemplate'])->name('authorisation-template');
    Route::middleware('can:manage-operations-users')->group(function (): void {
        Route::get('/pilots', [PilotController::class, 'index'])->name('pilots.index');
        Route::post('/pilots', [PilotController::class, 'store'])->name('pilots.store');
        Route::patch('/pilots/{pilot}/status', [PilotController::class, 'status'])->name('pilots.status');
        Route::delete('/pilots/{pilot}', [PilotController::class, 'destroy'])->name('pilots.destroy');
    });
});
