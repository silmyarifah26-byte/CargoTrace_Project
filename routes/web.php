<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShipmentController;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
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

    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();

        $shipmentQuery = Shipment::query();
        if ($user->role === 'customer') {
            $shipmentQuery->where('customer_id', $user->id);
        }

        $totalShipments = (clone $shipmentQuery)->count();
        $deliveredCount = (clone $shipmentQuery)->where('current_status', 'delivered')->count();
        $inTransitCount = max(0, $totalShipments - $deliveredCount);
        $delayedCount = (clone $shipmentQuery)->where('is_delayed', true)->count();
        $onTimeCount = max(0, $totalShipments - $delayedCount);
        $onTimeRate = $totalShipments > 0 ? round(($onTimeCount / $totalShipments) * 100, 1) : 100.0;

        $groupConfigs = [
            'A' => 12,
            'B' => 18,
            'C' => 24,
        ];

        $slaGroups = [];
        foreach ($groupConfigs as $code => $defaultThreshold) {
            $groupBase = (clone $shipmentQuery)->where('group_code', $code);
            $groupTotal = (clone $groupBase)->count();
            $groupDelayed = (clone $groupBase)->where('is_delayed', true)->count();
            $groupOnTime = max(0, $groupTotal - $groupDelayed);
            $thresholdHours = (clone $groupBase)->value('delay_threshold_hours') ?? $defaultThreshold;
            $complianceRate = $groupTotal > 0 ? round(($groupOnTime / $groupTotal) * 100, 1) : 100.0;

            $slaGroups[] = [
                'code' => $code,
                'threshold_hours' => $thresholdHours,
                'total' => $groupTotal,
                'on_time' => $groupOnTime,
                'delayed' => $groupDelayed,
                'compliance_rate' => $complianceRate,
            ];
        }

        $exceptionQuery = ShipmentException::query()->with('shipment');
        if ($user->role === 'customer') {
            $exceptionQuery->whereHas('shipment', fn ($q) => $q->where('customer_id', $user->id));
        }

        $openExceptionsCount = (clone $exceptionQuery)->where('status', 'open')->count();
        $totalExceptionsCount = (clone $exceptionQuery)->count();

        $recentShipments = (clone $shipmentQuery)
            ->with(['carrier', 'originLocation', 'destinationLocation'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $recentExceptions = (clone $exceptionQuery)
            ->orderByDesc('detected_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $activeCarriersCount = Carrier::where('is_active', true)->count();
        $totalLocationsCount = Location::count();

        // Pencarian nomor resi untuk customer (hanya muncul setelah resi dicari)
        $resi = trim((string) $request->query('resi', ''));
        $searchedResi = $request->has('resi');
        $trackedShipment = null;

        if ($searchedResi && $resi !== '') {
            $trackedQuery = Shipment::with([
                'carrier',
                'originLocation',
                'destinationLocation',
                'trackingEvents' => function ($q) {
                    $q->orderByDesc('occurred_at')->orderByDesc('id');
                },
            ]);

            if ($user->role === 'customer') {
                $trackedQuery->where('customer_id', $user->id);
            }

            $trackedShipment = $trackedQuery
                ->where('shipment_number', 'like', '%' . $resi . '%')
                ->first();
        }

        return view('dashboard', compact(
            'totalShipments',
            'deliveredCount',
            'inTransitCount',
            'delayedCount',
            'onTimeRate',
            'slaGroups',
            'openExceptionsCount',
            'totalExceptionsCount',
            'recentShipments',
            'recentExceptions',
            'activeCarriersCount',
            'totalLocationsCount',
            'resi',
            'searchedResi',
            'trackedShipment'
        ));
    })->name('dashboard');

    Route::get('/notifications', function (Request $request) {
        $user = $request->user();
        $filter = $request->query('filter', 'all');

        $baseQuery = Notification::query()
            ->with('shipment')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);
                if ($user->role === 'customer') {
                    $query->orWhereHas('shipment', fn ($q) => $q->where('customer_id', $user->id));
                }
            });

        $unreadCount = (clone $baseQuery)->whereNull('read_at')->count();
        $totalCount = (clone $baseQuery)->count();

        $notifications = (clone $baseQuery)
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($filter === 'read', fn ($q) => $q->whereNotNull('read_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('notifications.index', compact(
            'notifications',
            'unreadCount',
            'totalCount',
            'filter'
        ));
    })->name('notifications.index');

    Route::patch('/notifications/{notification}/read', function (Request $request, Notification $notification) {
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return back()->with('success', 'Notifikasi ditandai sebagai sudah dibaca.');
    })->name('notifications.read');

    Route::patch('/notifications/read-all', function (Request $request) {
        $user = $request->user();

        Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi berhasil ditandai dibaca.');
    })->name('notifications.read-all');

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
        Route::get('/master', function (Request $request) {
            $carrierSearch = trim((string) $request->query('carrier_q', ''));
            $locationSearch = trim((string) $request->query('location_q', ''));

            $carriers = Carrier::withCount('shipments')
                ->when($carrierSearch !== '', function ($query) use ($carrierSearch) {
                    $query->where(function ($sub) use ($carrierSearch) {
                        $sub->where('code', 'like', "%{$carrierSearch}%")
                            ->orWhere('name', 'like', "%{$carrierSearch}%")
                            ->orWhere('contact_email', 'like', "%{$carrierSearch}%");
                    });
                })
                ->orderBy('code')
                ->get();

            $locations = Location::query()
                ->when($locationSearch !== '', function ($query) use ($locationSearch) {
                    $query->where(function ($sub) use ($locationSearch) {
                        $sub->where('code', 'like', "%{$locationSearch}%")
                            ->orWhere('name', 'like', "%{$locationSearch}%")
                            ->orWhere('city', 'like', "%{$locationSearch}%")
                            ->orWhere('country', 'like', "%{$locationSearch}%");
                    });
                })
                ->orderBy('country')
                ->orderBy('name')
                ->paginate(20, ['*'], 'locations_page')
                ->withQueryString();

            $totalCarriers = Carrier::count();
            $activeCarriers = Carrier::where('is_active', true)->count();
            $totalLocations = Location::count();

            return view('master.index', compact(
                'carriers',
                'locations',
                'carrierSearch',
                'locationSearch',
                'totalCarriers',
                'activeCarriers',
                'totalLocations'
            ));
        })->name('master.index');

        Route::post('/master/carriers', function (Request $request) {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:16', 'unique:carriers,code'],
                'name' => ['required', 'string', 'max:150'],
                'contact_email' => ['nullable', 'email', 'max:150'],
                'contact_phone' => ['nullable', 'string', 'max:40'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            Carrier::create([
                'code' => strtoupper(trim($validated['code'])),
                'name' => trim($validated['name']),
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return redirect()->route('master.index')
                ->with('success', 'Carrier baru berhasil ditambahkan.');
        })->name('master.carriers.store');

        Route::post('/master/locations', function (Request $request) {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:16', 'unique:locations,code'],
                'name' => ['required', 'string', 'max:180'],
                'city' => ['required', 'string', 'max:120'],
                'country' => ['required', 'string', 'max:120'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ]);

            Location::create([
                'code' => strtoupper(trim($validated['code'])),
                'name' => trim($validated['name']),
                'city' => trim($validated['city']),
                'country' => trim($validated['country']),
                'latitude' => round((float) $validated['latitude'], 7),
                'longitude' => round((float) $validated['longitude'], 7),
            ]);

            return redirect()->route('master.index')
                ->with('success', 'Pelabuhan baru berhasil ditambahkan ke master lokasi.');
        })->name('master.locations.store');

        Route::get('/audit-logs', function () {
            $auditLogs = AuditLog::with('actor')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(15);

            return view('audit-logs.index', compact('auditLogs'));
        })->name('audit-logs.index');
    });
});