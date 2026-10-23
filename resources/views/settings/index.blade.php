@extends('layouts.admin')

@section('title', 'Settings - BrakeSafe')
@section('page-title', 'Settings')

@section('content')
    <div class="space-y-6">
        <div class="glass rounded-[32px] p-8 border border-white/10 shadow-[0_35px_120px_rgba(0,0,0,0.28)]">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-white">System Settings</h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-400">Configure BRAKESAFE parameters and preferences.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full bg-[#061a2c] px-4 py-2 text-sm text-cyan-300 border border-cyan-500/15 shadow-[0_10px_30px_rgba(6,182,212,0.12)]">
                        <svg class="w-5 h-5 text-cyan-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c3.866 0 7 3.134 7 7 0 5.25-7 13-7 13S5 15.25 5 10c0-3.866 3.134-7 7-7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        <span>Profile</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="glass rounded-[32px] border border-white/10 p-8 shadow-[0_35px_120px_rgba(0,0,0,0.16)] bg-[#06101f]">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-[#081928] text-cyan-300 shadow-[0_20px_40px_rgba(6,182,212,0.16)]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9 9 0 0112 15a9 9 0 016.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-white">Profile</h2>
                        <p class="text-sm text-slate-400">Your account and role details.</p>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    @php $user = auth()->user(); @endphp
                    <div class="rounded-[24px] border border-white/10 bg-slate-950/70 p-5 shadow-[inset_0_0_24px_rgba(14,165,233,0.08)]">
                        <label class="text-xs uppercase tracking-[0.3em] text-slate-500">Full Name</label>
                        <input type="text" value="{{ $user?->full_name ?? 'Admin' }}" disabled class="mt-3 w-full rounded-3xl border border-white/10 bg-[#07121f] px-4 py-3 text-sm text-black-important focus:outline-none" />
                    </div>
                    <div class="rounded-[24px] border border-white/10 bg-slate-950/70 p-5 shadow-[inset_0_0_24px_rgba(14,165,233,0.08)]">
                        <label class="text-xs uppercase tracking-[0.3em] text-slate-500">Email</label>
                        <input type="text" value="{{ $user?->email ?? 'admin@example.com' }}" disabled class="mt-3 w-full rounded-3xl border border-white/10 bg-[#07121f] px-4 py-3 text-sm text-black-important focus:outline-none" />
                    </div>
                    <div class="rounded-[24px] border border-white/10 bg-slate-950/70 p-5 shadow-[inset_0_0_24px_rgba(14,165,233,0.08)] sm:col-span-2">
                        <label class="text-xs uppercase tracking-[0.3em] text-slate-500">Role</label>
                        <input type="text" value="{{ $user?->role ?? 'Admin' }}" disabled class="mt-3 w-full rounded-3xl border border-white/10 bg-[#07121f] px-4 py-3 text-sm text-black-important focus:outline-none" />
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="glass rounded-[32px] border border-white/10 p-8 shadow-[0_35px_120px_rgba(0,0,0,0.16)] bg-[#06101f]">
                    <div class="flex items-center gap-3 rounded-[24px] border border-amber-500/10 bg-[#171f2b] p-4 shadow-[inset_0_0_20px_rgba(245,158,11,0.12)]">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-3xl bg-amber-500/10 text-amber-300">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 2c-1.657 0-3 1.343-3 3v1h6v-1c0-1.657-1.343-3-3-3z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-white">Change Password</h2>
                            <p class="text-sm text-slate-400">Update your login password with strength and match feedback.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('settings.password') }}" class="mt-8 space-y-6" novalidate>
                        @csrf
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-slate-300">Current Password</label>
                            <input id="current-password" name="current_password" type="password" autocomplete="current-password" class="w-full rounded-[20px] border border-white/10 bg-[#07121f] px-4 py-3 text-sm text-black-important outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/10" style="color:#000000 !important; -webkit-text-fill-color:#000000 !important; caret-color:#000000 !important;" required />
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-slate-300">New Password</label>
                            <input id="new-password" name="password" type="password" autocomplete="new-password" class="w-full rounded-[20px] border border-white/10 bg-[#07121f] px-4 py-3 text-sm text-black-important outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/10" style="color:#000000 !important; -webkit-text-fill-color:#000000 !important; caret-color:#000000 !important;" required />
                            <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                                <span>Password strength:</span>
                                <span id="password-strength" class="font-semibold text-cyan-300">Enter a password</span>
                            </div>
                            <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                                <div id="strength-bar" class="h-full rounded-full bg-cyan-400 transition-all duration-200" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-slate-300">Confirm New Password</label>
                            <input id="confirm-password" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-[20px] border border-white/10 bg-[#07121f] px-4 py-3 text-sm text-black-important outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/10" style="color:#000000 !important; -webkit-text-fill-color:#000000 !important; caret-color:#000000 !important;" required />
                            <p id="match-indicator" class="mt-2 text-sm font-medium text-slate-400">Passwords must match.</p>
                        </div>
                        <div class="mt-4 flex items-center justify-center">
                            <div class="flex items-center gap-3">

                                <button type="submit" class="hidden md:inline-flex items-center gap-2 rounded-full bg-rose-500 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-rose-400 transition-colors">
                                    Save new password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="glass rounded-[32px] border border-white/10 p-8 shadow-[0_35px_120px_rgba(0,0,0,0.16)] bg-[#06101f]">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <div class="flex items-center gap-3 rounded-[24px] border border-cyan-500/10 bg-[#071623] p-4 shadow-[inset_0_0_20px_rgba(6,182,212,0.12)]">
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-3xl bg-amber-500/10 text-amber-300">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM11 9a1 1 0 112 0v3a1 1 0 11-2 0V9zm1 6a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-white">Brake Detection Thresholds (m/s²)</h2>
                                <p class="text-sm text-slate-400">Adjust the sensitivity levels for event classification.</p>
                            </div>
                        </div>

                        <div class="mt-8 space-y-6">
                            @foreach($thresholds as $label => $value)
                                @php
                                    $display = ucfirst(str_replace('_', ' ', $label));
                                    $inputId = 'threshold-' . $label;
                                @endphp
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between gap-4 text-sm text-slate-300">
                                        <span>{{ $display }}</span>
                                        <span id="value-{{ $label }}" class="font-semibold text-cyan-300">{{ number_format($value, 1) }} m/s²</span>
                                    </div>
                                    <input
                                        id="{{ $inputId }}"
                                        type="range"
                                        name="{{ $label }}"
                                        min="0"
                                        max="15"
                                        step="0.1"
                                        value="{{ $value }}"
                                        class="w-full accent-cyan-400"
                                        oninput="document.getElementById('value-{{ $label }}').textContent = this.value + ' m/s²'"
                                    />
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-cyan-500 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Save changes</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
    const passwordInput = document.getElementById('new-password');
    const confirmInput = document.getElementById('confirm-password');
    const strengthText = document.getElementById('password-strength');
    const strengthBar = document.getElementById('strength-bar');
    const matchIndicator = document.getElementById('match-indicator');
    const passwordSubmit = document.getElementById('password-submit');

    function getPasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        return score;
    }

    function updateStrength(password) {
        const score = getPasswordStrength(password);
        const levels = [
            {label: 'Very weak', color: '#f97316', width: '20%'},
            {label: 'Weak', color: '#f97316', width: '40%'},
            {label: 'Moderate', color: '#facc15', width: '60%'},
            {label: 'Strong', color: '#22c55e', width: '80%'},
            {label: 'Very strong', color: '#14b8a6', width: '100%'},
        ];

        const index = Math.min(Math.max(score, 0), 4);
        const level = levels[index];
        strengthText.textContent = password ? level.label : 'Enter a password';
        strengthText.style.color = password ? level.color : '';
        strengthBar.style.width = password ? level.width : '0%';
        strengthBar.style.backgroundColor = level.color;
    }

    function updateMatch() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (!confirm) {
            matchIndicator.textContent = 'Passwords must match.';
            matchIndicator.className = 'mt-2 text-sm font-medium text-slate-400';
            passwordSubmit.disabled = false;
            return;
        }

        if (password === confirm) {
            matchIndicator.textContent = 'Passwords match.';
            matchIndicator.className = 'mt-2 text-sm font-medium text-emerald-400';
            passwordSubmit.disabled = false;
        } else {
            matchIndicator.textContent = 'Passwords do not match.';
            matchIndicator.className = 'mt-2 text-sm font-medium text-rose-400';
            passwordSubmit.disabled = true;
        }
    }

    passwordInput?.addEventListener('input', (event) => {
        updateStrength(event.target.value);
        updateMatch();
    });

    confirmInput?.addEventListener('input', updateMatch);
    document.addEventListener('DOMContentLoaded', () => {
        updateStrength(passwordInput?.value || '');
        updateMatch();
    });
</script>
@endpush
