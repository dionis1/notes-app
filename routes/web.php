<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Notes;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/notes');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.form', ['mode' => 'login'])->name('login');
    Route::view('/register', 'auth.form', ['mode' => 'register'])->name('register');
    Route::view('/forgot-password', 'auth.form', ['mode' => 'forgot'])->name('password.request');
    Route::get('/reset-password/{token}', fn (string $token) => view('auth.form', ['mode' => 'reset', 'token' => $token]))->name('password.reset');
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('password.email');
        Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
    });
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/notes', Notes::class)->middleware('auth')->name('notes');
