<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pengiriman — CargoTrace Customer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-screen flex flex-col md:flex-row">
        {{-- Sidebar --}}
        <aside class="w-full md:w-64 bg-slate-900 text-white flex flex-col justify-between p-5">
            <div>
                <div class="mb-8">
                    <div class="text-xl font-bold tracking-tight">CargoTrace</div>
                    <div class="text-xs uppercase tracking-wider text-sky-400 mt-1">Shipment Visibility</div>
                </div>

                <nav class="space-y-2 text-sm">
                    <a href="{{ route('customer.tracking') }}"
                       class="block px-4 py-2.5 rounded-lg bg-sky-600 text-white font-medium">
                        Lacak Paket / Cek Resi
                    </a>
                    @if (Route::has('notifications.index'))
                        <a href="{{ route('notifications.index') }}"
                           class="block px-4 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800">
                            Notifikasi
                        </a>
                    @endif
                </nav>
            </div>

            <div class="pt-6 border-t border-slate-800 text-xs text-slate-400 space-y-3">
                <div>
                    <div>Login sebagai: <span class="text-white font-semibold">{{ auth()->user()->name ?? 'Customer' }}</span></div>
                    <div class="mt-1">Role: <span class="text-sky-400 font-semibold">CUSTOMER</span></div>
                </div>
                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-3 py-2 rounded-lg bg-slate-800 hover:bg-rose-900/60 text-slate-200 transition cursor-pointer">
                            Keluar / Logout
                        </button>
                    </form>
                @endif
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 p-6 md:p-8 space-y-6">
            {{-- Kolom Cek Resi / Pencarian Nomor Shipment --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Pelacakan Pengiriman Saya</h1>
                        <p class="text-sm text-slate-500">
                            Masukkan nomor resi / shipment number untuk memantau status, ETA, peta rute, dan riwayat perjalanan.
                        </p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200">
                        Khusus Resi Milik Akun Anda
                    </span>
                </div>

                <form method="GET" action="{{ route('customer.tracking') }}" class="flex flex-col sm:flex-row gap-3">
                    <input
                        type="text"
                        name="resi"
                        value="{{ $resi }}"
                        placeholder="Masukkan Nomor Resi / Shipment Number (contoh: CT-2026-0001)"
                        class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                    >
                    <button
                        type="submit"
                        class="px-6 py-3 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-semibold text-sm transition cursor-pointer"
                    >
                        Lacak Paket
                    </button>
                    @if ($resi !== '')
                        <a
                            href="{{ route('customer.tracking') }}"
                            class="px-4 py-3 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-medium text-center"
                        >
                            Reset
                        </a>
                    @endif
                </form>

                {{-- Daftar Cepat Resi Milik Customer --}}
                @if ($shipments->isNotEmpty())
                    <div class="mt-5 pt-4 border-t border-slate-100">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2.5">
                            Daftar Resi Anda ({{ $shipments->count() }})
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($shipments as $item)
                                @php
                                    $isSelected = $activeShipment && $activeShipment->id === $item->id;
                                @endphp
                                <a
                                    href="{{ route('customer.tracking', ['shipment_id' => $item->id]) }}"
                                    class="px-3.5 py-2 rounded-xl text-xs border transition {{ $isSelected ? 'bg-sky-600 text-white border-sky-600 font-semibold' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}"
                                >
                                    {{ $item->shipment_number }}
                                    <span class="opacity-80">• {{ strtoupper((string) $item->current_status) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if (!$activeShipment)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
                    <h2 class="text-lg font-semibold text-slate-800">Pengiriman tidak ditemukan</h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Pastikan nomor resi yang dimasukkan cocok dengan pengiriman yang terdaftar pada akun Anda.
                    </p>
                </div>
            @else
                @php
                    $statusUpper = strtoupper((string) ($activeShipment->current_status ?? 'CREATED'));
                    $isDelayed = (bool) ($activeShipment->is_delayed ?? false);

                    if (!$isDelayed && !empty($activeShipment->estimated_arrival) && !empty($activeShipment->expected_arrival)) {
                        $isDelayed = \Carbon\Carbon::parse($activeShipment->estimated_arrival)
                            ->gt(\Carbon\Carbon::parse($activeShipment->expected_arrival));
                    }

                    $latestEvent = $activeShipment->trackingEvents->first();
                    $currentLocationText = $latestEvent->location_name
                        ?? ($latestEvent->location ?? ($activeShipment->originLocation->name ?? ($activeShipment->origin ?? '-')));
                @endphp

                {{-- Ringkasan Status & Lifecycle Stepper --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-400">Nomor Resi / Shipment Number</div>
                            <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ $activeShipment->shipment_number }}</div>
                            <div class="text-sm text-slate-600 mt-1">
                                Rute:
                                <span class="font-semibold">{{ $activeShipment->originLocation->name ?? ($activeShipment->origin ?? '-') }}</span>
                                →
                                <span class="font-semibold">{{ $activeShipment->destinationLocation->name ?? ($activeShipment->destination ?? '-') }}</span>
                                @if (!empty($activeShipment->carrier->name))
                                    <span class="text-slate-400">• Carrier: {{ $activeShipment->carrier->name }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">
                                Status: {{ $statusUpper }}
                            </span>
                            @if ($isDelayed)
                                <span class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                    Delayed
                                </span>
                            @else
                                <span class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                    On Track
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Stepper 5 Tahap Lifecycle --}}
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                        @php $idx = 0; @endphp
                        @foreach ($stages as $stageKey => $stageLabel)
                            @php
                                $isDone = $idx <= $currentStageIndex;
                                $isCurrent = $idx === $currentStageIndex;
                            @endphp
                            <div class="rounded-xl p-3 border {{ $isCurrent ? 'bg-sky-100/70 border-sky-400 ring-2 ring-sky-200' : ($isDone ? 'bg-sky-50 border-sky-200' : 'bg-slate-50 border-slate-200 opacity-60') }}">
                                <div class="text-[11px] font-bold uppercase {{ $isDone ? 'text-sky-700' : 'text-slate-400' }}">
                                    Tahap {{ $idx + 1 }}
                                </div>
                                <div class="text-sm font-semibold text-slate-900 mt-0.5">{{ $stageKey }}</div>
                                <div class="text-xs text-slate-500">{{ $stageLabel }}</div>
                            </div>
                            @php $idx++; @endphp
                        @endforeach
                    </div>

                    {{-- Detail ETA, Lokasi Terkini & Kargo --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm bg-slate-50 rounded-xl p-4">
                        <div>
                            <div class="text-xs text-slate-400">Lokasi Terkini</div>
                            <div class="font-semibold text-slate-800 mt-0.5">{{ $currentLocationText }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400">Expected Arrival</div>
                            <div class="font-semibold text-slate-800 mt-0.5">
                                {{ !empty($activeShipment->expected_arrival) ? \Carbon\Carbon::parse($activeShipment->expected_arrival)->format('d M Y H:i') : '-' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400">Estimated Arrival (ETA)</div>
                            <div class="font-semibold text-slate-800 mt-0.5">
                                {{ !empty($activeShipment->estimated_arrival) ? \Carbon\Carbon::parse($activeShipment->estimated_arrival)->format('d M Y H:i') : '-' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400">Jenis Kargo / Berat</div>
                            <div class="font-semibold text-slate-800 mt-0.5">
                                {{ $activeShipment->cargo_type ?? 'General Cargo' }}
                                @if (!empty($activeShipment->weight))
                                    ({{ $activeShipment->weight }} kg)
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Exception Aktif / Terkait Jika Ada --}}
                    @if ($activeShipment->exceptions->isNotEmpty())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-2">
                            <div class="text-xs font-bold uppercase tracking-wider text-amber-800">
                                Catatan / Exception Pengiriman ({{ $activeShipment->exceptions->count() }})
                            </div>
                            @foreach ($activeShipment->exceptions as $exc)
                                <div class="text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 border-t border-amber-200/70 pt-2 first:border-t-0 first:pt-0">
                                    <div>
                                        <span class="font-bold">{{ strtoupper((string) ($exc->exception_type ?? $exc->type ?? 'EXCEPTION')) }}</span>
                                        @if (!empty($exc->severity))
                                            <span>• Severity: <strong>{{ strtoupper((string) $exc->severity) }}</strong></span>
                                        @endif
                                        @if (!empty($exc->reason))
                                            <span>— {{ $exc->reason }}</span>
                                        @elseif (!empty($exc->description))
                                            <span>— {{ $exc->description }}</span>
                                        @endif
                                    </div>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white text-amber-800 border border-amber-300">
                                        {{ strtoupper((string) ($exc->status ?? 'OPEN')) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Peta Titik Rute & Timeline Kronologis --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Leaflet.js + OpenStreetMap --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-bold text-slate-900 mb-1">Peta Rute & Titik Lokasi</h2>
                        <p class="text-xs text-slate-500 mb-4">
                            Marker origin, titik riwayat perjalanan (tracking points), dan destination.
                        </p>
                        <div id="shipment-map" class="w-full h-80 rounded-xl border border-slate-200 z-0"></div>
                    </div>

                    {{-- Timeline Kronologis Riwayat Pengiriman --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-bold text-slate-900 mb-1">Timeline Riwayat Pengiriman</h2>
                        <p class="text-xs text-slate-500 mb-4">
                            Riwayat kejadian berurutan secara kronologis (terbaru di atas).
                        </p>

                        @if ($activeShipment->trackingEvents->isEmpty())
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-6 text-center text-sm text-slate-500">
                                Belum ada riwayat tracking event untuk pengiriman ini.
                            </div>
                        @else
                            <div class="space-y-4 max-h-80 overflow-y-auto pr-1">
                                @foreach ($activeShipment->trackingEvents as $event)
                                    @php
                                        $labelEvent = strtoupper((string) ($event->event_type ?? $event->status ?? 'EVENT'));
                                        $isExceptionEvent = str_contains($labelEvent, 'EXCEPTION')
                                            || str_contains($labelEvent, 'HOLD')
                                            || str_contains($labelEvent, 'DELAY')
                                            || str_contains($labelEvent, 'DAMAGED')
                                            || str_contains($labelEvent, 'MISROUTED');
                                    @endphp
                                    <div class="border-l-4 pl-4 py-1.5 {{ $isExceptionEvent ? 'border-rose-500 bg-rose-50/60 rounded-r-lg' : 'border-sky-500' }}">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-sm font-bold {{ $isExceptionEvent ? 'text-rose-700' : 'text-slate-900' }}">
                                                {{ $labelEvent }}
                                            </span>
                                            <span class="text-xs text-slate-400 whitespace-nowrap">
                                                @if (!empty($event->occurred_at))
                                                    {{ \Carbon\Carbon::parse($event->occurred_at)->format('d M Y H:i') }}
                                                @elseif (!empty($event->created_at))
                                                    {{ \Carbon\Carbon::parse($event->created_at)->format('d M Y H:i') }}
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>
                                        <div class="text-xs font-medium text-slate-600 mt-0.5">
                                            Lokasi: {{ $event->location_name ?? ($event->location ?? '-') }}
                                        </div>
                                        @if (!empty($event->description))
                                            <p class="text-xs text-slate-500 mt-1">{{ $event->description }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </main>
    </div>

    @if ($activeShipment)
        @php
            $mapPoints = [];

            if (!empty($activeShipment->originLocation->latitude) && !empty($activeShipment->originLocation->longitude)) {
                $mapPoints[] = [
                    'lat'   => (float) $activeShipment->originLocation->latitude,
                    'lng'   => (float) $activeShipment->originLocation->longitude,
                    'label' => 'Origin: ' . ($activeShipment->originLocation->name ?? 'Titik Asal'),
                ];
            }

            foreach ($activeShipment->trackingEvents->reverse() as $ev) {
                if (!empty($ev->latitude) && !empty($ev->longitude)) {
                    $evTime = !empty($ev->occurred_at)
                        ? \Carbon\Carbon::parse($ev->occurred_at)->format('d M Y H:i')
                        : '';
                    $evLoc = $ev->location_name ?? ($ev->location ?? '');
                    $evStatus = $ev->event_type ?? ($ev->status ?? 'Tracking Point');

                    $mapPoints[] = [
                        'lat'   => (float) $ev->latitude,
                        'lng'   => (float) $ev->longitude,
                        'label' => trim($evStatus . ($evLoc ? ' — ' . $evLoc : '') . ($evTime ? ' (' . $evTime . ')' : '')),
                    ];
                }
            }

            if (!empty($activeShipment->destinationLocation->latitude) && !empty($activeShipment->destinationLocation->longitude)) {
                $mapPoints[] = [
                    'lat'   => (float) $activeShipment->destinationLocation->latitude,
                    'lng'   => (float) $activeShipment->destinationLocation->longitude,
                    'label' => 'Destination: ' . ($activeShipment->destinationLocation->name ?? 'Titik Tujuan'),
                ];
            }
        @endphp
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const points = @json($mapPoints);
                const defaultCenter = points.length > 0 ? [points[0].lat, points[0].lng] : [-6.2088, 106.8456];

                const map = L.map('shipment-map').setView(defaultCenter, 5);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                if (points.length > 0) {
                    const latLngs = points.map(p => [p.lat, p.lng]);

                    points.forEach(p => {
                        L.marker([p.lat, p.lng])
                            .addTo(map)
                            .bindPopup(p.label);
                    });

                    if (latLngs.length > 1) {
                        const polyline = L.polyline(latLngs, { color: '#0284c7', weight: 3 }).addTo(map);
                        map.fitBounds(polyline.getBounds(), { padding: [25, 25] });
                    }
                }
            });
        </script>
    @endif
</body>
</html>