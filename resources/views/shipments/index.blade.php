@extends('layouts.app')

@section('title', 'Shipments & SLA Monitoring')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Shipments &amp; SLA Monitoring</h1>
            <p class="text-sm text-slate-500">
                Delay Thresholds — Group A: 12h &bull; Group B: 18h &bull; Group C: 24h
            </p>
        </div>
        <a href="{{ route('notifications.index') }}"
           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">
            Notifications
        </a>
    </div>

    <form method="GET" action="{{ route('shipments.index') }}"
          class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-3">
        <input type="text"
               name="search"
               value="{{ request('search') }}"
               placeholder="Cari nomor shipment (mis. SHP-1)..."
               class="flex-1 rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">

        <select name="status" class="rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
            <option value="">Semua Status</option>
            @foreach (['created', 'booked', 'in_transit', 'out_for_delivery', 'delivered', 'delayed', 'exception'] as $st)
                <option value="{{ $st }}" @selected(request('status') === $st)>
                    {{ ucwords(str_replace('_', ' ', $st)) }}
                </option>
            @endforeach
        </select>

        <button type="submit"
                class="px-5 py-2 rounded-lg bg-sky-600 text-white text-sm font-medium hover:bg-sky-700">
            Filter
        </button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Shipment #</th>
                        <th class="px-5 py-3.5">Carrier</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">SLA Group</th>
                        <th class="px-5 py-3.5">SLA Status</th>
                        <th class="px-5 py-3.5 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($shipments as $shipment)
                        @php
                            $eval = $slaEvaluations[$shipment->id] ?? $slaService->evaluate($shipment);
                            $statusText = ucwords(str_replace('_', ' ', (string) ($shipment->current_status ?? 'pending')));
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4 font-semibold text-slate-900">
                                {{ $shipment->shipment_number ?? ('SHP-' . $shipment->id) }}
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                {{ $shipment->carrier?->name ?? '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                Group {{ $eval['group_code'] }} ({{ $eval['threshold_hours'] }}h)
                            </td>
                            <td class="px-5 py-4">
                                @if ($eval['is_breached'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                        {{ $eval['label'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        {{ $eval['label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('shipments.show', $shipment) }}"
                                   class="inline-flex items-center px-3.5 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">
                                    Timeline
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                Tidak ada data shipment yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($shipments->hasPages())
            <div class="px-5 py-3 border-t border-slate-200 bg-slate-50">
                {{ $shipments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection