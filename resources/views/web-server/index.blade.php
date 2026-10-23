@extends('layouts.admin')

@section('title', 'Web Server - BrakeSafe')
@section('page-title', 'Web Server')

@section('content')
    <style>
        body.web-server-page #sidebar,
        body.web-server-page #sidebar-overlay {
            display: none !important;
        }
        body.web-server-page #main-content {
            width: 100%;
            margin-left: 0 !important;
        }
        body.web-server-page .page-body {
            overflow: hidden;
            padding: 0 !important;
        }
        #web-server-app {
            display: flex;
            height: 100dvh;
            min-height: 0;
            flex-direction: column;
            background: #fff;
        }
        .web-server-toolbar {
            flex: 0 0 80px;
            min-width: 0;
            background: #050505;
        }
        #embedded-server-content {
            position: relative;
            flex: 1 1 auto;
            min-height: 0;
            background: #fff;
        }
        #embedded-server-content iframe {
            width: 100%;
            height: 100%;
            border: 0;
            background: #fff;
        }
        html[data-admin-theme="light"] #web-server-app,
        html[data-admin-theme="light"] #embedded-server-content {
            background: #f1f5f9;
            color: #0f172a;
        }
        html[data-admin-theme="light"] .web-server-toolbar {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        html[data-admin-theme="light"] .web-server-toolbar > a:not([href*="download"]) {
            color: #334155;
        }
        html[data-admin-theme="light"] #server-address-form > div {
            background: #fff;
            border-color: #cbd5e1;
        }
        html[data-admin-theme="light"] #server-address {
            color: #0f172a;
            -webkit-text-fill-color: #0f172a;
        }
        @media (max-width: 480px) {
            .web-server-toolbar {
                flex-basis: 72px;
                padding-left: 12px;
                padding-right: 12px;
            }
        }
    </style>
    <section id="web-server-app" aria-label="BrakeSafe Web Server">
        <section class="web-server-toolbar flex items-center gap-3 border-b border-white/10 px-5 sm:px-7" aria-label="Web server address">
            <a href="{{ route('dashboard') }}" aria-label="Return to Dashboard" title="Return to Dashboard" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-red-400/70">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
            </a>

            <form id="server-address-form" class="flex min-w-0 flex-1 items-center gap-2" novalidate>
                <div class="flex h-12 min-w-0 flex-1 items-center gap-3 rounded-full border border-[#3b3b3b] bg-[#181818] px-4 transition focus-within:border-[#777] focus-within:ring-2 focus-within:ring-white/10">
                    <svg class="h-5 w-5 shrink-0 text-[#888]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input id="server-address" name="server-address" type="text" inputmode="url" autocomplete="url" spellcheck="false" aria-label="ESP32 IP address or web-server URL" aria-describedby="server-address-error" placeholder="ESP32 IP address or web-server URL" class="h-full min-w-0 flex-1 bg-transparent text-sm text-white outline-none placeholder:text-[#858585] sm:text-base">
                </div>
                <button type="submit" aria-label="Open address" title="Open address" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-600 text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 focus:ring-offset-black">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </button>
            </form>
        </section>

        <section id="embedded-server-content" aria-label="Embedded server content">
            <p id="server-address-error" role="alert" class="absolute left-4 right-4 top-4 z-10 mx-auto hidden max-w-xl rounded-xl border border-red-200 bg-white px-4 py-3 text-sm text-red-700 shadow-lg"></p>
            <p id="server-load-status" class="sr-only" role="status" aria-live="polite"></p>
        </section>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('server-address-form');
            const addressInput = document.getElementById('server-address');
            const contentArea = document.getElementById('embedded-server-content');
            const errorMessage = document.getElementById('server-address-error');
            const loadStatus = document.getElementById('server-load-status');

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const enteredAddress = addressInput.value.trim();
                errorMessage.classList.add('hidden');
                errorMessage.textContent = '';

                if (!enteredAddress) {
                    errorMessage.textContent = 'Enter an ESP32 IP address or web-server URL before opening it.';
                    errorMessage.classList.remove('hidden');
                    addressInput.focus();
                    return;
                }

                const suppliedScheme = enteredAddress.match(/^([a-z][a-z\d+.-]*):/i);
                if (suppliedScheme && !['http', 'https'].includes(suppliedScheme[1].toLowerCase())) {
                    errorMessage.textContent = 'Only HTTP and HTTPS web-server addresses can be opened.';
                    errorMessage.classList.remove('hidden');
                    addressInput.focus();
                    return;
                }

                const addressWithProtocol = /^https?:\/\//i.test(enteredAddress)
                    ? enteredAddress
                    : `http://${enteredAddress}`;
                let parsedAddress;
                try {
                    parsedAddress = new URL(addressWithProtocol);
                } catch (error) {
                    errorMessage.textContent = 'Enter a valid web-server address.';
                    errorMessage.classList.remove('hidden');
                    addressInput.focus();
                    return;
                }

                if (!['http:', 'https:'].includes(parsedAddress.protocol) || parsedAddress.username || parsedAddress.password) {
                    errorMessage.textContent = 'Enter a valid HTTP or HTTPS address without embedded credentials.';
                    errorMessage.classList.remove('hidden');
                    addressInput.focus();
                    return;
                }

                let embeddedContent = document.getElementById('server-content-frame');
                if (!embeddedContent) {
                    embeddedContent = document.createElement('iframe');
                    embeddedContent.id = 'server-content-frame';
                    embeddedContent.title = 'Embedded web-server content';
                    embeddedContent.referrerPolicy = 'no-referrer';
                    embeddedContent.addEventListener('load', () => {
                        loadStatus.textContent = `Loaded ${parsedAddress.origin} in the embedded server area.`;
                    }, { once: true });
                    contentArea.append(embeddedContent);
                }

                loadStatus.textContent = `Opening ${parsedAddress.origin}.`;
                embeddedContent.src = parsedAddress.href;
            });
        });
    </script>
@endsection
