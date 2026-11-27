@extends('layouts.app')

@section('title', __('Sign In'))

@section('content')
<div class="min-h-screen w-full bg-slate-100 flex flex-col justify-between">
    {{-- Brand Header Kiri Atas --}}
    <header class="w-full max-w-6xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5 text-slate-950 font-bold text-lg tracking-tight shrink-0">
            <span class="w-7 h-7 rounded-lg bg-slate-950 text-sky-400 flex items-center justify-center">
                {{-- Maritime Anchor Icon --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="5" r="3"></circle>
                    <line x1="12" y1="22" x2="12" y2="8"></line>
                    <path d="M5 12H2a10 10 0 0 0 20 0h-3"></path>
                </svg>
            </span>
            <span>CargoTrace</span>
        </a>

        @include('partials.language-switcher')
    </header>

    {{-- Centered Login Card --}}
    <main class="flex-1 flex items-center justify-center px-4 py-6">
        <div class="w-full max-w-md bg-white rounded-2xl border border-slate-200/90 shadow-sm p-7 sm:p-9">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-950">{{ __('Sign in to CargoTrace') }}</h1>
                <p class="mt-1.5 text-xs sm:text-sm text-slate-500 leading-relaxed">
                    {{ __('Please enter your email address and password to access the shipment monitoring dashboard.') }}
                </p>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-xs text-rose-800">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-800 mb-1.5">
                        {{ __('Email Address') }} <span class="text-rose-600">*</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="{{ __('Enter your email') }}"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15"
                    >
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-800">
                            {{ __('Password') }} <span class="text-rose-600">*</span>
                        </label>
                        @if (\Illuminate\Support\Facades\Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs font-medium text-slate-700 hover:text-slate-950 hover:underline">
                                {{ __('Forgot password?') }}
                            </a>
                        @endif
                    </div>

                    <div class="relative">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="{{ __('Enter your password') }}"
                            class="w-full rounded-lg border border-slate-300 bg-white pl-3.5 pr-11 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15"
                        >
                        <button
                            type="button"
                            id="toggle-password-btn"
                            aria-label="{{ __('Show or hide password') }}"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-500 hover:text-slate-900 focus:outline-none cursor-pointer"
                        >
                            <svg id="eye-off-icon" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                            <svg id="eye-on-icon" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pt-0.5">
                    <label for="remember_me" class="inline-flex items-center gap-2 text-xs text-slate-700 cursor-pointer select-none">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                        >
                        <span>{{ __('Remember me on this device') }}</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-slate-950 hover:bg-slate-800 text-white font-semibold py-2.5 px-4 text-sm transition cursor-pointer"
                >
                    {{ __('Sign In') }}
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
                @if (\Illuminate\Support\Facades\Route::has('register'))
                    <span>
                        {{ __("Don't have an account?") }}
                        <a href="{{ route('register') }}" class="font-semibold text-slate-950 hover:underline">
                            {{ __('Register Now') }}
                        </a>
                    </span>
                @else
                    <span>{{ __('Admin, Operator & Customer role access is managed by the system.') }}</span>
                @endif
            </div>
        </div>
    </main>

    {{-- Bottom Footer --}}
    <footer class="w-full bg-white border-t border-slate-200 px-6 py-3 text-xs text-slate-500">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                <span class="font-semibold text-slate-900">CargoTrace</span>
                <span class="mx-1.5 text-slate-300">—</span>
                <span>{{ __('Shipment & Cargo Route Monitoring System') }}</span>
            </div>
            <div>
                {{ __('Information Systems Project') }} &copy; {{ date('Y') }}
            </div>
        </div>
    </footer>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('toggle-password-btn');
        const input = document.getElementById('password');
        const eyeOff = document.getElementById('eye-off-icon');
        const eyeOn = document.getElementById('eye-on-icon');

        if (!btn || !input) return;

        btn.addEventListener('click', function () {
            const isHidden = input.getAttribute('type') === 'password';
            input.setAttribute('type', isHidden ? 'text' : 'password');

            if (eyeOff && eyeOn) {
                eyeOff.classList.toggle('hidden', isHidden);
                eyeOn.classList.toggle('hidden', !isHidden);
            }
        });
    });
</script>
@endpush
