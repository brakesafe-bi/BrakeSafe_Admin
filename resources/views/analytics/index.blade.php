@extends('layouts.admin')

@section('title', 'Analytics - BrakeSafe')
@section('page-title', 'Analytics')

@section('content')
    @php
        $minimumSeveritySegmentHeight = 6;
        $maximumSeverityBarHeight = 144;
        $severityColors = [
            'soft' => '#22c55e',
            'moderate' => '#eab308',
            'hard' => '#f43f5e',
            'emergency' => '#f97316',
        ];
    @endphp
    <script id="brakesafe-live-data" type="application/json">@json(['events' => $mappedEvents, 'barangaysByMunicipality' => $barangaysByMunicipality])</script>
    <div id="analytics-page" class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <button id="analytics-nav-toggle" type="button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="true" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#111317] text-slate-200 transition hover:bg-[#202329] focus:outline-none focus:ring-2 focus:ring-cyan-400/50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">Analytics</h1>
            </div>
            <div class="flex min-w-0 flex-wrap items-center justify-end gap-4">
                <button id="analytics-theme-toggle" type="button" aria-label="Switch to light theme" title="Switch to light theme" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/10 bg-slate-950 text-slate-100 shadow-[0_20px_40px_rgba(0,0,0,0.25)] transition duration-200 ease-out hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                    <span class="sr-only">Dark theme</span>
                </button>
                @include('layouts.admin-user-indicator')
            </div>
        </header>
        <p id="analytics-control-status" class="sr-only" aria-live="polite"></p>

        <section aria-labelledby="top-barangays-title" class="analytics-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 id="top-barangays-title" class="text-lg font-semibold text-white">Top {{ count($topBarangays) }} barangays by brake severity</h2>
                    <p class="mt-1 text-sm leading-relaxed text-slate-400">Database events grouped by barangay and brake severity</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <p id="barangay-scope" class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">All municipalities</p>
                    <p id="barangay-count" class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">{{ count($topBarangays) }} barangays</p>
                </div>
            </div>
            <ul aria-label="Brake severity legend" class="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-b border-white/10 pb-4">
                <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>Soft</li>
                <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>Moderate</li>
                <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>Hard</li>
                <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span>Emergency</li>
            </ul>
            <div id="top-barangays-content" class="mt-4" aria-live="polite">
                @if($topBarangays === [])
                    <p id="top-barangays-empty" class="rounded-xl border border-dashed border-white/10 bg-[#0b0d10] px-4 py-8 text-center text-sm text-slate-400">No barangay values are available in the current database records.</p>
                @else
                    @php($maxBarangayTotal = max(array_column($topBarangays, 'total')))
                    <div class="top-barangays-scroll overflow-x-auto pb-2">
                        <div class="top-barangays-chart grid min-w-[760px] grid-cols-10 items-start gap-3" role="list" aria-label="Top {{ count($topBarangays) }} barangays by brake severity, vertical stacked bar chart">
                            @foreach($topBarangays as $barangay)
                                @php($barSegmentHeights = array_map(fn ($count) => $count > 0 ? max($minimumSeveritySegmentHeight, ($count / $maxBarangayTotal) * $maximumSeverityBarHeight) : 0, $barangay['counts']))
                                @php($barHeight = array_sum($barSegmentHeights))
                                <div class="top-barangay-column flex min-w-0 flex-col items-center gap-2" role="listitem">
                                    <div class="top-barangay-track relative w-full rounded-t-lg bg-white/[0.035]" style="--bar-height: {{ number_format($barHeight, 4, '.', '') }}px" role="img" aria-label="{{ $barangay['municipality'] }}, {{ $barangay['name'] }}: {{ $barangay['total'] }} total events">
                                        <p class="top-barangay-total absolute left-0 right-0 text-center text-xs font-medium text-slate-300">{{ number_format($barangay['total']) }}</p>
                                        <div class="top-barangay-bar absolute bottom-0 left-1/2 flex w-full max-w-10 -translate-x-1/2 flex-col-reverse overflow-hidden rounded-t-md" style="height: var(--bar-height); background-color: #111317">
                                            @foreach($severityColors as $severity => $color)
                                                @if(($barangay['counts'][$severity] ?? 0) > 0)
                                                    <span class="top-barangay-segment" tabindex="0" data-barangay="{{ $barangay['name'] }}" data-municipality="{{ $barangay['municipality'] }}" data-severity="{{ ucfirst($severity) }}" data-event-count="{{ $barangay['counts'][$severity] }}" data-total="{{ $barangay['total'] }}" aria-label="{{ $barangay['municipality'] }}, {{ $barangay['name'] }}: {{ ucfirst($severity) }}, {{ number_format($barangay['counts'][$severity]) }} brake events" style="height: {{ number_format($barSegmentHeights[$severity], 4, '.', '') }}px; background-color: {{ $color }}"></span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="w-full text-center">
                                        <p class="top-barangay-name break-words text-xs leading-tight text-slate-300">{{ $barangay['name'] }}</p>
                                        <p class="top-barangay-municipality mt-1 text-[11px] leading-tight text-slate-500">{{ $barangay['municipality'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            <div id="barangay-chart-tooltip" role="tooltip" class="pointer-events-none fixed z-[2000] hidden max-w-56 rounded-lg border border-white/15 bg-[#090a0c] px-3 py-2 text-xs text-white shadow-xl"></div>
        </section>

        <section aria-label="Analytics filters" class="analytics-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_auto] xl:items-end">
                <div>
                    <label for="analytics-municipality" class="mb-1.5 block text-xs font-medium text-slate-300">Municipality</label>
                    <select id="analytics-municipality" class="analytics-select min-h-10 w-full rounded-xl border border-white/10 bg-[#0b0d10] px-3 text-sm text-slate-200 focus:border-cyan-400/50 focus:outline-none focus:ring-2 focus:ring-cyan-400/30">
                        <option value="All municipalities">All municipalities</option>
                        <option value="Bantayan">Bantayan</option>
                        <option value="Madridejos">Madridejos</option>
                        <option value="Santa Fe">Santa Fe</option>
                    </select>
                </div>
                <div>
                    <label for="analytics-barangay" class="mb-1.5 block text-xs font-medium text-slate-300">Barangay</label>
                    <select id="analytics-barangay" disabled class="analytics-select min-h-10 w-full rounded-xl border border-white/10 bg-[#0b0d10] px-3 text-sm text-slate-200 focus:border-cyan-400/50 focus:outline-none focus:ring-2 focus:ring-cyan-400/30 disabled:cursor-not-allowed disabled:opacity-60">
                        <option value="All barangays">All barangays</option>
                    </select>
                </div>
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="analytics-event-date" class="block text-xs font-medium text-slate-300">Event date</label>
                        <label for="analytics-available-event-date" class="sr-only">Choose a date available in the database</label>
                        <select id="analytics-available-event-date" class="analytics-select min-h-8 max-w-[55%] rounded-lg border border-white/10 bg-[#0b0d10] px-2 text-xs text-slate-200 focus:border-cyan-400/50 focus:outline-none focus:ring-2 focus:ring-cyan-400/30">
                            <option value="all" selected>All dates</option>
                            @foreach($availableEventDates as $eventDate)
                                <option value="{{ $eventDate }}">{{ \Carbon\Carbon::createFromFormat('Y-m-d', $eventDate)->format('m/d/Y') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input id="analytics-event-date" type="date" class="analytics-select min-h-10 w-full rounded-xl border border-white/10 bg-[#0b0d10] px-3 text-sm text-slate-200 focus:border-cyan-400/50 focus:outline-none focus:ring-2 focus:ring-cyan-400/30">
                </div>
                <button id="analytics-search" type="button" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-red-500 px-5 text-sm font-semibold text-white transition hover:bg-red-400 focus:outline-none focus:ring-2 focus:ring-red-300/60 focus:ring-offset-2 focus:ring-offset-[#111317]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    Search
                </button>
            </div>
            <p id="analytics-selection-summary" class="mt-4 text-xs text-slate-400">Selected: All municipalities / All barangays / All days</p>
        </section>

        <section aria-labelledby="analytics-map-title" class="analytics-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 id="analytics-map-title" class="text-lg font-semibold text-white">Brake event map</h2>
                    <p id="analytics-map-count" class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">{{ number_format($mappedEvents->count()) }} mapped events</p>
                </div>
                <div class="inline-flex self-start rounded-xl border border-white/10 bg-[#0b0d10] p-1" role="group" aria-label="Map display mode">
                    <button id="analytics-mode-heatmap" type="button" aria-pressed="true" class="analytics-map-mode rounded-lg bg-[#292d33] px-3 py-1.5 text-xs font-semibold text-white focus:outline-none focus:ring-2 focus:ring-cyan-400/50">Heatmap</button>
                    <button id="analytics-mode-route" type="button" aria-pressed="false" class="analytics-map-mode rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-400 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-400/50">Road route</button>
                </div>
            </div>
            <div class="relative mt-4 h-[450px] overflow-hidden rounded-xl border border-white/10 bg-[#17191d] sm:h-[480px]">
                <div id="analytics-map" class="absolute inset-0" role="application" aria-label="Brake event map"></div>
                <div class="absolute left-3 top-3 z-[500] flex flex-col gap-2">
                    <button id="analytics-zoom-in" type="button" aria-label="Zoom in" title="Zoom in" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/15 bg-[#111317]/95 text-xl text-slate-100 shadow-lg transition hover:bg-[#25282d] focus:outline-none focus:ring-2 focus:ring-cyan-400/60">+</button>
                    <button id="analytics-zoom-out" type="button" aria-label="Zoom out" title="Zoom out" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/15 bg-[#111317]/95 text-xl text-slate-100 shadow-lg transition hover:bg-[#25282d] focus:outline-none focus:ring-2 focus:ring-cyan-400/60">−</button>
                </div>
                <div id="analytics-map-empty" class="pointer-events-none absolute inset-0 z-[450] flex items-center justify-center px-5 {{ $mappedEvents->isNotEmpty() ? 'hidden' : '' }}">
                    <p class="max-w-md rounded-xl border border-white/10 bg-[#0b0d10]/95 px-5 py-4 text-center text-sm font-medium leading-relaxed text-slate-300 shadow-xl">No mapped brake events are available in the database.</p>
                </div>
                @if(!$mapTilerKey)
                    <p role="alert" class="absolute bottom-3 left-3 right-3 z-[600] rounded-lg border border-amber-500/30 bg-[#0b0d10]/95 px-3 py-2 text-xs text-amber-200">MapTiler is not configured. Set MAPTILER_API_KEY to load the map.</p>
                @endif
            </div>
            <div id="analytics-map-attribution" class="map-attribution-outside" hidden></div>
            <div class="mt-4 border-t border-white/10 pt-4">
                <div aria-label="Heatmap density: low to high" class="mx-auto h-3 w-full max-w-sm rounded-full bg-gradient-to-r from-green-500 via-yellow-400 via-orange-500 to-red-500"></div>
                <ul aria-label="Brake severity map legend" class="mt-3 flex flex-wrap items-center justify-center gap-x-7 gap-y-3 sm:justify-evenly">
                    <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>Soft</li>
                    <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>Moderate</li>
                    <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>Hard</li>
                    <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span>Emergency</li>
                    <li class="analytics-route-legend-item hidden items-center gap-2 text-xs text-slate-300">
                        <svg class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 1 22 23 12 18 2 23Z" fill="currentColor" stroke="currentColor" stroke-linejoin="round"/></svg>
                        Start
                    </li>
                    <li class="analytics-route-legend-item hidden items-center gap-2 text-xs text-slate-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 1 22 23 12 18 2 23Z" fill="#111111" stroke="#e5e7eb" stroke-linejoin="round"/></svg>
                        End
                    </li>
                </ul>
            </div>
        </section>

        <div id="severity-summary-grid" class="grid gap-5 xl:grid-cols-2">
            <section aria-labelledby="severity-summary-title" class="analytics-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="severity-summary-title" class="text-lg font-semibold text-white">Brake severity</h2>
                    <p id="analytics-total-count" class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">{{ number_format($datasetEvents->count()) }} events</p>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-white/10 bg-[#0b0d10] p-4">
                        <dt class="text-xs text-green-400">Soft</dt>
                        <dd id="severity-soft" class="mt-2 text-2xl font-semibold text-white">{{ $severityCounts['soft'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-[#0b0d10] p-4">
                        <dt class="text-xs text-yellow-400">Moderate</dt>
                        <dd id="severity-moderate" class="mt-2 text-2xl font-semibold text-white">{{ $severityCounts['moderate'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-[#0b0d10] p-4">
                        <dt class="text-xs text-rose-400">Hard</dt>
                        <dd id="severity-hard" class="mt-2 text-2xl font-semibold text-white">{{ $severityCounts['hard'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-[#0b0d10] p-4">
                        <dt class="text-xs text-orange-400">Emergency</dt>
                        <dd id="severity-emergency" class="mt-2 text-2xl font-semibold text-white">{{ $severityCounts['emergency'] }}</dd>
                    </div>
                </dl>
            </section>

            <section aria-labelledby="data-checks-title" class="analytics-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
                <h2 id="data-checks-title" class="text-lg font-semibold text-white">Data checks</h2>
                <p class="mt-1 text-sm text-slate-400">Location quality and peak readings.</p>
                <dl class="mt-4 divide-y divide-white/10">
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-sm text-slate-400">Invalid location rows</dt>
                        <dd id="check-invalid-locations" class="text-sm font-medium text-slate-200">{{ number_format($dataChecks['invalidLocationRows']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-sm text-slate-400">Maximum speed</dt>
                        <dd id="check-max-speed" class="text-sm font-medium text-slate-200">{{ number_format($dataChecks['maximumSpeed'], 2) }} km/h</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-sm text-slate-400">Maximum deceleration</dt>
                        <dd id="check-max-deceleration" class="text-sm font-medium text-slate-200">{{ number_format($dataChecks['maximumDeceleration'], 2) }} g</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-sm text-slate-400">Mapped locations</dt>
                        <dd id="check-mapped-locations" class="text-sm font-medium text-slate-200">{{ number_format($dataChecks['mappedLocations']) }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        let events = @json($mappedEvents);
        const topBarangays = @json($topBarangays);
        let barangaysByMunicipality = @json($barangaysByMunicipality);
        const severityColors = @json($severityColors);
        const mapTilerKey = @json($mapTilerKey);
        const municipalityFilter = document.getElementById('analytics-municipality');
        const barangayFilter = document.getElementById('analytics-barangay');
        const dateFilter = document.getElementById('analytics-event-date');
        const availableDateFilter = document.getElementById('analytics-available-event-date');
        const mapElement = document.getElementById('analytics-map');
        const emptyState = document.getElementById('analytics-map-empty');
        const map = typeof L !== 'undefined' && mapElement
            ? L.map(mapElement, { zoomControl: false, maxZoom: 16 }).fitBounds([[11.10, 123.62], [11.34, 123.84]], { padding: [24, 24], maxZoom: 11 })
            : null;
        let routeLayer = null;
        let mapMode = 'heatmap';
        let filteredEvents = events;
        let heatmapCanvas = null;
        let heatmapContext = null;
        const minimumSeveritySegmentHeight = 6;
        const maximumSeverityBarHeight = 144;

        if (map) {
            window.BrakeSafeMapAttribution?.move(map, 'analytics-map-attribution');
            window.BrakeSafeMapLayout?.observe(map, mapElement);
            heatmapCanvas = document.createElement('canvas');
            heatmapCanvas.setAttribute('aria-hidden', 'true');
            heatmapCanvas.style.cssText = 'position:absolute;inset:0;z-index:450;pointer-events:none';
            mapElement.append(heatmapCanvas);
            heatmapContext = heatmapCanvas.getContext('2d');
            map.on('moveend zoomend resize', () => drawHeatmap(filteredEvents));
        }
        if (map && mapTilerKey) {
            const tiles = L.tileLayer(window.BrakeSafeMapTheme.tileUrl(mapTilerKey), {
                maxZoom: 16,
                attribution: '&copy; <a href="https://www.maptiler.com/copyright/">MapTiler</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }).addTo(map);
            window.BrakeSafeMapTheme.watch(map, tiles, mapTilerKey);
            window.setTimeout(() => map.invalidateSize(), 100);
        } else if (!map) {
            mapElement.setAttribute('aria-label', 'Map unavailable because the map library did not load');
        }

        const drawHeatmap = (points) => {
            if (!map || !heatmapCanvas || !heatmapContext) return;
            const size = map.getSize();
            const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
            heatmapCanvas.width = Math.max(1, Math.round(size.x * pixelRatio));
            heatmapCanvas.height = Math.max(1, Math.round(size.y * pixelRatio));
            heatmapCanvas.style.width = `${size.x}px`;
            heatmapCanvas.style.height = `${size.y}px`;
            const width = heatmapCanvas.width;
            const height = heatmapCanvas.height;
            const radius = Math.round(Math.max(32, Math.min(58, map.getZoom() * 4.5)) * pixelRatio);
            const kernelSize = radius * 2 + 1;
            const kernel = new Float32Array(kernelSize * kernelSize);
            const sigma = radius * 0.48;
            const sigmaFactor = 1 / (2 * sigma * sigma);
            for (let y = -radius; y <= radius; y++) {
                for (let x = -radius; x <= radius; x++) {
                    const distance = x * x + y * y;
                    if (distance <= radius * radius) {
                        kernel[(y + radius) * kernelSize + x + radius] = Math.exp(-distance * sigmaFactor);
                    }
                }
            }
            const density = new Float32Array(width * height);
            points.forEach((event) => {
                const point = map.latLngToContainerPoint([event.latitude, event.longitude]);
                const centerX = Math.round(point.x * pixelRatio);
                const centerY = Math.round(point.y * pixelRatio);
                for (let offsetY = -radius; offsetY <= radius; offsetY++) {
                    const y = centerY + offsetY;
                    if (y < 0 || y >= height) continue;
                    const kernelRow = (offsetY + radius) * kernelSize;
                    const row = y * width;
                    for (let offsetX = -radius; offsetX <= radius; offsetX++) {
                        const x = centerX + offsetX;
                        if (x < 0 || x >= width) continue;
                        density[row + x] += kernel[kernelRow + offsetX + radius];
                    }
                }
            });

            let peakDensity = 0;
            density.forEach((value) => { peakDensity = Math.max(peakDensity, value); });
            if (peakDensity === 0) return;
            const colorStops = [
                [0.02, [34, 197, 94]],
                [0.25, [250, 204, 21]],
                [0.62, [249, 115, 22]],
                [0.98, [239, 68, 68]],
            ];
            const image = heatmapContext.createImageData(width, height);
            const pixels = image.data;
            for (let index = 0; index < density.length; index++) {
                const intensity = density[index] / peakDensity;
                if (intensity < colorStops[0][0]) {
                    continue;
                }
                let lower = colorStops[0];
                let upper = colorStops[colorStops.length - 1];
                if (intensity >= upper[0]) {
                    lower = upper;
                } else {
                    for (let stop = 1; stop < colorStops.length; stop++) {
                        if (intensity <= colorStops[stop][0]) {
                            upper = colorStops[stop];
                            break;
                        }
                        lower = colorStops[stop];
                    }
                }
                const range = upper[0] - lower[0];
                const mix = range === 0 ? 0 : Math.max(0, Math.min(1, (intensity - lower[0]) / range));
                const pixel = index * 4;
                pixels[pixel] = Math.round(lower[1][0] + (upper[1][0] - lower[1][0]) * mix);
                pixels[pixel + 1] = Math.round(lower[1][1] + (upper[1][1] - lower[1][1]) * mix);
                pixels[pixel + 2] = Math.round(lower[1][2] + (upper[1][2] - lower[1][2]) * mix);
                pixels[pixel + 3] = Math.min(210, Math.round(Math.pow(intensity, 0.65) * 190));
            }
            heatmapContext.putImageData(image, 0, 0);
        };
        const refreshBarangays = (selectedBarangay = 'All barangays') => {
            const municipality = municipalityFilter.value;
            barangayFilter.replaceChildren(new Option('All barangays', 'All barangays'));
            barangayFilter.value = 'All barangays';
            barangayFilter.disabled = municipality === 'All municipalities';
            if (barangayFilter.disabled) return;

            (barangaysByMunicipality[municipality] || [])
                .forEach((barangay) => barangayFilter.add(new Option(barangay, barangay)));
            if ([...barangayFilter.options].some((option) => option.value === selectedBarangay)) {
                barangayFilter.value = selectedBarangay;
            }
        };

        const renderTopBarangays = (selectedEvents) => {
            const counts = new Map();
            selectedEvents.forEach((event) => {
                const barangay = (event.barangay || '').trim();
                if (!barangay) return;
                const key = `${event.municipality}\u0000${barangay}`;
                if (!counts.has(key)) {
                    counts.set(key, {
                        name: barangay,
                        municipality: event.municipality,
                        soft: 0,
                        moderate: 0,
                        hard: 0,
                        emergency: 0,
                    });
                }
                const classification = event.classification === 'medium' ? 'moderate' : event.classification;
                if (Object.prototype.hasOwnProperty.call(counts.get(key), classification)) {
                    counts.get(key)[classification]++;
                }
            });

            const rows = [...counts.entries()]
                .map(([, row]) => ({
                    name: row.name,
                    municipality: row.municipality,
                    severities: {
                        soft: row.soft,
                        moderate: row.moderate,
                        hard: row.hard,
                        emergency: row.emergency,
                    },
                    total: row.soft + row.moderate + row.hard + row.emergency,
                }))
                .sort((a, b) => b.total - a.total)
                .slice(0, 10);
            const content = document.getElementById('top-barangays-content');
            const municipality = municipalityFilter.value;
            const barangay = barangayFilter.value;
            document.getElementById('barangay-scope').textContent = barangay !== 'All barangays'
                ? `${municipality} / ${barangay}`
                : municipality;
            document.getElementById('barangay-count').textContent = `${rows.length} barangays`;
            document.getElementById('top-barangays-title').textContent = `Top ${rows.length} barangays by brake severity`;
            if (rows.length === 0) {
                const emptyState = document.createElement('p');
                emptyState.id = 'top-barangays-empty';
                emptyState.className = 'rounded-xl border border-dashed border-white/10 bg-[#0b0d10] px-4 py-8 text-center text-sm text-slate-400';
                emptyState.textContent = 'No barangay values are available in the current database records.';
                content.replaceChildren(emptyState);
                return;
            }

            const maxTotal = Math.max(...rows.map((row) => row.total));
            const chart = document.createElement('div');
            chart.className = 'top-barangays-chart grid min-w-[760px] grid-cols-10 items-start gap-3';
            chart.setAttribute('role', 'list');
            chart.setAttribute('aria-label', `Top ${rows.length} barangays by brake severity, vertical stacked bar chart`);

            rows.forEach((row) => {
                const segmentHeights = Object.fromEntries(
                    Object.entries(row.severities).map(([severity, value]) => [
                        severity,
                        value ? Math.max(minimumSeveritySegmentHeight, value / maxTotal * maximumSeverityBarHeight) : 0,
                    ])
                );
                const barHeight = Object.values(segmentHeights).reduce((total, height) => total + height, 0);
                const column = document.createElement('div');
                column.className = 'top-barangay-column flex min-w-0 flex-col items-center gap-2';
                column.setAttribute('role', 'listitem');
                const track = document.createElement('div');
                track.className = 'top-barangay-track relative w-full rounded-t-lg bg-white/[0.035]';
                track.style.setProperty('--bar-height', `${barHeight}px`);
                track.setAttribute('role', 'img');
                track.setAttribute('aria-label', `${row.municipality}, ${row.name}: ${row.total} total events`);
                const total = document.createElement('p');
                total.className = 'top-barangay-total absolute left-0 right-0 text-center text-xs font-medium text-slate-300';
                total.textContent = row.total.toLocaleString();
                const bar = document.createElement('div');
                bar.className = 'top-barangay-bar absolute bottom-0 left-1/2 flex w-full max-w-10 -translate-x-1/2 flex-col-reverse overflow-hidden rounded-t-md';
                bar.style.height = 'var(--bar-height)';
                bar.style.backgroundColor = '#111317';
                ['soft', 'moderate', 'hard', 'emergency'].forEach((severity) => {
                    const value = row.severities[severity];
                    if (!value) return;
                    const segment = document.createElement('span');
                    segment.className = 'top-barangay-segment';
                    segment.tabIndex = 0;
                    segment.dataset.barangay = row.name;
                    segment.dataset.municipality = row.municipality;
                    segment.dataset.severity = `${severity[0].toUpperCase()}${severity.slice(1)}`;
                    segment.dataset.eventCount = String(value);
                    segment.dataset.total = String(row.total);
                    segment.setAttribute('aria-label', `${row.municipality}, ${row.name}: ${segment.dataset.severity}, ${value.toLocaleString()} brake events`);
                    segment.style.height = `${segmentHeights[severity]}px`;
                    segment.style.backgroundColor = severityColors[severity];
                    bar.append(segment);
                });
                track.append(total);
                track.append(bar);
                const name = document.createElement('p');
                name.className = 'top-barangay-name w-full break-words text-center text-xs leading-tight text-slate-300';
                name.textContent = row.name;
                const municipalityLabel = document.createElement('p');
                municipalityLabel.className = 'top-barangay-municipality mt-1 w-full break-words text-center text-[11px] leading-tight text-slate-500';
                municipalityLabel.textContent = row.municipality;
                const label = document.createElement('div');
                label.className = 'w-full text-center';
                label.append(name, municipalityLabel);
                column.append(track, label);
                chart.append(column);
            });

            const scroll = document.createElement('div');
            scroll.className = 'top-barangays-scroll overflow-x-auto pb-2';
            scroll.append(chart);
            content.replaceChildren(scroll);
        };

        const chartTooltip = document.getElementById('barangay-chart-tooltip');
        const showChartTooltip = (segment, x, y) => {
            const { barangay, municipality, severity, eventCount, total } = segment.dataset;
            chartTooltip.replaceChildren();
            const title = document.createElement('p');
            title.className = 'font-semibold text-white';
            title.textContent = `${barangay} · ${municipality}`;
            const value = document.createElement('p');
            value.className = 'mt-1 text-slate-200';
            value.textContent = `${severity}: ${Number(eventCount).toLocaleString()} brake events`;
            const totalValue = document.createElement('p');
            totalValue.className = 'mt-1 text-slate-400';
            totalValue.textContent = `Total: ${Number(total).toLocaleString()} events`;
            chartTooltip.append(title, value, totalValue);
            chartTooltip.classList.remove('hidden');

            const gap = 14;
            const tooltipRect = chartTooltip.getBoundingClientRect();
            chartTooltip.style.left = `${Math.min(x + gap, window.innerWidth - tooltipRect.width - 8)}px`;
            chartTooltip.style.top = `${Math.max(8, Math.min(y + gap, window.innerHeight - tooltipRect.height - 8))}px`;
        };
        const hideChartTooltip = () => chartTooltip.classList.add('hidden');
        const chartContent = document.getElementById('top-barangays-content');
        chartContent.addEventListener('pointermove', (event) => {
            const segment = document.elementFromPoint(event.clientX, event.clientY)?.closest('.top-barangay-segment');
            if (!segment || !chartContent.contains(segment)) {
                hideChartTooltip();
                return;
            }
            showChartTooltip(segment, event.clientX, event.clientY);
        });
        chartContent.addEventListener('pointerleave', hideChartTooltip);
        chartContent.addEventListener('focusin', (event) => {
            const segment = event.target.closest('.top-barangay-segment');
            if (segment) {
                const rect = segment.getBoundingClientRect();
                showChartTooltip(segment, rect.left + rect.width / 2, rect.top);
            }
        });
        chartContent.addEventListener('focusout', hideChartTooltip);
        chartContent.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') hideChartTooltip();
        });

        const renderEvents = (fitMap = true) => {
            const municipality = municipalityFilter.value;
            const barangay = barangayFilter.value;
            const selectedDate = dateFilter.value;
            filteredEvents = events.filter((event) =>
                (municipality === 'All municipalities' || event.municipality === municipality)
                && (barangay === 'All barangays' || event.barangay === barangay)
                && (!selectedDate || event.timestamp?.slice(0, 10) === selectedDate)
            );

            const eventDay = selectedDate || 'All days';
            document.getElementById('analytics-selection-summary').textContent = `Selected: ${municipality} / ${barangay} / ${eventDay}`;
            document.getElementById('analytics-map-count').textContent = `${filteredEvents.length.toLocaleString()} mapped events`;
            document.getElementById('analytics-total-count').textContent = `${filteredEvents.length.toLocaleString()} events`;
            const counts = { soft: 0, moderate: 0, hard: 0, emergency: 0 };
            filteredEvents.forEach((event) => {
                const classification = event.classification === 'medium' ? 'moderate' : event.classification;
                if (Object.prototype.hasOwnProperty.call(counts, classification)) counts[classification]++;
            });
            Object.entries(counts).forEach(([severity, value]) => {
                document.getElementById(`severity-${severity}`).textContent = value.toLocaleString();
            });
            document.getElementById('check-mapped-locations').textContent = filteredEvents.length.toLocaleString();
            renderTopBarangays(filteredEvents);

            const hasPoints = filteredEvents.length > 0;
            emptyState.classList.toggle('hidden', hasPoints);
            routeLayer?.remove();
            routeLayer = null;
            if (heatmapCanvas) heatmapCanvas.style.display = mapMode === 'heatmap' ? 'block' : 'none';
            if (!map) return;
            if (!hasPoints) {
                drawHeatmap([]);
                return;
            }

            const eventBounds = L.latLngBounds(filteredEvents.map((event) => [event.latitude, event.longitude]));
            if (fitMap) {
                map.fitBounds(eventBounds, {
                    padding: [30, 30],
                    maxZoom: municipality === 'All municipalities' ? 11 : 12,
                });
            }
            if (mapMode === 'heatmap') {
                drawHeatmap(filteredEvents);
                return;
            }

            const orderedEvents = [...filteredEvents].sort((a, b) => {
                const firstTime = Date.parse(a.timestamp || '');
                const secondTime = Date.parse(b.timestamp || '');
                if (Number.isFinite(firstTime) && Number.isFinite(secondTime)) return firstTime - secondTime;
                if (Number.isFinite(firstTime)) return -1;
                if (Number.isFinite(secondTime)) return 1;
                return (a.timestamp || '').localeCompare(b.timestamp || '');
            });
            if (orderedEvents.length < 2) return;

            routeLayer = L.layerGroup().addTo(map);
            L.polyline(orderedEvents.map((event) => [event.latitude, event.longitude]), {
                color: '#9ca3af',
                opacity: 0.9,
                weight: 3,
                dashArray: '8 8',
                lineCap: 'butt',
                lineJoin: 'round',
            }).addTo(routeLayer);

            const addArrow = (event, fromEvent, toEvent, color) => {
                const start = L.latLng(fromEvent.latitude, fromEvent.longitude);
                const next = L.latLng(toEvent.latitude, toEvent.longitude);
                const bearing = (Math.atan2(
                    Math.sin((next.lng - start.lng) * Math.PI / 180) * Math.cos(next.lat * Math.PI / 180),
                    Math.cos(start.lat * Math.PI / 180) * Math.sin(next.lat * Math.PI / 180)
                        - Math.sin(start.lat * Math.PI / 180) * Math.cos(next.lat * Math.PI / 180)
                            * Math.cos((next.lng - start.lng) * Math.PI / 180)
                ) * 180 / Math.PI + 360) % 360;
                const icon = L.divIcon({
                    className: 'analytics-route-arrow',
                    html: `<svg aria-hidden="true" viewBox="0 0 24 24" width="24" height="24"><path d="M12 1 22 23 12 18 2 23Z" fill="${color}" stroke="#e5e7eb" stroke-width="1.5" stroke-linejoin="round" transform="rotate(${bearing} 12 12)"/></svg>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12],
                });
                L.marker([event.latitude, event.longitude], { icon, interactive: false, keyboard: false }).addTo(routeLayer);
            };

            addArrow(orderedEvents[0], orderedEvents[0], orderedEvents[1], '#9ca3af');
            addArrow(orderedEvents[orderedEvents.length - 1], orderedEvents[orderedEvents.length - 2], orderedEvents[orderedEvents.length - 1], '#111111');
        };

        const syncFiltersToUrl = () => {
            const url = new URL(window.location.href);
            const filters = {
                municipality: municipalityFilter.value === 'All municipalities' ? '' : municipalityFilter.value,
                barangay: barangayFilter.value === 'All barangays' ? '' : barangayFilter.value,
                date: dateFilter.value,
            };

            Object.entries(filters).forEach(([key, value]) => {
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            });

            window.history.replaceState({}, '', url);
        };

        const restoreFiltersFromUrl = () => {
            const params = new URLSearchParams(window.location.search);
            const municipality = params.get('municipality');
            if ([...municipalityFilter.options].some((option) => option.value === municipality)) {
                municipalityFilter.value = municipality;
            }

            refreshBarangays(params.get('barangay') || 'All barangays');

            const date = params.get('date') || '';
            if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
                dateFilter.value = date;
                availableDateFilter.value = [...availableDateFilter.options]
                    .some((option) => option.value === date)
                    ? date
                    : 'all';
            }
        };

        municipalityFilter.addEventListener('change', () => {
            refreshBarangays();
            syncFiltersToUrl();
            renderEvents();
        });
        barangayFilter.addEventListener('change', () => {
            syncFiltersToUrl();
            renderEvents();
        });
        availableDateFilter.addEventListener('change', () => {
            if (availableDateFilter.value === 'all') {
                dateFilter.value = '';
            } else if (availableDateFilter.value) {
                dateFilter.value = availableDateFilter.value;
            }
            syncFiltersToUrl();
            renderEvents();
        });
        dateFilter.addEventListener('change', () => {
            const matchingDate = [...availableDateFilter.options]
                .some((option) => option.value === dateFilter.value)
                ? dateFilter.value
                : 'all';
            availableDateFilter.value = matchingDate;
            syncFiltersToUrl();
            renderEvents();
        });
        document.getElementById('analytics-search').addEventListener('click', () => {
            syncFiltersToUrl();
            renderEvents();
        });
        document.getElementById('analytics-zoom-in').addEventListener('click', () => map?.zoomIn());
        document.getElementById('analytics-zoom-out').addEventListener('click', () => map?.zoomOut());
        const setMapMode = (mode) => {
            mapMode = mode;
            document.getElementById('analytics-mode-heatmap').setAttribute('aria-pressed', String(mode === 'heatmap'));
            document.getElementById('analytics-mode-route').setAttribute('aria-pressed', String(mode === 'route'));
            document.getElementById('analytics-mode-heatmap').classList.toggle('bg-[#292d33]', mode === 'heatmap');
            document.getElementById('analytics-mode-heatmap').classList.toggle('text-white', mode === 'heatmap');
            document.getElementById('analytics-mode-route').classList.toggle('bg-[#292d33]', mode === 'route');
            document.getElementById('analytics-mode-route').classList.toggle('text-white', mode === 'route');
            document.querySelectorAll('[aria-label="Brake severity map legend"] li:not(.analytics-route-legend-item)').forEach((item) => {
                item.classList.toggle('hidden', mode === 'route');
                item.classList.toggle('inline-flex', mode !== 'route');
            });
            document.querySelectorAll('.analytics-route-legend-item').forEach((item) => {
                item.classList.toggle('hidden', mode !== 'route');
                item.classList.toggle('inline-flex', mode === 'route');
            });
            renderEvents();
        };
        document.getElementById('analytics-mode-heatmap').addEventListener('click', () => setMapMode('heatmap'));
        document.getElementById('analytics-mode-route').addEventListener('click', () => setMapMode('route'));
        restoreFiltersFromUrl();
        renderEvents();
        window.BrakeSafeMapLayout?.restoreView(map, 'brakesafe-map-analytics');
        window.addEventListener('brakesafe:live-update', (event) => {
            if (event.detail?.pageId !== 'analytics-page' || !Array.isArray(event.detail.data?.events)) return;
            events = event.detail.data.events;
            barangaysByMunicipality = event.detail.data.barangaysByMunicipality || {};
            const selectedBarangay = barangayFilter.value;
            refreshBarangays(selectedBarangay);
            renderEvents(false);
        });

        const navToggle = document.getElementById('analytics-nav-toggle');
        const syncNavToggle = () => {
            const sidebar = document.getElementById('sidebar');
            const isMobile = window.innerWidth < 1024;
            const isOpen = isMobile
                ? document.documentElement.classList.contains('sidebar-visible')
                : !sidebar?.classList.contains('sidebar-collapsed');
            navToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            navToggle.setAttribute('aria-expanded', String(isOpen));
            document.getElementById('sidebar-close')?.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            document.getElementById('sidebar-close')?.setAttribute('aria-expanded', String(isOpen));
        };
        navToggle.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                const isOpen = document.documentElement.classList.toggle('sidebar-visible');
                const sidebar = document.getElementById('sidebar');
                sidebar?.classList.toggle('-translate-x-full', !isOpen);
                if (isOpen && sidebar) window.BrakeSafeAnimateSidebarItems?.(sidebar);
                window.BrakeSafeMapLayout?.notify();
            } else {
                toggleSidebarCollapse();
            }
            window.setTimeout(syncNavToggle, 0);
        });
        syncNavToggle();
        window.addEventListener('resize', syncNavToggle);
        window.addEventListener('brakesafe:sidebar-layout-change', syncNavToggle);
        document.getElementById('sidebar-close')?.addEventListener('click', () => window.setTimeout(syncNavToggle, 0));
        document.getElementById('sidebar-overlay')?.addEventListener('click', () => window.setTimeout(syncNavToggle, 0));

        const themeToggle = document.getElementById('analytics-theme-toggle');
        themeToggle.addEventListener('click', () => {
            const light = document.getElementById('analytics-page').classList.toggle('analytics-light');
            themeToggle.setAttribute('aria-label', light ? 'Switch to dark theme' : 'Switch to light theme');
            themeToggle.querySelector('span').textContent = light ? 'Light theme' : 'Dark theme';
        });

        const notificationsToggle = document.getElementById('analytics-notifications-toggle');
        const controlStatus = document.getElementById('analytics-control-status');
        notificationsToggle.addEventListener('click', async () => {
            const enabled = notificationsToggle.getAttribute('aria-pressed') === 'true';
            if (enabled) {
                window.BrakeSafeNearbyAlerts?.disable();
                notificationsToggle.setAttribute('aria-pressed', 'false');
                notificationsToggle.setAttribute('aria-label', 'Enable nearby notifications');
                notificationsToggle.querySelector('span').textContent = 'Nearby notifications off';
                controlStatus.textContent = 'Nearby notifications off.';
                return;
            }
            if (!('Notification' in window)) {
                controlStatus.textContent = 'Browser notifications are not available.';
                return;
            }
            let permission;
            try {
                permission = Notification.permission === 'default'
                    ? await Notification.requestPermission()
                    : Notification.permission;
            } catch (error) {
                console.error('Unable to request nearby notification permission.', error);
                controlStatus.textContent = 'Unable to request browser notification permission.';
                return;
            }
            if (permission !== 'granted') {
                controlStatus.textContent = 'Notification permission was not granted.';
                return;
            }
            if (!await window.BrakeSafeNearbyAlerts?.enable({ status: controlStatus, button: notificationsToggle })) {
                return;
            }
            notificationsToggle.setAttribute('aria-pressed', 'true');
            notificationsToggle.setAttribute('aria-label', 'Disable nearby notifications');
            notificationsToggle.querySelector('span').textContent = 'Nearby notifications on';
            controlStatus.textContent = 'Nearby notifications on.';
        });
    });
</script>
<style>
    #top-barangays-content .top-barangays-chart {
        grid-template-columns: repeat(10, minmax(0, 1fr));
        min-width: 760px;
    }
    #top-barangays-content .top-barangay-track {
        height: 176px;
    }
    #top-barangays-content .top-barangay-total {
        bottom: calc(var(--bar-height) + 4px);
    }
    #top-barangays-content .top-barangay-bar {
        bottom: 0;
        left: 50%;
        width: 40px;
        max-width: calc(100% - 8px);
        max-height: 100%;
        flex-direction: column-reverse;
        transform: translateX(-50%);
    }
    #top-barangays-content .top-barangay-bar > span {
        flex: 0 0 auto;
        width: 100%;
    }
    #top-barangays-content .top-barangay-segment {
        cursor: crosshair;
        transition: filter 120ms ease;
    }
    #top-barangays-content .top-barangay-segment:hover,
    #top-barangays-content .top-barangay-segment:focus-visible {
        filter: brightness(1.35);
        outline: 2px solid #fff;
        outline-offset: -2px;
    }
    #analytics-map.leaflet-container {
        background-color: #17191d;
    }
    html[data-admin-theme="dark"] #analytics-map .leaflet-tile {
        filter: grayscale(1) brightness(1.15);
    }
    #analytics-page.analytics-light .analytics-card {
        background-color: #f8fafc;
        border-color: #cbd5e1;
    }
    #analytics-page.analytics-light h1,
    #analytics-page.analytics-light h2,
    #analytics-page.analytics-light dd {
        color: #0f172a;
    }
    #analytics-page.analytics-light label,
    #analytics-page.analytics-light .analytics-select,
    #analytics-page.analytics-light #barangay-scope,
    #analytics-page.analytics-light #barangay-count {
        color: #334155;
    }
    #analytics-page.analytics-light p,
    #analytics-page.analytics-light dt,
    #analytics-page.analytics-light li {
        color: #475569;
    }
    #analytics-page.analytics-light .analytics-select {
        background-color: #fff;
        border-color: #cbd5e1;
        color: #0f172a;
    }
    #analytics-page.analytics-light #barangay-scope,
    #analytics-page.analytics-light #barangay-count {
        background-color: #f1f5f9;
        border-color: #cbd5e1;
    }
    @media (min-width: 1024px) {
        #analytics-page [aria-labelledby="severity-summary-title"] #analytics-total-count {
            font-size: 0.875rem;
        }
        #analytics-page [aria-labelledby="severity-summary-title"] dl dt {
            font-size: 1rem;
            line-height: 1.5rem;
        }
        #analytics-page [aria-labelledby="severity-summary-title"] dl dd {
            font-size: clamp(2rem, 2.5vw, 2.25rem);
            line-height: 1.2;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        #analytics-page [aria-labelledby="severity-summary-title"] dl > div {
            padding: 0.75rem;
        }
    }
    @media (min-width: 1280px) {
        #severity-summary-grid {
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
        }
    }
    #sidebar .nav-item[aria-current="page"] {
        background-color: #1c2026 !important;
        color: #f8fafc !important;
        box-shadow: none !important;
    }
    #sidebar .nav-item[aria-current="page"] svg {
        color: #f8fafc !important;
    }
    #sidebar.sidebar-collapsed #sidebar-close {
        display: none !important;
    }
    #sidebar.sidebar-collapsed .nav-item[aria-current="page"] {
        background-color: #1c2026 !important;
        color: #f8fafc !important;
        box-shadow: none !important;
    }
    #sidebar.sidebar-collapsed .nav-item[aria-current="page"] svg {
        color: #f8fafc !important;
    }
</style>
@endpush
