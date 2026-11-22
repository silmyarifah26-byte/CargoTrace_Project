<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', fn () => view('placeholder', ['title' => 'Dashboard']))->name('dashboard');
    Route::get('/shipments', fn () => view('placeholder', ['title' => 'Shipments']))->name('shipments.index');
    Route::get('/notifications', fn () => view('placeholder', ['title' => 'Notifications']))->name('notifications.index');

    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/exceptions', fn () => view('placeholder', ['title' => 'Exceptions']))->name('exceptions.index');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/master', fn () => view('placeholder', ['title' => 'Carriers & Locations']))->name('master.index');
        Route::get('/audit-logs', fn () => view('placeholder', ['title' => 'Audit Logs']))->name('audit-logs.index');
    });
});