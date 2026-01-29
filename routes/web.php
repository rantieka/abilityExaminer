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
Route::get('/test/{application}/welcome', [TestController::class, 'welcome'])->name('test.welcome');
Route::post('/test/{application}/start', [TestController::class, 'startTest'])->name('test.start');
Route::get('/test/{application}', [TestController::class, 'show'])->name('test.show');
Route::post('/test/{application}/part1', [TestController::class, 'submitPart1'])->name('test.submitPart1');
Route::get('/test/{application}/instruction', [TestController::class, 'instruction'])->name('test.instruction');
Route::post('/test/{application}', [TestController::class, 'submit'])->name('test.submit');

// Email Preview Routes (Development Only)
if (app()->environment('local')) {
    Route::get('/email-preview/accepted/{application}', function ($id) {
        $application = \App\Models\Application::findOrFail($id);
        return new \App\Mail\ApplicationAccepted($application);
    })->name('email.preview.accepted');

    Route::get('/email-preview/rejected/{application}', function ($id) {
        $application = \App\Models\Application::findOrFail($id);
        return new \App\Mail\ApplicationRejected($application);
    })->name('email.preview.rejected');
}
