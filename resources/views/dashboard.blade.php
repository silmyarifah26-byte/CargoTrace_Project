@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Ringkasan KPI Utama --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Shipments</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalShipments }}</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ $inTransitCount }} in-transit • {{ $deliveredCount }} delivered
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">SLA Breached / Delayed</p>
            <p class="mt-2 text-2xl font-bold {{ $delayedCount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ $delayedCount }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                On-time rate: <span class="font-semibold text-slate-800">{{ $onTimeRate }}%</span>
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Open Exceptions</p>
            <p class="mt-2 text-2xl font-bold {{ $openExceptionsCount > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                {{ $openExceptionsCount }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                Total {{ $totalExceptionsCount }} exception tercatat
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Network Coverage</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $activeCarriersCount }} / {{ $totalLocationsCount }}</p>
            <p class="mt-1 text-xs text-slate-500">
                Active carriers / Seaport locations
            </p>
        </div>
    </div>

    {{-- Performa SLA Group A / B / C --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">SLA Performance by Group</h2>
                <p class="text-xs text-slate-500">Ambang batas keterlambatan: Group A (12h), Group B (18h), Group C (24h).</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                Role: {{ strtoupper(auth()->user()->role) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach ($slaGroups as $group)
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-bold text-slate-900">Group {{ $group['code'] }}</span>
                            <span class="ml-2 rounded-md bg-white px-2 py-0.5 text-xs font-mono text-slate-600 border border-slate-200">
                                Threshold {{ $group['threshold_hours'] }}h
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
                        <span class="text-emerald-700">On-Time: <strong>{{ $group['on_time'] }}</strong></span>
                        <span class="text-rose-700">Delayed: <strong>{{ $group['delayed'] }}</strong></span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Daftar Shipment Terbaru & Exception Terbuka --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Recent Shipments</h2>
                    <p class="text-xs text-slate-500">Pemantauan rute pelayaran, status SLA, dan estimasi tiba.</p>
                </div>
                <a href="{{ route('shipments.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-950 underline">
                    Lihat semua →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 bg-slate-50">
                            <th class="px-3 py-2.5">Shipment</th>
                            <th class="px-3 py-2.5">Rute Pelabuhan</th>
                            <th class="px-3 py-2.5">Group</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-3 py-2.5">SLA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentShipments as $shipment)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-3 py-2.5">
                                    <a href="{{ route('shipments.show', $shipment) }}" class="font-mono font-semibold text-slate-900 hover:underline">
                                        {{ $shipment->shipment_number }}
                                    </a>
                                    <div class="text-xs text-slate-500">{{ $shipment->carrier->code ?? '-' }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-xs text-slate-700">
                                    <span class="font-semibold">{{ $shipment->originLocation->code ?? '-' }}</span>
                                    →
                                    <span class="font-semibold">{{ $shipment->destinationLocation->code ?? '-' }}</span>
                                    <div class="text-slate-400">
                                        {{ $shipment->originLocation->city ?? '' }} to {{ $shipment->destinationLocation->city ?? '' }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 font-mono text-xs font-semibold text-slate-800">
                                    {{ $shipment->group_code }} ({{ $shipment->delay_threshold_hours }}h)
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                        {{ str_replace('_', ' ', $shipment->current_status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    @if ($shipment->is_delayed)
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 border border-rose-200">
                                            Delayed +{{ $shipment->delay_hours }}h
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                                            On Time
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">Belum ada data shipment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Recent Exceptions</h2>
                    <p class="text-xs text-slate-500">Kendala operasional & anomali pengiriman.</p>
                </div>
                @if (in_array(auth()->user()->role, ['admin', 'operator'], true))
                    <a href="{{ route('exceptions.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-950 underline">
                        Kelola →
                    </a>
                @endif
            </div>

            <div class="space-y-3">
                @forelse ($recentExceptions as $exception)
                    <div class="rounded-xl border border-slate-200 p-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-bold text-slate-900">
                                {{ $exception->shipment->shipment_number ?? ('Shipment #' . $exception->shipment_id) }}
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $exception->status === 'open' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                {{ strtoupper($exception->status) }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs font-medium text-slate-700">
                            {{ $exception->exception_type ?? $exception->category ?? 'Operational Exception' }}
                        </p>
                        @if (!empty($exception->notes) || !empty($exception->description))
                            <p class="mt-0.5 text-xs text-slate-500 line-clamp-2">
                                {{ $exception->notes ?? $exception->description }}
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-4 text-center">Tidak ada exception terbaru.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection