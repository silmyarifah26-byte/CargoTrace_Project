@extends('layouts.app')

@section('title', 'Shipment Timeline — ' . ($shipment->shipment_number ?? ('#' . $shipment->id)))

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
            'label' => 'Origin Port: ' . $origin->name . ($origin->city ? ' (' . $origin->city . ')' : ''),
            'role' => 'ORIGIN',
            'lat' => (float) $origin->latitude,
            'lng' => (float) $origin->longitude,
        ];
    }

    foreach ($shipment->trackingEvents as $ev) {
        $evLat = $ev->latitude ?? $ev->location?->latitude;
        $evLng = $ev->longitude ?? $ev->location?->longitude;
        if ($evLat !== null && $evLng !== null) {
            $routePoints[] = [
                'label' => ($ev->location_label ?? $ev->location?->name ?? 'Checkpoint') . ' — ' . str_replace('_', ' ', (string) $ev->status),
                'role' => 'EVENT',
                'lat' => (float) $evLat,
                'lng' => (float) $evLng,
            ];
        }
    }

    if ($destination && $destination->latitude !== null && $destination->longitude !== null) {
        $routePoints[] = [
            'label' => 'Destination Port: ' . $destination->name . ($destination->city ? ' (' . $destination->city . ')' : ''),
            'role' => 'DESTINATION',
            'lat' => (float) $destination->latitude,
            'lng' => (float) $destination->longitude,
        ];
    }

    $currentStatusUpper = strtoupper((string) ($shipment->current_status ?? 'CREATED'));
    $statusOptions = [
        'CREATED' => 'Created',
        'BOOKED' => 'Booked',
        'PICKED_UP' => 'Picked Up',
        'IN_TRANSIT' => 'In Transit',
        'DELIVERED' => 'Delivered',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">
                    Shipment Timeline — {{ $shipment->shipment_number ?? ('#' . $shipment->id) }}
                </h1>
                @if ($isOverdue)
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                        Delayed (+{{ $overdueHours }}h)
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                        Within SLA
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Current Status: <strong class="text-slate-800">{{ ucwords(str_replace('_', ' ', $currentStatusUpper)) }}</strong>
                &bull; SLA Group {{ $slaEval['group_code'] }} ({{ $thresholdHours }}h)
                @if ($origin && $destination)
                    &bull; Route: <strong class="text-slate-800">{{ $origin->name }} &rarr; {{ $destination->name }}</strong>
                @endif
            </p>
        </div>

        <a href="{{ route('shipments.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
            &larr; Kembali ke Daftar
        </a>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Maritime Route Map</h2>
                        <p class="text-xs text-slate-500">Geser kiri/kanan/atas/bawah atau gunakan scroll &amp; tombol kontrol untuk zoom</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Origin
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-600"></span> Checkpoint
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span> Destination
                        </span>
                    </div>
                </div>

                @if (count($routePoints) > 0)
                    <div class="relative">
                        <div id="maritime-route-map" class="border border-slate-200"></div>

                        <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                            <span>Tips: Klik &amp; tahan lalu geser ke kiri/kanan/atas/bawah, atau pakai tombol panah di kanan.</span>
                            <div class="inline-flex items-center gap-1">
                                <button type="button" id="pan-left-btn" title="Geser Kiri"
                                        class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&larr; Kiri</button>
                                <button type="button" id="pan-up-btn" title="Geser Atas"
                                        class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&uarr; Atas</button>
                                <button type="button" id="pan-down-btn" title="Geser Bawah"
                                        class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&darr; Bawah</button>
                                <button type="button" id="pan-right-btn" title="Geser Kanan"
                                        class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">Kanan &rarr;</button>
                            </div>
                        </div>
                    </div>
                    <div id="map-fallback-note" class="hidden p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600">
                        Koordinat jalur pelayaran terdeteksi ({{ count($routePoints) }} titik). Hubungkan internet untuk merender peta tile OpenStreetMap.
                    </div>
                @else
                    <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-500">
                        Koordinat pelabuhan asal, checkpoint, atau tujuan belum tersedia untuk shipment ini.
                    </div>
                @endif
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Chronological Tracking Events</h2>
                        <p class="text-xs text-slate-500">Riwayat perpindahan status dan checkpoint pengiriman (membedakan event normal dan exception)</p>
                    </div>
                </div>

                <div class="space-y-5 border-l-2 border-slate-200 ml-2 pl-5">
                    @forelse ($shipment->trackingEvents as $event)
                        @php
                            $isExceptionEvent = ($event->event_type === 'exception');
                        @endphp
                        <div class="relative">
                            <span class="w-3 h-3 rounded-full {{ $isExceptionEvent ? 'bg-rose-600' : 'bg-sky-600' }} absolute -left-[27px] top-1.5 ring-4 ring-white"></span>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span>{{ optional($event->occurred_at)->format('d M Y H:i') ?? '-' }}</span>
                                @if ($event->location_label || $event->location)
                                    <span>&bull; <strong class="font-medium text-slate-700">{{ $event->location_label ?? $event->location?->name }}</strong></span>
                                @endif
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $isExceptionEvent ? 'bg-rose-100 text-rose-700' : 'bg-sky-100 text-sky-700' }}">
                                    {{ $isExceptionEvent ? 'EXCEPTION EVENT' : 'NORMAL EVENT' }}
                                </span>
                            </div>
                            <div class="text-sm font-bold text-slate-900 uppercase mt-0.5">
                                {{ str_replace('_', ' ', (string) $event->status) }}
                            </div>
                            <p class="text-sm text-slate-600 mt-0.5">{{ $event->description }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada tracking event tercatat.</p>
                    @endforelse
                </div>
            </div>

            @if ($shipment->exceptions->isNotEmpty())
                <div class="bg-white p-6 rounded-xl border border-rose-200 shadow-sm">
                    <h2 class="text-base font-semibold text-rose-800 mb-3">SLA &amp; Shipment Exceptions</h2>
                    <div class="space-y-3">
                        @foreach ($shipment->exceptions as $exc)
                            <div class="p-3 rounded-lg bg-rose-50/70 border border-rose-100 text-sm">
                                <div class="flex items-center justify-between font-semibold text-rose-900">
                                    <span>{{ strtoupper((string) $exc->exception_type) }} ({{ strtoupper((string) $exc->severity) }})</span>
                                    <span class="text-xs uppercase">{{ $exc->status }}</span>
                                </div>
                                <p class="text-rose-800 mt-1">{{ $exc->notes }}</p>
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
                        <h2 class="text-base font-semibold text-slate-900">Record Tracking Event</h2>
                        <p class="text-xs text-slate-500">Perbarui status, lokasi checkpoint, dan evaluasi SLA otomatis</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Status Baru</label>
                        <select name="status" required class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach ($statusOptions as $val => $label)
                                <option value="{{ $val }}" @selected(old('status', $currentStatusUpper) === $val)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Lokasi Checkpoint</label>
                        <select name="location_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}" @selected((string) old('location_id') === (string) $loc->id)>
                                    {{ $loc->name }} ({{ $loc->country }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Waktu Kejadian (Opsional)</label>
                        <input type="datetime-local" name="occurred_at" value="{{ old('occurred_at') }}"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Alasan Delay / Keterangan Tambahan (Opsional)</label>
                        <input type="text" name="delay_reason" value="{{ old('delay_reason') }}"
                               placeholder="Contoh: Weather delay / Customs hold"
                               class="w-full rounded-lg border-slate-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Deskripsi Event</label>
                        <textarea name="description" rows="3" required
                                  placeholder="Contoh: Vessel departed Port of Singapore toward Rotterdam."
                                  class="w-full rounded-lg border-slate-300 text-sm">{{ old('description') }}</textarea>
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700">
                        Simpan Event
                    </button>
                </form>
            @endif

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-3 text-sm">
                <h2 class="text-base font-semibold text-slate-900">Ringkasan Pengiriman</h2>
                <p class="text-xs text-slate-500">Pantau pembaruan status dan riwayat checkpoint secara real-time.</p>

                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Shipment #</span>
                    <span class="font-medium text-slate-900">{{ $shipment->shipment_number ?? ('SHP-' . $shipment->id) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Carrier</span>
                    <span class="font-medium text-slate-900">{{ $shipment->carrier?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Origin Port</span>
                    <span class="font-medium text-slate-900 text-right">{{ $origin?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">Destination Port</span>
                    <span class="font-medium text-slate-900 text-right">{{ $destination?->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100">
                    <span class="text-slate-500">SLA Group</span>
                    <span class="font-medium text-slate-900">Group {{ $slaEval['group_code'] }} ({{ $thresholdHours }}h)</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-500">Delay Hours</span>
                    <span class="font-medium text-slate-900">{{ $overdueHours }}h</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-2.5 text-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">External Weather Feed</h2>
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600">
                        {{ strtoupper($destinationWeather['source'] ?? 'API') }}
                    </span>
                </div>
                <p class="text-xs text-slate-500">{{ $destinationWeather['location'] ?? '-' }}</p>

                @if (!empty($destinationWeather['available']))
                    <div class="pt-1 space-y-1.5">
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-500">Condition</span>
                            <span class="font-medium text-slate-900 text-right">{{ $destinationWeather['summary'] }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100">
                            <span class="text-slate-500">Temperature</span>
                            <span class="font-medium text-slate-900">{{ $destinationWeather['temperature_c'] }} &deg;C</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-slate-500">Wind Speed</span>
                            <span class="font-medium text-slate-900">{{ $destinationWeather['wind_speed_kmh'] }} km/h</span>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600">
                        {{ $destinationWeather['summary'] ?? 'Data cuaca eksternal belum tersedia.' }}
                    </div>
                @endif

                <p class="text-[11px] text-slate-400 pt-1">
                    Informasi cuaca pendukung operasional; bukan klaim prediksi keterlambatan otomatis.
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
            }).addTo(map).bindPopup('<strong>' + pt.role + '</strong><br>' + pt.label);
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