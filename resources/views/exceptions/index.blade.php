@extends('layouts.app')

@section('title', __('Exceptions & SLA'))
@section('page-title', __('Exceptions & SLA'))

@section('content')
@php
    $exceptionTypeLabels = [
        'WEATHER_DELAY' => __('Weather Delay'),
        'CUSTOMS_HOLD' => __('Customs Hold'),
        'DOCUMENTATION_ISSUE' => __('Documentation Issue'),
        'PORT_CONGESTION' => __('Port Congestion'),
        'VESSEL_DELAY' => __('Vessel Delay'),
        'SLA_DELAY' => __('SLA Delay'),
    ];

    $severityLabels = [
        'critical' => ['label' => __('CRITICAL'), 'class' => 'bg-rose-100 text-rose-800'],
        'high' => ['label' => __('HIGH'), 'class' => 'bg-rose-100 text-rose-700'],
        'medium' => ['label' => __('MEDIUM'), 'class' => 'bg-amber-100 text-amber-800'],
        'low' => ['label' => __('LOW'), 'class' => 'bg-slate-100 text-slate-700'],
    ];

    $statusLabels = [
        'open' => __('OPEN'),
        'investigating' => __('INVESTIGATING'),
        'resolved' => __('RESOLVED'),
        'closed' => __('CLOSED'),
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Shipment Exceptions & SLA') }}</h1>
            <p class="text-sm text-slate-500">
                {{ __('Delay Thresholds — Group A: 12h • Group B: 18h • Group C: 24h') }}
            </p>
        </div>
        <a href="{{ route('shipments.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
            {{ __('View Shipments') }}
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">{{ __('Shipment #') }}</th>
                        <th class="px-5 py-3.5">{{ __('Type & Severity') }}</th>
                        <th class="px-5 py-3.5">{{ __('Status') }}</th>
                        <th class="px-5 py-3.5">{{ __('Notes') }}</th>
                        <th class="px-5 py-3.5">{{ __('Detected At') }}</th>
                        <th class="px-5 py-3.5 text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($exceptions as $exc)
                        @php
                            $rawType = strtoupper((string) ($exc->exception_type ?? ''));
                            $typeLabel = $exceptionTypeLabels[$rawType] ?? ($rawType !== '' ? str_replace('_', ' ', $rawType) : __('Operational Exception'));

                            $rawSeverity = strtolower((string) ($exc->severity ?? 'high'));
                            $sevMeta = $severityLabels[$rawSeverity] ?? [
                                'label' => strtoupper($rawSeverity),
                                'class' => 'bg-rose-100 text-rose-700',
                            ];

                            $rawStatus = strtolower((string) ($exc->status ?? 'open'));
                            $statusLabel = $statusLabels[$rawStatus] ?? strtoupper((string) $exc->status);

                            $rawNotes = (string) ($exc->notes ?? '');
                            $notesText = $rawNotes !== '' ? __($rawNotes) : '';
                        @endphp
                        <tr class="hover:bg-slate-50/70 align-top">
                            <td class="px-5 py-4 font-semibold text-slate-900 whitespace-nowrap">
                                @if ($exc->shipment)
                                    <a href="{{ route('shipments.show', $exc->shipment) }}" class="text-slate-900 hover:underline">
                                        {{ $exc->shipment->shipment_number ?? ('CT-' . $exc->shipment_id) }}
                                    </a>
                                @else
                                    #{{ $exc->shipment_id }}
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-900">{{ $typeLabel }}</div>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-semibold {{ $sevMeta['class'] }}">
                                    {{ $sevMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $rawStatus === 'resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-700 max-w-md">
                                {{ $notesText }}
                            </td>
                            <td class="px-5 py-4 text-slate-500 whitespace-nowrap text-xs">
                                {{ optional($exc->detected_at)->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                @if ($rawStatus !== 'resolved')
                                    <form method="POST" action="{{ route('exceptions.resolve', $exc) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-slate-950 text-white text-xs font-medium hover:bg-slate-800 cursor-pointer">
                                            {{ __('Resolve') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">
                                        {{ __('Resolved :date', ['date' => optional($exc->resolved_at)->format('d M Y H:i') ?? '-']) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                {{ __('No exceptions recorded yet.') }}
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
