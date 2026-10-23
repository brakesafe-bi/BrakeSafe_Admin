@extends('layouts.admin')

@section('title', 'Password Resets - BrakeSafe')
@section('page-title', 'Password Resets')

@section('content')
    <div id="password-reset-requests-page" class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-base text-slate-400">Codes are emailed automatically when users submit reset requests. You can resend a code if needed.</p>
                <p class="mt-1 text-sm text-slate-500">Codes expire after 10 minutes. Sending another code invalidates the previous one.</p>
            </div>
            <nav class="flex flex-wrap gap-2" aria-label="Request management">
                <a href="{{ route('registration-requests.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-white/10 bg-[#111317] px-4 text-base font-semibold text-slate-200 transition hover:bg-[#1a1d22]">Registration Requests</a>
                <a href="{{ route('users.password-requests.index') }}" aria-current="page" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-cyan-500 px-4 text-base font-semibold text-slate-950">Password Resets</a>
            </nav>
        </header>

        @if(session('status'))
            <div role="status" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#111317]" aria-label="Password reset requests">
            @if($passwordResetRequests->isEmpty())
                <div class="px-5 py-12 text-center">
                    <p class="text-lg font-medium text-white">No password reset requests</p>
                    <p class="mt-1 text-sm text-slate-400">Requests submitted by BrakeSafe users will appear here.</p>
                </div>
            @else
                <div class="divide-y divide-white/5">
                    @foreach($passwordResetRequests as $passwordResetRequest)
                        <article class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                            <div class="min-w-0">
                                <h2 class="break-all text-lg font-semibold text-white">{{ $passwordResetRequest->email }}</h2>
                                <p class="mt-1 text-base text-slate-400">
                                    Requested {{ $passwordResetRequest->created_at ? \Illuminate\Support\Carbon::parse($passwordResetRequest->created_at)->format('M j, Y g:i A') : 'recently' }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    @if($passwordResetRequest->code_expires_at && \Illuminate\Support\Carbon::parse($passwordResetRequest->code_expires_at)->isFuture())
                                        Verification code sent; expires {{ \Illuminate\Support\Carbon::parse($passwordResetRequest->code_expires_at)->format('M j, Y g:i A') }}
                                    @else
                                        No active verification code
                                    @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('users.password-requests.send-code') }}" class="shrink-0">
                                @csrf
                                <input type="hidden" name="email" value="{{ $passwordResetRequest->email }}">
                                <button type="submit" data-sweetalert-confirm data-swal-title="Send a password reset code?" data-swal-text="A new code will replace and invalidate the previous code." data-swal-confirm-button-text="Send code" data-swal-cancel-button-text="Cancel" class="min-h-10 w-full rounded-lg bg-blue-600 px-4 text-base font-semibold text-white transition hover:bg-blue-500 sm:w-auto">
                                    Send verification code
                                </button>
                            </form>
                        </article>
                    @endforeach
                </div>
                @if($passwordResetRequests->hasPages())
                    <div class="border-t border-white/10 px-4 py-3">{{ $passwordResetRequests->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection
