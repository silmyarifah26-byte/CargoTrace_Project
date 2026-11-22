@extends('layouts.app')

@section('title', 'Shipment Timeline — ' . ($shipment->shipment_number ?? ('#' . $shipment->id)))

@section('content')
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
                Current Status: <strong class="text-slate-800">{{ ucwords(str_replace('_', ' ', (string) ($shipment->current_status ?? '-'))) }}</strong>
                &bull; SLA Group {{ $slaEval['group_code'] }} ({{ $thresholdHours }}h)
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
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Chronological Tracking Events</h2>
                        <p class="text-xs text-slate-500">Riwayat perpindahan status dan checkpoint pengiriman</p>
                    </div>
                </div>

                <div class="space-y-5 border-l-2 border-slate-200 ml-2 pl-5">
                    @forelse ($shipment->trackingEvents as $event)
                        <div class="relative">
                            <span class="w-3 h-3 rounded-full bg-sky-600 absolute -left-[27px] top-1.5 ring-4 ring-white"></span>
                            <div class="text-xs text-slate-500">
                                {{ optional($event->occurred_at)->format('d M Y H:i') ?? '-' }}
                                @if ($event->location_label || $event->location)
                                    &bull; <span class="font-medium text-slate-700">{{ $event->location_label ?? $event->location?->name }}</span>
                                @endif
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
                            <option value="booked">Booked</option>
                            <option value="in_transit">In Transit</option>
                            <option value="out_for_delivery">Out for Delivery</option>
                            <option value="delivered">Delivered</option>
                            <option value="delayed">Delayed</option>
                            <option value="exception">Exception</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Lokasi Checkpoint</label>
                        <select name="location_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Deskripsi Event</label>
                        <textarea name="description" rows="3" required
                                  placeholder="Contoh: Container cleared customs and dispatched to destination hub."
                                  class="w-full rounded-lg border-slate-300 text-sm"></textarea>
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
                    <span class="text-slate-500">SLA Group</span>
                    <span class="font-medium text-slate-900">Group {{ $slaEval['group_code'] }} ({{ $thresholdHours }}h)</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-500">Delay Hours</span>
                    <span class="font-medium text-slate-900">{{ $overdueHours }}h</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection