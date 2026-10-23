@extends('layouts.admin')

@section('title', 'Dashboard - BrakeSafe')
@section('page-title', 'Dashboard')

@section('content')
    @php
        $municipalityColors = [
            'Bantayan' => '#22d3ee',
            'Madridejos' => '#ef4444',
            'Santa Fe' => '#f97316',
        ];
    @endphp
    <script id="brakesafe-live-data" type="application/json">@json(['proximityTargets' => $proximityTargets])</script>
    <div id="dashboard-root" class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <button id="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-[#111317] text-slate-200 transition hover:bg-[#1a1d22] focus:outline-none focus:ring-2 focus:ring-cyan-400/50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-3xl font-semibold tracking-tight text-white">Dashboard</h1>
            </div>
            <div class="flex min-w-0 flex-wrap items-center justify-end gap-4">
                <button id="theme-toggle" type="button" aria-label="Switch to light theme" title="Switch to light theme" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/10 bg-slate-950 text-slate-100 shadow-[0_20px_40px_rgba(0,0,0,0.25)] transition duration-200 ease-out hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                    <span class="sr-only">Dark theme</span>
                </button>
                @include('layouts.admin-user-indicator')
            </div>
        </header>

        @if(session('status'))
            <div role="status" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->has('brake_csv'))
            <div role="alert" class="rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                {{ $errors->first('brake_csv') }}
            </div>
        @endif

        <section aria-labelledby="device-location-title" class="dashboard-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
            <div class="dashboard-location-heading flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="dashboard-location-icon hidden h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/15 bg-[#0b0d10] text-cyan-300">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    </span>
                    <div>
                        <h2 id="device-location-title" class="dashboard-location-title text-lg font-semibold text-white">Device location</h2>
                        <p class="mt-1 text-sm text-slate-400">With GPS on, all brake events within 500 meters appear on the map; events within 200 meters blink. Nearby alerts also cover saved APA locations.</p>
                    </div>
                </div>
                <button id="use-gps" type="button" class="inline-flex min-h-10 items-center justify-center gap-2 self-start rounded-xl border border-cyan-400/30 bg-cyan-400/10 px-4 text-sm font-semibold text-cyan-200 transition hover:bg-cyan-400/20 focus:outline-none focus:ring-2 focus:ring-cyan-400/50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
                    <span>Use device GPS</span>
                </button>
            </div>
            <div class="dashboard-location-details mt-5 grid gap-5 xl:grid-cols-[1fr_260px]">
                <div class="dashboard-metrics grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="dashboard-metric rounded-xl border border-white/10 bg-[#0b0d10] p-4">
                        <p class="text-xs text-slate-500">Latitude</p>
                        <p id="device-latitude" class="dashboard-metric-output mt-2 font-mono text-lg text-slate-300">--</p>
                    </div>
                    <div class="dashboard-metric rounded-xl border border-white/10 bg-[#0b0d10] p-4">
                        <p class="text-xs text-slate-500">Longitude</p>
                        <p id="device-longitude" class="dashboard-metric-output mt-2 font-mono text-lg text-slate-300">--</p>
                    </div>
                    <div class="dashboard-metric rounded-xl border border-white/10 bg-[#0b0d10] p-4">
                        <p class="text-xs text-slate-500">Direction</p>
                        <p id="device-direction" class="dashboard-metric-output mt-2 text-base text-slate-400">Compass unavailable</p>
                        <p id="compass-status" class="mt-1 text-xs text-slate-600">Compass not requested</p>
                    </div>
                    <div class="dashboard-metric rounded-xl border border-white/10 bg-[#0b0d10] p-4">
                        <p class="text-xs text-slate-500">GPS accuracy</p>
                        <p id="device-accuracy" class="dashboard-metric-output mt-2 font-mono text-lg text-slate-300">--</p>
                    </div>
                </div>
                <div class="dashboard-local-time flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-[#0b0d10] p-4">
                    <div>
                        <p class="text-xs text-slate-500">Device local time</p>
                        <p id="device-local-time" class="mt-2 text-base font-medium text-slate-200"></p>
                        <p id="device-local-date" class="mt-1 text-xs text-slate-500"></p>
                        <p id="location-status" class="mt-2 text-xs text-slate-500" aria-live="polite">Location not requested.</p>
                    </div>
                    <div id="compass-indicator" class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-full border border-white/10 text-slate-700" aria-label="Compass unavailable" aria-live="polite">
                        <svg id="compass-needle" class="h-9 w-9 opacity-35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2.2 5-4.8 2 2-4.8 5-2.2Z"/></svg>
                    </div>
                </div>
            </div>
            <div class="dashboard-map-frame relative mt-5 h-[300px] overflow-hidden rounded-xl border border-white/10 bg-[#0b0d10] sm:h-[360px]">
                <div id="dashboard-map" class="absolute inset-0" aria-label="Map showing your location"></div>
                <div id="dashboard-map-debug" class="absolute inset-0 z-[550] flex items-center justify-center bg-[#101216]/90 px-6 text-center text-sm text-slate-300" role="status" aria-live="polite" style="{{ $mapTilerKey ? 'display: none;' : '' }}">
                    @if($mapTilerKey)
                        Loading MapTiler map…
                    @else
                        MapTiler API key is missing. Add MAPTILER_API_KEY to your .env file, then clear Laravel's configuration cache.
                    @endif
                </div>
                <div id="map-empty-state" class="pointer-events-none absolute inset-0 z-[500] flex items-center justify-center bg-[#101216]/85 px-6 text-center text-sm text-slate-400">
                    <span>Use GPS to show your current location on the map.</span>
                </div>
                <div id="gps-radius-label" class="pointer-events-none absolute left-3 top-3 z-[600] flex items-center gap-2 rounded-lg border border-cyan-300/25 bg-slate-950/85 px-3 py-2 text-xs font-medium text-cyan-100 shadow-lg" aria-hidden="true" style="display: none;">
                    <span class="inline-block h-3 w-3 rounded-full border-2 border-cyan-300 bg-cyan-300/20"></span>
                    <span>500 m radius</span>
                </div>
                <div class="dashboard-map-controls pointer-events-auto absolute right-3 top-3 z-[600] flex gap-2">
                    <button id="map-zoom-in" type="button" aria-label="Zoom in" title="Zoom in" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/10 bg-[#0b0d10] text-lg text-slate-200 transition hover:bg-[#202329]">+</button>
                    <button id="map-zoom-out" type="button" aria-label="Zoom out" title="Zoom out" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/10 bg-[#0b0d10] text-lg text-slate-200 transition hover:bg-[#202329] disabled:cursor-not-allowed disabled:opacity-40">−</button>
                </div>
            </div>
            <div id="dashboard-map-attribution" class="map-attribution-outside" hidden></div>
            <p id="dashboard-map-note" class="sr-only">The map shows your current location and a 500 meter radius when GPS is enabled.</p>
        </section>

        <div class="grid items-start gap-5 xl:grid-cols-[1.55fr_1fr]">
            <div class="space-y-5">
            <section aria-labelledby="severity-title" class="dashboard-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 id="severity-title" class="text-lg font-semibold text-white">Brake severity by municipality</h2>
                        <p class="mt-1 text-sm text-slate-400">GPS events matched to barangay boundaries.</p>
                    </div>
                    <span class="whitespace-nowrap text-sm text-slate-400"><strong class="text-white">{{ number_format($mappedEventCount) }}</strong> mapped events</span>
                </div>
                <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2" aria-label="Municipality legend">
                    @foreach($municipalities as $municipality)
                        <span class="inline-flex items-center gap-2 text-xs text-slate-400">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $municipalityColors[$municipality] }}"></span>
                            {{ $municipality }}
                        </span>
                    @endforeach
                </div>
                <div class="mt-5 space-y-4">
                    @foreach($classifications as $classification)
                        @php
                            $classificationKey = $classification === 'Moderate' ? 'medium' : strtolower($classification);
                            $rowCounts = $severityByMunicipality[$classificationKey];
                            $rowTotal = array_sum($rowCounts);
                            $rowScale = max(1, $rowTotal);
                        @endphp
                        <div class="grid grid-cols-[78px_1fr] gap-3 sm:grid-cols-[92px_1fr_70px] sm:items-center">
                            <h3 class="text-sm font-medium text-slate-300">{{ $classification }}</h3>
                            <div>
                                <div class="flex h-3 overflow-hidden rounded-full bg-[#24272d]" role="img" aria-label="{{ $classification }}: {{ $rowCounts['Bantayan'] }} Bantayan, {{ $rowCounts['Madridejos'] }} Madridejos, {{ $rowCounts['Santa Fe'] }} Santa Fe">
                                    @foreach($municipalities as $municipality)
                                        <span
                                            title="{{ $municipality }}: {{ $rowCounts[$municipality] }} {{ strtolower($classification) }} events"
                                            aria-label="{{ $municipality }}: {{ $rowCounts[$municipality] }} {{ strtolower($classification) }} events"
                                            style="width: {{ ($rowCounts[$municipality] / $rowScale) * 100 }}%; background-color: {{ $municipalityColors[$municipality] }}"
                                        ></span>
                                    @endforeach
                                </div>
                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[0.68rem] text-slate-500">
                                    @foreach($municipalities as $municipality)
                                        <span>{{ $municipality }} <strong class="font-medium text-slate-400">{{ $rowCounts[$municipality] }}</strong></span>
                                    @endforeach
                                </div>
                            </div>
                            <p class="col-span-2 text-right text-xs text-slate-500 sm:col-span-1"><strong class="text-slate-300">{{ $rowTotal }}</strong> total</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-5 border-t border-white/10 pt-4 text-xs text-slate-500">Import GPS event rows inside Bantayan, Madridejos, or Santa Fe to populate this chart.</p>
            </section>

            <section aria-labelledby="dataset-title" class="dashboard-card flex flex-col gap-5 rounded-2xl border border-white/10 bg-[#111317] p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Current dataset</p>
                    @if($datasetEventCount === 0)
                        <h2 id="dataset-title" class="mt-2 text-lg font-semibold text-white">No file imported yet</h2>
                        <p class="mt-1 text-sm text-slate-400">Import a CSV or Excel file to explore brake events.</p>
                    @else
                        <h2 id="dataset-title" class="mt-2 text-lg font-semibold text-white">{{ $datasetInfo['file_name'] }}</h2>
                        <p class="mt-1 text-sm text-slate-400">{{ number_format($datasetEventCount) }} brake events in the current dataset.</p>
                    @endif
                </div>
                <a id="dashboard-import-button" href="{{ route('brake-events.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-cyan-400 px-5 text-sm font-semibold text-[#061014] transition hover:bg-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-200 focus:ring-offset-2 focus:ring-offset-[#111317]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5"/></svg>
                    Import brake events
                </a>
            </section>
            </div>

            <section aria-labelledby="contribution-title" class="dashboard-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
                <h2 id="contribution-title" class="text-lg font-semibold text-white">Municipality contribution</h2>
                <p class="mt-1 text-sm text-slate-400">Share of GPS events inside the selected municipalities.</p>
                <div class="mx-auto mt-5 h-52 w-full max-w-xs">
                    <canvas
                        id="municipality-contribution-chart"
                        role="img"
                        aria-label="Pie chart of GPS event contribution: {{ implode(', ', array_map(fn ($municipality) => $municipality . ' ' . number_format($municipalityTotals[$municipality]) . ' events', $municipalities)) }}."
                    ></canvas>
                    <p class="sr-only">Municipality contribution pie chart.</p>
                </div>
                <div class="mt-5 space-y-4">
                    @foreach($municipalities as $municipality)
                        @php
                            $count = $municipalityTotals[$municipality];
                            $percentage = $mappedEventCount > 0 ? ($count / $mappedEventCount) * 100 : 0;
                        @endphp
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                <span class="inline-flex items-center gap-2 text-slate-300"><span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $municipalityColors[$municipality] }}"></span>{{ $municipality }}</span>
                                <span class="text-slate-400">{{ $count }} <span class="ml-2">{{ number_format($percentage, 1) }}%</span></span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-[#24272d]" role="img" aria-label="{{ $municipality }}: {{ $count }} events, {{ number_format($percentage, 1) }} percent">
                                <div class="h-full rounded-full" style="width: {{ $percentage }}%; background-color: {{ $municipalityColors[$municipality] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex items-center justify-between border-t border-white/10 pt-4 text-sm">
                    <span class="font-medium text-slate-300">Total contribution</span>
                    <strong class="text-white">{{ $mappedEventCount }} <span class="ml-2 font-normal text-slate-400">{{ $mappedEventCount > 0 ? '100.0' : '0.0' }}%</span></strong>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const municipalities = @json($municipalities);
        const municipalityColors = @json($municipalityColors);
        const municipalityTotals = @json($municipalityTotals);
        const mappedEventCount = @json($mappedEventCount);
        const contributionChartCanvas = document.getElementById('municipality-contribution-chart');
        if (contributionChartCanvas && window.Chart) {
            const hasMappedEvents = mappedEventCount > 0;
            new Chart(contributionChartCanvas, {
                type: 'pie',
                data: {
                    labels: hasMappedEvents ? municipalities : ['No mapped events'],
                    datasets: [{
                        data: hasMappedEvents
                            ? municipalities.map((municipality) => municipalityTotals[municipality])
                            : [1],
                        backgroundColor: hasMappedEvents
                            ? municipalities.map((municipality) => municipalityColors[municipality])
                            : ['#334155'],
                        borderColor: '#111317',
                        borderWidth: 3,
                        hoverOffset: 8,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    if (!hasMappedEvents) return 'No mapped events';
                                    const count = Number(context.raw);
                                    const percentage = (count / mappedEventCount) * 100;
                                    return `${context.label}: ${count.toLocaleString()} events (${percentage.toFixed(1)}%)`;
                                },
                            },
                        },
                    },
                },
            });
        } else if (contributionChartCanvas) {
            console.error('Unable to render municipality contribution chart because Chart.js is unavailable.');
        }
        const mapTilerKey = @json($mapTilerKey);
        let proximityTargets = @json($proximityTargets);
        const defaultCenter = [11.2200, 123.7600];
        const mapElement = document.getElementById('dashboard-map');
        const mapDebug = document.getElementById('dashboard-map-debug');
        const emptyState = document.getElementById('map-empty-state');
        const gpsButton = document.getElementById('use-gps');
        const gpsStatus = document.getElementById('location-status');
        const latitudeOutput = document.getElementById('device-latitude');
        const longitudeOutput = document.getElementById('device-longitude');
        const accuracyOutput = document.getElementById('device-accuracy');
        const directionOutput = document.getElementById('device-direction');
        const compassStatus = document.getElementById('compass-status');
        const compassIndicator = document.getElementById('compass-indicator');
        const compassNeedle = document.getElementById('compass-needle');
        let gpsMarker = null;
        let gpsRadiusCircle = null;
        let gpsWatchId = null;
        let currentHeading = 0;
        let notificationsEnabled = false;
        const brakeEventMarkers = new Map();
        const nearbyTargetIds = new Set();
        const activeNearbyNotifications = new Set();
        const gpsSessionKey = 'brakesafe-dashboard-gps-enabled';
        const notificationsSessionKey = 'brakesafe-dashboard-notifications-enabled';
        const readSessionFlag = (key) => {
            try {
                return sessionStorage.getItem(key) === 'true';
            } catch (error) {
                console.error('Unable to restore a dashboard preference.', error);
                return false;
            }
        };
        const writeSessionFlag = (key, enabled) => {
            try {
                if (enabled) {
                    sessionStorage.setItem(key, 'true');
                } else {
                    sessionStorage.removeItem(key);
                }
            } catch (error) {
                console.error('Unable to save a dashboard preference.', error);
            }
        };
        const resumeGpsAfterReload = readSessionFlag(gpsSessionKey);
        const resumeNotificationsAfterReload = readSessionFlag(notificationsSessionKey);

        const updateDeviceClock = () => {
            const now = new Date();
            document.getElementById('device-local-time').textContent = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            document.getElementById('device-local-date').textContent = now.toLocaleDateString([], { year: 'numeric', month: 'long', day: 'numeric' });
        };
        updateDeviceClock();
        window.setInterval(updateDeviceClock, 1000);

        const map = typeof L !== 'undefined' && mapElement
            ? L.map(mapElement, { zoomControl: false, minZoom: 10, maxZoom: 16 }).setView(defaultCenter, 11)
            : null;
        let mapTiles = null;
        const isLightTheme = () => document.documentElement.dataset.adminTheme === 'light';
        const gpsArrowIcon = () => {
            const color = isLightTheme() ? '#2563eb' : '#dc2626';
            return L.divIcon({
                className: 'gps-location-icon',
                html: `<div class="gps-location-arrow" style="transform:rotate(${currentHeading}deg)"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 1.5 21 22l-9-4.2L3 22 12 1.5Z" fill="${color}" stroke="white" stroke-width="1.7" stroke-linejoin="round"/></svg></div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
            });
        };
        const updateGpsMarkerIcon = () => {
            if (gpsMarker) gpsMarker.setIcon(gpsArrowIcon());
        };

        if (map) {
            window.BrakeSafeMapAttribution?.move(map, 'dashboard-map-attribution');
            window.BrakeSafeMapLayout?.observe(map, mapElement);
            if (mapTilerKey) {
                mapTiles = L.tileLayer(
                    window.BrakeSafeMapTheme.tileUrl(mapTilerKey),
                    {
                        maxZoom: 16,
                        attribution: '&copy; <a href="https://www.maptiler.com/copyright/">MapTiler</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    }
                );
                mapTiles.once('tileload', () => { mapDebug.style.display = 'none'; });
                mapTiles.once('tileerror', () => {
                    mapDebug.textContent = 'MapTiler could not load map tiles. Check that MAPTILER_API_KEY is valid and allows this site.';
                    mapDebug.style.display = 'flex';
                    console.error('MapTiler tile request failed. Check the API key and its allowed domains.');
                });
                mapTiles.addTo(map);
                window.BrakeSafeMapTheme.watch(map, mapTiles, mapTilerKey);
            }

            window.BrakeSafeMapLayout?.restoreView(map, 'brakesafe-map-dashboard');
            window.setTimeout(() => map.invalidateSize(), 100);
        } else if (mapElement) {
            mapElement.setAttribute('aria-label', 'Map unavailable because the map library did not load');
        }

        const mapZoomOutButton = document.getElementById('map-zoom-out');
        const updateMapZoomOutButton = () => {
            mapZoomOutButton.disabled = !map || map.getZoom() <= map.getMinZoom();
        };
        document.getElementById('map-zoom-in').addEventListener('click', () => map?.zoomIn());
        mapZoomOutButton.addEventListener('click', () => {
            if (map && map.getZoom() > map.getMinZoom()) map.zoomOut();
        });
        map?.on('zoomend', updateMapZoomOutButton);
        updateMapZoomOutButton();
        window.addEventListener('brakesafe:theme-change', (event) => {
            updateGpsMarkerIcon();
        });

        const compassDirections = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        let compassRequested = false;
        const startCompass = async () => {
            if (compassRequested) return;
            compassRequested = true;
            if (typeof DeviceOrientationEvent === 'undefined') {
                compassStatus.textContent = 'Compass unavailable on this device';
                return;
            }

            if (typeof DeviceOrientationEvent.requestPermission === 'function') {
                try {
                    const permission = await DeviceOrientationEvent.requestPermission();
                    if (permission !== 'granted') {
                        compassStatus.textContent = 'Compass permission not granted';
                        return;
                    }
                } catch (error) {
                    compassStatus.textContent = 'Compass permission unavailable';
                    console.error('Unable to request compass permission', error);
                    return;
                }
            }

            compassStatus.textContent = 'Waiting for compass data';
            window.addEventListener('deviceorientationabsolute', updateCompass);
            window.addEventListener('deviceorientation', updateCompass);
        };

        function updateCompass(event) {
            const heading = Number.isFinite(event.webkitCompassHeading)
                ? event.webkitCompassHeading
                : Number.isFinite(event.alpha) ? (360 - event.alpha) % 360 : null;
            if (heading === null) return;
            currentHeading = heading;

            const direction = compassDirections[Math.round(heading / 45) % compassDirections.length];
            directionOutput.textContent = `${direction} (${Math.round(heading)}°)`;
            directionOutput.classList.remove('text-slate-400');
            directionOutput.classList.add('text-slate-200');
            compassStatus.textContent = 'Compass active';
            compassIndicator.setAttribute('aria-label', `Compass heading ${direction}, ${Math.round(heading)} degrees`);
            compassIndicator.classList.remove('text-slate-700');
            compassIndicator.classList.add('text-cyan-300');
            compassNeedle.classList.remove('opacity-35');
            compassNeedle.style.transform = `rotate(${heading}deg)`;
            updateGpsMarkerIcon();
        }

        const distanceInMeters = (latitudeA, longitudeA, latitudeB, longitudeB) => {
            const radians = (degrees) => degrees * Math.PI / 180;
            const latitudeDelta = radians(latitudeB - latitudeA);
            const longitudeDelta = radians(longitudeB - longitudeA);
            const haversine = Math.sin(latitudeDelta / 2) ** 2
                + Math.cos(radians(latitudeA)) * Math.cos(radians(latitudeB))
                * Math.sin(longitudeDelta / 2) ** 2;
            return 6371000 * 2 * Math.atan2(Math.sqrt(haversine), Math.sqrt(1 - haversine));
        };

        const brakeEventColor = (type) => ({
            Soft: '#22c55e',
            Moderate: '#eab308',
            Hard: '#ef4444',
            Emergency: '#f97316',
        })[type] ?? '#facc15';
        const brakeEventIcon = (target, isVeryNearby) => L.divIcon({
            className: 'dashboard-brake-event-icon',
            html: `<div class="dashboard-brake-event-marker${isVeryNearby ? ' dashboard-brake-event-marker-nearby' : ''}" style="--brake-event-color:${brakeEventColor(target.type)}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 22 21H2L12 3Z" fill="currentColor" stroke="white" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 9v5m0 3h.01" stroke="#111827" stroke-width="2" stroke-linecap="round"/></svg></div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
        });
        const brakeEventPopup = (target, distance) => {
            const popup = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = `${target.type} brake event`;
            const place = document.createElement('div');
            place.textContent = target.place || 'Brake event location';
            const distanceLabel = document.createElement('div');
            distanceLabel.textContent = `${Math.round(distance)} m away`;
            popup.append(title, place, distanceLabel);
            return popup;
        };
        const clearNearbyBrakeEventMarkers = () => {
            brakeEventMarkers.forEach(({ marker }) => map?.removeLayer(marker));
            brakeEventMarkers.clear();
        };
        const updateNearbyBrakeEventMarkers = ({ latitude, longitude }) => {
            if (!map) return;
            const visibleEventIds = new Set();

            proximityTargets.forEach((target) => {
                if (!String(target.id).startsWith('brake-event-')) return;

                const distance = distanceInMeters(latitude, longitude, target.latitude, target.longitude);
                if (distance > 500) return;

                const targetId = String(target.id);
                const isVeryNearby = distance <= 200;
                visibleEventIds.add(targetId);
                let entry = brakeEventMarkers.get(targetId);

                if (!entry) {
                    const marker = L.marker([target.latitude, target.longitude], {
                        icon: brakeEventIcon(target, isVeryNearby),
                        keyboard: true,
                        title: `${target.type} brake event, ${Math.round(distance)} meters away`,
                        riseOnHover: true,
                    }).addTo(map);
                    marker.bindPopup(brakeEventPopup(target, distance));
                    entry = { marker, isVeryNearby };
                    brakeEventMarkers.set(targetId, entry);
                } else {
                    entry.marker.setLatLng([target.latitude, target.longitude]);
                    entry.marker.setPopupContent(brakeEventPopup(target, distance));
                    if (entry.isVeryNearby !== isVeryNearby) {
                        entry.marker.setIcon(brakeEventIcon(target, isVeryNearby));
                        entry.isVeryNearby = isVeryNearby;
                    }
                }
            });

            brakeEventMarkers.forEach((entry, targetId) => {
                if (!visibleEventIds.has(targetId)) {
                    map.removeLayer(entry.marker);
                    brakeEventMarkers.delete(targetId);
                }
            });
        };

        const nearbyAlertQueue = [];
        let processingNearbyAlerts = false;
        const processNearbyAlertQueue = async () => {
            if (processingNearbyAlerts) return;
            processingNearbyAlerts = true;
            try {
                while (nearbyAlertQueue.length > 0 && notificationsEnabled && Notification.permission === 'granted') {
                    const target = nearbyAlertQueue.shift();
                    const alert = window.BrakeSafeNearbyAlerts?.format(target, target.distance) ?? {
                        title: `Nearby ${target.type} Brake Event in ${target.place || 'saved location'}`,
                        body: `is about ${Math.round(target.distance)} m away.`,
                    };
                    let notification = null;

                    try {
                        notification = new Notification(alert.title, {
                            body: alert.body,
                            tag: `brakesafe-nearby-${target.id}`,
                        });
                        activeNearbyNotifications.add(notification);
                        notification.onclose = () => activeNearbyNotifications.delete(notification);
                        notification.onclick = () => {
                            window.focus();
                            map?.setView([target.latitude, target.longitude], 16);
                            notification.close();
                        };
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
                processingNearbyAlerts = false;
                if (nearbyAlertQueue.length > 0 && notificationsEnabled && Notification.permission === 'granted') {
                    void processNearbyAlertQueue();
                }
            }
        };

        const notifyNearbyTargets = ({ latitude, longitude, accuracy }) => {
            if (!notificationsEnabled || Notification.permission !== 'granted') return;
            if (accuracy > 500) {
                gpsStatus.textContent = `GPS accuracy is too low (${Math.round(accuracy)} m) for reliable 500 m alerts.`;
                return;
            }

            const newlyNearbyTargets = [];
            proximityTargets.forEach((target) => {
                const distance = distanceInMeters(latitude, longitude, target.latitude, target.longitude);
                const targetId = String(target.id);
                if (distance <= 500 && !nearbyTargetIds.has(targetId)) {
                    nearbyTargetIds.add(targetId);
                    newlyNearbyTargets.push({ ...target, distance });
                } else if (distance > 550) {
                    nearbyTargetIds.delete(targetId);
                }
            });

            if (newlyNearbyTargets.length === 0) return;

            newlyNearbyTargets.sort((first, second) => first.distance - second.distance);
            nearbyAlertQueue.push(...newlyNearbyTargets);
            void processNearbyAlertQueue();
        };

        window.addEventListener('brakesafe:live-update', (event) => {
            if (event.detail?.pageId !== 'dashboard-root' || !Array.isArray(event.detail.data?.proximityTargets)) return;
            proximityTargets = event.detail.data.proximityTargets;
            const currentTargetIds = new Set(proximityTargets.map((target) => String(target.id)));
            nearbyTargetIds.forEach((targetId) => {
                if (!currentTargetIds.has(targetId)) nearbyTargetIds.delete(targetId);
            });
            if (gpsMarker) {
                const { lat, lng } = gpsMarker.getLatLng();
                updateNearbyBrakeEventMarkers({ latitude: lat, longitude: lng });
            }
        });

        const handleGpsPosition = (position, centerMap = false) => {
            const { latitude, longitude, accuracy, heading } = position.coords;
            if (Number.isFinite(heading)) currentHeading = heading;
            latitudeOutput.textContent = latitude.toFixed(6);
            longitudeOutput.textContent = longitude.toFixed(6);
            accuracyOutput.textContent = `${Math.round(accuracy)} m`;
            gpsStatus.textContent = 'GPS tracking active.';

            if (map) {
                if (!gpsRadiusCircle) {
                    gpsRadiusCircle = L.circle([latitude, longitude], {
                        radius: 500,
                        color: '#38bdf8',
                        weight: 2,
                        opacity: 0.85,
                        fillColor: '#38bdf8',
                        fillOpacity: 0.08,
                        dashArray: '7 7',
                        interactive: false,
                    }).addTo(map);
                } else {
                    gpsRadiusCircle.setLatLng([latitude, longitude]);
                }
                document.getElementById('gps-radius-label').style.display = 'flex';
                if (!gpsMarker) {
                    gpsMarker = L.marker([latitude, longitude], {
                        icon: gpsArrowIcon(),
                        keyboard: true,
                        title: 'Your current location',
                        zIndexOffset: 1000,
                    }).addTo(map).bindPopup('Your current location');
                    emptyState.classList.add('hidden');
                } else {
                    gpsMarker.setLatLng([latitude, longitude]);
                    updateGpsMarkerIcon();
                }
                updateNearbyBrakeEventMarkers({ latitude, longitude });
                if (centerMap) map.setView([latitude, longitude], 14);
            }

            notifyNearbyTargets({ latitude, longitude, accuracy });
        };

        const handleGpsError = (error) => {
            const messages = {
                1: 'Location permission was denied.',
                2: 'Device location is unavailable.',
                3: 'Location request timed out.',
            };
            gpsStatus.textContent = messages[error.code] || 'Unable to get device location.';
            if (error.code === 1 && gpsWatchId !== null) {
                navigator.geolocation.clearWatch(gpsWatchId);
                gpsWatchId = null;
                writeSessionFlag(gpsSessionKey, false);
                gpsButton.setAttribute('aria-pressed', 'false');
                gpsButton.setAttribute('aria-label', 'Turn on GPS');
                gpsButton.querySelector('span').textContent = 'Use device GPS';
                if (notificationsEnabled) {
                    setNearbyNotificationsEnabled(false);
                    mapNote.textContent = 'Location permission was denied; nearby notifications are off.';
                }
            }
            if (gpsWatchId === null) gpsButton.querySelector('span').textContent = 'Use device GPS';
            console.error('Unable to track device location.', error);
        };

        const startGpsTracking = () => {
            if (!navigator.geolocation) {
                gpsStatus.textContent = 'Device location is not available in this browser.';
                return false;
            }
            if (gpsWatchId !== null) return true;

            gpsButton.disabled = true;
            gpsButton.querySelector('span').textContent = 'Requesting GPS…';
            gpsStatus.textContent = 'Waiting for location permission.';
            startCompass();
            gpsWatchId = navigator.geolocation.watchPosition((position) => {
                const firstFix = !gpsMarker;
                handleGpsPosition(position, firstFix);
                gpsButton.disabled = false;
            }, (error) => {
                handleGpsError(error);
                gpsButton.disabled = false;
            }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 5000 });
            gpsButton.setAttribute('aria-pressed', 'true');
            gpsButton.setAttribute('aria-label', 'Turn off GPS');
            gpsButton.querySelector('span').textContent = 'Turn off GPS';
            writeSessionFlag(gpsSessionKey, true);
            return true;
        };

        const confirmAndStartGpsTracking = async () => {
            if (gpsWatchId !== null) {
                return true;
            }
            if (!navigator.geolocation) {
                gpsStatus.textContent = 'Device location is not available in this browser.';
                return false;
            }

            if (window.Swal) {
                const confirmation = await window.Swal.fire({
                    icon: 'question',
                    title: 'Turn on GPS tracking?',
                    showCancelButton: true,
                    confirmButtonText: 'Continue',
                    cancelButtonText: 'Not now',
                    reverseButtons: true,
                });
                if (!confirmation.isConfirmed) {
                    return false;
                }
            }

            return startGpsTracking();
        };

        const nearbyButton = document.getElementById('nearby-notifications-toggle');
        const mapNote = document.getElementById('dashboard-map-note');
        const setNearbyNotificationsEnabled = (enabled, title = null) => {
            notificationsEnabled = enabled;
            writeSessionFlag(notificationsSessionKey, enabled);
            if (!enabled) {
                nearbyAlertQueue.length = 0;
                activeNearbyNotifications.forEach((notification) => notification.close());
                activeNearbyNotifications.clear();
            }
            nearbyButton.setAttribute('aria-pressed', String(enabled));
            nearbyButton.setAttribute('aria-label', enabled ? 'Disable nearby notifications' : 'Enable nearby notifications');
            nearbyButton.title = title ?? (enabled ? 'Nearby notifications are on' : 'Nearby notifications are off');
            nearbyButton.querySelector('span').textContent = enabled ? 'Nearby notifications on' : 'Nearby notifications off';
            nearbyButton.classList.toggle('nearby-notifications-enabled', enabled);
            nearbyButton.querySelector('svg').innerHTML = enabled
                ? '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/><path d="m9 11 2 2 4-4"/>'
                : '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/><path d="m3 3 18 18"/>';
        };
        const stopGpsTracking = () => {
            if (gpsWatchId !== null) {
                navigator.geolocation.clearWatch(gpsWatchId);
                gpsWatchId = null;
            }
            writeSessionFlag(gpsSessionKey, false);
            if (gpsMarker && map) {
                map.removeLayer(gpsMarker);
                gpsMarker = null;
            }
            if (gpsRadiusCircle && map) {
                map.removeLayer(gpsRadiusCircle);
                gpsRadiusCircle = null;
            }
            document.getElementById('gps-radius-label').style.display = 'none';
            clearNearbyBrakeEventMarkers();
            emptyState.classList.remove('hidden');
            latitudeOutput.textContent = '--';
            longitudeOutput.textContent = '--';
            accuracyOutput.textContent = '--';
            gpsButton.querySelector('span').textContent = 'Use device GPS';
            gpsButton.setAttribute('aria-pressed', 'false');
            gpsButton.setAttribute('aria-label', 'Turn on GPS');
            gpsStatus.textContent = 'GPS tracking stopped.';
            if (notificationsEnabled) {
                setNearbyNotificationsEnabled(false);
                mapNote.textContent = 'GPS tracking stopped; nearby notifications are off.';
            }
            nearbyTargetIds.clear();
        };

        gpsButton.setAttribute('aria-label', 'Turn on GPS');
        gpsButton.setAttribute('aria-pressed', 'false');
        gpsButton.addEventListener('click', async () => {
            if (gpsWatchId !== null) {
                if (window.Swal) {
                    const confirmation = await window.Swal.fire({
                        icon: 'warning',
                        title: 'Turn off GPS tracking?',
                        showCancelButton: true,
                        confirmButtonText: 'Turn off GPS',
                        cancelButtonText: 'Keep tracking',
                        reverseButtons: true,
                    });
                    if (!confirmation.isConfirmed) {
                        return;
                    }
                }
                stopGpsTracking();
            } else {
                await confirmAndStartGpsTracking();
            }
        });

        nearbyButton.addEventListener('click', async () => {
            if (notificationsEnabled) {
                setNearbyNotificationsEnabled(false);
                mapNote.textContent = 'Nearby notifications are off. Browser permission remains enabled; change it in site settings to revoke permission.';
                if (window.Swal) {
                    await window.Swal.fire({
                        icon: 'info',
                        title: 'Nearby notifications turned off',
                        text: 'BrakeSafe will stop sending alerts and close any visible alerts. Browser notification permission cannot be revoked by a webpage; change it in your browser site settings if you also want to revoke permission.',
                    });
                }
                return;
            }

            if (!('Notification' in window)) {
                gpsStatus.textContent = 'Browser notifications are not available.';
                if (window.Swal) {
                    await window.Swal.fire({
                        icon: 'error',
                        title: 'Notifications unavailable',
                        text: 'This browser does not support desktop notifications.',
                    });
                }
                return;
            }

            if (window.Swal) {
                const confirmation = await window.Swal.fire({
                    icon: 'question',
                    title: 'Enable nearby notifications?',
                    text: 'Allow browser notifications for brake events and saved accident-prone areas within 500 meters. GPS tracking and this dashboard must remain active.',
                    showCancelButton: true,
                    confirmButtonText: 'Continue',
                    cancelButtonText: 'Not now',
                    reverseButtons: true,
                });
                if (!confirmation.isConfirmed) {
                    return;
                }
            }

            try {
                const permission = Notification.permission === 'granted'
                    ? 'granted'
                    : await Notification.requestPermission();
                if (permission !== 'granted') {
                    const blocked = permission === 'denied';
                    setNearbyNotificationsEnabled(false, blocked
                        ? 'Notifications are blocked. Allow them in browser site settings.'
                        : 'Notification permission has not been granted.');
                    gpsStatus.textContent = blocked
                        ? 'Notifications are blocked for this site. Allow them in your browser site settings, then try again.'
                        : 'Notification permission was not granted.';
                    if (window.Swal) {
                        await window.Swal.fire({
                            icon: 'warning',
                            title: blocked ? 'Notifications are blocked' : 'Permission not granted',
                            text: blocked
                                ? 'Allow notifications for this site in your browser settings, then click the bell again.'
                                : 'Allow browser notifications to receive nearby brake-event and accident-prone-area alerts.',
                        });
                    }
                    return;
                }
            } catch (error) {
                console.error('Unable to request nearby notification permission.', error);
                gpsStatus.textContent = 'Unable to request browser notification permission.';
                if (window.Swal) {
                    await window.Swal.fire({
                        icon: 'error',
                        title: 'Permission request failed',
                        text: 'Your browser could not request notification permission. Check its site settings and try again.',
                    });
                }
                return;
            }

            if (!await confirmAndStartGpsTracking()) {
                gpsStatus.textContent = 'GPS tracking was not started; nearby notifications remain off.';
                return;
            }

            setNearbyNotificationsEnabled(true);
            mapNote.textContent = 'Nearby notifications are on for brake events and saved accident-prone areas within 500 meters. Events within 200 meters blink on the map. Keep the dashboard open and GPS tracking enabled.';
            try {
                const notification = new Notification('Nearby notifications enabled', {
                    body: 'You will be alerted when a brake event or saved accident-prone area is within 500 meters.',
                    tag: 'brakesafe-notifications-enabled',
                });
                activeNearbyNotifications.add(notification);
                notification.onclose = () => activeNearbyNotifications.delete(notification);
            } catch (error) {
                console.error('Unable to show the nearby notifications confirmation.', error);
            }
            if (window.Swal) {
                await window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Nearby notifications enabled',
                    text: 'You will be alerted when a target is within 500 meters.',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                });
            }
        });

        const themeButton = document.getElementById('theme-toggle');
        themeButton.addEventListener('click', () => {
            const isLight = document.getElementById('dashboard-root').classList.toggle('dashboard-light');
            themeButton.setAttribute('aria-label', isLight ? 'Switch to dark theme' : 'Switch to light theme');
            themeButton.querySelector('span').textContent = isLight ? 'Light theme' : 'Dark theme';
        });

        if (resumeGpsAfterReload && !startGpsTracking()) {
            writeSessionFlag(gpsSessionKey, false);
        }
        if (resumeNotificationsAfterReload) {
            if (resumeGpsAfterReload && window.Notification && Notification.permission === 'granted') {
                setNearbyNotificationsEnabled(true);
            } else {
                writeSessionFlag(notificationsSessionKey, false);
            }
        }

    });
</script>
<style>
    #dashboard-map .gps-location-icon {
        background: transparent;
        border: 0;
    }
    #dashboard-map .gps-location-arrow,
    #dashboard-map .gps-location-arrow svg {
        width: 40px;
        height: 40px;
        display: block;
        transform-origin: center;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.75));
    }
    #dashboard-map .dashboard-brake-event-icon {
        background: transparent;
        border: 0;
    }
    #dashboard-map .dashboard-brake-event-marker,
    #dashboard-map .dashboard-brake-event-marker svg {
        width: 34px;
        height: 34px;
        display: block;
        color: var(--brake-event-color);
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.8));
    }
    #dashboard-map .dashboard-brake-event-marker-nearby {
        animation: dashboard-brake-event-blink 0.9s ease-in-out infinite;
    }
    @keyframes dashboard-brake-event-blink {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.45; transform: scale(1.18); }
    }
    @media (prefers-reduced-motion: reduce) {
        #dashboard-map .dashboard-brake-event-marker-nearby {
            animation: none;
            filter: drop-shadow(0 0 8px var(--brake-event-color));
        }
    }
    #nearby-notifications-toggle.nearby-notifications-enabled {
        border-color: rgba(250, 204, 21, 0.45);
        background-color: rgba(250, 204, 21, 0.12);
        color: #facc15;
    }
    html[data-admin-theme="light"] #nearby-notifications-toggle.nearby-notifications-enabled {
        border-color: #facc15;
        background-color: #fef9c3;
        color: #a16207;
    }
    #dashboard-root.dashboard-light .dashboard-card {
        background-color: #f8fafc;
        border-color: #cbd5e1;
    }
    #dashboard-root.dashboard-light h1,
    #dashboard-root.dashboard-light h2,
    #dashboard-root.dashboard-light h3 {
        color: #0f172a;
    }
    #dashboard-root.dashboard-light p,
    #dashboard-root.dashboard-light span {
        --tw-text-opacity: 1;
        color: #475569;
    }
</style>
@endpush
