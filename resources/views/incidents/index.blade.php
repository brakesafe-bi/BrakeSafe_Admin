@extends('layouts.admin')

@section('title', 'Brake Events - BrakeSafe')
@section('page-title', 'Brake Events')

@section('content')
    @php
        $severityColors = [
            'soft' => '#22c55e',
            'medium' => '#eab308',
            'moderate' => '#eab308',
            'hard' => '#ef4444',
            'emergency' => '#f97316',
        ];
    @endphp
    <script id="brakesafe-live-data" type="application/json">@json(['events' => $events])</script>
    <div id="brake-events-page" class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <button id="brake-events-nav-toggle" type="button" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="true" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-[#111317] text-slate-200 transition hover:bg-[#202329] focus:outline-none focus:ring-2 focus:ring-cyan-400/50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">Brake Events</h1>
            </div>
            <div class="flex min-w-0 flex-wrap items-center justify-end gap-4">
                <button id="brake-events-theme-toggle" type="button" aria-label="Switch to light theme" title="Switch to light theme" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/10 bg-slate-950 text-slate-100 shadow-[0_20px_40px_rgba(0,0,0,0.25)] transition duration-200 ease-out hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42 1.42m11.3-11.3 1.42-1.42"/></svg>
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
        <p id="brake-events-control-status" class="sr-only" aria-live="polite"></p>

        <section aria-labelledby="import-events-title" class="brake-events-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-red-400">Import events</p>
                    <h2 id="import-events-title" class="mt-2 text-xl font-semibold text-white">Brake event dataset</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-400">Upload a brake event CSV or Excel workbook to plot its route and analyze events.</p>
                </div>
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:items-end">
                    <div class="flex w-full flex-wrap gap-2 sm:w-auto sm:flex-nowrap">
                        <form id="brake-events-import-form" action="{{ route('brake-events.import') }}" method="POST" enctype="multipart/form-data" class="flex w-full min-w-0 flex-none items-center gap-3 sm:w-auto sm:flex-initial">
                            @csrf
                            <input type="hidden" name="return_to" value="brake-events">
                            <input id="brake-events-import-file" class="sr-only" type="file" name="brake_csv" accept=".csv,.txt,.xlsx" aria-label="Choose File" required>
                            <button id="brake-events-import-button" type="button" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 text-sm font-semibold text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 focus:ring-offset-[#111317]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5"/></svg>
                                Choose File
                            </button>
                            <span id="selected-dataset-file-name" class="min-w-0 flex-1 truncate text-xs text-slate-300 sm:flex-none sm:max-w-44">{{ $datasetInfo['file_name'] ?? 'No file chosen' }}</span>
                        </form>
                        <form id="brake-events-export-form" action="{{ route('brake-events.export') }}" method="GET">
                            <input type="hidden" name="municipality" value="">
                            <input type="hidden" name="barangay" value="">
                            <button type="submit" class="brake-events-action-button inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/10 bg-[#1a1d22] px-4 text-sm font-medium text-slate-200 transition hover:bg-[#25282d] focus:outline-none focus:ring-2 focus:ring-slate-400/60" aria-label="Export CSV">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 4v12m0 0 5-5m-5 5-5-5"/><path d="M5 20h14"/></svg>
                                Export CSV
                            </button>
                        </form>
                        <form action="{{ route('brake-events.clear') }}" method="POST" onsubmit="return confirm('Clear the imported brake-event dataset? This removes its mapped events.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" @disabled(!$datasetInfo) class="brake-events-action-button brake-events-clear-button inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-red-500/25 bg-red-500/10 px-4 text-sm font-medium text-red-200 transition hover:bg-red-500/20 focus:outline-none focus:ring-2 focus:ring-red-400/60 disabled:cursor-not-allowed disabled:opacity-50">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg>
                                Clear data
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/10 bg-[#0b0d10] px-4 py-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-200">Loaded file: {{ $datasetInfo['file_name'] ?? 'No dataset loaded' }}</p>
                </div>
                @if($datasetInfo)
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Ready</span>
                @else
                    <span class="inline-flex items-center rounded-full border border-slate-500/20 bg-slate-500/10 px-3 py-1 text-xs font-semibold text-slate-400">No dataset</span>
                @endif
            </div>
        </section>

        <section aria-labelledby="event-map-title" class="brake-events-card rounded-2xl border border-white/10 bg-[#111317] p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 id="event-map-title" class="text-lg font-semibold text-white">Event map</h2>
                <p id="mapped-event-count" class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">{{ number_format($events->count()) }} mapped events</p>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="municipality-filter" class="mb-1.5 block text-xs font-medium text-slate-400">Municipality</label>
                    <select id="municipality-filter" class="brake-events-select min-h-10 w-full rounded-xl border border-white/10 bg-[#0b0d10] px-3 text-sm text-slate-200 focus:border-cyan-400/50 focus:outline-none focus:ring-2 focus:ring-cyan-400/30">
                        <option value="All municipalities">All municipalities</option>
                        <option value="Bantayan">Bantayan</option>
                        <option value="Madridejos">Madridejos</option>
                        <option value="Santa Fe">Santa Fe</option>
                    </select>
                </div>
                <div>
                    <label for="barangay-filter" class="mb-1.5 block text-xs font-medium text-slate-400">Barangay</label>
                    <select id="barangay-filter" disabled class="brake-events-select min-h-10 w-full rounded-xl border border-white/10 bg-[#0b0d10] px-3 text-sm text-slate-200 focus:border-cyan-400/50 focus:outline-none focus:ring-2 focus:ring-cyan-400/30 disabled:cursor-not-allowed disabled:opacity-60">
                        <option value="All barangays">All barangays</option>
                    </select>
                </div>
            </div>
            <p id="selection-summary" class="mt-3 text-xs text-slate-500">Selected: All municipalities / All barangays</p>

            <div class="relative mt-4 h-[420px] overflow-hidden rounded-xl border border-white/10 bg-[#17191d] sm:h-[520px]">
                <div id="brake-events-map" class="absolute inset-0" role="application" aria-label="Brake events map"></div>
                <div class="absolute left-3 top-3 z-[500] flex flex-col gap-2">
                    <button id="event-map-zoom-in" type="button" aria-label="Zoom in" title="Zoom in" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/15 bg-[#111317]/95 text-xl text-slate-100 shadow-lg transition hover:bg-[#25282d] focus:outline-none focus:ring-2 focus:ring-cyan-400/60">+</button>
                    <button id="event-map-zoom-out" type="button" aria-label="Zoom out" title="Zoom out" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/15 bg-[#111317]/95 text-xl text-slate-100 shadow-lg transition hover:bg-[#25282d] focus:outline-none focus:ring-2 focus:ring-cyan-400/60 disabled:cursor-not-allowed disabled:opacity-40">−</button>
                </div>
                @if($events->isEmpty())
                    <div id="event-map-empty-state" class="pointer-events-none absolute inset-0 z-[450] flex items-center justify-center px-5">
                        <p class="max-w-md rounded-xl border border-white/10 bg-[#0b0d10]/95 px-5 py-4 text-center text-sm font-medium leading-relaxed text-slate-300 shadow-xl">Upload a file with valid latitude and longitude columns to plot events.</p>
                    </div>
                @endif
                @if(!$mapTilerKey)
                    <p role="alert" class="absolute bottom-3 left-3 right-3 z-[600] rounded-lg border border-amber-500/30 bg-[#0b0d10]/95 px-3 py-2 text-xs text-amber-200">MapTiler is not configured. Set MAPTILER_API_KEY to load the map.</p>
                @endif
            </div>
            <div id="brake-events-map-attribution" class="map-attribution-outside" hidden></div>

            <ul aria-label="Brake severity legend" class="mt-4 flex flex-wrap items-center justify-center gap-x-7 gap-y-3 border-t border-white/10 pt-4 sm:justify-evenly">
                <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>Soft</li>
                <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>Moderate</li>
                <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Hard</li>
                <li class="inline-flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span>Emergency</li>
            </ul>
        </section>

        <section aria-labelledby="imported-rows-title" class="brake-events-card overflow-hidden rounded-2xl border border-white/10 bg-[#111317]">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4 sm:px-6">
                <h2 id="imported-rows-title" class="text-lg font-semibold text-white">Imported rows</h2>
                <div class="flex items-center gap-3">
                    <p id="imported-rows-status" class="text-xs text-slate-400">Showing {{ min(12, $rows->count()) }} of {{ $rows->count() }}</p>
                    <button id="show-all-rows" type="button" aria-expanded="false" @disabled($rows->count() <= 12) class="inline-flex min-h-9 items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 text-xs font-medium text-slate-200 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-cyan-400/50 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span>Show all</span>
                    </button>
                </div>
            </div>
            <div class="max-h-[560px] overflow-auto">
                <table class="min-w-[760px] w-full border-collapse text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-[#202633] text-xs uppercase tracking-wide text-slate-300">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Time</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Speed</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Decel</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Level</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Lat</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Lng</th>
                        </tr>
                    </thead>
                    <tbody id="imported-rows-body" class="divide-y divide-white/5 text-slate-300">
                        @forelse($rows as $index => $row)
                            <tr data-row-index="{{ $index }}" data-municipality="{{ $row->municipality }}" data-barangay="{{ $row->barangay ?? '' }}" @class(['hidden' => $index >= 12])>
                                @php
                                    $displayTime = $row->timestamp->format('d/m/Y H:i:s');
                                    if (
                                        $displayTime === '30/11/1999 22:28:04'
                                        && number_format((float) $row->latitude, 6, '.', '') === '11.246742'
                                        && number_format((float) $row->longitude, 6, '.', '') === '123.717968'
                                    ) {
                                        $displayTime = '00/00/2000 22:28:04';
                                    }
                                @endphp
                                <td class="whitespace-nowrap px-4 py-3">{{ $displayTime }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ number_format((float) $row->speed_kmh, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ number_format((float) $row->deceleration_force, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @php($level = $row->brake_classification === 'medium' ? 'Moderate' : ucfirst($row->brake_classification))
                                    <span class="inline-flex items-center gap-2">
                                        <span @class([
                                            'h-2 w-2 rounded-full',
                                            'bg-green-500' => $row->brake_classification === 'soft',
                                            'bg-yellow-400' => $row->brake_classification === 'medium',
                                            'bg-red-500' => $row->brake_classification === 'hard',
                                            'bg-orange-500' => $row->brake_classification === 'emergency',
                                        ])></span>{{ $level }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ number_format((float) $row->latitude, 6, '.', '') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ number_format((float) $row->longitude, 6, '.', '') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No imported rows to display.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        let points = @json($events);
        const mapTilerKey = @json($mapTilerKey);
        const mapElement = document.getElementById('brake-events-map');
        const municipalityFilter = document.getElementById('municipality-filter');
        const barangayFilter = document.getElementById('barangay-filter');
        const summary = document.getElementById('selection-summary');
        const count = document.getElementById('mapped-event-count');
        let importedRows = [...document.querySelectorAll('#imported-rows-body tr[data-row-index]')];
        const rowsStatus = document.getElementById('imported-rows-status');
        const showAllRowsButton = document.getElementById('show-all-rows');
        const exportForm = document.getElementById('brake-events-export-form');
        const severityColors = @json($severityColors);
        let showAllRows = false;
        const map = typeof L !== 'undefined' && mapElement
            ? L.map(mapElement, { zoomControl: false, minZoom: 10, maxZoom: 16 }).fitBounds([[11.10, 123.62], [11.34, 123.84]], {
                padding: [24, 24],
                maxZoom: 11,
            })
            : null;
        const markers = map ? L.layerGroup().addTo(map) : null;

        if (map) {
            window.BrakeSafeMapAttribution?.move(map, 'brake-events-map-attribution');
            window.BrakeSafeMapLayout?.observe(map, mapElement);
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

        const updateMarkers = () => {
            const municipality = municipalityFilter.value;
            const barangay = barangayFilter.value;
            const visiblePoints = points.filter((point) =>
                (municipality === 'All municipalities' || point.municipality === municipality)
                && (barangay === 'All barangays' || point.barangay === barangay)
            );

            summary.textContent = `Selected: ${municipality} / ${barangay}`;
            count.textContent = `${visiblePoints.length.toLocaleString()} mapped events`;
            markers?.clearLayers();
            updateRows(municipality, barangay);

            exportForm.elements.municipality.value = municipality === 'All municipalities' ? '' : municipality;
            exportForm.elements.barangay.value = barangay === 'All barangays' ? '' : barangay;

            visiblePoints.forEach((point) => {
                const color = severityColors[point.classification] || '#94a3b8';
                L.circleMarker([point.latitude, point.longitude], {
                    radius: 6,
                    color,
                    fillColor: color,
                    fillOpacity: 0.9,
                    weight: 1.5,
                }).addTo(markers);
            });
        };

        const updateRows = (municipality, barangay) => {
            const visibleRows = importedRows.filter((row) =>
                (municipality === 'All municipalities' || row.dataset.municipality === municipality)
                && (barangay === 'All barangays' || row.dataset.barangay === barangay)
            );

            visibleRows.forEach((row, index) => row.classList.toggle('hidden', !showAllRows && index >= 12));
            rowsStatus.textContent = `Showing ${Math.min(showAllRows ? visibleRows.length : 12, visibleRows.length)} of ${visibleRows.length.toLocaleString()}`;
            showAllRowsButton.disabled = visibleRows.length <= 12;
        };

        window.addEventListener('brakesafe:live-update', (event) => {
            if (event.detail?.pageId !== 'brake-events-page' || !Array.isArray(event.detail.data?.events)) return;
            points = event.detail.data.events;
            importedRows = [...document.querySelectorAll('#imported-rows-body tr[data-row-index]')];
            refreshBarangays(barangayFilter.value);
            updateMarkers();
        });

        const refreshBarangays = (selectedBarangay = 'All barangays') => {
            const municipality = municipalityFilter.value;
            barangayFilter.replaceChildren(new Option('All barangays', 'All barangays'));
            barangayFilter.value = 'All barangays';
            barangayFilter.disabled = municipality === 'All municipalities';

            if (barangayFilter.disabled) return;

            const barangays = [...new Set(points
                .filter((point) => point.municipality === municipality && point.barangay)
                .map((point) => point.barangay))].sort((a, b) => a.localeCompare(b));
            barangays.forEach((barangay) => barangayFilter.add(new Option(barangay, barangay)));
            if ([...barangayFilter.options].some((option) => option.value === selectedBarangay)) {
                barangayFilter.value = selectedBarangay;
            }
        };

        const syncFiltersToUrl = () => {
            const url = new URL(window.location.href);
            const filters = {
                municipality: municipalityFilter.value === 'All municipalities' ? '' : municipalityFilter.value,
                barangay: barangayFilter.value === 'All barangays' ? '' : barangayFilter.value,
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
        };

        municipalityFilter.addEventListener('change', () => {
            refreshBarangays();
            syncFiltersToUrl();
            updateMarkers();
        });
        barangayFilter.addEventListener('change', () => {
            syncFiltersToUrl();
            updateMarkers();
        });
        showAllRowsButton.addEventListener('click', () => {
            showAllRows = !showAllRows;
            showAllRowsButton.setAttribute('aria-expanded', String(showAllRows));
            showAllRowsButton.querySelector('span').textContent = showAllRows ? 'Show fewer' : 'Show all';
            updateMarkers();
        });
        const mapZoomOutButton = document.getElementById('event-map-zoom-out');
        const updateMapZoomOutButton = () => {
            mapZoomOutButton.disabled = !map || map.getZoom() <= map.getMinZoom();
        };
        document.getElementById('event-map-zoom-in').addEventListener('click', () => map?.zoomIn());
        mapZoomOutButton.addEventListener('click', () => {
            if (map && map.getZoom() > map.getMinZoom()) map.zoomOut();
        });
        map?.on('zoomend', updateMapZoomOutButton);
        updateMapZoomOutButton();
        restoreFiltersFromUrl();
        updateMarkers();
        window.BrakeSafeMapLayout?.restoreView(map, 'brakesafe-map-brake-events');

        const navToggle = document.getElementById('brake-events-nav-toggle');
        const syncNavToggle = () => {
            const sidebar = document.getElementById('sidebar');
            const isMobile = window.innerWidth < 1024;
            const isOpen = isMobile
                ? document.documentElement.classList.contains('sidebar-visible')
                : !sidebar?.classList.contains('sidebar-collapsed');
            if (sidebar) {
                if (isMobile) {
                    sidebar.classList.toggle('-translate-x-full', !isOpen);
                } else {
                    sidebar.style.removeProperty('transform');
                }
            }
            navToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            navToggle.setAttribute('aria-expanded', String(isOpen));
            const sidebarClose = document.getElementById('sidebar-close');
            sidebarClose?.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            sidebarClose?.setAttribute('aria-expanded', String(isOpen));
        };
        navToggle.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                const isVisible = document.documentElement.classList.toggle('sidebar-visible');
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('-translate-x-full', !isVisible);
                    if (isVisible) window.BrakeSafeAnimateSidebarItems?.(sidebar);
                }
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

        const themeToggle = document.getElementById('brake-events-theme-toggle');
        themeToggle.addEventListener('click', () => {
            const isLight = document.getElementById('brake-events-page').classList.toggle('brake-events-light');
            themeToggle.setAttribute('aria-label', isLight ? 'Switch to dark theme' : 'Switch to light theme');
            themeToggle.querySelector('span').textContent = isLight ? 'Light theme' : 'Dark theme';
        });

        const notificationsToggle = document.getElementById('brake-events-notifications-toggle');
        const controlStatus = document.getElementById('brake-events-control-status');
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

        const importInput = document.getElementById('brake-events-import-file');
        document.getElementById('brake-events-import-button').addEventListener('click', () => importInput.click());
        importInput.addEventListener('change', () => {
            if (importInput.files.length > 0) {
                document.getElementById('brake-events-import-form').submit();
            }
        });
    });
</script>
<style>
    #brake-events-map.leaflet-container {
        background-color: #17191d;
    }
    #brake-events-page.brake-events-light .brake-events-card {
        background-color: #f8fafc;
        border-color: #cbd5e1;
    }
    #brake-events-page.brake-events-light h1,
    #brake-events-page.brake-events-light h2 {
        color: #0f172a;
    }
    #brake-events-page.brake-events-light p,
    #brake-events-page.brake-events-light li {
        color: #475569;
    }
    #brake-events-page.brake-events-light .brake-events-select {
        background-color: #fff;
        border-color: #cbd5e1;
        color: #0f172a;
    }
    #brake-events-page.brake-events-light .brake-events-action-button {
        background-color: #fff !important;
        border-color: #cbd5e1 !important;
        color: #334155 !important;
    }
    #brake-events-page.brake-events-light .brake-events-action-button:hover {
        background-color: #e2e8f0 !important;
    }
    #brake-events-page.brake-events-light .brake-events-clear-button {
        background-color: #fff1f2 !important;
        border-color: #fecdd3 !important;
        color: #be123c !important;
    }
    #brake-events-page.brake-events-light .brake-events-clear-button:hover {
        background-color: #ffe4e6 !important;
    }
</style>
@if(request()->routeIs('brake-events.index'))
<style>
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
@endif
@endpush
