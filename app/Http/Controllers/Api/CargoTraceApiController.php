<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\ShipmentLifecycleService;
use App\Services\ShipmentSlaService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CargoTraceApiController extends Controller
{
    public function __construct(
        private readonly ShipmentLifecycleService $lifecycleService,
        private readonly ShipmentSlaService $slaService
    ) {}

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:150', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['nullable', 'in:customer,operator,admin'],
            ]);

            $role = $validated['role'] ?? 'customer';

            $user = User::create([
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
                'role' => $role,
            ]);

            $plainToken = Str::random(64);
            Cache::put('cargotrace:api_token:' . hash('sha256', $plainToken), $user->id, now()->addHours(24));

            return response()->json([
                'message' => 'User registered successfully.',
                'token_type' => 'Bearer',
                'token' => $plainToken,
                'user' => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable) {
            return response()->json(['message' => 'Failed to register user.'], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $user = User::where('email', strtolower(trim($credentials['email'])))->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'message' => 'Invalid email or password.',
                ], 401);
            }

            $plainToken = Str::random(64);
            Cache::put('cargotrace:api_token:' . hash('sha256', $plainToken), $user->id, now()->addHours(24));

            return response()->json([
                'message' => 'Authenticated successfully.',
                'token_type' => 'Bearer',
                'token' => $plainToken,
                'user' => $user,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable) {
            return response()->json(['message' => 'Authentication error.'], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $tokenHash = $request->attributes->get('api_token_hash');
        if (is_string($tokenHash) && $tokenHash !== '') {
            Cache::forget('cargotrace:api_token:' . $tokenHash);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ], 200);
    }

    public function indexShipments(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Shipment::with(['carrier', 'originLocation', 'destinationLocation'])
            ->orderByDesc('id');

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('current_status', strtoupper($request->string('status')->toString()));
        }

        if ($request->filled('group_code')) {
            $query->where('group_code', strtoupper($request->string('group_code')->toString()));
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where('shipment_number', 'like', "%{$search}%");
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));
        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->getCollection()->transform(function (Shipment $shipment) {
            $shipment->setAttribute('sla_evaluation', $this->slaService->evaluate($shipment));
            return $shipment;
        });

        return response()->json($paginator, 200);
    }

    public function storeShipment(Request $request): JsonResponse
    {
        if (! in_array($request->user()->role, ['admin', 'operator'], true)) {
            return response()->json(['message' => 'Forbidden. Operator or admin role required.'], 403);
        }

        try {
            $validated = $request->validate([
                'shipment_number' => ['required', 'string', 'max:60', 'unique:shipments,shipment_number'],
                'customer_id' => ['nullable', 'integer', 'exists:users,id'],
                'carrier_id' => ['required', 'integer', 'exists:carriers,id'],
                'origin_location_id' => ['required', 'integer', 'exists:locations,id'],
                'destination_location_id' => ['required', 'integer', 'exists:locations,id'],
                'group_code' => ['nullable', 'in:A,B,C,a,b,c'],
                'cargo_type' => ['nullable', 'string', 'max:120'],
                'weight_kg' => ['nullable', 'numeric', 'min:0'],
                'expected_departure_at' => ['nullable', 'date'],
                'expected_arrival_at' => ['nullable', 'date'],
            ]);

            $groupCode = strtoupper((string) ($validated['group_code'] ?? 'C'));
            $threshold = $this->slaService->thresholdHoursFor($groupCode);

            $shipment = Shipment::create([
                'shipment_number' => strtoupper(trim($validated['shipment_number'])),
                'customer_id' => $validated['customer_id'] ?? $request->user()->id,
                'carrier_id' => $validated['carrier_id'],
                'origin_location_id' => $validated['origin_location_id'],
                'destination_location_id' => $validated['destination_location_id'],
                'group_code' => $groupCode,
                'delay_threshold_hours' => $threshold,
                'current_status' => 'CREATED',
                'cargo_type' => $validated['cargo_type'] ?? 'General Maritime Cargo',
                'weight_kg' => $validated['weight_kg'] ?? 1000,
                'expected_departure_at' => $validated['expected_departure_at'] ?? now()->addDay(),
                'expected_arrival_at' => $validated['expected_arrival_at'] ?? now()->addDays(7),
                'is_delayed' => false,
                'delay_hours' => 0,
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'action' => 'api.shipment.created',
                'entity_type' => Shipment::class,
                'entity_id' => $shipment->id,
                'metadata' => ['shipment_number' => $shipment->shipment_number],
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'Shipment created successfully.',
                'data' => $shipment->load(['carrier', 'originLocation', 'destinationLocation']),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Throwable) {
            return response()->json(['message' => 'Failed to create shipment.'], 500);
        }
    }

    public function showShipment(Request $request, Shipment $shipment): JsonResponse
    {
        if (! $this->canAccessShipment($request, $shipment)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $shipment->load(['carrier', 'originLocation', 'destinationLocation', 'trackingEvents', 'exceptions']);

        return response()->json([
            'data' => $shipment,
            'sla_evaluation' => $this->slaService->evaluate($shipment),
        ], 200);
    }

    public function updateShipment(Request $request, Shipment $shipment): JsonResponse
    {
        if (! in_array($request->user()->role, ['admin', 'operator'], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            $validated = $request->validate([
                'carrier_id' => ['sometimes', 'integer', 'exists:carriers,id'],
                'origin_location_id' => ['sometimes', 'integer', 'exists:locations,id'],
                'destination_location_id' => ['sometimes', 'integer', 'exists:locations,id'],
                'group_code' => ['sometimes', 'in:A,B,C,a,b,c'],
                'cargo_type' => ['sometimes', 'string', 'max:120'],
                'expected_arrival_at' => ['sometimes', 'nullable', 'date'],
                'estimated_arrival_at' => ['sometimes', 'nullable', 'date'],
            ]);

            if (isset($validated['group_code'])) {
                $groupCode = strtoupper($validated['group_code']);
                $validated['group_code'] = $groupCode;
                $validated['delay_threshold_hours'] = $this->slaService->thresholdHoursFor($groupCode);
            }

            $shipment->update($validated);
            $shipment->refresh();

            $sla = $this->slaService->evaluate($shipment);
            $shipment->update([
                'is_delayed' => $sla['is_breached'],
                'delay_hours' => $sla['overdue_hours'],
                'delay_severity' => $sla['severity'],
            ]);

            return response()->json([
                'message' => 'Shipment updated successfully.',
                'data' => $shipment->fresh(['carrier', 'originLocation', 'destinationLocation']),
                'sla_evaluation' => $sla,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Throwable) {
            return response()->json(['message' => 'Failed to update shipment.'], 500);
        }
    }

    public function destroyShipment(Request $request, Shipment $shipment): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden. Admin role required.'], 403);
        }

        $shipment->delete();

        return response()->json(['message' => 'Shipment deleted successfully.'], 200);
    }

    public function trackingIndex(Request $request, Shipment $shipment): JsonResponse
    {
        if (! $this->canAccessShipment($request, $shipment)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $events = $shipment->trackingEvents()
            ->with('location')
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'shipment_id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
            'current_status' => $shipment->current_status,
            'data' => $events,
        ], 200);
    }

    public function storeTrackingEvent(Request $request, Shipment $shipment): JsonResponse
    {
        if (! in_array($request->user()->role, ['admin', 'operator'], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            $validated = $request->validate([
                'status' => ['required', 'string', 'max:50'],
                'location_id' => ['nullable', 'integer', 'exists:locations,id'],
                'description' => ['required', 'string', 'max:500'],
                'occurred_at' => ['nullable', 'date'],
                'delay_reason' => ['nullable', 'string', 'max:500'],
            ]);

            $event = $this->lifecycleService->recordTrackingEvent($shipment, $validated, $request->user());

            return response()->json([
                'message' => 'Tracking event recorded successfully.',
                'data' => $event,
                'shipment' => $shipment->fresh(),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable) {
            return response()->json(['message' => 'Failed to record tracking event.'], 500);
        }
    }

    public function indexCarriers(): JsonResponse
    {
        return response()->json([
            'data' => Carrier::orderBy('code')->get(),
        ], 200);
    }

    public function storeCarrier(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden. Admin role required.'], 403);
        }

        try {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:16', 'unique:carriers,code'],
                'name' => ['required', 'string', 'max:150'],
                'contact_email' => ['nullable', 'email', 'max:150'],
                'contact_phone' => ['nullable', 'string', 'max:40'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $carrier = Carrier::create([
                'code' => strtoupper(trim($validated['code'])),
                'name' => trim($validated['name']),
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            return response()->json(['message' => 'Carrier created.', 'data' => $carrier], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        }
    }

    public function indexLocations(Request $request): JsonResponse
    {
        $query = Location::query()->orderBy('country')->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(20)->withQueryString(), 200);
    }

    public function storeLocation(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden. Admin role required.'], 403);
        }

        try {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:16', 'unique:locations,code'],
                'name' => ['required', 'string', 'max:180'],
                'city' => ['required', 'string', 'max:120'],
                'country' => ['required', 'string', 'max:120'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ]);

            $location = Location::create([
                'code' => strtoupper(trim($validated['code'])),
                'name' => trim($validated['name']),
                'city' => trim($validated['city']),
                'country' => trim($validated['country']),
                'latitude' => round((float) $validated['latitude'], 7),
                'longitude' => round((float) $validated['longitude'], 7),
            ]);

            return response()->json(['message' => 'Location created.', 'data' => $location], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        }
    }

    public function indexExceptions(Request $request, Shipment $shipment): JsonResponse
    {
        if (! $this->canAccessShipment($request, $shipment)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json([
            'shipment_id' => $shipment->id,
            'data' => $shipment->exceptions()->orderByDesc('detected_at')->orderByDesc('id')->get(),
        ], 200);
    }

    public function storeException(Request $request, Shipment $shipment): JsonResponse
    {
        if (! in_array($request->user()->role, ['admin', 'operator'], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            $validated = $request->validate([
                'exception_type' => ['required', 'string', 'max:80'],
                'severity' => ['required', 'in:low,medium,high,critical'],
                'notes' => ['required', 'string', 'max:1000'],
            ]);

            $exception = ShipmentException::create([
                'shipment_id' => $shipment->id,
                'reported_by' => $request->user()->id,
                'exception_type' => strtolower(trim($validated['exception_type'])),
                'severity' => strtolower($validated['severity']),
                'status' => 'open',
                'notes' => trim($validated['notes']),
                'detected_at' => now(),
            ]);

            return response()->json([
                'message' => 'Exception created.',
                'data' => $exception,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        }
    }

    public function updateException(Request $request, ShipmentException $exception): JsonResponse
    {
        if (! in_array($request->user()->role, ['admin', 'operator'], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            $validated = $request->validate([
                'status' => ['required', 'in:open,investigating,resolved'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);

            $updates = [
                'status' => $validated['status'],
            ];

            if (isset($validated['notes'])) {
                $updates['notes'] = trim($validated['notes']);
            }

            if ($validated['status'] === 'resolved') {
                $updates['resolved_by'] = $request->user()->id;
                $updates['resolved_at'] = now();
            }

            $exception->update($updates);

            return response()->json([
                'message' => 'Exception updated.',
                'data' => $exception->fresh(),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        }
    }

    private function canAccessShipment(Request $request, Shipment $shipment): bool
    {
        $user = $request->user();

        if (in_array($user->role, ['admin', 'operator'], true)) {
            return true;
        }

        return (int) $shipment->customer_id === (int) $user->id;
    }
}