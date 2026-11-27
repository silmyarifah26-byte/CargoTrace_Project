@php
    $currentLocale = app()->getLocale();
@endphp
<div class="inline-flex items-center rounded-lg border border-slate-300 bg-slate-50 p-0.5 text-xs font-semibold shrink-0"
     role="group"
     aria-label="{{ __('Language Switcher') }}">
    <a href="{{ route('lang.switch', 'id') }}"
       class="px-2.5 py-1 rounded-md transition {{ $currentLocale === 'id' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
       @if ($currentLocale === 'id') aria-current="true" @endif>
        ID
    </a>
    <span class="px-0.5 text-slate-300 select-none" aria-hidden="true">|</span>
    <a href="{{ route('lang.switch', 'en') }}"
       class="px-2.5 py-1 rounded-md transition {{ $currentLocale === 'en' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
       @if ($currentLocale === 'en') aria-current="true" @endif>
        EN
    </a>
</div>
