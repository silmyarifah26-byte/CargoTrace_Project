<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CargoTrace') — Pemantauan Pengiriman Global</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('styles')
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    @auth
        <div class="min-h-screen flex flex-col lg:flex-row">
            {{-- Sidebar --}}
            <aside class="w-full lg:w-64 bg-slate-900 text-slate-100 flex-shrink-0 flex flex-col justify-between">
                <div>
                    <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-lg font-bold tracking-tight text-white">CargoTrace</span>
                            <p class="text-[11px] uppercase tracking-wider text-sky-400 font-semibold">Pemantauan Pengiriman</p>
                        </div>
                        <span class="px-2 py-0.5 text-xs rounded bg-sky-500/20 text-sky-300 border border-sky-500/30">
                            A/B/C SLA
                        </span>
                    </div>

                    <nav class="px-4 py-4 space-y-1 text-sm">
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('dashboard') ? 'bg-sky-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span>Dasbor</span>
                        </a>

                        <a href="{{ route('shipments.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('shipments.*') ? 'bg-sky-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <span>Pengiriman</span>
                        </a>

                        @if(in_array(auth()->user()->role, ['admin', 'operator'], true))
                            <a href="{{ route('exceptions.index') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('exceptions.*') ? 'bg-sky-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>Kendala</span>
                            </a>
                        @endif

                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('master.index') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('master.*') ? 'bg-sky-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>Kurir &amp; Pelabuhan</span>
                            </a>
                            <a href="{{ route('audit-logs.index') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('audit-logs.*') ? 'bg-sky-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span>Log Audit</span>
                            </a>
                        @endif
                    </nav>
                </div>

                <div class="p-4 border-t border-slate-800 bg-slate-950/50">
                    <div class="text-xs text-slate-400">Masuk sebagai</div>
                    <div class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="mt-1 inline-block px-2 py-0.5 text-[11px] rounded bg-slate-800 text-sky-300 uppercase">
                        {{ str_replace('_', ' ', auth()->user()->role) }}
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit"
                                class="w-full text-center px-3 py-1.5 text-xs font-medium rounded-lg bg-rose-600/90 hover:bg-rose-600 text-white transition">
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Main Content --}}
            <div class="flex-1 flex flex-col min-w-0">
                <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">@yield('page-title', 'Dasbor')</h1>
                        <p class="text-xs text-slate-500">Ambang Batas Keterlambatan — Grup A: 12j • Grup B: 18j • Grup C: 24j</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('notifications.index') }}"
                           class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                            Notifikasi
                        </a>
                    </div>
                </header>

                <main class="flex-1 p-6">
                    @if(session('success'))
                        <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 px-4 py-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <main class="min-h-screen flex items-center justify-center p-6">
            @yield('content')
        </main>
    @endauth

    @stack('scripts')
</body>
</html>