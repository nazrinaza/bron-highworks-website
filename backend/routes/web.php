<?php

use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AuthController;
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
    Route::post('/documents/{document}/payments', [\App\Http\Controllers\Admin\InvoicePaymentController::class, 'store'])->name('documents.payments.store');
    Route::resource('documents', DocumentController::class)->except(['destroy']);
    Route::patch('/documents/{document}/status', [DocumentController::class, 'status'])->name('documents.status');
});
