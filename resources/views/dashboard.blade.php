@extends('layouts.app')

@section('title', 'Dasbor')
@section('page-title', 'Dasbor')

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
    $mapCoords = [];
    if (!empty($trackedShipment)) {
        $origin = $trackedShipment->originLocation;
        $destination = $trackedShipment->destinationLocation;

        if ($origin && $origin->latitude !== null && $origin->longitude !== null) {
            $mapCoords[] = [
                'label' => 'Pelabuhan Asal: ' . $origin->name . ($origin->city ? ' (' . $origin->city . ')' : ''),
                'role' => 'ASAL',
                'lat' => (float) $origin->latitude,
                'lng' => (float) $origin->longitude,
            ];
        }

        foreach ($trackedShipment->trackingEvents->reverse() as $ev) {
            $evLat = $ev->latitude ?? $ev->location?->latitude;
            $evLng = $ev->longitude ?? $ev->location?->longitude;
            if ($evLat !== null && $evLng !== null) {
                $mapCoords[] = [
                    'label' => ($ev->location_label ?? $ev->location_name ?? $ev->location?->name ?? 'Titik Pantau') . ' — ' . str_replace('_', ' ', (string) ($ev->event_type ?? $ev->status ?? 'Pelacakan')),
                    'role' => 'TITIK PANTAU',
                    'lat' => (float) $evLat,
                    'lng' => (float) $evLng,
                ];
            }
        }

        if ($destination && $destination->latitude !== null && $destination->longitude !== null) {
            $mapCoords[] = [
                'label' => 'Pelabuhan Tujuan: ' . $destination->name . ($destination->city ? ' (' . $destination->city . ')' : ''),
                'role' => 'TUJUAN',
                'lat' => (float) $destination->latitude,
                'lng' => (float) $destination->longitude,
            ];
        }
    }

    $exceptionStatusLabels = [
        'open' => 'TERBUKA',
        'investigating' => 'INVESTIGASI',
        'resolved' => 'SELESAI',
        'closed' => 'DITUTUP',
    ];

    $exceptionTypeLabels = [
        'WEATHER_DELAY' => 'Keterlambatan Cuaca',
        'CUSTOMS_HOLD' => 'Penahanan Kepabeanan',
        'DOCUMENTATION_ISSUE' => 'Kendala Dokumen',
        'PORT_CONGESTION' => 'Kepadatan Pelabuhan',
        'VESSEL_DELAY' => 'Keterlambatan Kapal',
    ];

    $exceptionNoteTranslations = [
        'Heavy swell conditions in North Pacific corridor.' => 'Gelombang tinggi di jalur Pasifik Utara.',
        'Phytosanitary certificate verification requested at transshipment port.' => 'Verifikasi sertifikat fitosanitari diminta di pelabuhan transit.',
        'Bill of Lading consignee tax ID corrected prior to arrival.' => 'NPWP penerima pada Bill of Lading telah diperbaiki sebelum kedatangan.',
    ];

    $shipmentStatusLabels = [
        'CREATED' => 'DIBUAT',
        'BOOKED' => 'TERJADWAL',
        'IN_TRANSIT' => 'DALAM PERJALANAN',
        'DELIVERED' => 'TERKIRIM',
        'DELAYED' => 'TERLAMBAT',
        'CANCELLED' => 'DIBATALKAN',
    ];
@endphp

<div class="space-y-6">
    {{-- Kolom Masukkan Nomor Resi untuk Customer --}}
    @if (auth()->user()->role === 'customer')
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Lacak Paket / Cek Nomor Resi</h2>
                    <p class="text-xs text-slate-500">Masukkan nomor resi pengiriman Anda untuk melihat posisi paket terkini, peta rute, dan riwayat pelacakan.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row gap-2.5">
                <input
                    type="text"
                    name="resi"
                    value="{{ $resi ?? '' }}"
                    placeholder="Masukkan nomor resi (contoh: CT-2026-A001)"
                    class="flex-1 rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:border-slate-900"
                >
                <button
                    type="submit"
                    class="rounded-lg bg-slate-950 px-5 py-2 text-sm font-medium text-white hover:bg-slate-800 cursor-pointer"
                >
                    Lacak Paket
                </button>
                @if (!empty($resi))
                    <a
                        href="{{ route('dashboard') }}"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 text-center"
                    >
                        Reset
                    </a>
                @endif
            </form>

            {{-- Hasil Pelacakan Muncul Setelah Nomor Resi Dicari --}}
            @if (!empty($searchedResi) && ($resi ?? '') !== '')
                @if (!$trackedShipment)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-800">
                        Nomor resi <span class="font-mono font-semibold">{{ $resi }}</span> tidak ditemukan pada pengiriman akun Anda.
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
                                <span class="text-xs text-slate-500">Nomor Resi:</span>
                                <span class="font-mono text-sm font-bold text-slate-900">{{ $trackedShipment->shipment_number }}</span>
                                <span class="mx-2 text-slate-300">|</span>
                                <span class="text-xs text-slate-600">
                                    Posisi saat ini: <strong class="text-slate-900">{{ $posText }}</strong>
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
                                        <span>Geser kiri/kanan/atas/bawah, gulir untuk perbesar/perkecil, atau pakai tombol geser.</span>
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" id="resi-pan-left-btn" title="Geser Kiri"
                                                    class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&larr; Kiri</button>
                                            <button type="button" id="resi-pan-up-btn" title="Geser Atas"
                                                    class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&uarr; Atas</button>
                                            <button type="button" id="resi-pan-down-btn" title="Geser Bawah"
                                                    class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">&darr; Bawah</button>
                                            <button type="button" id="resi-pan-right-btn" title="Geser Kanan"
                                                    class="px-2.5 py-1 rounded border border-slate-300 bg-white hover:bg-slate-50 font-semibold">Kanan &rarr;</button>
                                        </div>
                                    </div>
                                    <div id="resi-map-fallback-note" class="hidden mt-2 p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600">
                                        Koordinat rute terdeteksi ({{ count($mapCoords) }} titik). Hubungkan internet untuk memuat peta OpenStreetMap.
                                    </div>
                                @else
                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500">
                                        Koordinat lokasi belum tersedia untuk pengiriman ini.
                                    </div>
                                @endif
                            </div>

                            <div class="rounded-xl border border-slate-200 p-4 space-y-3 max-h-80 overflow-y-auto">
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Riwayat Perjalanan</h3>
                                @forelse ($trackedShipment->trackingEvents as $ev)
                                    <div class="border-l-2 border-slate-400 pl-3 text-xs">
                                        <div class="font-semibold text-slate-900">{{ $ev->event_type ?? $ev->status }}</div>
                                        <div class="text-slate-600">{{ $ev->location_label ?? ($ev->location_name ?? ($ev->location?->name ?? ($ev->location ?? '-'))) }}</div>
                                        <div class="text-[11px] text-slate-400">
                                            {{ !empty($ev->occurred_at) ? \Carbon\Carbon::parse($ev->occurred_at)->format('d M Y H:i') : '' }}
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500">Belum ada riwayat pelacakan.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif

    {{-- Ringkasan KPI Utama --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Pengiriman</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalShipments }}</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ $inTransitCount }} dalam perjalanan • {{ $deliveredCount }} terkirim
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Melewati SLA / Terlambat</p>
            <p class="mt-2 text-2xl font-bold {{ $delayedCount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ $delayedCount }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                Tingkat tepat waktu: <span class="font-semibold text-slate-800">{{ $onTimeRate }}%</span>
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Kendala Terbuka</p>
            <p class="mt-2 text-2xl font-bold {{ $openExceptionsCount > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                {{ $openExceptionsCount }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                Total {{ $totalExceptionsCount }} kendala tercatat
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Cakupan Jaringan</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $activeCarriersCount }} / {{ $totalLocationsCount }}</p>
            <p class="mt-1 text-xs text-slate-500">
                Kurir aktif / Lokasi pelabuhan
            </p>
        </div>
    </div>

    {{-- Performa SLA Grup A / B / C (Khusus Admin & Operator) --}}
    @if (in_array(auth()->user()->role, ['admin', 'operator'], true))
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Performa SLA per Grup</h2>
                    <p class="text-xs text-slate-500">Ambang batas keterlambatan: Grup A (12j), Grup B (18j), Grup C (24j).</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 shrink-0">
                    Peran: {{ strtoupper(auth()->user()->role) }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($slaGroups as $group)
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-bold text-slate-900">Grup {{ $group['code'] }}</span>
                                <span class="ml-2 rounded-md bg-white px-2 py-0.5 text-xs font-mono text-slate-600 border border-slate-200">
                                    Batas {{ $group['threshold_hours'] }}j
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
                            <span>Total: <strong>{{ $group['total'] }}</strong></span>
                            <span class="text-emerald-700">Tepat Waktu: <strong>{{ $group['on_time'] }}</strong></span>
                            <span class="text-rose-700">Terlambat: <strong>{{ $group['delayed'] }}</strong></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Daftar Pengiriman Terbaru & Kendala Terbaru --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Pengiriman Terbaru</h2>
                    <p class="text-xs text-slate-500">Pemantauan rute pelayaran, status SLA, dan estimasi tiba.</p>
                </div>
                <a href="{{ route('shipments.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-950 underline whitespace-nowrap shrink-0">
                    Lihat semua &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 bg-slate-50">
                            <th class="px-3 py-2.5">Pengiriman</th>
                            <th class="px-3 py-2.5">Rute Pelabuhan</th>
                            <th class="px-3 py-2.5">Grup</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-3 py-2.5">SLA</th>
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
                                        {{ $shipment->originLocation->city ?? '' }} ke {{ $shipment->destinationLocation->city ?? '' }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 font-mono text-xs font-semibold text-slate-800 whitespace-nowrap">
                                    {{ $shipment->group_code }} ({{ $shipment->delay_threshold_hours }}j)
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    @if ($shipment->is_delayed)
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 border border-rose-200">
                                            Terlambat +{{ $shipment->delay_hours }}j
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                                            Tepat Waktu
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">Belum ada data pengiriman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Kendala Terbaru</h2>
                    <p class="text-xs text-slate-500">Kendala operasional &amp; anomali pengiriman.</p>
                </div>
                @if (in_array(auth()->user()->role, ['admin', 'operator'], true))
                    <a href="{{ route('exceptions.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-950 underline whitespace-nowrap shrink-0">
                        Kelola &rarr;
                    </a>
                @endif
            </div>

            <div class="space-y-3">
                @forelse ($recentExceptions as $exception)
                    @php
                        $rawExStatus = strtolower((string) $exception->status);
                        $exStatusText = $exceptionStatusLabels[$rawExStatus] ?? strtoupper((string) $exception->status);
                        $rawExType = (string) ($exception->exception_type ?? $exception->category ?? '');
                        $exTypeText = $exceptionTypeLabels[$rawExType] ?? ($rawExType !== '' ? str_replace('_', ' ', $rawExType) : 'Kendala Operasional');
                        $rawNotes = (string) ($exception->notes ?? $exception->description ?? '');
                        $notesText = $exceptionNoteTranslations[$rawNotes] ?? $rawNotes;
                    @endphp
                    <div class="rounded-xl border border-slate-200 p-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-bold text-slate-900">
                                {{ $exception->shipment->shipment_number ?? ('Pengiriman #' . $exception->shipment_id) }}
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
                    <p class="text-xs text-slate-500 py-4 text-center">Tidak ada kendala terbaru.</p>
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
            ASAL: '#059669',
            'TITIK PANTAU': '#0284c7',
            TUJUAN: '#e11d48',
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