@extends('layouts.app')

@section('title', 'Kendala & SLA')

@section('content')
@php
    $exceptionTypeLabels = [
        'WEATHER_DELAY' => 'Keterlambatan Cuaca',
        'CUSTOMS_HOLD' => 'Penahanan Kepabeanan',
        'DOCUMENTATION_ISSUE' => 'Kendala Dokumen',
        'PORT_CONGESTION' => 'Kepadatan Pelabuhan',
        'VESSEL_DELAY' => 'Keterlambatan Kapal',
    ];

    $severityLabels = [
        'high' => ['label' => 'TINGGI', 'class' => 'bg-rose-100 text-rose-700'],
        'medium' => ['label' => 'SEDANG', 'class' => 'bg-amber-100 text-amber-800'],
        'low' => ['label' => 'RENDAH', 'class' => 'bg-slate-100 text-slate-700'],
    ];

    $statusLabels = [
        'open' => 'TERBUKA',
        'investigating' => 'INVESTIGASI',
        'resolved' => 'SELESAI',
        'closed' => 'DITUTUP',
    ];

    $noteTranslations = [
        'Heavy swell conditions in North Pacific corridor.' => 'Gelombang tinggi di jalur Pasifik Utara.',
        'Phytosanitary certificate verification requested at transshipment port.' => 'Verifikasi sertifikat fitosanitari diminta di pelabuhan transit.',
        'Bill of Lading consignee tax ID corrected prior to arrival.' => 'NPWP penerima pada Bill of Lading telah diperbaiki sebelum kedatangan.',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Kendala Pengiriman &amp; SLA</h1>
            <p class="text-sm text-slate-500">
                Ambang Batas Keterlambatan — Grup A: 12j &bull; Grup B: 18j &bull; Grup C: 24j
            </p>
        </div>
        <a href="{{ route('shipments.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
            Lihat Pengiriman
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
                        <th class="px-5 py-3.5">No. Pengiriman</th>
                        <th class="px-5 py-3.5">Jenis &amp; Keparahan</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Catatan</th>
                        <th class="px-5 py-3.5">Terdeteksi Pada</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($exceptions as $exc)
                        @php
                            $rawType = strtoupper((string) ($exc->exception_type ?? ''));
                            $typeLabel = $exceptionTypeLabels[$rawType] ?? ($rawType !== '' ? str_replace('_', ' ', $rawType) : 'Kendala Operasional');

                            $rawSeverity = strtolower((string) ($exc->severity ?? 'high'));
                            $sevMeta = $severityLabels[$rawSeverity] ?? [
                                'label' => strtoupper($rawSeverity),
                                'class' => 'bg-rose-100 text-rose-700',
                            ];

                            $rawStatus = strtolower((string) ($exc->status ?? 'open'));
                            $statusLabel = $statusLabels[$rawStatus] ?? strtoupper((string) $exc->status);

                            $rawNotes = (string) ($exc->notes ?? '');
                            $notesText = $noteTranslations[$rawNotes] ?? $rawNotes;
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
                                            Selesaikan
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">
                                        Selesai {{ optional($exc->resolved_at)->format('d M Y H:i') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                Belum ada data kendala tercatat.
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