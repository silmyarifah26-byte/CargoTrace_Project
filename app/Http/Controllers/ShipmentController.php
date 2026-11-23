<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Services\MaritimeWeatherService;
use App\Services\ShipmentLifecycleService;
use App\Services\ShipmentSlaService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShipmentLifecycleService $lifecycleService,
        private readonly ShipmentSlaService $slaService,
        private readonly MaritimeWeatherService $weatherService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $allowedSorts = [
            'latest' => ['column' => 'id', 'direction' => 'desc'],
            'shipment_number' => ['column' => 'shipment_number', 'direction' => 'asc'],
            'expected_arrival_at' => ['column' => 'expected_arrival_at', 'direction' => 'asc'],
            'delay_hours' => ['column' => 'delay_hours', 'direction' => 'desc'],
        ];

        $sortKey = (string) $request->query('sort', 'latest');
        $sortConfig = $allowedSorts[$sortKey] ?? $allowedSorts['latest'];

        $query = Shipment::with(['carrier', 'originLocation', 'destinationLocation'])
            ->orderBy($sortConfig['column'], $sortConfig['direction']);

        if ($sortKey !== 'latest') {
            $query->orderByDesc('id');
        }

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('current_status', $request->string('status')->toString());
        }

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->integer('carrier_id'));
        }

        if ($request->filled('origin_id')) {
            $query->where('origin_location_id', $request->integer('origin_id'));
        }

        if ($request->filled('destination_id')) {
            $query->where('destination_location_id', $request->integer('destination_id'));
        }

        if ($request->filled('group_code')) {
            $query->where('group_code', strtoupper($request->string('group_code')->toString()));
        }

        if ($request->filled('delay_status')) {
            $delayStatus = $request->string('delay_status')->toString();
            if ($delayStatus === 'delayed') {
                $query->where('is_delayed', true);
            } elseif ($delayStatus === 'on_time') {
                $query->where('is_delayed', false);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($sub) use ($search) {
                $sub->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('cargo_type', 'like', "%{$search}%")
                    ->orWhereHas('carrier', fn ($c) => $c->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('originLocation', fn ($l) => $l->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('destinationLocation', fn ($l) => $l->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $shipments = $query->paginate(10)->withQueryString();

        $slaEvaluations = $shipments->getCollection()->mapWithKeys(
            fn (Shipment $item) => [$item->id => $this->slaService->evaluate($item)]
        );

        $carriers = Carrier::where('is_active', true)->orderBy('code')->get();
        $locations = Location::orderBy('name')->get();

        return view('shipments.index', [
            'shipments' => $shipments,
            'slaService' => $this->slaService,
            'slaEvaluations' => $slaEvaluations,
            'carriers' => $carriers,
            'locations' => $locations,
            'sortKey' => $sortKey,
        ]);
    }

    public function show(Request $request, Shipment $shipment): View
    {
        $this->authorizeShipmentAccess($request, $shipment);

        $shipment->load([
            'customer',
            'carrier',
            'originLocation',
            'destinationLocation',
            'items',
            'trackingEvents' => fn ($q) => $q->with('location')->orderBy('occurred_at')->orderBy('id'),
            'exceptions',
        ]);

        $locations = Location::orderBy('name')->get();
        $slaEval = $this->slaService->evaluate($shipment);

        $targetLocation = $shipment->destinationLocation ?? $shipment->originLocation;
        $destinationWeather = $this->weatherService->fetchLocationWeather(
            $targetLocation?->latitude !== null ? (float) $targetLocation->latitude : null,
            $targetLocation?->longitude !== null ? (float) $targetLocation->longitude : null,
            $targetLocation
                ? ($targetLocation->name . ($targetLocation->city ? ' (' . $targetLocation->city . ')' : ''))
                : 'Destination'
        );

        return view('shipments.show', [
            'shipment' => $shipment,
            'locations' => $locations,
            'slaEval' => $slaEval,
            'isOverdue' => $slaEval['is_breached'],
            'overdueHours' => $slaEval['overdue_hours'],
            'thresholdHours' => $slaEval['threshold_hours'],
            'destinationWeather' => $destinationWeather,
        ]);
    }

    public function storeEvent(Request $request, Shipment $shipment): RedirectResponse
    {
        if (! in_array($request->user()->role, ['admin', 'operator'], true)) {
            abort(403, 'Anda tidak memiliki izin untuk memperbarui status pengiriman.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'description' => ['required', 'string', 'max:500'],
            'occurred_at' => ['nullable', 'date'],
            'delay_reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->lifecycleService->recordTrackingEvent($shipment, $validated, $request->user());
        } catch (DomainException $e) {
            return back()
                ->withInput()
                ->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Event pelacakan, status shipment, notifikasi, dan audit log berhasil diperbarui.');
    }

    private function authorizeShipmentAccess(Request $request, Shipment $shipment): void
    {
        $user = $request->user();

        if (in_array($user->role, ['admin', 'operator'], true)) {
            return;
        }

        if ($shipment->customer_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat pengiriman ini.');
        }
    }
}