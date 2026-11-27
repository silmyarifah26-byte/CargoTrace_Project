@extends('layouts.app')

@section('title', __($title))
@section('page-title', __($title))

@section('content')
<div class="bg-white rounded-xl border border-slate-200 p-6">
    <h2 class="text-base font-semibold text-slate-900">{{ __($title) }}</h2>
    <p class="mt-1 text-sm text-slate-500">
        {!! __('Authentication and role-based access check passed for role: :role.', ['role' => '<strong class="text-slate-800">' . e(auth()->user()->role) . '</strong>']) !!}
    </p>
</div>
@endsection
