@extends('layouts.app')

@section('title', 'Exceptions')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Shipment &amp; SLA Exceptions</h1>
            <p class="text-sm text-slate-500">
                Delay Thresholds — Group A: 12h &bull; Group B: 18h &bull; Group C: 24h
            </p>
        </div>
        <a href="{{ route('shipments.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
            Lihat Shipments
        </a>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Shipment #</th>
                        <th class="px-5 py-3.5">Type &amp; Severity</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Notes</th>
                        <th class="px-5 py-3.5">Detected At</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($exceptions as $exc)
                        <tr class="hover:bg-slate-50/70 align-top">
                            <td class="px-5 py-4 font-semibold text-slate-900 whitespace-nowrap">
                                @if ($exc->shipment)
                                    <a href="{{ route('shipments.show', $exc->shipment) }}" class="text-sky-700 hover:underline">
                                        {{ $exc->shipment->shipment_number ?? ('SHP-' . $exc->shipment_id) }}
                                    </a>
                                @else
                                    #{{ $exc->shipment_id }}
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-900">{{ strtoupper((string) $exc->exception_type) }}</div>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-700 uppercase">
                                    {{ $exc->severity ?? 'high' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase {{ $exc->status === 'resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $exc->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-700 max-w-md">
                                {{ $exc->notes }}
                            </td>
                            <td class="px-5 py-4 text-slate-500 whitespace-nowrap text-xs">
                                {{ optional($exc->detected_at)->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                @if ($exc->status !== 'resolved')
                                    <form method="POST" action="{{ route('exceptions.resolve', $exc) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                                            Resolve
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">
                                        Resolved {{ optional($exc->resolved_at)->format('d M Y H:i') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                Belum ada data exception tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($exceptions->hasPages())
            <div class="px-5 py-3 border-t border-slate-200 bg-slate-50">
                {{ $exceptions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection