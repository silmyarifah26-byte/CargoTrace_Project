@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Audit Logs</h1>
            <p class="text-sm text-slate-500">
                Jejak aktivitas sistem &amp; perubahan status pengiriman
            </p>
        </div>
        <a href="{{ route('shipments.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
            Lihat Shipments
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Waktu</th>
                        <th class="px-5 py-3.5">Actor</th>
                        <th class="px-5 py-3.5">Action</th>
                        <th class="px-5 py-3.5">Entity</th>
                        <th class="px-5 py-3.5">Metadata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($auditLogs as $log)
                        <tr class="hover:bg-slate-50/70 align-top">
                            <td class="px-5 py-4 text-slate-500 whitespace-nowrap text-xs">
                                {{ optional($log->created_at)->format('d M Y H:i:s') ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-slate-800 whitespace-nowrap font-medium">
                                {{ $log->actor?->name ?? ('User #' . $log->actor_id) }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-700 whitespace-nowrap text-xs">
                                {{ class_basename((string) $log->entity_type) }} #{{ $log->entity_id }}
                            </td>
                            <td class="px-5 py-4 text-slate-600 text-xs max-w-md">
                                @if (is_array($log->metadata))
                                    <pre class="bg-slate-50 p-2 rounded border border-slate-200 overflow-x-auto text-[11px]">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    {{ $log->metadata ?? '-' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                                Belum ada audit log tercatat.
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