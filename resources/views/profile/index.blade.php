@extends('layouts.admin')

@section('title', 'Profile - BrakeSafe')
@section('page-title', 'Profile')

@section('content')
    <div id="profile-page" class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <button id="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#111317] text-slate-200 transition hover:bg-[#202329] focus:outline-none focus:ring-2 focus:ring-cyan-400/50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">Profile</h1>
            </div>
            <div class="flex min-w-0 flex-wrap items-center justify-end gap-4">
                <button id="profile-theme-toggle" type="button" aria-label="Switch to light theme" title="Switch to light theme" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/10 bg-slate-950 text-slate-100 shadow-[0_20px_40px_rgba(0,0,0,0.25)] transition duration-200 ease-out hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42 1.42m11.3-11.3 1.42-1.42"/></svg>
                </button>
                @include('layouts.admin-user-indicator')
                <span id="profile-control-status" class="sr-only" role="status" aria-live="polite"></span>
            </div>
        </header>

        <section aria-labelledby="profile-security-title" class="profile-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
            <div class="border-b border-white/10 pb-5">
                <h2 id="profile-security-title" class="text-xl font-semibold text-white">Profile and security</h2>
                <p class="mt-1 text-sm text-slate-400">Update your account details or change your password.</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="pt-5">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    @php
                        $existingName = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', trim($profile->full_name ?: $profile->name));
                        $nameParts = preg_split('/\s+/', $existingName, 2) ?: [];
                    @endphp
                    <div class="flex flex-col gap-4 rounded-xl border border-white/10 bg-[#0b0d10] p-4 sm:flex-row sm:items-center">
                        @include('layouts.admin-avatar', ['admin' => $profile, 'sizeClass' => 'h-20 w-20'])
                        <div class="min-w-0 flex-1">
                            <label for="profile-photo" class="mb-1.5 block text-sm font-medium text-slate-300">Profile picture</label>
                            <input id="profile-photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="profile-input block min-h-11 w-full rounded-xl border border-white/10 bg-[#111317] px-3 py-2 text-sm text-slate-200 file:mr-3 file:rounded-lg file:border-0 file:bg-cyan-500 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-950">
                            <p class="mt-1 text-xs text-slate-400">Choose a JPG, PNG, or WebP image up to 2 MB.</p>
                            @error('profile_photo')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="profile-first-name" class="mb-1.5 block text-sm font-medium text-slate-300">First name</label>
                            <input id="profile-first-name" name="first_name" type="text" autocomplete="given-name" value="{{ old('first_name', $nameParts[0] ?? '') }}" required maxlength="127" class="profile-input min-h-11 w-full rounded-xl border border-white/10 bg-[#0b0d10] px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400/50 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                        <div>
                            <label for="profile-last-name" class="mb-1.5 block text-sm font-medium text-slate-300">Last name</label>
                            <input id="profile-last-name" name="last_name" type="text" autocomplete="family-name" value="{{ old('last_name', $nameParts[1] ?? '') }}" required maxlength="127" class="profile-input min-h-11 w-full rounded-xl border border-white/10 bg-[#0b0d10] px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400/50 focus:ring-2 focus:ring-cyan-400/20">
                        </div>
                    </div>
                    <div>
                        <label for="profile-email" class="mb-1.5 block text-sm font-medium text-slate-300">Email</label>
                        <input id="profile-email" name="email" type="email" autocomplete="email" value="{{ old('email', $profile->email) }}" required maxlength="50" class="profile-input min-h-11 w-full rounded-xl border border-white/10 bg-[#0b0d10] px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400/50 focus:ring-2 focus:ring-cyan-400/20">
                    </div>
                </div>

                <div class="mt-6 border-t border-white/10 pt-5">
                    <h3 class="text-lg font-semibold text-white">Change password</h3>
                    <p class="mt-1 text-sm text-slate-400">Leave new password fields empty to keep your current password.</p>
                    <div class="mt-4 space-y-4">
                        @foreach([
                            ['id' => 'current-password', 'name' => 'current_password', 'label' => 'Current password', 'autocomplete' => 'current-password', 'accessible' => 'current password'],
                            ['id' => 'new-password', 'name' => 'password', 'label' => 'New password', 'autocomplete' => 'new-password', 'accessible' => 'new password'],
                            ['id' => 'confirmation-password', 'name' => 'password_confirmation', 'label' => 'Confirm new password', 'autocomplete' => 'new-password', 'accessible' => 'confirmation password'],
                        ] as $passwordField)
                            <div>
                                <label for="{{ $passwordField['id'] }}" class="mb-1.5 block text-sm font-medium text-slate-300">{{ $passwordField['label'] }}</label>
                                <div class="relative">
                                    <input id="{{ $passwordField['id'] }}" name="{{ $passwordField['name'] }}" type="password" autocomplete="{{ $passwordField['autocomplete'] }}" class="profile-input min-h-11 w-full rounded-xl border border-white/10 bg-[#0b0d10] py-3 pl-4 pr-12 text-sm text-white outline-none transition focus:border-cyan-400/50 focus:ring-2 focus:ring-cyan-400/20">
                                    <button type="button" class="password-visibility absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center rounded-r-xl text-slate-400 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-cyan-400/50" aria-label="Show {{ $passwordField['accessible'] }}" aria-controls="{{ $passwordField['id'] }}">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 flex justify-start border-t border-white/10 pt-5">
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-red-600 px-5 text-sm font-semibold text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-300/60 focus:ring-offset-2 focus:ring-offset-[#111317]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h12l4 4v14H3V3h2Z"/><path d="M7 3v6h10V3M7 21v-8h10v8"/></svg>
                        Save profile
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const photoInput = document.getElementById('profile-photo');
        let photoPreviewUrl = null;
        photoInput.addEventListener('change', () => {
            const photo = photoInput.files?.[0];
            if (!photo) return;

            if (photoPreviewUrl) URL.revokeObjectURL(photoPreviewUrl);
            photoPreviewUrl = URL.createObjectURL(photo);

            document.querySelectorAll('[data-admin-avatar]').forEach((avatar) => {
                let image = avatar.querySelector('img');
                if (!image) {
                    image = document.createElement('img');
                    image.className = 'h-full w-full object-cover';
                    image.alt = 'Profile picture preview';
                    avatar.replaceChildren(image);
                }
                image.src = photoPreviewUrl;
            });
        });

        document.querySelectorAll('.password-visibility').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.getAttribute('aria-controls'));
                const showing = input.type === 'password';
                input.type = showing ? 'text' : 'password';
                const label = button.getAttribute('aria-label').replace(showing ? 'Show' : 'Hide', showing ? 'Hide' : 'Show');
                button.setAttribute('aria-label', label);
            });
        });

        const themeButton = document.getElementById('profile-theme-toggle');
        themeButton.addEventListener('click', () => {
            const light = document.getElementById('profile-page').classList.toggle('profile-light');
            themeButton.setAttribute('aria-label', light ? 'Switch to dark theme' : 'Switch to light theme');
            themeButton.setAttribute('title', light ? 'Switch to dark theme' : 'Switch to light theme');
        });

        const notificationsButton = document.getElementById('profile-notifications-toggle');
        const status = document.getElementById('profile-control-status');
        notificationsButton.addEventListener('click', async () => {
            if (notificationsButton.getAttribute('aria-pressed') === 'true') {
                window.BrakeSafeNearbyAlerts?.disable();
                notificationsButton.setAttribute('aria-pressed', 'false');
                notificationsButton.setAttribute('aria-label', 'Enable nearby notifications');
                notificationsButton.setAttribute('title', 'Enable nearby notifications');
                notificationsButton.querySelector('.notification-slash').style.display = '';
                status.textContent = 'Nearby notifications off.';
                return;
            }
            if (!('Notification' in window)) {
                status.textContent = 'Browser notifications are not available.';
                return;
            }
            let permission;
            try {
                permission = Notification.permission === 'default'
                    ? await Notification.requestPermission()
                    : Notification.permission;
            } catch (error) {
                console.error('Unable to request nearby notification permission.', error);
                status.textContent = 'Unable to request browser notification permission.';
                return;
            }
            if (permission !== 'granted') {
                status.textContent = 'Notification permission was not granted.';
                return;
            }
            if (!await window.BrakeSafeNearbyAlerts?.enable({ status, button: notificationsButton })) {
                return;
            }
            notificationsButton.setAttribute('aria-pressed', 'true');
            notificationsButton.setAttribute('aria-label', 'Disable nearby notifications');
            notificationsButton.setAttribute('title', 'Disable nearby notifications');
            notificationsButton.querySelector('.notification-slash').style.display = 'none';
            status.textContent = 'Nearby notifications on.';
        });
    });
</script>
<style>
    #profile-page.profile-light .profile-card { background-color: #f8fafc; border-color: #cbd5e1; }
    #profile-page.profile-light h1,
    #profile-page.profile-light h2,
    #profile-page.profile-light h3 { color: #0f172a; }
    #profile-page.profile-light p,
    #profile-page.profile-light label { color: #475569; }
    #profile-page.profile-light .profile-input { background-color: #fff; border-color: #cbd5e1; color: #0f172a; }
</style>
@endpush
