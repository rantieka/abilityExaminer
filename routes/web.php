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
