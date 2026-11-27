@extends('layouts.app')

@section('title', __('Carriers & Ports'))
@section('page-title', __('Carriers & Ports'))

@section('content')
@php
    $roleLabels = [
        'admin' => __('Admin'),
        'operator' => __('Logistics Operator'),
        'customer' => __('Customer'),
    ];
    $currentRoleLabel = $roleLabels[auth()->user()->role] ?? strtoupper(auth()->user()->role);
@endphp
<div class="space-y-6">
    {{-- Master Data Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('Total Carriers') }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalCarriers }}</p>
            <p class="mt-1 text-xs text-emerald-600">{{ __(':count active operating carriers', ['count' => $activeCarriers]) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('Total Ports') }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalLocations }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ __('Registered ports with GPS coordinates') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ __('Access Level') }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 uppercase">{{ $currentRoleLabel }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ __('Fleet & port master data management') }}</p>
        </div>
    </div>

    {{-- Carriers Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">{{ __('Add New Carrier') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('Register a new shipping operator / carrier line.') }}</p>

            <form method="POST" action="{{ route('master.carriers.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Carrier Code (SCAC)') }}</label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="{{ __('Example: HLCU') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Carrier Name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Example: Hapag-Lloyd Maritime') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Contact Email') }}</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email') }}" placeholder="ops@carrier.test"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Contact Phone') }}</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" placeholder="+62-21-5550199"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <label class="inline-flex items-center gap-2 pt-1 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                           class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span>{{ __('Active Status') }}</span>
                </label>
                <div class="pt-2">
                    <button type="submit"
                            class="w-full rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition cursor-pointer">
                        {{ __('Save Carrier') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Carrier List') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Showing :count carriers matching filter.', ['count' => $carriers->count()]) }}</p>
                </div>
                <form method="GET" action="{{ route('master.index') }}" class="flex items-center gap-2">
                    @if ($locationSearch !== '')
                        <input type="hidden" name="location_q" value="{{ $locationSearch }}">
                    @endif
                    <input type="text" name="carrier_q" value="{{ $carrierSearch }}" placeholder="{{ __('Search carrier code / name...') }}"
                           class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-slate-900 focus:outline-none">
                    <button type="submit" class="rounded-lg bg-slate-950 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-slate-800 cursor-pointer">
                        {{ __('Search') }}
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 bg-slate-50">
                            <th class="px-3 py-2.5">{{ __('Code') }}</th>
                            <th class="px-3 py-2.5">{{ __('Carrier Name') }}</th>
                            <th class="px-3 py-2.5">{{ __('Contact') }}</th>
                            <th class="px-3 py-2.5 text-center">{{ __('Shipments') }}</th>
                            <th class="px-3 py-2.5">{{ __('Status') }}</th>
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
                                            {{ __('Active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 border border-slate-200">
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">{{ __('No carrier data yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Port Locations Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-base font-semibold text-slate-900">{{ __('Add New Port') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('New ports will immediately appear in checkpoint location options.') }}</p>

            <form method="POST" action="{{ route('master.locations.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('LOCODE') }}</label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="{{ __('Example: IDBLW2') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase focus:border-slate-900 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Port Name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Example: Port of Tanjung Balai') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('City') }}</label>
                        <input type="text" name="city" value="{{ old('city') }}" placeholder="Tanjungbalai" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Country') }}</label>
                        <input type="text" name="country" value="{{ old('country') }}" placeholder="Indonesia" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Latitude') }}</label>
                        <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude') }}" placeholder="2.9825000" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">{{ __('Longitude') }}</label>
                        <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude') }}" placeholder="99.8058000" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit"
                            class="w-full rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition cursor-pointer">
                        {{ __('Save Port') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Port Directory') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Total :total ports found.', ['total' => $locations->total()]) }}</p>
                </div>
                <form method="GET" action="{{ route('master.index') }}" class="flex items-center gap-2">
                    @if ($carrierSearch !== '')
                        <input type="hidden" name="carrier_q" value="{{ $carrierSearch }}">
                    @endif
                    <input type="text" name="location_q" value="{{ $locationSearch }}" placeholder="{{ __('Search code, port, city, country...') }}"
                           class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-slate-900 focus:outline-none">
                    <button type="submit" class="rounded-lg bg-slate-950 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-slate-800 cursor-pointer">
                        {{ __('Search') }}
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 bg-slate-50">
                            <th class="px-3 py-2.5">{{ __('Code') }}</th>
                            <th class="px-3 py-2.5">{{ __('Port') }}</th>
                            <th class="px-3 py-2.5">{{ __('City / Country') }}</th>
                            <th class="px-3 py-2.5">{{ __('Coordinates') }}</th>
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
                                <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">{{ __('No matching ports found.') }}</td>
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
