<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        document.documentElement.classList.add('theme-pending');
        try {
            const isLightTheme = localStorage.getItem('brakesafe-admin-theme') === 'light';
            document.documentElement.dataset.adminTheme = isLightTheme ? 'light' : 'dark';
            document.documentElement.classList.toggle('dark', !isLightTheme);
            document.documentElement.style.colorScheme = isLightTheme ? 'light' : 'dark';
        } catch (error) {
            console.warn('Unable to restore the saved theme preference.', error);
            document.documentElement.dataset.adminTheme = 'dark';
            document.documentElement.classList.add('dark');
            document.documentElement.style.colorScheme = 'dark';
        }
        try {
            if (window.innerWidth >= 1024 && localStorage.getItem('brakesafe.sidebarCollapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {
            console.error('Unable to restore the saved sidebar state.', error);
        }
        try {
            if (sessionStorage.getItem('brakesafe.pageTransition') === '1') {
                document.documentElement.classList.add('page-transition-pending');
            }
        } catch (error) {
            console.error('Unable to restore the page transition state.', error);
        }
    </script>
    <style>
        html {
            background-color: #0b0d10;
        }
        html[data-admin-theme="light"] {
            background-color: #f1f5f9;
        }
        html.theme-pending body,
        html.page-transition-pending body {
            visibility: hidden;
        }
    </style>
    <link rel="icon" type="image/png" href="{{ asset('administrator/logo.png') }}">
    <title>@yield('title', 'BrakeSafe Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/heroicons@2.0.18/24/solid/index.min.css" />
    <script>
        window.BrakeSafeMapAttribution = {
            move(map, targetId) {
                const target = document.getElementById(targetId);
                const attribution = map?.attributionControl?.getContainer();
                if (!target || !attribution) {
                    console.error(`Unable to place map attribution outside the map: ${targetId}`);
                    return;
                }

                target.append(attribution);
                target.hidden = false;
            },
        };
    </script>
    <style>
        html.theme-pending {
            background-color: #0b0d10;
        }
        html.theme-pending[data-admin-theme="light"] {
            background-color: #f1f5f9;
        }
        html.theme-pending body {
            visibility: hidden;
        }
        html.page-transition-pending body {
            visibility: hidden;
        }
        #brakesafe-page-transition {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: radial-gradient(ellipse at 50% 48%, rgba(30, 41, 59, 0.94), rgba(3, 8, 16, 0.98) 72%);
            opacity: 0;
            pointer-events: none;
            transition: opacity 320ms ease;
        }
        #brakesafe-page-transition[hidden] {
            display: none;
        }
        #brakesafe-page-transition.is-active {
            opacity: 1;
        }
        #brakesafe-page-transition .transition-scene {
            position: relative;
            width: min(760px, 78vw);
            height: 310px;
            overflow: hidden;
        }
        #brakesafe-page-transition .transition-alert {
            position: absolute;
            z-index: 2;
            top: 12px;
            left: 50%;
            width: max-content;
            max-width: 100%;
            padding: 12px 22px;
            border: 1px solid rgba(248, 113, 113, 0.65);
            border-radius: 14px;
            background: rgba(35, 10, 15, 0.92);
            box-shadow: 0 0 36px rgba(239, 68, 68, 0.22);
            color: #fff;
            text-align: center;
            transform: translate(-50%, -18px);
            opacity: 0;
        }
        #brakesafe-page-transition.is-active .transition-alert {
            animation: apa-transition-alert 3.6s ease both;
        }
        #brakesafe-page-transition .transition-alert strong {
            display: block;
            color: #fca5a5;
            font-size: 1.05rem;
            letter-spacing: 0.16em;
        }
        #brakesafe-page-transition .transition-alert span {
            display: block;
            margin-top: 3px;
            color: #e2e8f0;
            font-size: 0.85rem;
        }
        #brakesafe-page-transition .transition-road {
            position: absolute;
            right: -8%;
            bottom: 62px;
            left: -8%;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(226, 232, 240, 0.45) 15%, rgba(226, 232, 240, 0.45) 85%, transparent);
        }
        #brakesafe-page-transition .transition-road::after {
            position: absolute;
            top: 13px;
            right: 0;
            left: 0;
            height: 1px;
            background: repeating-linear-gradient(90deg, rgba(148, 163, 184, 0.35) 0 28px, transparent 28px 54px);
            content: "";
        }
        #brakesafe-page-transition .transition-car {
            position: absolute;
            bottom: 66px;
            left: 0;
            width: min(310px, 42vw);
            color: #ef4444;
            filter: drop-shadow(0 10px 24px rgba(239, 68, 68, 0.28));
            opacity: 0;
            transform: translateX(-115%);
        }
        #brakesafe-page-transition.is-active .transition-car {
            animation: apa-transition-drive 3.6s cubic-bezier(0.22, 0.7, 0.25, 1) both;
        }
        #brakesafe-page-transition .transition-brake-indicator {
            position: absolute;
            right: 6%;
            bottom: 105px;
            padding: 7px 11px;
            border: 1px solid rgba(248, 113, 113, 0.55);
            border-radius: 999px;
            background: rgba(69, 10, 10, 0.85);
            color: #fecaca;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            opacity: 0;
        }
        #brakesafe-page-transition.is-active .transition-brake-indicator {
            animation: apa-transition-brake 3.6s ease both;
        }
        #brakesafe-page-transition .transition-brake-light {
            filter: drop-shadow(0 0 5px #ef4444);
        }
        #brakesafe-page-transition.is-active .transition-brake-light {
            animation: apa-transition-brake-light 3.6s ease both;
        }
        #brakesafe-page-transition .transition-speed-line {
            position: absolute;
            right: 90%;
            width: 110px;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(248, 113, 113, 0.8));
            opacity: 0;
        }
        #brakesafe-page-transition .transition-speed-line:nth-of-type(1) { bottom: 150px; }
        #brakesafe-page-transition .transition-speed-line:nth-of-type(2) { bottom: 118px; width: 75px; }
        #brakesafe-page-transition.is-active .transition-speed-line {
            animation: apa-transition-speed 1.25s ease-out both;
        }
        @keyframes apa-transition-alert {
            0%, 8% { opacity: 0; transform: translate(-50%, -18px); }
            16%, 63% { opacity: 1; transform: translate(-50%, 0); }
            78%, 100% { opacity: 0; transform: translate(-50%, -8px); }
        }
        @keyframes apa-transition-drive {
            0%, 15% { opacity: 0; transform: translateX(-115%); }
            24% { opacity: 1; transform: translateX(-95%); }
            60% { opacity: 1; transform: translateX(min(44vw, 400px)); }
            70%, 78% { opacity: 1; transform: translateX(min(44vw, 400px)); }
            86%, 100% { opacity: 0; transform: translateX(min(44vw, 400px)); }
        }
        @keyframes apa-transition-speed {
            0%, 20% { opacity: 0; transform: translateX(-30px); }
            35% { opacity: 0.8; }
            72%, 100% { opacity: 0; transform: translateX(115px); }
        }
        @keyframes apa-transition-brake {
            0%, 60% { opacity: 0; transform: translateY(5px); }
            65%, 92% { opacity: 1; transform: translateY(0); }
            98%, 100% { opacity: 0; transform: translateY(-4px); }
        }
        @keyframes apa-transition-brake-light {
            0%, 60% { filter: drop-shadow(0 0 2px #7f1d1d); }
            65%, 92% { filter: drop-shadow(0 0 13px #ef4444); }
            98%, 100% { filter: drop-shadow(0 0 2px #7f1d1d); }
        }
        @media (max-width: 767px) {
            #brakesafe-page-transition .transition-scene {
                width: calc(100vw - 24px);
                max-width: 600px;
                height: 260px;
            }
            #brakesafe-page-transition .transition-alert {
                top: 8px;
                padding: 10px 16px;
            }
            #brakesafe-page-transition .transition-alert strong {
                font-size: 0.95rem;
            }
            #brakesafe-page-transition .transition-alert span {
                font-size: 0.75rem;
            }
            #brakesafe-page-transition .transition-road {
                bottom: 48px;
            }
            #brakesafe-page-transition .transition-car {
                bottom: 52px;
                width: min(310px, 74vw);
            }
            #brakesafe-page-transition .transition-brake-indicator {
                right: 2%;
                bottom: 84px;
                padding: 6px 9px;
                font-size: 0.62rem;
            }
            #brakesafe-page-transition .transition-speed-line:nth-of-type(1) { bottom: 132px; }
            #brakesafe-page-transition .transition-speed-line:nth-of-type(2) { bottom: 108px; }
            #brakesafe-page-transition.is-active .transition-car {
                animation-name: apa-transition-drive-mobile;
            }
            @keyframes apa-transition-drive-mobile {
                0%, 15% { opacity: 0; transform: translateX(-115%); }
                24% { opacity: 1; transform: translateX(-95%); }
                60%, 70%, 78% {
                    opacity: 1;
                    transform: translateX(calc(100vw - min(310px, 74vw) - 24px));
                }
                86%, 100% {
                    opacity: 0;
                    transform: translateX(calc(100vw - min(310px, 74vw) - 24px));
                }
            }
        }
        @media (min-width: 768px) and (max-width: 1023px) {
            #brakesafe-page-transition .transition-scene {
                width: calc(100vw - 48px);
                max-width: 700px;
            }
            #brakesafe-page-transition .transition-car {
                width: min(310px, 42vw);
            }
            #brakesafe-page-transition.is-active .transition-car {
                animation-name: apa-transition-drive-tablet;
            }
            @keyframes apa-transition-drive-tablet {
                0%, 15% { opacity: 0; transform: translateX(-115%); }
                24% { opacity: 1; transform: translateX(-95%); }
                60%, 70%, 78% { opacity: 1; transform: translateX(calc(100vw - min(310px, 42vw) - 48px)); }
                86%, 100% { opacity: 0; transform: translateX(calc(100vw - min(310px, 42vw) - 48px)); }
            }
        }
        @media (prefers-reduced-motion: reduce) {
            #brakesafe-page-transition,
            #brakesafe-page-transition * {
                animation: none !important;
                transition: none !important;
            }
            #brakesafe-page-transition .transition-alert,
            #brakesafe-page-transition .transition-car,
            #brakesafe-page-transition .transition-brake-indicator {
                opacity: 1;
                transform: none;
            }
        }
        /* Ensure header stays fixed and page body scrolls only below it */
        html, body { height: 100%; min-height: 100vh; margin: 0; }
        body { overflow: hidden; overflow-x: hidden; }
        #app-shell { display: flex; min-height: 100vh; height: 100vh; }
        #main-content { display: flex; flex-direction: column; height: 100%; min-width: 0; min-height: 0; }
        .page-body { flex: 1 1 auto; min-width: 0; min-height: 0; overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; touch-action: pan-y; scrollbar-width: none; -ms-overflow-style: none; }
        .page-body::-webkit-scrollbar { display: none; }
        @media (max-width: 1023px) {
            #main-content { transition: none !important; }
        }
        @media (min-width: 1024px) {
            #admin-topbar { height: 6.8rem; }
        }
        .map-attribution-outside .leaflet-control-attribution {
            max-width: min(28rem, calc(100vw - 5rem));
            margin: 0;
            padding: 0.2rem 0.45rem;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 0.4rem;
            background: rgba(9, 10, 12, 0.82);
            color: #cbd5e1;
            font-size: 0.625rem;
            line-height: 1.4;
            text-align: right;
            white-space: normal;
            backdrop-filter: blur(6px);
            float: right;
        }
        .map-attribution-outside .leaflet-control-attribution a {
            color: #67e8f9;
        }
        .map-attribution-outside .leaflet-control-attribution a:hover {
            color: #a5f3fc;
            text-decoration: underline;
        }
        .map-attribution-outside {
            min-height: 1.75rem;
            overflow: auto;
            padding-top: 0.25rem;
        }

        /* Mobile sidebar overlay and visibility */
        #sidebar { transform: translateX(-100%) !important; }
        #sidebar-overlay { opacity: 0; transition: opacity .3s ease; }
        html.sidebar-visible #sidebar { transform: translateX(0) !important; }
        .sidebar-visible #sidebar-overlay { display: block; opacity: 1; }
        @media (max-width: 1023px) {
            #sidebar,
            #sidebar.sidebar-collapsed {
                width: min(100%, 18rem) !important;
                left: -100% !important;
                transform: none !important;
            }
            html.sidebar-visible #sidebar {
                left: 0 !important;
                transform: none !important;
            }
        }

        @media (max-width: 639px) {
            #dashboard-root .dashboard-location-heading {
                align-items: stretch;
                flex-direction: column;
                gap: 0;
            }

            #dashboard-root .dashboard-location-icon {
                display: flex;
            }

            #dashboard-root .dashboard-location-title {
                font-size: 1.125rem;
            }

            #dashboard-root #use-gps {
                align-self: flex-start;
                min-height: 3.125rem;
                margin-top: 1.5rem;
                background-color: #e8232a;
                border-color: #e8232a;
                color: #fff;
                padding-right: 1rem;
                padding-left: 1rem;
                font-size: 1rem;
            }

            #dashboard-root .dashboard-location-details {
                display: flex;
                flex-direction: column;
                gap: 1.25rem;
                margin-top: 1.25rem;
                padding-top: 1.25rem;
                border-top: 1px solid rgba(255, 255, 255, 0.12);
            }

            #dashboard-root .dashboard-metrics {
                row-gap: 2rem;
                column-gap: 1rem;
            }

            #dashboard-root .dashboard-metric {
                min-width: 0;
                padding: 0;
                border: 0;
                background: transparent;
            }

            #dashboard-root .dashboard-metric-output {
                margin-top: 0.35rem;
                font-size: 1rem;
                line-height: 1.4;
            }

            #dashboard-root #compass-status {
                margin-top: 0.35rem;
            }

            #dashboard-root .dashboard-local-time {
                margin-top: 1.75rem;
                padding: 1.25rem 0 0;
                border-top: 1px solid rgba(255, 255, 255, 0.12);
                border-right: 0;
                border-bottom: 0;
                border-left: 0;
                border-radius: 0;
                background: transparent;
            }

            #dashboard-root #device-local-time {
                font-size: 1.25rem;
                color: #fff;
            }

            #dashboard-root #device-direction {
                color: #f8fafc;
                font-weight: 600;
            }

            #dashboard-root #device-local-date {
                margin-top: 0.25rem;
                font-size: 0.875rem;
            }

            #dashboard-root #location-status {
                margin-top: 0.625rem;
            }

            #dashboard-root #compass-indicator {
                width: 3rem;
                height: 3rem;
                border: 0;
                color: #475569;
            }

            #dashboard-root #compass-needle {
                opacity: 0.65;
            }

            #dashboard-root .dashboard-map-frame {
                height: 18rem;
                margin-top: 1.5rem;
                border-color: rgba(255, 255, 255, 0.14);
                background-color: #171719;
            }

            #dashboard-root .dashboard-map-controls {
                position: absolute;
                top: 0.875rem;
                left: 0.875rem;
                z-index: 600;
                display: flex;
                flex-direction: column;
                gap: 1px;
            }

            #dashboard-root .dashboard-map-controls button {
                width: 2.25rem;
                height: 2.25rem;
                border: 0;
                border-radius: 0;
                background: #a3a3a3;
                color: #111;
            }

            #dashboard-root .dashboard-map-controls button:first-child {
                border-radius: 0.25rem 0.25rem 0 0;
            }

            #dashboard-root .dashboard-map-controls button:last-child {
                border-radius: 0 0 0.25rem 0.25rem;
            }

            #dashboard-root #map-empty-state {
                align-items: flex-end;
                padding: 0.75rem 1.25rem;
                background: transparent;
            }

            #dashboard-root #map-empty-state span {
                max-width: 24rem;
                padding: 0.875rem 1rem;
                border: 1px solid rgba(255, 255, 255, 0.17);
                border-radius: 0.75rem;
                background: rgba(8, 9, 11, 0.94);
                color: #e2e8f0;
                font-size: 1rem;
                line-height: 1.5;
            }
        }
        @media (min-width: 1024px) {
            #sidebar { transform: translateX(0) !important; }
            #sidebar-overlay { display: none !important; }
            #dashboard-root > header,
            #brake-events-page > header,
            #analytics-page > header,
            #apa-page > header,
            #profile-page > header {
                position: fixed;
                top: 0;
                right: 0;
                left: 280px;
                z-index: 900;
                box-sizing: border-box;
                height: 109px;
                min-height: 109px;
                margin: 0;
                padding: 1rem 2rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.12);
                background: #090a0c;
                isolation: isolate;
                transition: left 300ms ease;
            }
            html.sidebar-collapsed #dashboard-root > header,
            html.sidebar-collapsed #brake-events-page > header,
            html.sidebar-collapsed #analytics-page > header,
            html.sidebar-collapsed #apa-page > header,
            html.sidebar-collapsed #profile-page > header {
                left: 80px;
            }
            body:has(#dashboard-root, #brake-events-page, #analytics-page, #apa-page, #profile-page) .page-body {
                padding-top: 7rem !important;
            }
            html[data-admin-theme="light"] #dashboard-root > header,
            html[data-admin-theme="light"] #brake-events-page > header,
            html[data-admin-theme="light"] #analytics-page > header,
            html[data-admin-theme="light"] #apa-page > header,
            html[data-admin-theme="light"] #profile-page > header {
                background: #f8fafc;
                border-color: #cbd5e1;
            }
            html.sidebar-collapsed #sidebar {
                width: 80px !important;
            }
            html.sidebar-collapsed #sidebar .nav-label,
            html.sidebar-collapsed #sidebar .button-label,
            html.sidebar-collapsed #sidebar .status-text,
            html.sidebar-collapsed #sidebar .logo-text {
                display: none !important;
            }
            html.sidebar-collapsed #live-update-indicator {
                display: none !important;
            }
            html.sidebar-collapsed #main-content {
                margin-left: 80px !important;
            }
            #profile-sidebar [data-admin-avatar] {
                display: none;
            }
            #sidebar.sidebar-collapsed #live-update-indicator {
                display: none !important;
            }
            html.sidebar-collapsed #profile-sidebar,
            #sidebar.sidebar-collapsed #profile-sidebar {
                display: flex !important;
                justify-content: center;
                padding: 0.25rem !important;
                border-color: transparent !important;
                background: transparent !important;
            }
            html.sidebar-collapsed #profile-sidebar [data-admin-avatar],
            #sidebar.sidebar-collapsed #profile-sidebar [data-admin-avatar] {
                display: flex !important;
            }
            html.sidebar-collapsed #profile-sidebar .sidebar-profile-details,
            #sidebar.sidebar-collapsed #profile-sidebar .sidebar-profile-details {
                display: none !important;
            }
            body.dashboard-page #admin-topbar {
                display: none !important;
            }
            #sidebar-close,
            #admin-topbar #menu-toggle,
            #brake-events-nav-toggle,
            #analytics-nav-toggle,
            #dashboard-root #menu-toggle,
            #profile-page #menu-toggle,
            #apa-page #menu-toggle {
                display: none !important;
            }
        }

        /* Make logout compact when sidebar collapsed */
        #sidebar.sidebar-collapsed form.sidebar-footer-logout {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        #sidebar.sidebar-collapsed form.sidebar-footer-logout button.btn-danger {
            width: 40px !important;
            height: 40px;
            padding: 0 !important;
            gap: 0;
        }

        @keyframes sidebar-nav-item-pop {
            0% { opacity: 0.45; transform: translateY(12px) scale(0.96); }
            65% { opacity: 1; transform: translateY(-4px) scale(1.02); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        #sidebar.sidebar-items-entering .nav-item {
            animation: sidebar-nav-item-pop 480ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
        }
        #sidebar.sidebar-items-entering .sidebar-footer-item {
            animation: sidebar-nav-item-pop 480ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
        }
        #sidebar.sidebar-items-entering .nav-item:nth-child(2) { animation-delay: 60ms; }
        #sidebar.sidebar-items-entering .nav-item:nth-child(3) { animation-delay: 120ms; }
        #sidebar.sidebar-items-entering .nav-item:nth-child(4) { animation-delay: 180ms; }
        #sidebar.sidebar-items-entering .nav-item:nth-child(5) { animation-delay: 240ms; }
        #sidebar.sidebar-items-entering .nav-item:nth-child(6) { animation-delay: 300ms; }
        #sidebar.sidebar-items-entering .nav-item:nth-child(7) { animation-delay: 360ms; }
        #sidebar.sidebar-items-entering .sidebar-footer-live-update { animation-delay: 420ms; }
        #sidebar.sidebar-items-entering .sidebar-footer-profile { animation-delay: 480ms; }
        #sidebar.sidebar-items-entering .sidebar-footer-logout { animation-delay: 540ms; }
        #sidebar.sidebar-items-entering .sidebar-footer-collapse { animation-delay: 600ms; }
        @media (prefers-reduced-motion: reduce) {
            #sidebar.sidebar-items-entering .nav-item,
            #sidebar.sidebar-items-entering .sidebar-footer-item {
                animation: none;
            }
        }

        /* Smooth mobile content padding */
        @media (max-width: 767px) {
            .page-body { padding-left: 1rem; padding-right: 1rem; }
        }
        @media (max-width: 639px) {
            #admin-user-badge {
                max-width: none;
            }
            #admin-user-badge .admin-user-name {
                display: none;
            }

            body.dashboard-page #admin-topbar {
                display: none;
            }

            #dashboard-root > header,
            #brake-events-page > header,
            #analytics-page > header,
            #apa-page > header {
                position: sticky !important;
                top: -1rem !important;
                z-index: 1000 !important;
                isolation: isolate;
                background-color: #090a0c !important;
            }

            #dashboard-root > header {
                top: 0 !important;
                margin-top: -1rem;
                margin-bottom: 3.5rem;
                transform: translateY(-1rem);
            }

            #brake-events-page > header,
            #analytics-page > header,
            #apa-page > header {
                top: 0 !important;
                margin-top: -1rem;
                transform: translateY(-1rem);
            }

            #dashboard-root > header,
            #brake-events-page > header,
            #analytics-page > header,
            #apa-page > header {
                box-sizing: border-box;
                display: flex;
                width: calc(100% + 2rem);
                height: 5.5rem;
                min-height: 5.5rem;
                margin: -1rem -1rem 0;
                padding: 0 1rem;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.14);
                background-color: #090a0c !important;
                transform: translateY(-1rem);
            }

            #dashboard-root > header > div:first-child,
            #brake-events-page > header > div:first-child,
            #analytics-page > header > div:first-child,
            #apa-page > header > div:first-child {
                display: flex;
                min-width: 0;
                flex: 1 1 auto;
                align-items: center;
                gap: 0.5rem;
            }

            #dashboard-root > header h1,
            #brake-events-page > header h1,
            #analytics-page > header h1,
            #apa-page > header h1 {
                margin: 0;
                font-size: 1.375rem;
                line-height: 1.25;
                white-space: nowrap;
            }

            #dashboard-root > header > div:last-child,
            #brake-events-page > header > div:last-child,
            #analytics-page > header > div:last-child,
            #apa-page > header > div:last-child {
                display: flex;
                min-width: 0;
                flex: 0 0 auto;
                flex-wrap: nowrap;
                align-items: center;
                gap: 0.5rem;
            }

            #dashboard-root > header > div:first-child > button,
            #brake-events-page > header > div:first-child > button,
            #analytics-page > header > div:first-child > button,
            #apa-page > header > div:first-child > button,
            #dashboard-root > header > div:last-child > button,
            #brake-events-page > header > div:last-child > button,
            #analytics-page > header > div:last-child > button,
            #apa-page > header > div:last-child > button {
                box-sizing: border-box;
                width: 2.75rem;
                min-width: 2.75rem;
                height: 2.75rem;
                min-height: 2.75rem;
                padding: 0;
                justify-content: center;
                border-radius: 0.75rem;
            }

            #dashboard-root > header > div:last-child > button svg,
            #brake-events-page > header > div:last-child > button svg,
            #analytics-page > header > div:last-child > button svg,
            #apa-page > header > div:last-child > button svg,
            #dashboard-root > header > div:first-child > button svg,
            #brake-events-page > header > div:first-child > button svg,
            #analytics-page > header > div:first-child > button svg,
            #apa-page > header > div:first-child > button svg {
                width: 1.25rem;
                height: 1.25rem;
            }

            #dashboard-root > header > div:last-child > button span,
            #brake-events-page > header > div:last-child > button span,
            #analytics-page > header > div:last-child > button span,
            #apa-page > header > div:last-child > button span {
                display: none;
            }

            #apa-page > header > div:last-child > span {
                display: none;
            }

            #profile-page > header {
                box-sizing: border-box;
                position: sticky;
                top: 0 !important;
                z-index: 1000;
                isolation: isolate;
                display: flex;
                width: calc(100% + 2rem);
                height: 5.5rem;
                min-height: 5.5rem;
                margin: -1rem -1rem 0;
                padding: 0 1rem;
                flex-direction: row;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.14);
                background: #090a0c;
                transform: translateY(-1rem);
            }

            #profile-page > header > div:first-child {
                display: flex;
                min-width: 0;
                flex: 1 1 auto;
                align-items: center;
                gap: 0.5rem;
            }

            #profile-page > header > div:last-child {
                display: flex;
                min-width: 0;
                flex: 0 0 auto;
                align-items: center;
                gap: 0.5rem;
            }

            #profile-page > header h1 {
                margin: 0;
                font-size: 1.375rem;
                line-height: 1.25;
                white-space: nowrap;
            }

            #profile-page > header button {
                box-sizing: border-box;
                width: 2.75rem;
                min-width: 2.75rem;
                height: 2.75rem;
                min-height: 2.75rem;
                padding: 0;
                justify-content: center;
                border-radius: 0.75rem;
            }

            #profile-page > header button svg {
                width: 1.25rem;
                height: 1.25rem;
            }

            #dashboard-map,
            #brake-events-map,
            #analytics-map,
            #apa-map {
                z-index: 0;
                isolation: isolate;
            }
        }
    </style>
</head>
<body class="bg-[#090a0c] text-foreground font-sans {{ request()->routeIs('dashboard') ? 'dashboard-page' : '' }} {{ request()->routeIs('web-server.index') ? 'web-server-page' : '' }}">
    <div id="brakesafe-page-transition" role="status" aria-live="polite" aria-label="APA alert. A vehicle is speeding, then braking." hidden>
        <div class="transition-scene" aria-hidden="true">
            <div class="transition-alert">
                <strong id="transition-alert-title">APA ALERT</strong>
                <span id="transition-alert-message">Accident-prone area ahead</span>
            </div>
            <span class="transition-speed-line"></span>
            <span class="transition-speed-line"></span>
            <div class="transition-brake-indicator">BRAKES ENGAGED</div>
            <div class="transition-road"></div>
            <svg class="transition-car" viewBox="0 0 360 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M30 76L47 64L72 58L102 30C110 22 121 18 134 18H226C241 18 251 24 261 37L282 61L321 68C336 71 346 81 348 94H14C15 85 20 79 30 76Z" fill="currentColor"/>
                <path d="M111 32C117 26 124 24 134 24H171V57H91L111 32Z" fill="#172033"/>
                <path d="M181 24H225C236 24 244 29 251 39L266 57H181V24Z" fill="#172033"/>
                <path class="transition-brake-light" d="M18 79L48 74L54 88H15C15 84 16 81 18 79Z" fill="#FCA5A5"/>
                <path d="M311 73L337 80C343 82 346 86 347 91H314L311 73Z" fill="#7F1D1D"/>
                <path d="M281 67L300 69" stroke="#FCA5A5" stroke-width="4" stroke-linecap="round"/>
                <circle cx="81" cy="93" r="20" fill="#0B0D10"/>
                <circle cx="81" cy="93" r="9" fill="#94A3B8"/>
                <circle cx="288" cy="93" r="20" fill="#0B0D10"/>
                <circle cx="288" cy="93" r="9" fill="#94A3B8"/>
                <path d="M24 97H345" stroke="#F87171" stroke-opacity=".45" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>
    </div>
    <script>
        (() => {
            const transitionKey = 'brakesafe.pageTransition';
            const showLoginWelcome = @json(session('login_success', false));
            const startTransition = (forceAnimation = false, loginWelcome = false) => {
                const transition = document.getElementById('brakesafe-page-transition');
                let shouldAnimate = false;
                try {
                    shouldAnimate = forceAnimation || sessionStorage.getItem(transitionKey) === '1';
                    sessionStorage.removeItem(transitionKey);
                } catch (error) {
                    console.error('Unable to read the page transition state.', error);
                }

                document.documentElement.classList.remove('page-transition-pending');
                document.body.style.removeProperty('visibility');
                if (!shouldAnimate || !transition) return;

                if (loginWelcome) {
                    transition.setAttribute('aria-label', 'BrakeSafe is loading your dashboard.');
                    document.getElementById('transition-alert-title').textContent = 'BRAKESAFE';
                    document.getElementById('transition-alert-message').textContent = 'Loading your dashboard...';
                }
                transition.hidden = false;
                requestAnimationFrame(() => transition.classList.add('is-active'));
                window.setTimeout(() => {
                    transition.classList.remove('is-active');
                    window.setTimeout(() => { transition.hidden = true; }, 350);
                }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 650 : 3600);
            };

            document.addEventListener('click', (event) => {
                if (event.defaultPrevented || event.button !== 0
                    || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
                    || !(event.target instanceof Element)) return;

                const link = event.target.closest('a[href]');
                if (!link || link.target && link.target !== '_self' || link.hasAttribute('download')) return;

                const destination = new URL(link.href, window.location.href);
                if (destination.origin !== window.location.origin
                    || destination.pathname === window.location.pathname
                    && destination.search === window.location.search) return;

                try {
                    sessionStorage.setItem(transitionKey, '1');
                } catch (error) {
                    console.error('Unable to save the page transition state.', error);
                }
            }, true);

            if (showLoginWelcome) {
                document.addEventListener('DOMContentLoaded', async () => {
                    if (!window.Swal) {
                        console.error('SweetAlert is unavailable; continuing with the BrakeSafe loading animation.');
                        startTransition(true, true);
                        return;
                    }

                    await window.Swal.fire({
                        icon: 'success',
                        title: 'Welcome to BrakeSafe',
                        text: @json('Welcome back, ' . (auth()->user()->full_name ?? auth()->user()->name ?? 'Admin') . '!'),
                        confirmButtonText: 'Continue to dashboard',
                        customClass: {
                            popup: 'rounded-3xl',
                        },
                    });
                    startTransition(true, true);
                });
            } else {
                startTransition();
            }
        })();
    </script>
    <div id="app-shell" class="flex min-h-screen">
        <div id="sidebar-overlay" class="hidden fixed inset-0 z-[1000] bg-[#090a0c] lg:hidden" style="z-index: 1000;"></div>
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-[1010] w-full lg:w-[280px] bg-[#07101f] border-r border-[rgba(255,255,255,0.06)] transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out overflow-hidden scrollbar-thin shadow-[0_40px_80px_rgba(0,0,0,0.35)]" style="z-index: 1010;">
            <div class="flex h-full flex-col">
                <div class="p-6 border-b border-[rgba(255,255,255,0.06)]">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-12 h-12 rounded-3xl overflow-hidden bg-[#081520] shadow-lg shadow-cyan-500/15 border border-white/10">
                            <img src="{{ asset('administrator/logo.png') }}" alt="BrakeSafe logo" class="w-full h-full object-cover">
                        </div>
                        <div class="logo-text min-w-0">
                            <h1 class="text-lg font-semibold text-white tracking-[0.08em]">BrakeSafe</h1>
                            <p class="whitespace-nowrap text-xs uppercase tracking-[0.2em] text-slate-500">Admin workspace</p>
                        </div>
                    </div>
                    <button id="sidebar-close" class="p-2 rounded-2xl bg-[#07131f] border border-white/10 text-slate-200 hover:bg-[#0e1a2c] transition-all duration-200" aria-label="Close navigation" aria-expanded="true">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <nav class="px-4 py-3 space-y-2 flex-1 overflow-y-auto">
                @php $navItems = [
                    ['href' => route('dashboard'), 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'active' => request()->routeIs('dashboard')],
                    ['href' => route('profile.edit'), 'label' => 'Profile', 'icon' => 'user', 'active' => request()->routeIs('profile.*')],
                    ['href' => route('registration-requests.index'), 'label' => 'User Requests', 'icon' => 'users', 'active' => request()->routeIs('registration-requests.*')],
                    ['href' => route('brake-events.index'), 'label' => 'Brake Events', 'icon' => 'car-front', 'active' => request()->routeIs('brake-events.*')],
                    ['href' => route('analytics'), 'label' => 'Analytics', 'icon' => 'bar-chart-3', 'active' => request()->routeIs('analytics')],
                    ['href' => route('analytics.apa'), 'label' => 'APA', 'icon' => 'map-pin', 'active' => request()->routeIs('analytics.apa')],
                    ['href' => route('web-server.index'), 'label' => 'Web Server', 'icon' => 'cpu', 'active' => request()->routeIs('web-server.*')],
                ];
                @endphp
                    @foreach($navItems as $item)
                    @php $active = $item['active']; @endphp
                    <a href="{{ $item['href'] }}" aria-current="{{ $active ? 'page' : 'false' }}" class="group nav-item flex items-center gap-3 rounded-[20px] px-4 py-3 transition-all duration-200 text-sm {{ $active ? 'bg-[#0e2738] text-cyan-300 shadow-[0_0_30px_rgba(6,182,212,0.14)]' : 'text-slate-300 hover:bg-[#101d2e] hover:text-white' }}">
                        @php $icon = $item['icon']; @endphp
                        @switch($icon)
                            @case('layout-dashboard')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                                @break
                            @case('map')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 20l-5-2V6l5 2 6-3 5 2v12l-5-2-6 3Z"/></svg>
                                @break
                            @case('bar-chart-3')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 20V10"/><path d="M8 20V4"/><path d="M13 20v-7"/><path d="M18 20v-4"/></svg>
                                @break
                            @case('map-pin')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-6-4.35-6-10a6 6 0 1 1 12 0c0 5.65-6 10-6 10Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                @break
                            @case('settings')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 0 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 0 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
                                @break
                            @case('user')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
                                @break
                            @case('users')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                @break
                            @case('car-front')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 13h18"/><path d="M5 13 7 7h10l2 6"/><path d="M7 13v4"/><path d="M17 13v4"/><path d="M7 17h10"/></svg>
                                @break
                            @case('cpu')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 4v2"/><path d="M15 4v2"/><path d="M9 18v2"/><path d="M15 18v2"/><path d="M4 9h2"/><path d="M4 15h2"/><path d="M18 9h2"/><path d="M18 15h2"/></svg>
                                @break
                            @case('file-text')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/></svg>
                                @break
                            @case('disc')
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="8"/></svg>
                                @break
                            @default
                                <svg class="w-5 h-5 flex-shrink-0 text-slate-300 group-hover:text-white transition-colors duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>
                        @endswitch
                        <span class="nav-label">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="mt-auto px-4 pb-6 space-y-3 border-t border-[rgba(255,255,255,0.08)]">
                <div id="live-update-indicator" class="sidebar-footer-item sidebar-footer-live-update rounded-2xl border border-white/10 bg-[#0b111b] px-4 py-3 text-xs text-slate-300" role="status" aria-live="polite">
                    Live updates · {{ now()->format('g:i:s A') }}
                </div>
                <div id="profile-sidebar" class="sidebar-footer-item sidebar-footer-profile rounded-2xl border border-white/10 bg-[#0b111b] px-4 py-3">
                    <div class="flex items-center gap-3">
                        @include('layouts.admin-avatar', ['admin' => auth()->user(), 'sizeClass' => 'h-9 w-9'])
                        <div class="sidebar-profile-details min-w-0">
                            <p class="text-[0.65rem] uppercase tracking-[0.2em] text-slate-500">Signed in</p>
                            <p class="mt-1 truncate text-sm text-slate-200">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>
                <form method="POST" action="/logout" class="sidebar-footer-item sidebar-footer-logout mb-3">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-full btn-danger px-4 py-3 text-sm hover:brightness-95 transition-all duration-200">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v9m6.36-6.36a9 9 0 1 1-12.72 0"/></svg>
                        <span class="button-label">Logout</span>
                    </button>
                </form>
                <button type="button" id="sidebar-collapse" onclick="toggleSidebarCollapse(event)" class="sidebar-footer-item sidebar-footer-collapse hidden lg:flex w-full items-center justify-center gap-2 rounded-full border border-[rgba(255,255,255,0.08)] bg-[#06111f] px-4 py-3 text-sm text-cyan-200 hover:bg-[#0b1728] transition-all duration-200" aria-label="Collapse navigation" aria-expanded="false">
                    <svg class="w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="button-label">Collapse</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="flex-1 lg:ml-[280px] w-full lg:w-auto flex flex-col transition-all duration-300 bg-[#090a0c]">
            @unless(request()->routeIs('dashboard') || request()->routeIs('brake-events.index') || request()->routeIs('analytics') || request()->routeIs('analytics.apa') || request()->routeIs('profile.*') || request()->routeIs('web-server.index'))
            <header id="admin-topbar" class="flex-shrink-0 sticky top-0 z-50 min-h-20 border-b border-white/10 bg-[#0B1220]/95 px-4 backdrop-blur-md transition-all duration-300 ease-in-out sm:px-6 lg:flex lg:items-center">
                <div class="flex min-h-20 w-full flex-wrap items-center justify-between gap-3 py-2">
                    <div class="flex items-center gap-4">
                        @if(!request()->routeIs('dashboard'))
                            <button id="menu-toggle" class="p-3 rounded-2xl bg-[#07131f] border border-white/10 text-slate-200 hover:bg-[#0e1a2c] transition-all duration-200" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                        @endif
                        <div class="lg:hidden">
                            <p class="text-sm font-semibold text-white">@yield('page-title', 'Dashboard')</p>
                            <p class="text-[0.72rem] text-slate-400">Barangay maps, alerts & brake events</p>
                        </div>
                        @if(request()->routeIs('registration-requests.index'))
                            <h1 class="hidden text-3xl font-semibold tracking-tight text-white lg:block">@yield('page-title')</h1>
                        @endif
                    </div>

                    <div class="flex items-center gap-4 flex-shrink-0">
                        <button id="admin-theme-toggle" type="button" aria-label="Switch to light theme" title="Switch to light theme" class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-slate-950 text-slate-100 border border-white/10 shadow-[0_20px_40px_rgba(0,0,0,0.25)] transition duration-200 ease-out hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                        </button>
                        @include('layouts.admin-user-indicator')
                    </div>
                </div>
            </header>
            @endunless

            <!-- Page Content (scrollable) -->
            <div class="page-body p-4 sm:p-6 lg:p-8 max-w-full flex-1 overflow-auto">
                @hasSection('page-title')
                @endif
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400">
                        <strong>Errors:</strong>
                        <ul class="mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('status'))
                    <div id="status-alert" class="mb-6 p-4 rounded-lg bg-success/10 border border-success/30 text-success">
                        {{ session('status') }}
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const statusText = @json(session('status'));
                            const titleText = statusText.toLowerCase().includes('removed')
                                ? 'Deleted successfully'
                                : 'Success';

                            Swal.fire({
                                icon: 'success',
                                title: titleText,
                                text: statusText,
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false,
                                customClass: {
                                    popup: 'rounded-3xl',
                                },
                                buttonsStyling: false,
                            });

                            document.getElementById('status-alert')?.classList.add('hidden');
                        });
                    </script>
                @endif

                @if (session('import_error'))
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Import declined',
                                text: @json(session('import_error')),
                                customClass: {
                                    popup: 'rounded-3xl',
                                },
                                buttonsStyling: false,
                            });
                        });
                    </script>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
        (() => {
            const targetsUrl = @json(route('dashboard.nearby-alert-targets'));
            const maximumAlertDistance = 400;
            const resetDistance = 550;
            let targets = [];
            let targetRequest = null;
            let watchId = null;
            let enabled = false;
            let alertQueue = [];
            let processingAlerts = false;
            let alertedTargetIds = new Set();
            const activeNotifications = new Set();

            const formatAlert = (target, distance) => {
                const isApa = String(target.id).startsWith('apa-')
                    || String(target.type).toLowerCase().includes('accident-prone');
                const eventType = isApa ? 'Accident-Prone Area' : `${target.type} Brake Event`;
                const place = target.place || 'saved location';

                return {
                    title: `Nearby ${eventType} in ${place}`,
                    body: `is about ${Math.round(distance)} m away.`,
                };
            };

            const distanceInMeters = (latitudeA, longitudeA, latitudeB, longitudeB) => {
                const radians = (degrees) => degrees * Math.PI / 180;
                const latitudeDelta = radians(latitudeB - latitudeA);
                const longitudeDelta = radians(longitudeB - longitudeA);
                const haversine = Math.sin(latitudeDelta / 2) ** 2
                    + Math.cos(radians(latitudeA)) * Math.cos(radians(latitudeB))
                    * Math.sin(longitudeDelta / 2) ** 2;
                return 6371000 * 2 * Math.atan2(Math.sqrt(haversine), Math.sqrt(1 - haversine));
            };

            const processAlertQueue = async () => {
                if (processingAlerts) return;
                processingAlerts = true;
                try {
                    while (alertQueue.length > 0 && enabled) {
                        const { target, distance } = alertQueue.shift();
                        const alert = formatAlert(target, distance);
                        let notification = null;
                        try {
                            notification = new Notification(alert.title, {
                                body: alert.body,
                                tag: `brakesafe-nearby-${target.id}`,
                            });
                            activeNotifications.add(notification);
                            notification.onclose = () => activeNotifications.delete(notification);
                        } catch (error) {
                            console.error('Unable to display a nearby browser notification.', error);
                        }

                        if (window.Swal) {
                            await window.Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'warning',
                                title: alert.title,
                                text: alert.body,
                                showConfirmButton: false,
                                timer: 3500,
                                timerProgressBar: true,
                            });
                        } else {
                            await new Promise((resolve) => window.setTimeout(resolve, 3500));
                        }
                        notification?.close();
                    }
                } finally {
                    processingAlerts = false;
                    if (alertQueue.length > 0 && enabled) void processAlertQueue();
                }
            };

            const handlePosition = ({ coords }) => {
                if (!enabled || coords.accuracy > maximumAlertDistance) return;
                const newlyNearby = [];
                targets.forEach((target) => {
                    const distance = distanceInMeters(
                        coords.latitude,
                        coords.longitude,
                        target.latitude,
                        target.longitude
                    );
                    const targetId = String(target.id);
                    if (distance <= maximumAlertDistance && !alertedTargetIds.has(targetId)) {
                        alertedTargetIds.add(targetId);
                        newlyNearby.push({ target, distance });
                    } else if (distance > resetDistance) {
                        alertedTargetIds.delete(targetId);
                    }
                });

                newlyNearby.sort((first, second) => first.distance - second.distance);
                alertQueue.push(...newlyNearby);
                if (newlyNearby.length > 0) void processAlertQueue();
            };

            const stopTracking = () => {
                if (watchId !== null && navigator.geolocation) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                enabled = false;
                alertQueue = [];
                alertedTargetIds.clear();
                activeNotifications.forEach((notification) => notification.close());
                activeNotifications.clear();
            };

            const reportLocationError = (error, status, button) => {
                const messages = {
                    1: 'Location permission was denied. Allow location access to receive nearby alerts.',
                    2: 'Device location is unavailable; nearby alerts have stopped.',
                    3: 'Location request timed out; nearby alerts have stopped.',
                };
                console.error('Unable to track device location for nearby alerts.', error);
                stopTracking();
                if (button) {
                    button.setAttribute('aria-pressed', 'false');
                    button.setAttribute('aria-label', 'Enable nearby notifications');
                    button.setAttribute('title', 'Nearby notifications off');
                    button.querySelector('span')?.replaceChildren('Nearby notifications off');
                    const slash = button.querySelector('.notification-slash');
                    if (slash) slash.style.display = '';
                }
                if (status) status.textContent = messages[error.code] || 'Unable to get device location; nearby alerts have stopped.';
            };

            window.BrakeSafeNearbyAlerts = {
                format: formatAlert,
                async enable({ status, button } = {}) {
                    if (document.getElementById('dashboard-root')) return true;
                    if (!navigator.geolocation) {
                        if (status) status.textContent = 'Device location is not available; nearby alerts cannot start.';
                        return false;
                    }

                    try {
                        if (!targetRequest) {
                            targetRequest = fetch(targetsUrl, {
                                headers: { 'Accept': 'application/json' },
                                cache: 'no-store',
                            }).then(async (response) => {
                                if (!response.ok) throw new Error(`Nearby alert targets request failed with HTTP ${response.status}.`);
                                const payload = await response.json();
                                if (!Array.isArray(payload.targets)) throw new Error('Nearby alert targets response was invalid.');
                                return payload.targets;
                            });
                        }
                        targets = await targetRequest;
                    } catch (error) {
                        targetRequest = null;
                        console.error('Unable to load nearby alert targets.', error);
                        if (status) status.textContent = 'Nearby alerts could not load locations. Please try again.';
                        return false;
                    }

                    if (watchId !== null) return true;
                    enabled = true;
                    try {
                        watchId = navigator.geolocation.watchPosition(
                            handlePosition,
                            (error) => reportLocationError(error, status, button),
                            { enableHighAccuracy: true, timeout: 20000, maximumAge: 5000 }
                        );
                    } catch (error) {
                        console.error('Unable to start device location tracking for nearby alerts.', error);
                        stopTracking();
                        if (status) status.textContent = 'Unable to start device location tracking for nearby alerts.';
                        return false;
                    }
                    if (status) status.textContent = 'Nearby alerts are on. Keep this page open and allow location access.';
                    return true;
                },
                disable() {
                    stopTracking();
                },
            };
        })();

        window.BrakeSafeMapLayout = {
            observe(map, container) {
                if (!map || !container || typeof ResizeObserver === 'undefined') {
                    return;
                }

                let frame = null;
                let timer = null;
                const invalidate = () => {
                    if (timer !== null) {
                        clearTimeout(timer);
                    }
                    timer = window.setTimeout(() => {
                        timer = null;
                        scheduleInvalidation();
                    }, 100);
                };
                const scheduleInvalidation = () => {
                    if (frame !== null) {
                        cancelAnimationFrame(frame);
                    }
                    frame = requestAnimationFrame(() => {
                        frame = null;
                        if (container.isConnected && container.clientWidth > 0 && container.clientHeight > 0) {
                            map.invalidateSize({ pan: false, debounceMoveend: true });
                        }
                    });
                };
                const observer = new ResizeObserver(invalidate);
                observer.observe(container);
                window.addEventListener('brakesafe:sidebar-layout-change', invalidate);
                map.once('unload', () => {
                    observer.disconnect();
                    window.removeEventListener('brakesafe:sidebar-layout-change', invalidate);
                    if (frame !== null) {
                        cancelAnimationFrame(frame);
                    }
                    if (timer !== null) {
                        clearTimeout(timer);
                    }
                });
            },
            restoreView(map, key, allowedBounds = null) {
                if (!map) {
                    return;
                }

                try {
                    const savedView = JSON.parse(sessionStorage.getItem(key) || 'null');
                    const savedCenterIsAllowed = !allowedBounds
                        || (Array.isArray(savedView?.center) && L.latLngBounds(allowedBounds).contains(savedView.center));
                    if (
                        Array.isArray(savedView?.center)
                        && savedView.center.length === 2
                        && savedView.center.every(Number.isFinite)
                        && Number.isFinite(savedView.zoom)
                        && savedCenterIsAllowed
                    ) {
                        map.setView(savedView.center, savedView.zoom, { animate: false });
                    } else if (savedView && allowedBounds) {
                        sessionStorage.removeItem(key);
                    }
                } catch (error) {
                    console.error('Unable to restore the saved map view.', error);
                }

                const saveView = () => {
                    try {
                        sessionStorage.setItem(key, JSON.stringify({
                            center: [map.getCenter().lat, map.getCenter().lng],
                            zoom: map.getZoom(),
                        }));
                    } catch (error) {
                        console.error('Unable to save the current map view.', error);
                    }
                };
                map.on('moveend', saveView);
                map.once('unload', () => map.off('moveend', saveView));
            },
            notify() {
                window.dispatchEvent(new Event('brakesafe:sidebar-layout-change'));
                window.setTimeout(() => window.dispatchEvent(new Event('resize')), 360);
            },
        };

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[data-sweetalert-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    Swal.fire({
                        title: form.dataset.swalTitle || 'Are you sure?',
                        text: form.dataset.swalText || 'This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: form.dataset.swalConfirmButtonText || 'Yes, delete it',
                        cancelButtonText: form.dataset.swalCancelButtonText || 'Cancel'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });

        // Sidebar collapse state persistence key
        const SIDEBAR_KEY = 'brakesafe.sidebarCollapsed';
        let sidebarAnimationTimer = null;

        function animateSidebarItems(sidebar) {
            if (sidebarAnimationTimer !== null) {
                window.clearTimeout(sidebarAnimationTimer);
            }
            sidebar.classList.remove('sidebar-items-entering');
            void sidebar.offsetWidth;
            sidebar.classList.add('sidebar-items-entering');
            sidebarAnimationTimer = window.setTimeout(() => {
                sidebar.classList.remove('sidebar-items-entering');
                sidebarAnimationTimer = null;
            }, 1100);
        }
        window.BrakeSafeAnimateSidebarItems = animateSidebarItems;

        function setCollapsedState(isCollapsed, save = true) {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const collapseButton = document.getElementById('sidebar-collapse');
            if (!sidebar || !mainContent || !collapseButton) return;
            const wasCollapsed = sidebar.classList.contains('sidebar-collapsed');

            sidebar.classList.toggle('sidebar-collapsed', Boolean(isCollapsed));
            mainContent.classList.toggle('sidebar-collapsed', Boolean(isCollapsed));
            document.documentElement.classList.toggle('sidebar-collapsed', Boolean(isCollapsed));
            sidebar.classList.remove('sidebar-items-entering');
            if (wasCollapsed && !isCollapsed && window.innerWidth >= 1024) {
                window.BrakeSafeAnimateSidebarItems(sidebar);
            }

            // chevron rotation
            const chevron = collapseButton.querySelector('svg');
            if (chevron) {
                chevron.classList.toggle('rotate-180', isCollapsed);
            }

            collapseButton.setAttribute('aria-expanded', String(!isCollapsed));
            collapseButton.setAttribute('aria-label', isCollapsed ? 'Expand navigation' : 'Collapse navigation');
            const collapseLabel = collapseButton.querySelector('.button-label');
            if (collapseLabel) {
                collapseLabel.textContent = isCollapsed ? 'Expand' : 'Collapse';
            }

            const navigationToggle = document.getElementById('menu-toggle');
            if (navigationToggle) {
                navigationToggle.setAttribute('aria-expanded', String(!isCollapsed));
                navigationToggle.setAttribute('aria-label', isCollapsed ? 'Open navigation' : 'Close navigation');
            }

            if (save) {
                try { localStorage.setItem(SIDEBAR_KEY, JSON.stringify(Boolean(isCollapsed))); } catch (e) { /* ignore */ }
            }
            if (wasCollapsed !== Boolean(isCollapsed)) {
                window.BrakeSafeMapLayout?.notify();
            }
        }

        function toggleSidebarCollapse(event) {
            event && event.preventDefault();
            if (window.innerWidth < 1024) {
                return;
            }
            const sidebar = document.getElementById('sidebar');
            if (!sidebar) return;
            const isCollapsed = !sidebar.classList.contains('sidebar-collapsed');
            setCollapsedState(isCollapsed);
        }

        const readCollapsedState = () => {
            try {
                const stored = localStorage.getItem(SIDEBAR_KEY);
                return stored === null ? false : Boolean(JSON.parse(stored));
            } catch (error) {
                console.error('Unable to read the saved sidebar state.', error);
                return false;
            }
        };

        const syncSidebarBreakpointState = () => {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth < 1024) {
                setCollapsedState(false, false);
                document.documentElement.classList.remove('sidebar-collapsed');
                sidebar?.classList.toggle('-translate-x-full', !document.documentElement.classList.contains('sidebar-visible'));
                return;
            }

            document.documentElement.classList.remove('sidebar-visible');
            sidebar?.classList.remove('-translate-x-full');
            setCollapsedState(readCollapsedState(), false);
        };

        // Preserve the desktop preference without applying its compact width on mobile.
        document.addEventListener('DOMContentLoaded', () => {
            syncSidebarBreakpointState();
            window.addEventListener('resize', syncSidebarBreakpointState);

            const navigationToggle = document.getElementById('menu-toggle');
            const closeMobileNavigation = () => {
                document.documentElement.classList.remove('sidebar-visible');
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.style.removeProperty('transform');
                }
                window.BrakeSafeMapLayout?.notify();
                if (navigationToggle) {
                    navigationToggle.setAttribute('aria-expanded', 'false');
                    navigationToggle.setAttribute('aria-label', 'Open navigation');
                }
            };

            navigationToggle?.addEventListener('click', () => {
                if (window.innerWidth >= 1024) {
                    toggleSidebarCollapse();
                    return;
                }

                const isVisible = document.documentElement.classList.toggle('sidebar-visible');
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('-translate-x-full', !isVisible);
                    sidebar.style.removeProperty('transform');
                    if (isVisible) window.BrakeSafeAnimateSidebarItems(sidebar);
                    else sidebar.classList.remove('sidebar-items-entering');
                }
                window.BrakeSafeMapLayout?.notify();
                navigationToggle.setAttribute('aria-expanded', String(isVisible));
                navigationToggle.setAttribute('aria-label', isVisible ? 'Close navigation' : 'Open navigation');
            });

            const syncNavigationControls = () => {
                if (!navigationToggle) return;
                if (window.innerWidth >= 1024) {
                    const isExpanded = !document.getElementById('sidebar')?.classList.contains('sidebar-collapsed');
                    navigationToggle.setAttribute('aria-expanded', String(isExpanded));
                    navigationToggle.setAttribute('aria-label', isExpanded ? 'Close navigation' : 'Open navigation');
                    return;
                }

                const isVisible = document.documentElement.classList.contains('sidebar-visible');
                navigationToggle.setAttribute('aria-expanded', String(isVisible));
                navigationToggle.setAttribute('aria-label', isVisible ? 'Close navigation' : 'Open navigation');
            };
            syncNavigationControls();
            window.addEventListener('resize', syncNavigationControls);

            document.getElementById('sidebar-close')?.addEventListener('click', () => {
                if (window.innerWidth >= 1024) {
                    toggleSidebarCollapse();
                    return;
                }
                closeMobileNavigation();
            });

            document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
                closeMobileNavigation();
            });

            document.addEventListener('keydown', (ev) => {
                if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'b') {
                    toggleSidebarCollapse();
                }
                if (ev.key === 'Escape') {
                    closeMobileNavigation();
                }
            });
        });
    </script>
    <script>
        (() => {
            const themeRoots = [
                ['#analytics-page', 'analytics-light'],
                ['#brake-events-page', 'brake-events-light'],
                ['#dashboard-root', 'dashboard-light'],
                ['#apa-page', 'apa-light'],
                ['#profile-page', 'profile-light'],
            ];
            const themeButtons = '#admin-theme-toggle, #theme-toggle, [id$="-theme-toggle"]';
            const lightThemeIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20.9 13A8.5 8.5 0 0 1 11 3.1 8.5 8.5 0 1 0 20.9 13Z"/></svg>';
            const darkThemeIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42-1.42m11.3-11.3 1.42-1.42"/></svg>';

            const applyTheme = (isLight, persist = false) => {
                document.documentElement.dataset.adminTheme = isLight ? 'light' : 'dark';
                document.documentElement.classList.toggle('dark', !isLight);
                document.documentElement.style.colorScheme = isLight ? 'light' : 'dark';
                themeRoots.forEach(([selector, lightClass]) => {
                    document.querySelector(selector)?.classList.toggle(lightClass, isLight);
                });
                window.dispatchEvent(new CustomEvent('brakesafe:theme-change', { detail: { isLight } }));
                document.querySelectorAll(themeButtons).forEach((button) => {
                    const action = isLight ? 'Switch to dark theme' : 'Switch to light theme';
                    button.setAttribute('aria-label', action);
                    button.setAttribute('title', action);
                    const label = button.querySelector('span');
                    if (label) {
                        label.textContent = isLight ? 'Light theme' : 'Dark theme';
                    }
                    const icon = button.querySelector('svg');
                    if (icon) {
                        icon.outerHTML = isLight ? lightThemeIcon : darkThemeIcon;
                    }
                });
                if (persist) {
                    try {
                        localStorage.setItem('brakesafe-admin-theme', isLight ? 'light' : 'dark');
                    } catch (error) {
                        console.error('Unable to save the theme preference.', error);
                    }
                }
            };

            window.BrakeSafeApplyTheme = applyTheme;
            applyTheme(document.documentElement.dataset.adminTheme === 'light');
            document.documentElement.classList.remove('theme-pending');
            document.addEventListener('click', (event) => {
                const button = event.target.closest(themeButtons);
                if (!button) {
                    return;
                }

                const root = themeRoots.find(([selector]) => document.querySelector(selector)?.contains(button));
                const isLight = root
                    ? document.querySelector(root[0]).classList.contains(root[1])
                    : document.documentElement.dataset.adminTheme !== 'light';
                applyTheme(isLight, true);
            });
        })();
    </script>
    <script>
        (() => {
            const isLightTheme = () => document.documentElement.dataset.adminTheme === 'light';
            const tileUrl = (key, isLight = isLightTheme()) => {
                const style = isLight ? 'streets-v2' : 'streets-v2-dark';
                return `https://api.maptiler.com/maps/${style}/{z}/{x}/{y}.png?key=${encodeURIComponent(key)}`;
            };

            window.BrakeSafeMapTheme = {
                tileUrl,
                watch(map, tiles, key) {
                    const tint = document.createElement('div');
                    tint.setAttribute('aria-hidden', 'true');
                    Object.assign(tint.style, {
                        position: 'absolute',
                        inset: '0',
                        zIndex: '425',
                        pointerEvents: 'none',
                        backgroundColor: '#87ceeb',
                        mixBlendMode: 'multiply',
                    });
                    map.getContainer().append(tint);

                    const update = (isLight) => {
                        map.getContainer().style.backgroundColor = isLight ? '#87ceeb' : '#000000';
                        tint.style.opacity = isLight ? '0.85' : '0';
                        tiles.setUrl(tileUrl(key, isLight));
                    };

                    update(isLightTheme());
                    window.addEventListener('brakesafe:theme-change', (event) => update(Boolean(event.detail?.isLight)));
                },
            };
        })();
    </script>
    @stack('scripts')
    <script>
        (() => {
            const refreshIntervalMs = 5000;
            const indicator = document.getElementById('live-update-indicator');
            const formatUpdateTime = () => new Date().toLocaleTimeString([], {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
            });
            const updateIndicators = (text, isError = false) => {
                if (!indicator) return;
                indicator.textContent = text;
                indicator.classList.toggle('text-red-300', isError);
            };
            const preservedSelector = [
                '#dashboard-map',
                '#brake-events-map',
                '#analytics-map',
                '#apa-map',
                '#use-gps',
                '#nearby-notifications-toggle',
                '#device-latitude',
                '#device-longitude',
                '#device-direction',
                '#compass-status',
                '#device-accuracy',
                '#compass-indicator',
                '#location-status',
                '#map-empty-state',
                '#dashboard-map-note',
                'canvas',
                'iframe',
                '[data-live-preserve]',
            ].join(',');
            let requestInProgress = false;
            let retryDelayMs = refreshIntervalMs;

            const isPreserved = (node) => node.nodeType === Node.ELEMENT_NODE
                && (node.matches(preservedSelector) || node.matches('script,style'));
            const nodeKey = (node) => node.nodeType === Node.ELEMENT_NODE
                ? node.id || node.getAttribute('data-live-key') || node.getAttribute('data-row-index')
                : null;

            const syncNode = (current, incoming) => {
                if (current.nodeType !== incoming.nodeType || current.nodeName !== incoming.nodeName) {
                    current.replaceWith(incoming.cloneNode(true));
                    return;
                }
                if (current.nodeType === Node.TEXT_NODE || current.nodeType === Node.COMMENT_NODE) {
                    if (current.nodeValue !== incoming.nodeValue) current.nodeValue = incoming.nodeValue;
                    return;
                }
                if (isPreserved(current)) return;

                const isThemeRoot = current.matches('#dashboard-root,#brake-events-page,#analytics-page,#apa-page,#profile-page');
                const currentLightClasses = isThemeRoot
                    ? [...current.classList].filter((className) => className.endsWith('-light'))
                    : [];
                [...current.attributes].forEach((attribute) => {
                    if (!incoming.hasAttribute(attribute.name)) current.removeAttribute(attribute.name);
                });
                [...incoming.attributes].forEach((attribute) => current.setAttribute(attribute.name, attribute.value));
                currentLightClasses.forEach((className) => current.classList.add(className));

                const value = current.value;
                const checked = current.checked;
                const oldChildren = [...current.childNodes];
                const usedChildren = new Set();
                const newChildren = [...incoming.childNodes];
                let insertionPoint = current.firstChild;

                newChildren.forEach((newChild, index) => {
                    const key = nodeKey(newChild);
                    let existing = key
                        ? oldChildren.find((child) => !usedChildren.has(child) && nodeKey(child) === key && child.nodeName === newChild.nodeName)
                        : null;
                    if (!existing) {
                        const positional = oldChildren[index];
                        if (positional && !usedChildren.has(positional) && !nodeKey(positional)
                            && positional.nodeType === newChild.nodeType && positional.nodeName === newChild.nodeName) {
                            existing = positional;
                        }
                    }
                    if (!existing && isPreserved(newChild)) {
                        existing = oldChildren.find((child) => !usedChildren.has(child)
                            && nodeKey(child) === key && child.nodeName === newChild.nodeName);
                    }

                    if (!existing) {
                        const clone = newChild.cloneNode(true);
                        current.insertBefore(clone, insertionPoint);
                        usedChildren.add(clone);
                        insertionPoint = clone.nextSibling;
                        return;
                    }

                    usedChildren.add(existing);
                    if (existing !== insertionPoint) current.insertBefore(existing, insertionPoint);
                    syncNode(existing, newChild);
                    insertionPoint = existing.nextSibling;
                });

                oldChildren.forEach((child) => {
                    if (!usedChildren.has(child) && !isPreserved(child)) child.remove();
                });

                if (current instanceof HTMLInputElement && current.type !== 'file') {
                    current.value = value;
                    current.checked = checked;
                } else if (current instanceof HTMLTextAreaElement || current instanceof HTMLSelectElement) {
                    current.value = value;
                }
            };

            const shouldDeferUpdate = () => {
                const active = document.activeElement;
                if (active && active.matches('input,textarea,select,[contenteditable="true"]')) return true;
                if (document.querySelector('.swal2-container:not(.swal2-inactive),dialog[open]')) return true;
                return [...document.forms].some((form) => form.dataset.liveSubmitting === 'true');
            };

            document.addEventListener('submit', (event) => {
                event.target.dataset.liveSubmitting = 'true';
            }, true);

            const refreshPageContent = async () => {
                if (requestInProgress || document.hidden || shouldDeferUpdate()) return;
                const currentRoot = document.querySelector('#dashboard-root,#brake-events-page,#analytics-page,#apa-page,#profile-page');
                if (!currentRoot) return;

                requestInProgress = true;
                try {
                    const response = await fetch(window.location.href, {
                        cache: 'no-store',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error(`Live update request failed with HTTP ${response.status}.`);
                    const html = await response.text();
                    const incomingDocument = new DOMParser().parseFromString(html, 'text/html');
                    const incomingRoot = incomingDocument.querySelector(`#${CSS.escape(currentRoot.id)}`);
                    if (!incomingRoot) throw new Error(`The refreshed page did not contain #${currentRoot.id}.`);
                    const liveDataNode = incomingDocument.getElementById('brakesafe-live-data');
                    const liveData = liveDataNode ? JSON.parse(liveDataNode.textContent) : null;

                    const themeWasLight = document.documentElement.dataset.adminTheme === 'light';
                    syncNode(currentRoot, incomingRoot);
                    window.BrakeSafeApplyTheme?.(themeWasLight);
                    window.dispatchEvent(new CustomEvent('brakesafe:live-update', {
                        detail: { data: liveData, pageId: currentRoot.id },
                    }));
                    updateIndicators(`Live updates · ${formatUpdateTime()}`);
                    retryDelayMs = refreshIntervalMs;
                } catch (error) {
                    console.error('Unable to refresh live page data.', error);
                    updateIndicators('Live updates unavailable · retrying', true);
                    retryDelayMs = Math.min(retryDelayMs * 2, 120000);
                } finally {
                    requestInProgress = false;
                }
            };

            updateIndicators(`Live updates · ${formatUpdateTime()}`);
            window.BrakeSafeLiveRefreshOnce = refreshPageContent;
            const scheduleNextRefresh = () => {
                window.setTimeout(async () => {
                    await refreshPageContent();
                    scheduleNextRefresh();
                }, retryDelayMs);
            };
            scheduleNextRefresh();
        })();
    </script>
    <style>
        html[data-admin-theme="light"] body,
        html[data-admin-theme="light"] #app-shell,
        html[data-admin-theme="light"] #main-content {
            background-color: #f1f5f9 !important;
            color: #0f172a;
        }
        html[data-admin-theme="light"] .page-body .glass,
        html[data-admin-theme="light"] .page-body .analytics-card,
        html[data-admin-theme="light"] .page-body .brake-events-card,
        html[data-admin-theme="light"] .page-body .dashboard-card,
        html[data-admin-theme="light"] .page-body .apa-card,
        html[data-admin-theme="light"] .page-body .profile-card {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
        }
        html[data-admin-theme="light"] .page-body .glass h1,
        html[data-admin-theme="light"] .page-body .glass h2,
        html[data-admin-theme="light"] .page-body .glass h3,
        html[data-admin-theme="light"] .page-body .glass h4,
        html[data-admin-theme="light"] .page-body .analytics-card h1,
        html[data-admin-theme="light"] .page-body .analytics-card h2,
        html[data-admin-theme="light"] .page-body .analytics-card h3,
        html[data-admin-theme="light"] .page-body .brake-events-card h1,
        html[data-admin-theme="light"] .page-body .brake-events-card h2,
        html[data-admin-theme="light"] .page-body .brake-events-card h3,
        html[data-admin-theme="light"] .page-body .dashboard-card h1,
        html[data-admin-theme="light"] .page-body .dashboard-card h2,
        html[data-admin-theme="light"] .page-body .dashboard-card h3,
        html[data-admin-theme="light"] .page-body .apa-card h1,
        html[data-admin-theme="light"] .page-body .apa-card h2,
        html[data-admin-theme="light"] .page-body .apa-card h3,
        html[data-admin-theme="light"] .page-body .profile-card h1,
        html[data-admin-theme="light"] .page-body .profile-card h2,
        html[data-admin-theme="light"] .page-body .profile-card h3 {
            color: #0f172a !important;
        }
        html[data-admin-theme="light"] .page-body .glass p,
        html[data-admin-theme="light"] .page-body .glass label,
        html[data-admin-theme="light"] .page-body .glass li,
        html[data-admin-theme="light"] .page-body .glass dt,
        html[data-admin-theme="light"] .page-body .glass dd,
        html[data-admin-theme="light"] .page-body .analytics-card p,
        html[data-admin-theme="light"] .page-body .analytics-card label,
        html[data-admin-theme="light"] .page-body .brake-events-card p,
        html[data-admin-theme="light"] .page-body .brake-events-card label,
        html[data-admin-theme="light"] .page-body .dashboard-card p,
        html[data-admin-theme="light"] .page-body .dashboard-card label,
        html[data-admin-theme="light"] .page-body .apa-card p,
        html[data-admin-theme="light"] .page-body .apa-card label,
        html[data-admin-theme="light"] .page-body .profile-card p,
        html[data-admin-theme="light"] .page-body .profile-card label {
            color: #475569 !important;
        }
        html[data-admin-theme="light"] .page-body [class~="bg-[#06101f]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#06111f]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#061a2c]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#071623]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#07121f]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#07131f]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#08111f]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#081520]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#081928]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#090a0c]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0b0d10]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0b111b]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0b1422]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0b1728]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0e1a2c]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#101216]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#101d2e]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#111317]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#171f2b]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#181818]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#1a1d22]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#202329]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#202633]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#24272d]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#25282d]"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#292d33]"],
        html[data-admin-theme="light"] .page-body [class~="bg-slate-950/40"],
        html[data-admin-theme="light"] .page-body [class~="bg-slate-950/70"],
        html[data-admin-theme="light"] .page-body [class~="bg-slate-950/90"],
        html[data-admin-theme="light"] .page-body [class~="bg-slate-900/80"] {
            background-color: #fff !important;
            border-color: #cbd5e1 !important;
        }
        html[data-admin-theme="light"] .page-body [class~="bg-[#061a2c]"],
        html[data-admin-theme="light"] .page-body [class~="bg-slate-900/80"] {
            color: #334155 !important;
        }
        html[data-admin-theme="light"] .page-body [class~="bg-[#06101f]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#06111f]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#061a2c]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#07121f]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#07131f]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#08111f]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#081520]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#081928]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0b0d10]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0b111b]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0b1422]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0b1728]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#0e1a2c]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#101216]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#101d2e]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#111317]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#171f2b]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#181818]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#1a1d22]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#202329]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#202633]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#24272d]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#25282d]"] [class~="text-white"],
        html[data-admin-theme="light"] .page-body [class~="bg-[#292d33]"] [class~="text-white"] {
            color: #0f172a !important;
        }
        html[data-admin-theme="light"] .page-body input:not([type="file"]),
        html[data-admin-theme="light"] .page-body select,
        html[data-admin-theme="light"] .page-body textarea {
            background-color: #fff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a;
        }
        html[data-admin-theme="light"] #profile-page .profile-input {
            background-color: #fff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a;
        }
        html[data-admin-theme="light"] #profile-page .password-visibility {
            color: #64748b !important;
        }
        html[data-admin-theme="light"] #profile-page .password-visibility:hover {
            color: #334155 !important;
        }
        html[data-admin-theme="light"] #imported-rows-body {
            color: #334155 !important;
        }
        html[data-admin-theme="light"] .page-body article {
            border-color: #cbd5e1 !important;
        }
        html[data-admin-theme="light"] .page-body article h3 {
            color: #0f172a !important;
        }
        html[data-admin-theme="light"] .page-body article p {
            color: #475569 !important;
        }
        #dashboard-map.leaflet-container,
        #apa-map-frame,
        #apa-map.leaflet-container,
        #analytics-map.leaflet-container,
        #brake-events-map.leaflet-container {
            background-color: #000 !important;
        }
        html[data-admin-theme="light"] #dashboard-map.leaflet-container,
        html[data-admin-theme="light"] #apa-map-frame,
        html[data-admin-theme="light"] #apa-map.leaflet-container,
        html[data-admin-theme="light"] #analytics-map.leaflet-container,
        html[data-admin-theme="light"] #brake-events-map.leaflet-container {
            background-color: #87ceeb !important;
        }
        html[data-admin-theme="light"] #map-empty-state {
            background-color: rgba(248, 250, 252, 0.9) !important;
            color: #475569 !important;
        }
        html[data-admin-theme="light"] #theme-toggle,
        html[data-admin-theme="light"] #admin-theme-toggle,
        html[data-admin-theme="light"] [id$="-theme-toggle"] {
            background-color: #fff !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
        }
        #theme-toggle svg,
        #analytics-theme-toggle svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        html[data-admin-theme="light"] #admin-topbar {
            background-color: rgba(248, 250, 252, 0.95) !important;
            border-color: #cbd5e1 !important;
        }
        html[data-admin-theme="light"] #admin-topbar p,
        html[data-admin-theme="light"] #admin-topbar span {
            color: #475569 !important;
        }
        html[data-admin-theme="light"] #admin-topbar p.text-white,
        html[data-admin-theme="light"] #admin-topbar span.text-white {
            color: #0f172a !important;
        }
        html[data-admin-theme="light"] #admin-topbar #menu-toggle,
        html[data-admin-theme="light"] [role="tooltip"] {
            background-color: #fff !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
        }
        @media (max-width: 639px) {
            html[data-admin-theme="light"] #dashboard-root > header,
            html[data-admin-theme="light"] #brake-events-page > header,
            html[data-admin-theme="light"] #analytics-page > header,
            html[data-admin-theme="light"] #apa-page > header,
            html[data-admin-theme="light"] #profile-page > header {
                background-color: #f8fafc !important;
                border-color: #cbd5e1 !important;
            }
            html[data-admin-theme="light"] #dashboard-root > header button,
            html[data-admin-theme="light"] #brake-events-page > header button,
            html[data-admin-theme="light"] #analytics-page > header button,
            html[data-admin-theme="light"] #apa-page > header button,
            html[data-admin-theme="light"] #profile-page > header button {
                background-color: #fff !important;
                border-color: #cbd5e1 !important;
                color: #334155 !important;
            }
            html[data-admin-theme="light"] #dashboard-root > header button:hover,
            html[data-admin-theme="light"] #brake-events-page > header button:hover,
            html[data-admin-theme="light"] #analytics-page > header button:hover,
            html[data-admin-theme="light"] #apa-page > header button:hover,
            html[data-admin-theme="light"] #profile-page > header button:hover {
                background-color: #e2e8f0 !important;
            }
        }
        html[data-admin-theme="light"] #sidebar {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
        }
        html[data-admin-theme="light"] #sidebar > div > div:first-child {
            border-color: #cbd5e1 !important;
        }
        html[data-admin-theme="light"] #sidebar .logo-text h1 {
            color: #0f172a !important;
        }
        html[data-admin-theme="light"] #sidebar .logo-text p {
            color: #64748b !important;
        }
        html[data-admin-theme="light"] #sidebar .nav-item {
            color: #334155 !important;
        }
        html[data-admin-theme="light"] #sidebar .nav-item:hover {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
        html[data-admin-theme="light"] #sidebar .nav-item svg {
            color: #64748b !important;
        }
        html[data-admin-theme="light"] #sidebar .nav-item[aria-current="page"],
        html[data-admin-theme="light"] #sidebar.sidebar-collapsed .nav-item[aria-current="page"] {
            background-color: #dbeafe !important;
            color: #1d4ed8 !important;
            box-shadow: none !important;
        }
        html[data-admin-theme="light"] #sidebar .nav-item[aria-current="page"] svg {
            color: #1d4ed8 !important;
        }
        html[data-admin-theme="light"] #sidebar-close {
            background-color: #e2e8f0 !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
        }
        html[data-admin-theme="light"] #profile-sidebar {
            background-color: #fff !important;
            border-color: #cbd5e1 !important;
        }
        html[data-admin-theme="light"] #live-update-indicator {
            background-color: #fff !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
        }
        html[data-admin-theme="light"] #sidebar .sidebar-footer-item:not(#sidebar-collapse) {
            background-color: #fff !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
        }
        html[data-admin-theme="light"] #live-update-indicator.text-red-300 {
            color: #b91c1c !important;
        }
        html[data-admin-theme="light"] #profile-sidebar p:first-child {
            color: #64748b !important;
        }
        html[data-admin-theme="light"] #profile-sidebar p:last-child {
            color: #334155 !important;
        }
        html[data-admin-theme="light"] #sidebar-collapse {
            background-color: #e2e8f0 !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
        }
        html[data-admin-theme="light"] #sidebar-collapse:hover {
            background-color: #cbd5e1 !important;
        }
        html[data-admin-theme="light"] #sidebar-overlay {
            background-color: rgba(15, 23, 42, 0.35) !important;
        }
    </style>
</body>
</html>
