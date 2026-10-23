@extends('layouts.admin')

@section('title', 'User Requests - BrakeSafe')
@section('page-title', 'User Requests')

@section('content')
    <div id="registration-requests-page" class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-base text-slate-400">Codes are emailed automatically when users submit requests. Resend becomes available when a code expires or delivery fails.</p>
            <nav class="flex flex-wrap gap-2" aria-label="Request management">
                <a href="{{ route('registration-requests.index') }}" aria-current="page" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-cyan-500 px-4 text-base font-semibold text-slate-950">Registration Requests</a>
                <a href="{{ route('users.password-requests.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-white/10 bg-[#111317] px-4 text-base font-semibold text-slate-200 transition hover:bg-[#1a1d22]">Password Resets</a>
            </nav>
        </header>

        @if(session('status'))
            <div role="status" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#111317]" aria-label="User access requests">
            @if($registrationRequests->isEmpty())
                <div class="px-5 py-12 text-center">
                    <p class="text-lg font-medium text-white">No requests yet</p>
                    <p class="mt-1 text-sm text-slate-400">Verified requests will appear here for review.</p>
                </div>
            @else
                <div class="divide-y divide-white/5">
                    @foreach($registrationRequests as $registrationRequest)
                        <article class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold text-white">{{ $registrationRequest->first_name }} {{ $registrationRequest->last_name }}</h2>
                                <p class="mt-1 break-all text-base text-slate-300">{{ $registrationRequest->email }}</p>
                                <p class="mt-1 break-all text-sm text-slate-400">
                                    Public token: <span class="font-mono">{{ $registrationRequest->public_token }}</span>
                                </p>
                                <p class="mt-1 text-sm uppercase tracking-wider text-slate-500">
                                    {{ str_replace('_', ' ', $registrationRequest->status) }}
                                    · Submitted {{ $registrationRequest->created_at?->format('M j, Y g:i A') }}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if(in_array($registrationRequest->status, ['pending', 'pending_verification'], true) && (! $registrationRequest->code_hash || ! $registrationRequest->code_expires_at || $registrationRequest->code_expires_at->isPast()))
                                    <button type="button" data-request-action="send-verification-code" data-request-id="{{ $registrationRequest->id }}" class="min-h-10 rounded-lg bg-blue-600 px-4 text-base font-semibold text-white transition hover:bg-blue-500">Send verification code</button>
                                @elseif($registrationRequest->status === 'pending_review')
                                    <button type="button" data-request-action="approve" data-request-id="{{ $registrationRequest->id }}" class="min-h-10 rounded-lg bg-emerald-600 px-4 text-base font-semibold text-white transition hover:bg-emerald-500">Approve</button>
                                    <button type="button" data-request-action="reject" data-request-id="{{ $registrationRequest->id }}" class="min-h-10 rounded-lg border border-red-500/40 px-4 text-base font-semibold text-red-300 transition hover:bg-red-500/10">Reject</button>
                                @endif
                                <button type="button" data-request-action="delete" data-request-id="{{ $registrationRequest->id }}" class="min-h-10 rounded-lg border border-slate-500/50 px-4 text-base font-semibold text-slate-300 transition hover:border-red-500 hover:bg-red-500/10 hover:text-red-300">Delete request</button>
                            </div>
                        </article>
                    @endforeach
                </div>
                @if($registrationRequests->hasPages())
                    <div class="border-t border-white/10 px-4 py-3">{{ $registrationRequests->links() }}</div>
                @endif
            @endif
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const endpoint = @json(url('/registration-requests'));
            const requestJson = async (id, action) => {
                const requestUrl = action === 'delete'
                    ? `${endpoint}/${encodeURIComponent(id)}`
                    : `${endpoint}/${encodeURIComponent(id)}/${action}`;
                const response = await fetch(requestUrl, {
                    method: action === 'delete' ? 'DELETE' : 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                });
                const result = await response.json();
                if (!response.ok) {
                    const message = result.errors ? Object.values(result.errors).flat()[0] : result.message;
                    throw new Error(message || 'The request could not be completed.');
                }
                return result;
            };
            const showAlert = (options) => {
                if (!window.Swal) {
                    console.error('SweetAlert is unavailable; cannot review access requests.');
                    return Promise.reject(new Error('SweetAlert could not be loaded.'));
                }
                return window.Swal.fire(options);
            };

            document.getElementById('registration-requests-page').addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-request-action]');
                if (!button) return;
                const action = button.dataset.requestAction;
                const actionLabels = {
                    'send-verification-code': {
                        title: 'Send verification code?',
                        text: 'A six-digit code will be emailed to the applicant and expire in 10 minutes. This replaces any previous code.',
                        confirm: 'Send code',
                    },
                    approve: {
                        title: 'Approve this request?',
                        text: 'This creates the user account and issues a one-time BrakeSafeCode.',
                        confirm: 'Approve request',
                    },
                    reject: {
                        title: 'Reject this request?',
                        text: 'The requester will not be able to use this request.',
                        confirm: 'Reject request',
                    },
                    delete: {
                        title: 'Delete this access request?',
                        text: 'This permanently deletes the request and any stored verification code. It will not delete a user account already created from an approved request.',
                        confirm: 'Delete request',
                    },
                }[action];
                const confirmation = await showAlert({
                    icon: action === 'approve' ? 'question' : action === 'send-verification-code' ? 'info' : 'warning',
                    title: actionLabels.title,
                    text: actionLabels.text,
                    showCancelButton: true,
                    confirmButtonText: actionLabels.confirm,
                    confirmButtonColor: action === 'approve' ? '#059669' : action === 'send-verification-code' ? '#2563eb' : '#dc2626',
                });
                if (!confirmation.isConfirmed) return;

                try {
                    const result = await requestJson(button.dataset.requestId, action);
                    if (action === 'send-verification-code') {
                        button.remove();
                        await showAlert({ icon: 'success', title: 'Verification code sent', text: result.message });
                    } else if (action === 'delete') {
                        await showAlert({ icon: 'success', title: 'Request deleted', text: result.message });
                    } else if (result.brakesafe_code) {
                        const code = document.createElement('input');
                        code.readOnly = true;
                        code.value = result.brakesafe_code;
                        code.className = 'mt-3 w-full rounded-lg border border-slate-500 px-3 py-2 text-center font-mono tracking-wider';
                        await showAlert({
                            icon: 'success',
                            title: 'Request approved',
                            html: '<p id="approval-message"></p><p class="mt-3 text-sm">Give this one-time BrakeSafeCode to the requester. It will not be shown again.</p>',
                            didOpen: () => {
                                window.Swal.getPopup().querySelector('#approval-message').textContent = result.message;
                                window.Swal.getPopup().querySelector('#approval-message').after(code);
                                code.addEventListener('click', () => code.select());
                                code.select();
                            },
                            confirmButtonText: 'Done',
                        });
                    } else {
                        await showAlert({ icon: 'success', title: 'Request rejected', text: result.message });
                    }
                    window.location.reload();
                } catch (error) {
                    await showAlert({ icon: 'error', title: 'Unable to process request', text: error.message });
                }
            });
        });
    </script>
@endsection
