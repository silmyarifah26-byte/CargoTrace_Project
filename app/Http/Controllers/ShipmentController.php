<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Shipment;
use App\Services\ShipmentLifecycleService;
use App\Services\ShipmentSlaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShipmentLifecycleService $lifecycleService,
        private readonly ShipmentSlaService $slaService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Shipment::with(['carrier', 'originLocation', 'destinationLocation'])
            ->latest();

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('current_status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('shipment_number', 'like', "%{$search}%");
        }

        $shipments = $query->paginate(10)->withQueryString();

        $slaEvaluations = $shipments->getCollection()->mapWithKeys(
            fn (Shipment $item) => [$item->id => $this->slaService->evaluate($item)]
        );

        return view('shipments.index', [
            'shipments' => $shipments,
            'slaService' => $this->slaService,
            'slaEvaluations' => $slaEvaluations,
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

        return view('shipments.show', [
            'shipment' => $shipment,
            'locations' => $locations,
            'slaEval' => $slaEval,
            'isOverdue' => $slaEval['is_breached'],
            'overdueHours' => $slaEval['overdue_hours'],
            'thresholdHours' => $slaEval['threshold_hours'],
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

        $this->lifecycleService->recordTrackingEvent($shipment, $validated, $request->user());

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