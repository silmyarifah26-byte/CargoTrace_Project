@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="w-full max-w-4xl grid grid-cols-1 md:grid-cols-2 bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
    <div class="bg-slate-900 text-white p-8 flex flex-col justify-between">
        <div>
            <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded bg-sky-500/20 text-sky-300 border border-sky-500/30">
                CargoTrace Platform
            </span>
            <h2 class="mt-4 text-2xl font-bold leading-snug">Global Shipment Visibility &amp; Tracking</h2>
            <p class="mt-2 text-sm text-slate-300">
                Monitor shipment lifecycle, chronological tracking timelines, route maps, delay thresholds (12h / 18h / 24h), and operational exceptions.
            </p>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-800 text-xs text-slate-300 space-y-2">
            <p class="font-semibold text-white">Authorized Workspace Access</p>
            <p class="text-slate-400">
                Role-based portal for Shipment Administrators, Operations Teams, and Registered Customers.
            </p>
        </div>
    </div>

    <div class="p-8">
        <h3 class="text-xl font-bold text-slate-900">Sign in to your account</h3>
        <p class="mt-1 text-xs text-slate-500">Enter your credentials to access your workspace.</p>

        @if ($errors->any())
            <div class="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-sky-500 focus:outline-none">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" required
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-sky-500 focus:outline-none">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="inline-flex items-center gap-2 text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-sky-600">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit"
                    class="w-full py-2.5 px-4 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold transition">
                Sign In
            </button>
        </form>
    </div>
</div>
@endsection