@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@section('content')
<div class="bg-white rounded-xl border border-slate-200 p-6">
    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
    <p class="mt-1 text-sm text-slate-500">
        Authentication and role-based access check passed for role: <strong class="text-slate-800">{{ auth()->user()->role }}</strong>.
    </p>
</div>
@endsection