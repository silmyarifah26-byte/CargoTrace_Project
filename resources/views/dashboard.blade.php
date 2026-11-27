@extends('layouts.app')

@section('title', __('Dashboard'))
@section('page-title', __('Dashboard'))

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    #customer-resi-map {
        height: 320px;
        width: 100%;
        border-radius: 0.75rem;
        z-index: 1;
        position: relative;
        user-select: none;
        -webkit-user-select: none;
    }
    #customer-resi-map .leaflet-container {
        cursor: grab;
        touch-action: none;
    }
    #customer-resi-map .leaflet-container:active {
        cursor: grabbing;
    }
</style>
@endpush

@section('content')
@php
    $roleLabels = [
        'admin' => __('Admin'),
        'operator' => __('Logistics Operator'),
        'customer' => __('Customer'),
    ];
    $currentRoleLabel = $roleLabels[auth()->user()->role] ?? strtoupper(auth()->user()->role);

    $mapCoords = [];
    if (!empty($trackedShipment)) {
        $origin = $trackedShipment->originLocation;
        $destination = $trackedShipment->destinationLocation;

        if ($origin && $origin->latitude !== null && $origin->longitude !== null) {
            $mapCoords[] = [
                'label' => __('Origin Port') . ': ' . $origin->name . ($origin->city ? ' (' . $origin->city . ')' : ''),
                'role' => 'ORIGIN',
                'roleLabel' => __('ORIGIN'),
                'lat' => (float) $origin->latitude,
                'lng' => (float) $origin->longitude,
            ];
        }

        foreach ($trackedShipment->trackingEvents->reverse() as $ev) {
            $evLat = $ev->latitude ?? $ev->location?->latitude;
            $evLng = $ev->longitude ?? $ev->location?->longitude;
            if ($evLat !== null && $evLng !== null) {
                $mapCoords[] = [
                    'label' => ($ev->location_label ?? $ev->location_name ?? $ev->location?->name ?? __('Checkpoint')) . ' — ' . str_replace('_', ' ', (string) ($ev->event_type ?? $ev->status ?? __('Tracking'))),
                    'role' => 'CHECKPOINT',
                    'roleLabel' => __('CHECKPOINT'),
                    'lat' => (float) $evLat,
                    'lng' => (float) $evLng,
                ];
            }
        }

        if ($destination && $destination->latitude !== null && $destination->longitude !== null) {
            $mapCoords[] = [
                'label' => __('Destination Port') . ': ' . $destination->name . ($destination->city ? ' (' . $destination->city . ')' : ''),
                'role' => 'DESTINATION',
                'roleLabel' => __('DESTINATION'),
                'lat' => (float) $destination->latitude,
                'lng' => (float) $destination->longitude,
            ];
        }
    }

    $exceptionStatusLabels = [
        'open' => __('OPEN'),
        'investigating' => __('INVESTIGATING'),
        'resolved' => __('RESOLVED'),
        'closed' => __('CLOSED'),
    ];

    $exceptionTypeLabels = [
        'WEATHER_DELAY' => __('Weather Delay'),
        'CUSTOMS_HOLD' => __('Customs Hold'),
        'DOCUMENTATION_ISSUE' => __('Documentation Issue'),
        'PORT_CONGESTION' => __('Port Congestion'),
        'VESSEL_DELAY' => __('Vessel Delay'),
        'SLA_DELAY' => __('SLA Delay'),
    ];

    $shipmentStatusLabels = [
        'CREATED' => __('CREATED'),
        'BOOKED' => __('BOOKED'),
        'PICKED_UP' => __('PICKED UP'),
        'IN_TRANSIT' => __('IN TRANSIT'),
        'OUT_FOR_DELIVERY' => __('OUT FOR DELIVERY'),
        'DELIVERED' => __('DELIVERED'),
        'DELAYED' => __('DELAYED'),
        'CANCELLED' => __('CANCELLED'),
    ];
@endphp

<div class="space-y-6">
    {{-- Customer Tracking Number Lookup --}}
    @if (auth()->user()->role === 'customer')
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Track Package / Check Tracking Number') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Enter your shipment tracking number to view the current package position, route map, and tracking history.') }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row gap-2.5">
                <input
                    type="text"
                    name="resi"
                    value="{{ $resi ?? '' }}"
                    placeholder="{{ __('Enter tracking number (e.g., CT-2026-A001)') }}"
                    class="flex-1 rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:border-slate-900"
                >
                <button
                    type="submit"
                    class="rounded-lg bg-slate-950 px-5 py-2 text-sm font-medium text-white hover:bg-slate-800 cursor-pointer"
                >
                    {{ __('Track Package') }}
                </button>
                @if (!empty($resi))
                    <a
                        href="{{ route('dashboard') }}"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 text-center"
                    >
                        {{ __('Reset') }}
                    </a>
                @endif
            </form>

            {{-- Tracking Result After Search --}}
            @if (!empty($searchedResi) && ($resi ?? '') !== '')
                @if (!$trackedShipment)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-800">
                        {!! __('Tracking number :resi was not found in your account shipments.', ['resi' => '<span class="font-mono font-semibold">' . e($resi) . '</span>']) !!}
                    </div>
                @else
                    @php
                        $latestEv = $trackedShipment->trackingEvents->first();
                        $posText = $latestEv->location_label
                            ?? ($latestEv->location_name
                            ?? ($latestEv->location?->name
                            ?? ($latestEv->location
                            ?? ($trackedShipment->destinationLocation->name ?? '-'))));
                        $rawTrackedStatus = strtoupper((string) $trackedShipment->current_status);
                        $trackedStatusLabel = $shipmentStatusLabels[$rawTrackedStatus] ?? str_replace('_', ' ', $rawTrackedStatus);
                    @endphp

                    <div class="mt-5 pt-5 border-t border-slate-200 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <span class="text-xs text-slate-500">{{ __('Tracking Number') }}:</span>
                                <span class="font-mono text-sm font-bold text-slate-900">{{ $trackedShipment->shipment_number }}</span>
                                <span class="mx-2 text-slate-300">|</span>
                                <span class="text-xs text-slate-600">
                                    {{ __('Current position') }}: <strong class="text-slate-900">{{ $posText }}</strong>
                                </span>
                            </div>
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-800">
                                {{ $trackedStatusLabel }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="lg:col-span-2">
                                @if (count($mapCoords) > 0)
                                    <div id="customer-resi-map" class="border border-slate-200"></div>
                                    <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                                        <span>{{ __('Drag left/right/up/down, scroll to zoom in/out, or use the pan buttons.') }}</span>
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" id="resi-pan-left-btn" title="{{ __('Pan Left') }}"
                                                    class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&larr; {{ __('Left') }}</button>
                                            <button type="button" id="resi-pan-up-btn" title="{{ __('Pan Up') }}"
                                                    class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&uarr; {{ __('Up') }}</button>
                                            <button type="button" id="resi-pan-down-btn" title="{{ __('Pan Down') }}"
                                                    class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&darr; {{ __('Down') }}</button>
                                            <button type="button" id="resi-pan-right-btn" title="{{ __('Pan Right') }}"
                                                    class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">{{ __('Right') }} &rarr;</button>
                                        </div>
                                    </div>
                                    <div id="resi-map-fallback-note" class="hidden mt-2 p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600">
                                        {{ __('Route coordinates detected (:count points). Connect to the internet to load OpenStreetMap tiles.', ['count' => count($mapCoords)]) }}
                                    </div>
                                @else
                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500">
                                        {{ __('Location coordinates are not yet available for this shipment.') }}
                                    </div>
                                @endif
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4 space-y-3 max-h-80 overflow-y-auto">
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Journey History') }}</h3>
                                @forelse ($trackedShipment->trackingEvents as $ev)
                                    <div class="border-l-2 border-slate-400 pl-3 text-xs">
                                        <div class="font-semibold text-slate-900">{{ $ev->event_type ?? $ev->status }}</div>
                                        <div class="text-slate-600">{{ $ev->location_label ?? ($ev->location_name ?? ($ev->location?->name ?? ($ev->location ?? '-'))) }}</div>
                                        <div class="text-[11px] text-slate-400">
                                            {{ !empty($ev->occurred_at) ? \Carbon\Carbon::parse($ev->occurred_at)->format('d M Y H:i') : '' }}
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500">{{ __('No tracking history yet.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif

    {{-- Key KPI Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('Total Shipments') }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalShipments }}</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __(':in_transit in transit • :delivered delivered', ['in_transit' => $inTransitCount, 'delivered' => $deliveredCount]) }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('SLA Breached / Delayed') }}</p>
            <p class="mt-2 text-2xl font-bold {{ $delayedCount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ $delayedCount }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('On-time rate') }}: <span class="font-semibold text-slate-800">{{ $onTimeRate }}%</span>
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('Open Exceptions') }}</p>
            <p class="mt-2 text-2xl font-bold {{ $openExceptionsCount > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                {{ $openExceptionsCount }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Total :count exceptions recorded', ['count' => $totalExceptionsCount]) }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('Network Coverage') }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $activeCarriersCount }} / {{ $totalLocationsCount }}</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Active carriers / Port locations') }}
            </p>
        </div>
    </div>

    {{-- SLA Group A / B / C Performance (Admin & Operator Only) --}}
    @if (in_array(auth()->user()->role, ['admin', 'operator'], true))
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('SLA Performance by Group') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Delay thresholds: Group A (12h), Group B (18h), Group C (24h).') }}</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 shrink-0">
                    {{ __('Role') }}: {{ strtoupper($currentRoleLabel) }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($slaGroups as $group)
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-bold text-slate-900">{{ __('Group :code', ['code' => $group['code']]) }}</span>
                                <span class="ml-2 rounded-md bg-white px-2 py-0.5 text-xs font-mono text-slate-600 border border-slate-200">
                                    {{ __('Limit :hoursh', ['hours' => $group['threshold_hours']]) }}
                                </span>
                            </div>
                            <span class="text-sm font-bold {{ $group['delayed'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $group['compliance_rate'] }}% SLA
                            </span>
                        </div>

                        <div class="mt-3 h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full rounded-full {{ $group['delayed'] > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"
                                 style="width: {{ $group['compliance_rate'] }}%"></div>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs text-slate-600">
                            <span>{{ __('Total') }}: <strong>{{ $group['total'] }}</strong></span>
                            <span class="text-emerald-700">{{ __('On Time') }}: <strong>{{ $group['on_time'] }}</strong></span>
                            <span class="text-rose-700">{{ __('Delayed') }}: <strong>{{ $group['delayed'] }}</strong></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent Shipments & Recent Exceptions --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Recent Shipments') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Sea route monitoring, SLA status, and estimated arrival.') }}</p>
                </div>
                <a href="{{ route('shipments.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-950 underline whitespace-nowrap shrink-0">
                    {{ __('View all') }} &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 bg-slate-50">
                            <th class="px-3 py-2.5">{{ __('Shipment') }}</th>
                            <th class="px-3 py-2.5">{{ __('Port Route') }}</th>
                            <th class="px-3 py-2.5">{{ __('Group') }}</th>
                            <th class="px-3 py-2.5">{{ __('Status') }}</th>
                            <th class="px-3 py-2.5">{{ __('SLA') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentShipments as $shipment)
                            @php
                                $rawStatus = strtoupper((string) $shipment->current_status);
                                $statusText = $shipmentStatusLabels[$rawStatus] ?? str_replace('_', ' ', $rawStatus);
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-3 py-2.5">
                                    <a href="{{ route('shipments.show', $shipment) }}" class="font-mono font-semibold text-slate-900 hover:underline">
                                        {{ $shipment->shipment_number }}
                                    </a>
                                    <div class="text-xs text-slate-500">{{ $shipment->carrier->code ?? '-' }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-xs text-slate-700">
                                    <span class="font-semibold">{{ $shipment->originLocation->code ?? '-' }}</span>
                                    &rarr;
                                    <span class="font-semibold">{{ $shipment->destinationLocation->code ?? '-' }}</span>
                                    <div class="text-slate-400">
                                        {{ __(':origin to :destination', ['origin' => $shipment->originLocation->city ?? '', 'destination' => $shipment->destinationLocation->city ?? '']) }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 font-mono text-xs font-semibold text-slate-800 whitespace-nowrap">
                                    {{ __(':code (:hoursh)', ['code' => $shipment->group_code, 'hours' => $shipment->delay_threshold_hours]) }}
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    @if ($shipment->is_delayed)
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 border border-rose-200">
                                            {{ __('Delayed +:hoursh', ['hours' => $shipment->delay_hours]) }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                                            {{ __('On Time') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">{{ __('No shipment data yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Recent Exceptions') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Operational exceptions & shipment anomalies.') }}</p>
                </div>
                @if (in_array(auth()->user()->role, ['admin', 'operator'], true))
                    <a href="{{ route('exceptions.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-950 underline whitespace-nowrap shrink-0">
                        {{ __('Manage') }} &rarr;
                    </a>
                @endif
            </div>

            <div class="space-y-3">
                @forelse ($recentExceptions as $exception)
                    @php
                        $rawExStatus = strtolower((string) $exception->status);
                        $exStatusText = $exceptionStatusLabels[$rawExStatus] ?? strtoupper((string) $exception->status);
                        $rawExType = strtoupper((string) ($exception->exception_type ?? $exception->category ?? ''));
                        $exTypeText = $exceptionTypeLabels[$rawExType] ?? ($rawExType !== '' ? str_replace('_', ' ', $rawExType) : __('Operational Exception'));
                        $rawNotes = (string) ($exception->notes ?? $exception->description ?? '');
                        $notesText = $rawNotes !== '' ? __($rawNotes) : '';
                    @endphp
                    <div class="rounded-xl border border-slate-200 p-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-bold text-slate-900">
                                {{ $exception->shipment->shipment_number ?? (__('Shipment') . ' #' . $exception->shipment_id) }}
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap shrink-0 {{ $rawExStatus === 'open' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                {{ $exStatusText }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs font-semibold text-slate-800">
                            {{ $exTypeText }}
                        </p>
                        @if ($notesText !== '')
                            <p class="mt-0.5 text-xs text-slate-500 line-clamp-2">
                                {{ $notesText }}
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-4 text-center">{{ __('No recent exceptions.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if (count($mapCoords) > 0)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const points = @json($mapCoords);
        const mapEl = document.getElementById('customer-resi-map');
        const fallbackEl = document.getElementById('resi-map-fallback-note');

        if (!mapEl || !Array.isArray(points) || points.length === 0) return;

        if (typeof window.L === 'undefined') {
            mapEl.classList.add('hidden');
            if (fallbackEl) fallbackEl.classList.remove('hidden');
            return;
        }

        const map = L.map('customer-resi-map', {
            dragging: true,
            scrollWheelZoom: true,
            doubleClickZoom: true,
            touchZoom: true,
            boxZoom: true,
            keyboard: true,
            zoomControl: true,
            worldCopyJump: false,
            inertia: true,
            inertiaDeceleration: 1800,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            minZoom: 2,
            maxZoom: 18,
            noWrap: true,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        map.dragging.enable();
        map.scrollWheelZoom.enable();
        map.touchZoom.enable();
        map.doubleClickZoom.enable();

        const latLngs = [];
        const colorMap = {
            ORIGIN: '#059669',
            CHECKPOINT: '#0284c7',
            DESTINATION: '#e11d48',
        };

        points.forEach(function (pt) {
            const coord = [pt.lat, pt.lng];
            latLngs.push(coord);

            L.circleMarker(coord, {
                radius: 7,
                fillColor: colorMap[pt.role] || '#0284c7',
                color: '#ffffff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.95,
            }).addTo(map).bindPopup('<strong>' + (pt.roleLabel || pt.role) + '</strong><br>' + pt.label);
        });

        if (latLngs.length > 1) {
            const polyline = L.polyline(latLngs, {
                color: '#0284c7',
                weight: 3,
                dashArray: '6, 6',
                opacity: 0.85,
            }).addTo(map);
            map.fitBounds(polyline.getBounds(), { padding: [30, 30] });
        } else {
            map.setView(latLngs[0], 6);
        }

        const panStep = 140;
        const bindPan = (id, dx, dy) => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.addEventListener('click', function () {
                    map.panBy([dx, dy], { animate: true, duration: 0.25 });
                });
            }
        };

        bindPan('resi-pan-left-btn', -panStep, 0);
        bindPan('resi-pan-right-btn', panStep, 0);
        bindPan('resi-pan-up-btn', 0, -panStep);
        bindPan('resi-pan-down-btn', 0, panStep);

        setTimeout(function () {
            map.invalidateSize();
        }, 200);
    });
</script>
@endif
@endpush
