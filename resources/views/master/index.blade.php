@extends('layouts.app')

@section('title', 'Carriers & Locations')
@section('page-title', 'Carriers & Locations')

@section('content')
<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">Periksa kembali inputan form:</p>
            <ul class="mt-1 list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Ringkasan Master Data --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Carriers</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalCarriers }}</p>
            <p class="mt-1 text-xs text-emerald-600">{{ $activeCarriers }} carrier aktif beroperasi</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Seaports</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalLocations }}</p>
            <p class="mt-1 text-xs text-slate-500">Pelabuhan terdaftar dengan koordinat GPS</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Access Level</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 uppercase">{{ auth()->user()->role }}</p>
            <p class="mt-1 text-xs text-slate-500">Master data manajemen armada & pelabuhan</p>
        </div>
    </div>

    {{-- Bagian Carriers --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">Tambah Carrier Baru</h2>
            <p class="mt-1 text-xs text-slate-500">Daftarkan operator pelayaran / jalur pengiriman baru.</p>

            <form method="POST" action="{{ route('master.carriers.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Kode Carrier (SCAC)</label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="Contoh: HLCU" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase focus:border-slate-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Carrier</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Hapag-Lloyd Maritime" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Email Kontak</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email') }}" placeholder="ops@carrier.test"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Telepon Kontak</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" placeholder="+62-21-5550199"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
                <label class="inline-flex items-center gap-2 pt-1 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                           class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                    <span>Status Aktif</span>
                </label>
                <div class="pt-2">
                    <button type="submit"
                            class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition">
                        Simpan Carrier
                    </button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Daftar Carriers</h2>
                    <p class="text-xs text-slate-500">Menampilkan {{ $carriers->count() }} carrier sesuai filter.</p>
                </div>
                <form method="GET" action="{{ route('master.index') }}" class="flex items-center gap-2">
                    @if ($locationSearch !== '')
                        <input type="hidden" name="location_q" value="{{ $locationSearch }}">
                    @endif
                    <input type="text" name="carrier_q" value="{{ $carrierSearch }}" placeholder="Cari kode / nama carrier..."
                           class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                    <button type="submit" class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200">
                        Cari
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 bg-slate-50">
                            <th class="px-3 py-2.5">Kode</th>
                            <th class="px-3 py-2.5">Nama Carrier</th>
                            <th class="px-3 py-2.5">Kontak</th>
                            <th class="px-3 py-2.5 text-center">Shipments</th>
                            <th class="px-3 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($carriers as $carrier)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-3 py-2.5 font-mono font-semibold text-slate-900">{{ $carrier->code }}</td>
                                <td class="px-3 py-2.5 font-medium text-slate-800">{{ $carrier->name }}</td>
                                <td class="px-3 py-2.5 text-xs text-slate-600">
                                    <div>{{ $carrier->contact_email ?: '-' }}</div>
                                    <div class="text-slate-400">{{ $carrier->contact_phone ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-center font-medium text-slate-700">{{ $carrier->shipments_count }}</td>
                                <td class="px-3 py-2.5">
                                    @if ($carrier->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 border border-slate-200">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">Belum ada data carrier.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Bagian Seaport Locations --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">Tambah Pelabuhan Baru</h2>
            <p class="mt-1 text-xs text-slate-500">Pelabuhan baru akan langsung muncul di pilihan checkpoint.</p>

            <form method="POST" action="{{ route('master.locations.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Kode LOCODE</label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="Contoh: IDBLW2" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase focus:border-slate-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Pelabuhan</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Port of Tanjung Balai" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Kota</label>
                        <input type="text" name="city" value="{{ old('city') }}" placeholder="Tanjungbalai" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Negara</label>
                        <input type="text" name="country" value="{{ old('country') }}" placeholder="Indonesia" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Latitude</label>
                        <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude') }}" placeholder="2.9825000" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude') }}" placeholder="99.8058000" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit"
                            class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition">
                        Simpan Pelabuhan
                    </button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Direktori Pelabuhan</h2>
                    <p class="text-xs text-slate-500">Total {{ $locations->total() }} pelabuhan ditemukan.</p>
                </div>
                <form method="GET" action="{{ route('master.index') }}" class="flex items-center gap-2">
                    @if ($carrierSearch !== '')
                        <input type="hidden" name="carrier_q" value="{{ $carrierSearch }}">
                    @endif
                    <input type="text" name="location_q" value="{{ $locationSearch }}" placeholder="Cari kode, pelabuhan, kota, negara..."
                           class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-slate-500 focus:outline-none">
                    <button type="submit" class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200">
                        Cari
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 bg-slate-50">
                            <th class="px-3 py-2.5">Code</th>
                            <th class="px-3 py-2.5">Pelabuhan</th>
                            <th class="px-3 py-2.5">Kota / Negara</th>
                            <th class="px-3 py-2.5">Koordinat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($locations as $locItem)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-3 py-2.5 font-mono font-semibold text-slate-900">{{ $locItem->code }}</td>
                                <td class="px-3 py-2.5 font-medium text-slate-800">{{ $locItem->name }}</td>
                                <td class="px-3 py-2.5 text-slate-600">{{ $locItem->city }}, {{ $locItem->country }}</td>
                                <td class="px-3 py-2.5 font-mono text-xs text-slate-500">
                                    {{ $locItem->latitude }}, {{ $locItem->longitude }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">Tidak ada pelabuhan yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $locations->links() }}
            </div>
        </div>
    </div>
</div>
@endsection