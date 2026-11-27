@extends('layouts.app')

@section('title', __('Notifications'))
@section('page-title', __('Notifications'))

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div>
                <h2 class="text-base font-semibold text-slate-900">{{ __('In-App Notifications') }}</h2>
                <p class="text-xs text-slate-500">
                    {{ __('Total :total notifications • :unread unread.', ['total' => $totalCount, 'unread' => $unreadCount]) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('notifications.index', ['filter' => 'all']) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-medium {{ $filter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ __('All') }}
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-medium {{ $filter === 'unread' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ __('Unread (:count)', ['count' => $unreadCount]) }}
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'read']) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-medium {{ $filter === 'read' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ __('Read') }}
                </a>

                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 cursor-pointer">
                            {{ __('Mark all as read') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-3">
            @forelse ($notifications as $notif)
                @php
                    $isUnread = is_null($notif->read_at);
                @endphp
                <div class="rounded-xl border p-4 transition {{ $isUnread ? 'border-sky-200 bg-sky-50/40' : 'border-slate-200 bg-white' }}">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ $notif->title }}</span>
                                @if (!empty($notif->type))
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600">
                                        {{ strtoupper($notif->type) }}
                                    </span>
                                @endif
                                @if ($isUnread)
                                    <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800">
                                        {{ __('UNREAD') }}
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                        {{ __('READ') }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-slate-600">{{ $notif->message }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-[11px] text-slate-400">
                                <span>{{ optional($notif->created_at)->format('d M Y H:i') ?? '-' }}</span>
                                @if (!empty($notif->shipment_id))
                                    <a href="{{ route('shipments.show', $notif->shipment_id) }}" class="font-semibold text-slate-700 hover:underline">
                                        {{ $notif->shipment->shipment_number ?? (__('Shipment') . ' #' . $notif->shipment_id) }} &rarr;
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if ($isUnread && $notif->user_id === auth()->id())
                            <form method="POST" action="{{ route('notifications.read', $notif->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 cursor-pointer">
                                    {{ __('Mark as read') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 py-10 text-center text-sm text-slate-500">
                    {{ __('No in-app notifications for this account yet.') }}
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection
