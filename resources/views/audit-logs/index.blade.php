@extends('layouts.app')

@section('title', 'Log Audit')

@section('content')
@php
    $actionLabels = [
        'SEED_DEMO_DATA' => 'Inisialisasi Data Demo',
        'SHIPMENT_CREATED' => 'Pengiriman Dibuat',
        'SHIPMENT_UPDATED' => 'Pengiriman Diperbarui',
        'STATUS_UPDATED' => 'Status Diperbarui',
        'EXCEPTION_CREATED' => 'Kendala Dibuat',
        'EXCEPTION_RESOLVED' => 'Kendala Diselesaikan',
        'CARRIER_CREATED' => 'Kurir Ditambahkan',
        'LOCATION_CREATED' => 'Pelabuhan Ditambahkan',
        'LOGIN' => 'Masuk Sistem',
        'LOGOUT' => 'Keluar Sistem',
    ];

    $entityLabels = [
        'Shipment' => 'Pengiriman',
        'ShipmentException' => 'Kendala',
        'Carrier' => 'Kurir',
        'Location' => 'Pelabuhan',
        'User' => 'Pengguna',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Log Audit</h1>
            <p class="text-sm text-slate-500">
                Jejak aktivitas sistem &amp; perubahan status pengiriman
            </p>
        </div>
        <a href="{{ route('shipments.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
            Lihat Pengiriman
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Waktu</th>
                        <th class="px-5 py-3.5">Aktor</th>
                        <th class="px-5 py-3.5">Aksi</th>
                        <th class="px-5 py-3.5">Entitas</th>
                        <th class="px-5 py-3.5">Metadata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($auditLogs as $log)
                        @php
                            $rawAction = strtoupper((string) ($log->action ?? ''));
                            $actionText = $actionLabels[$rawAction] ?? ($rawAction !== '' ? str_replace('_', ' ', $rawAction) : '-');

                            $rawEntity = class_basename((string) $log->entity_type);
                            $entityText = $entityLabels[$rawEntity] ?? ($rawEntity !== '' ? $rawEntity : 'Entitas');
                        @endphp
                        <tr class="hover:bg-slate-50/70 align-top">
                            <td class="px-5 py-4 text-slate-500 whitespace-nowrap text-xs">
                                {{ optional($log->created_at)->format('d M Y H:i:s') ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-slate-800 whitespace-nowrap font-medium">
                                {{ $log->actor?->name ?? ('Pengguna #' . $log->actor_id) }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-900 text-white">
                                    {{ $actionText }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-700 whitespace-nowrap text-xs">
                                {{ $entityText }} #{{ $log->entity_id }}
                            </td>
                            <td class="px-5 py-4 text-slate-600 text-xs max-w-md">
                                @if (is_array($log->metadata))
                                    <pre class="bg-slate-50 p-2 rounded border border-slate-200 overflow-x-auto text-[11px]">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                @else
                                    {{ $log->metadata ?? '-' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                                Belum ada log audit tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($auditLogs->hasPages())
            <div class="px-5 py-3 border-t border-slate-200 bg-slate-50">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection