<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShipmentController;
use App\Models\AuditLog;
use App\Models\ShipmentException;
use Illuminate\Http\Request;
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
    Route::get('/notifications', fn () => view('placeholder', ['title' => 'Notifications']))->name('notifications.index');

    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::post('/shipments/{shipment}/events', [ShipmentController::class, 'storeEvent'])
        ->middleware('role:admin,operator')
        ->name('shipments.events.store');

    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/exceptions', function () {
            $exceptions = ShipmentException::with(['shipment', 'reporter', 'resolver'])
                ->orderByDesc('detected_at')
                ->orderByDesc('id')
                ->paginate(10);

            return view('exceptions.index', compact('exceptions'));
        })->name('exceptions.index');

        Route::patch('/exceptions/{exception}/resolve', function (Request $request, ShipmentException $exception) {
            $exception->update([
                'status' => 'resolved',
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);

            return back()->with('success', 'Exception berhasil ditandai sebagai resolved.');
        })->name('exceptions.resolve');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/master', fn () => view('placeholder', ['title' => 'Carriers & Locations']))->name('master.index');

        Route::get('/audit-logs', function () {
            $auditLogs = AuditLog::with('actor')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(15);

            return view('audit-logs.index', compact('auditLogs'));
        })->name('audit-logs.index');
    });
});