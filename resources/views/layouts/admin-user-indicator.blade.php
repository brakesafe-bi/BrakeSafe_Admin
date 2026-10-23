@php
    $adminName = auth()->user()->full_name ?: auth()->user()->name ?: 'Admin';
    $nearbyNotificationsButtonId = match (true) {
        request()->routeIs('dashboard') => 'nearby-notifications-toggle',
        request()->routeIs('brake-events.index') => 'brake-events-notifications-toggle',
        request()->routeIs('analytics') => 'analytics-notifications-toggle',
        request()->routeIs('analytics.apa') => 'apa-notifications-toggle',
        request()->routeIs('profile.*') => 'profile-notifications-toggle',
        default => null,
    };
@endphp
<div id="admin-user-badge" class="flex max-w-[15rem] min-w-0 items-center gap-2 text-slate-200" role="group" aria-label="Signed in as {{ $adminName }}">
    @if ($nearbyNotificationsButtonId)
        <button id="{{ $nearbyNotificationsButtonId }}" type="button" aria-label="Enable nearby notifications" title="Nearby notifications off" aria-pressed="false" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/10 bg-[#0b111b] text-slate-300 transition hover:bg-[#101d2e] hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-400/50">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/><path class="notification-slash" d="m3 3 18 18"/></svg>
            <span class="button-label sr-only">Nearby notifications off</span>
        </button>
    @else
        <svg class="h-5 w-5 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
            <path d="M10 21h4"/>
        </svg>
    @endif
    <span class="admin-user-name max-w-[9rem] truncate text-sm font-semibold text-white sm:max-w-[13rem]">{{ $adminName }}</span>
</div>
