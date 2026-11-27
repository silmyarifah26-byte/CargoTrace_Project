@extends('layouts.app')

@section('title', __('Shipment Timeline') . ' — ' . ($shipment->shipment_number ?? ('#' . $shipment->id)))
@section('page-title', __('Shipment Timeline') . ' — ' . ($shipment->shipment_number ?? ('#' . $shipment->id)))

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    #maritime-route-map {
        height: 360px;
        width: 100%;
        border-radius: 0.75rem;
        z-index: 1;
        position: relative;
        user-select: none;
        -webkit-user-select: none;
    }
    #maritime-route-map .leaflet-container {
        cursor: grab;
        touch-action: none;
    }
    #maritime-route-map .leaflet-container:active {
        cursor: grabbing;
    }
</style>
@endpush

@section('content')
@php
    $origin = $shipment->originLocation;
    $destination = $shipment->destinationLocation;

    $routePoints = [];
    if ($origin && $origin->latitude !== null && $origin->longitude !== null) {
        $routePoints[] = [
            'label' => __('Origin Port') . ': ' . $origin->name . ($origin->city ? ' (' . $origin->city . ')' : ''),
            'role' => 'ORIGIN',
            'roleLabel' => __('ORIGIN'),
            'lat' => (float) $origin->latitude,
            'lng' => (float) $origin->longitude,
        ];
    }

    foreach ($shipment->trackingEvents as $ev) {
        $evLat = $ev->latitude ?? $ev->location?->latitude;
        $evLng = $ev->longitude ?? $ev->location?->longitude;
        if ($evLat !== null && $evLng !== null) {
            $routePoints[] = [
                'label' => ($ev->location_label ?? $ev->location?->name ?? __('Checkpoint')) . ' — ' . str_replace('_', ' ', (string) $ev->status),
                'role' => 'EVENT',
                'roleLabel' => __('CHECKPOINT'),
                'lat' => (float) $evLat,
                'lng' => (float) $evLng,
            ];
        }
    }

    if ($destination && $destination->latitude !== null && $destination->longitude !== null) {
        $routePoints[] = [
            'label' => __('Destination Port') . ': ' . $destination->name . ($destination->city ? ' (' . $destination->city . ')' : ''),
            'role' => 'DESTINATION',
            'roleLabel' => __('DESTINATION'),
            'lat' => (float) $destination->latitude,
            'lng' => (float) $destination->longitude,
        ];
    }

    $currentStatusUpper = strtoupper((string) ($shipment->current_status ?? 'CREATED'));
    $statusOptions = [
        'CREATED' => __('Created'),
        'BOOKED' => __('Booked'),
        'PICKED_UP' => __('Picked Up'),
        'IN_TRANSIT' => __('In Transit'),
        'DELIVERED' => __('Delivered'),
    ];

    $exceptionTypeLabels = [
        'WEATHER_DELAY' => __('Weather Delay'),
        'CUSTOMS_HOLD' => __('Customs Hold'),
        'DOCUMENTATION_ISSUE' => __('Documentation Issue'),
        'PORT_CONGESTION' => __('Port Congestion'),
        'VESSEL_DELAY' => __('Vessel Delay'),
        'SLA_DELAY' => __('SLA Delay'),
    ];

    $severityLabels = [
        'CRITICAL' => __('CRITICAL'),
        'HIGH' => __('HIGH'),
        'MEDIUM' => __('MEDIUM'),
        'LOW' => __('LOW'),
    ];

    $exceptionStatusLabels = [
        'OPEN' => __('OPEN'),
        'INVESTIGATING' => __('INVESTIGATING'),
        'RESOLVED' => __('RESOLVED'),
        'CLOSED' => __('CLOSED'),
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ __('Shipment Timeline') }} — {{ $shipment->shipment_number ?? ('#' . $shipment->id) }}
                </h1>
                @if ($isOverdue)
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                        {{ __('Delayed +:hoursh', ['hours' => $overdueHours]) }}
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                        {{ __('Within SLA') }}
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1">
                {{ __('Current Status') }}: <strong class="text-slate-800">{{ $statusOptions[$currentStatusUpper] ?? ucwords(str_replace('_', ' ', $currentStatusUpper)) }}</strong>
                &bull; {{ __('SLA Group') }} {{ __(':code (:hoursh)', ['code' => $slaEval['group_code'], 'hours' => $thresholdHours]) }}
                @if ($origin && $destination)
                    &bull; {{ __('Route') }}: <strong class="text-slate-800">{{ $origin->name }} &rarr; {{ $destination->name }}</strong>
                @endif
            </p>
        </div>

        <a href="{{ route('shipments.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 shrink-0">
            &larr; {{ __('Back to List') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Maritime Route Map') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('Drag left/right/up/down or use scroll & control buttons to zoom') }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> {{ __('Origin') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-600"></span> {{ __('Checkpoint') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span> {{ __('Destination') }}
                        </span>
                    </div>
                </div>

                @if (count($routePoints) > 0)
                    <div class="relative">
                        <div id="maritime-route-map" class="border border-slate-200"></div>

                        <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                            <span>{{ __('Tip: Click & hold to drag left/right/up/down, or use the arrow buttons on the right.') }}</span>
                            <div class="inline-flex items-center gap-1">
                                <button type="button" id="pan-left-btn" title="{{ __('Pan Left') }}"
                                        class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&larr; {{ __('Left') }}</button>
                                <button type="button" id="pan-up-btn" title="{{ __('Pan Up') }}"
                                        class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&uarr; {{ __('Up') }}</button>
                                <button type="button" id="pan-down-btn" title="{{ __('Pan Down') }}"
                                        class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&darr; {{ __('Down') }}</button>
                                <button type="button" id="pan-right-btn" title="{{ __('Pan Right') }}"
                                        class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">{{ __('Right') }} &rarr;</button>
                            </div>
                        </div>
                    </div>
                    <div id="map-fallback-note" class="hidden p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600">
                        {{ __('Maritime route coordinates detected (:count points). Connect to the internet to render OpenStreetMap tiles.', ['count' => count($routePoints)]) }}
                    </div>
                @else
                    <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-500">
                        {{ __('Origin, checkpoint, or destination port coordinates are not yet available for this shipment.') }}
                    </div>
                @endif
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Chronological Tracking Events') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('History of status transitions and shipment checkpoints (distinguishing normal events from exceptions)') }}</p>
                    </div>
                </div>

                <div class="space-y-5 border-l-2 border-slate-200 ml-2 pl-5">
                    @forelse ($shipment->trackingEvents as $event)
                        @php
                            $isExceptionEvent = strtolower((string) $event->event_type) === 'exception';
                            $evStatusUpper = strtoupper((string) $event->status);
                        @endphp
                        <div class="relative">
                            <span class="w-3 h-3 rounded-full {{ $isExceptionEvent ? 'bg-rose-600' : 'bg-sky-600' }} absolute -left-[27px] top-1.5 ring-4 ring-white"></span>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span>{{ optional($event->occurred_at)->format('d M Y H:i') ?? '-' }}</span>
                                @if ($event->location_label || $event->location)
                                    <span>&bull; <strong class="font-medium text-slate-700">{{ $event->location_label ?? $event->location?->name }}</strong></span>
                                @endif
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $isExceptionEvent ? 'bg-rose-100 text-rose-700' : 'bg-sky-100 text-sky-700' }}">
                                    {{ $isExceptionEvent ? __('EXCEPTION EVENT') : __('NORMAL EVENT') }}
                                </span>
                            </div>
                            <div class="text-sm font-bold text-slate-900 uppercase mt-0.5">
                                {{ $statusOptions[$evStatusUpper] ?? str_replace('_', ' ', (string) $event->status) }}
                            </div>
                            <p class="text-sm text-slate-600 mt-0.5">{{ $event->description }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No tracking events recorded yet.') }}</p>
                    @endforelse
                </div>
            </div>

            @if ($shipment->exceptions->isNotEmpty())
                <div class="bg-white p-6 rounded-xl border border-rose-200 shadow-sm">
                    <h2 class="text-base font-semibold text-rose-800 mb-3">{{ __('SLA & Shipment Exceptions') }}</h2>
                    <div class="space-y-3">
                        @foreach ($shipment->exceptions as $exc)
                            @php
                                $exTypeUpper = strtoupper((string) $exc->exception_type);
                                $exSeverityUpper = strtoupper((string) $exc->severity);
                                $exStatusUpper = strtoupper((string) $exc->status);
                            @endphp
                            <div class="p-3 rounded-lg bg-rose-50/70 border border-rose-100 text-sm">
                                <div class="flex items-center justify-between font-semibold text-rose-900">
                                    <span>
                                        {{ $exceptionTypeLabels[$exTypeUpper] ?? $exTypeUpper }}
                                        ({{ $severityLabels[$exSeverityUpper] ?? $exSeverityUpper }})
                                    </span>
                                    <span class="text-xs uppercase">{{ $exceptionStatusLabels[$exStatusUpper] ?? $exStatusUpper }}</span>
                                </div>
                                <p class="text-rose-800 mt-1">{{ __((string) $exc->notes) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @if (in_array(auth()->user()->role, ['admin', 'operator'], true))
                <form method="POST" action="{{ route('shipments.events.store', $shipment) }}"
                      class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                    @csrf
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">{{ __('Record Tracking Event') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('Update status, checkpoint location, and automatic SLA evaluation') }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('New Status') }}</label>
                        <select name="status" required class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach ($statusOptions as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $currentStatusUpper) === $val)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Checkpoint Location') }}</label>
                        <select name="location_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">{{ __('-- Select Location --') }}</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}" @selected((string) old('location_id') === (string) $loc->id)>
                                    {{ $loc->name }} ({{ $loc->country }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Event Time (Optional)') }}</label>
                        <input type="datetime-local" name="occurred_at" value="{{ old('occurred_at') }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Delay Reason / Additional Notes (Optional)') }}</label>
                        <input type="text" name="delay_reason" value="{{ old('delay_reason') }}"
                               placeholder="{{ __('Example: Weather delay / Customs hold') }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Event Description') }}</label>
                        <textarea name="description" rows="3" required
                                  placeholder="{{ __('Example: Vessel departed Port of Singapore toward Rotterdam.') }}"
                                  class="w-full rounded-lg border-slate-300 text-sm">{{ old('description') }}</textarea>
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 cursor-pointer">
                        {{ __('Save Event') }}
                    </button>
                </form>
            @endif

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-3 text-sm">
                <h2 class="text-base font-semibold text-slate-900">{{ __('Shipment Summary') }}</h2>
                <p class="text-xs text-slate-500">{{ __('Monitor status updates and checkpoint history in real time.') }}</p>

                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">{{ __('Shipment #') }}</span>
                    <span class="font-medium text-slate-900">{{ $shipment->shipment_number ?? ('SHP-' . $shipment->id) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">{{ __('Carrier') }}</span>
                    <span class="font-medium text-slate-900">{{ $shipment->carrier?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">{{ __('Origin Port') }}</span>
                    <span class="font-medium text-slate-900 text-right">{{ $origin?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">{{ __('Destination Port') }}</span>
                    <span class="font-medium text-slate-900 text-right">{{ $destination?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">{{ __('SLA Group') }}</span>
                    <span class="font-medium text-slate-900">{{ __('Group :code (:hoursh)', ['code' => $slaEval['group_code'], 'hours' => $thresholdHours]) }}</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-500">{{ __('Delay Hours') }}</span>
                    <span class="font-medium text-slate-900">{{ __(':hoursh', ['hours' => $overdueHours]) }}</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-2.5 text-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">{{ __('External Weather Feed') }}</h2>
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600">
                        {{ strtoupper($destinationWeather['source'] ?? 'API') }}
                    </span>
                </div>
                <p class="text-xs text-slate-500">{{ $destinationWeather['location'] ?? '-' }}</p>

                @if (!empty($destinationWeather['available']))
                    <div class="pt-1 space-y-1.5">
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-500">{{ __('Condition') }}</span>
                            <span class="font-medium text-slate-900 text-right">{{ $destinationWeather['summary'] }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-500">{{ __('Temperature') }}</span>
                            <span class="font-medium text-slate-900">{{ $destinationWeather['temperature_c'] }} &deg;C</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">{{ __('Wind Speed') }}</span>
                            <span class="font-medium text-slate-900">{{ $destinationWeather['wind_speed_kmh'] }} km/h</span>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600">
                        {{ $destinationWeather['summary'] ?? __('External weather data is currently unavailable.') }}
                    </div>
                @endif

                <p class="text-[11px] text-slate-400 pt-1">
                    {{ __('Weather information supports operational decisions; it is not an automated delay claim.') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if (count($routePoints) > 0)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const points = @json($routePoints);
        const mapEl = document.getElementById('maritime-route-map');
        const fallbackEl = document.getElementById('map-fallback-note');

        if (!mapEl || !Array.isArray(points) || points.length === 0) return;

        if (typeof window.L === 'undefined') {
            mapEl.classList.add('hidden');
            if (fallbackEl) fallbackEl.classList.remove('hidden');
            return;
        }

        const map = L.map('maritime-route-map', {
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
            EVENT: '#0284c7',
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

        bindPan('pan-left-btn', -panStep, 0);
        bindPan('pan-right-btn', panStep, 0);
        bindPan('pan-up-btn', 0, -panStep);
        bindPan('pan-down-btn', 0, panStep);

        setTimeout(function () {
            map.invalidateSize();
        }, 200);
    });
</script>
@endif
@endpush
