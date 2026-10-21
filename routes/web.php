<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/notes');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'spa')->name('login');
    Route::view('/register', 'spa')->name('register');
    Route::view('/forgot-password', 'spa')->name('password.request');
    Route::get('/reset-password/{token}', fn (string $token) => view('spa'))->name('password.reset');
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('password.email');
        Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
    });
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::view('/notes', 'spa')->middleware(['auth', 'auth.session'])->name('notes');
