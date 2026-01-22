<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CareerController;

Route::get('/', [LandingController::class, 'index'])->name('home');

Route::get('/test-bootstrap', function () {
    return view('test-bootstrap');
});

// Career
Route::get('/career', [CareerController::class, 'index'])->name('career.index');
Route::get('/career/{slug}', [CareerController::class, 'show'])->name('career.show');
Route::post('/career/{slug}/apply', [CareerController::class, 'apply'])->name('career.apply');

use App\Http\Controllers\TestController;
use App\Http\Controllers\TestAuthController;

// Test Auth
Route::get('/test/login', [TestAuthController::class, 'index'])->name('test.login');
Route::post('/test/login', [TestAuthController::class, 'authenticate'])->name('test.authenticate');

// Protected Test Routes (Middleware check in Controller)
Route::get('/test/{application}', [TestController::class, 'show'])->name('test.show');
Route::post('/test/{application}', [TestController::class, 'submit'])->name('test.submit');
