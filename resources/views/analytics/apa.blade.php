@extends('layouts.admin')

@section('title', 'APA - BrakeSafe')
@section('page-title', 'APA')

@section('content')
    <script id="brakesafe-live-data" type="application/json">@json(['accidentProneAreas' => $accidentProneAreas])</script>
    <div id="apa-page" class="mx-auto max-w-[1500px] space-y-5">
        <header class="sticky top-0 z-50 -mx-4 -mt-4 mb-5 flex min-h-[76px] flex-wrap items-center justify-between gap-4 border-b border-white/10 bg-[#090a0c]/95 px-4 pb-5 pt-4 shadow-[0_8px_20px_rgba(0,0,0,0.2)] backdrop-blur-md sm:static sm:z-auto sm:-mx-6 sm:-mt-6 sm:bg-transparent sm:px-6 sm:pb-5 sm:pt-4 sm:shadow-none sm:backdrop-blur-none lg:-mx-8 lg:-mt-8 lg:px-8">
            <div class="flex items-center gap-3">
                <button id="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-transparent bg-transparent text-slate-200 transition hover:border-white/10 hover:bg-[#111317] focus:outline-none focus:ring-2 focus:ring-cyan-400/60">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-2xl font-semibold tracking-tight text-white">APA</h1>
            </div>
            <div class="flex min-w-0 flex-wrap items-center justify-end gap-4">
                <button id="apa-theme-toggle" type="button" aria-label="Switch to light theme" title="Switch to light theme" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-white/10 bg-slate-950 text-slate-100 shadow-[0_20px_40px_rgba(0,0,0,0.25)] transition duration-200 ease-out hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                    <span class="sr-only">Dark theme</span>
                </button>
                @include('layouts.admin-user-indicator')
                <span id="apa-control-status" class="sr-only" role="status" aria-live="polite"></span>
            </div>
        </header>

        <section aria-labelledby="apa-management-title" class="apa-card rounded-2xl border border-white/10 bg-[#111317] p-4 shadow-xl sm:p-6">
            <div class="flex flex-col gap-4 border-b border-white/10 pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-[0.22em] text-cyan-300">BARANGAY BOUNDARIES</p>
                    <h2 id="apa-management-title" class="mt-2 text-2xl font-semibold text-white sm:text-3xl">Accident-prone area map</h2>
                </div>
            </div>

            <p class="mt-4 text-sm leading-6 text-slate-400">Explore the barangay boundaries for Bantayan, Madridejos, and Santa Fe.</p>
            <p id="apa-map-click-status" class="mt-2 text-sm text-amber-300" role="status" aria-live="polite" hidden></p>

            <div id="apa-map-frame" class="relative mt-4 h-[360px] overflow-hidden rounded-xl border border-white/10 bg-[#17191d] sm:h-[440px] lg:h-[520px]">
                <div id="apa-map" class="absolute inset-0" role="application" aria-label="Map showing barangay boundaries in Bantayan, Madridejos, and Santa Fe."></div>
                <div id="apa-map-debug" class="absolute inset-0 z-[1000] flex items-center justify-center bg-[#101216]/90 px-6 text-center text-sm text-slate-300" role="status" aria-live="polite" style="{{ $mapTilerKey ? 'display: none;' : '' }}">
                    @if($mapTilerKey)
                        Loading map…
                    @else
                        MapTiler API key is missing. Add MAPTILER_API_KEY to your .env file, then clear Laravel's configuration cache.
                    @endif
                </div>
                <div class="apa-map-zoom-controls absolute left-3 top-3 z-[500] flex flex-col overflow-hidden rounded-xl border border-white/25 shadow-xl">
                    <button id="apa-zoom-in" type="button" aria-label="Zoom in" title="Zoom in" class="flex h-11 w-11 items-center justify-center border-b border-white/20 bg-[#111317] text-2xl font-semibold text-white hover:bg-[#25282d] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-cyan-400">+</button>
                    <button id="apa-zoom-out" type="button" aria-label="Zoom out" title="Zoom out" class="flex h-11 w-11 items-center justify-center bg-[#111317] text-2xl font-semibold text-white hover:bg-[#25282d] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-cyan-400">−</button>
                </div>
            </div>
            <div id="apa-map-attribution" class="map-attribution-outside" hidden></div>
        </section>

        <section id="apa-saved-areas" aria-labelledby="apa-saved-areas-title" class="apa-card rounded-2xl border border-white/10 bg-[#111317] p-4 shadow-xl sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4">
                <div>
                    <h2 id="apa-saved-areas-title" class="text-xl font-semibold text-white">Saved accident-prone areas</h2>
                    <p id="apa-saved-area-count" class="mt-1 text-sm text-slate-400">{{ $accidentProneAreas->count() }} saved locations</p>
                </div>
            </div>
            <div id="apa-saved-area-list" class="mt-4 grid gap-3 sm:grid-cols-2">
                @if($accidentProneAreas->isEmpty())
                    <p id="apa-no-saved-areas" class="text-sm text-slate-400">No locations saved yet. Click inside a barangay boundary on the map to add one.</p>
                @endif
            </div>
        </section>

        <div id="apa-area-dialog" class="fixed inset-0 z-[1200] hidden items-center justify-center bg-slate-950/70 p-4" role="presentation">
            <section id="apa-area-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="apa-area-dialog-title" class="apa-card w-full max-w-lg rounded-2xl border border-white/10 bg-[#111317] p-5 shadow-2xl">
                <header class="border-b border-white/10 pb-3">
                    <div>
                        <h2 id="apa-area-dialog-title" class="text-lg font-semibold text-white">Save accident-prone area</h2>
                        <p class="text-xs text-slate-400">Confirm the selected map location.</p>
                    </div>
                </header>
                <div id="apa-area-dialog-body">
                    <form id="apa-area-form" class="mt-4 space-y-4">
                        @csrf
                        <input id="apa-area-latitude" name="latitude" type="hidden">
                        <input id="apa-area-longitude" name="longitude" type="hidden">
                        <div>
                            <label for="apa-area-place" class="mb-1 block text-sm font-medium text-slate-300">Place</label>
                            <input id="apa-area-place" type="text" readonly class="w-full rounded-xl border border-white/10 bg-[#0b0d10] px-3 py-2 text-sm text-slate-200">
                        </div>
                        <div>
                            <label for="apa-area-reason" class="mb-1 block text-sm font-medium text-slate-300">Reason</label>
                            <input id="apa-area-reason" name="reason" type="text" maxlength="255" required value="Accident Prone Area" class="w-full rounded-xl border border-white/10 bg-[#0b0d10] px-3 py-2 text-sm text-slate-200">
                        </div>
                        <p id="apa-area-form-status" class="text-sm text-slate-400" role="status" aria-live="polite"></p>
                        <div class="flex justify-end gap-2">
                            <button id="apa-area-cancel" type="button" class="rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-200">Cancel</button>
                            <button id="apa-area-save" type="submit" disabled class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Save location</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const mapElement = document.getElementById('apa-map');
        const debugOverlay = document.getElementById('apa-map-debug');
        const mapTilerKey = @json($mapTilerKey);
        const boundaries = @json($municipalityBoundaries);
        const boundaryColors = {
            Bantayan: '#22d3ee',
            Madridejos: '#f472b6',
            'Santa Fe': '#fb923c',
        };

        if (typeof L === 'undefined' || !mapElement) {
            console.error('APA map could not initialize because Leaflet is unavailable.');
            if (debugOverlay) {
                debugOverlay.textContent = 'Map library failed to load. Check your network connection and try again.';
                debugOverlay.style.display = 'flex';
            }
            return;
        }

        if (!mapTilerKey) {
            mapElement.setAttribute('aria-label', 'Map unavailable because the MapTiler API key is missing');
            return;
        }

        const apaBounds = [[10.95, 123.60], [11.40, 123.94]];
        const map = L.map(mapElement, { zoomControl: false, maxZoom: 16 }).fitBounds(apaBounds, {
            padding: [28, 28],
            maxZoom: 11,
        });
        window.BrakeSafeMapAttribution?.move(map, 'apa-map-attribution');
        window.BrakeSafeMapLayout?.observe(map, mapElement);
        const tiles = L.tileLayer(window.BrakeSafeMapTheme.tileUrl(mapTilerKey), {
            maxZoom: 16,
            attribution: '&copy; <a href="https://www.maptiler.com/copyright/">MapTiler</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        });
        tiles.once('tileload', () => {
            if (debugOverlay) debugOverlay.style.display = 'none';
        });
        tiles.once('tileerror', () => {
            if (debugOverlay) {
                debugOverlay.textContent = 'MapTiler could not load map tiles. Check that MAPTILER_API_KEY is valid and allows this site.';
                debugOverlay.style.display = 'flex';
            }
            console.error('MapTiler tile request failed. Check the API key and its allowed domains.');
        });
        tiles.addTo(map);
        window.BrakeSafeMapTheme.watch(map, tiles, mapTilerKey);

        Object.entries(boundaries).forEach(([municipality, collection]) => {
            L.geoJSON(collection, {
                style: {
                    color: boundaryColors[municipality],
                    weight: 1.25,
                    opacity: 0.75,
                    fillColor: boundaryColors[municipality],
                    fillOpacity: 0.035,
                },
                onEachFeature: (feature, layer) => {
                    if (feature.properties?.adm4_en) {
                        layer.bindTooltip(`${feature.properties.adm4_en}, ${municipality}`, { sticky: true });
                    }
                },
            }).addTo(map);
        });

        const dialog = document.getElementById('apa-area-dialog');
        const form = document.getElementById('apa-area-form');
        const formStatus = document.getElementById('apa-area-form-status');
        const saveButton = document.getElementById('apa-area-save');
        const savedAreaList = document.getElementById('apa-saved-area-list');
        const savedAreaCount = document.getElementById('apa-saved-area-count');
        const markerLayer = L.layerGroup().addTo(map);
        let savedAreaTotal = @json($accidentProneAreas->count());

        const popupContent = (area) => {
            const popup = document.createElement('div');
            const place = document.createElement('strong');
            place.textContent = area.place;
            const reason = document.createElement('div');
            reason.textContent = area.reason;
            const hitCount = document.createElement('div');
            hitCount.textContent = `${Number(area.emergency_hit_count || 0)} emergency brake hits`;
            popup.append(place, reason, hitCount);
            return popup;
        };

        const pointInRing = (longitude, latitude, ring) => {
            let inside = false;
            for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                const [xi, yi] = ring[i];
                const [xj, yj] = ring[j];
                const intersects = ((yi > latitude) !== (yj > latitude))
                    && longitude < ((xj - xi) * (latitude - yi)) / (yj - yi) + xi;
                if (intersects) inside = !inside;
            }
            return inside;
        };

        const pointInGeometry = (longitude, latitude, geometry) => {
            if (geometry?.type === 'Polygon') {
                return pointInRing(longitude, latitude, geometry.coordinates[0])
                    && !geometry.coordinates.slice(1).some((ring) => pointInRing(longitude, latitude, ring));
            }
            if (geometry?.type === 'MultiPolygon') {
                return geometry.coordinates.some((polygon) => pointInRing(longitude, latitude, polygon[0])
                    && !polygon.slice(1).some((ring) => pointInRing(longitude, latitude, ring)));
            }
            return false;
        };

        const resolveLocation = (latitude, longitude) => {
            for (const [municipality, collection] of Object.entries(boundaries)) {
                const feature = collection.features.find((candidate) => pointInGeometry(longitude, latitude, candidate.geometry));
                if (feature) {
                    let barangay = String(feature.properties?.adm4_en || '').trim();
                    if (municipality === 'Bantayan' && ['Bantigue', 'Binaobao', 'Suba', 'Ticad'].includes(barangay)) {
                        barangay += ' (Poblacion)';
                    }
                    return { barangay, municipality };
                }
            }
            return null;
        };

        const renderSavedArea = (area) => {
            const marker = L.circleMarker([Number(area.latitude), Number(area.longitude)], {
                radius: 8,
                color: '#fff',
                weight: 2,
                fillColor: '#ef4444',
                fillOpacity: 0.95,
            }).bindPopup(popupContent(area)).addTo(markerLayer);
            marker.areaId = Number(area.id);

            const article = document.createElement('article');
            article.className = 'apa-saved-area rounded-xl border border-white/10 bg-[#0b0d10] p-4';
            article.dataset.areaId = String(area.id);
            const place = document.createElement('h3');
            place.className = 'font-semibold text-white';
            place.textContent = area.place;
            const reason = document.createElement('p');
            reason.className = 'mt-1 text-sm text-slate-300';
            reason.textContent = area.reason;
            const emergencyHits = document.createElement('p');
            emergencyHits.className = 'mt-2 text-xs text-amber-300';
            emergencyHits.textContent = `${Number(area.emergency_hit_count || 0)} emergency brake hits${area.is_auto_generated ? ' · auto-detected' : ''}`;
            const coordinates = document.createElement('p');
            coordinates.className = 'mt-2 text-xs text-slate-400';
            coordinates.textContent = `${Number(area.latitude).toFixed(7)}, ${Number(area.longitude).toFixed(7)}`;
            const actions = document.createElement('div');
            actions.className = 'mt-3 flex gap-2';
            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.dataset.areaAction = 'edit';
            editButton.className = 'rounded-lg border border-white/10 px-3 py-2 text-sm text-slate-200 hover:bg-white/5';
            editButton.textContent = 'Edit';
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.dataset.areaAction = 'delete';
            deleteButton.className = 'rounded-lg border border-red-500/40 px-3 py-2 text-sm text-red-300 hover:bg-red-500/10';
            deleteButton.textContent = 'Delete';
            actions.append(editButton, deleteButton);
            article.append(place, reason, emergencyHits, coordinates, actions);
            savedAreaList.prepend(article);
            savedAreaTotal++;
            savedAreaCount.textContent = `${savedAreaTotal} saved locations`;
            document.getElementById('apa-no-saved-areas')?.remove();
        };

        let savedAreas = @json($accidentProneAreas);
        savedAreas.forEach(renderSavedArea);
        window.addEventListener('brakesafe:live-update', (event) => {
            if (event.detail?.pageId !== 'apa-page' || !Array.isArray(event.detail.data?.accidentProneAreas)) return;
            savedAreas = event.detail.data.accidentProneAreas;
            markerLayer.clearLayers();
            savedAreaList.replaceChildren();
            savedAreaTotal = 0;
            if (savedAreas.length === 0) {
                const emptyMessage = document.createElement('p');
                emptyMessage.id = 'apa-no-saved-areas';
                emptyMessage.className = 'text-sm text-slate-400';
                emptyMessage.textContent = 'No locations saved yet. Click inside a barangay boundary on the map to add one.';
                savedAreaList.append(emptyMessage);
                savedAreaCount.textContent = '0 saved locations';
                return;
            }
            savedAreas.forEach(renderSavedArea);
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const areaUrl = (id) => @json(url('/apa/locations')) + `/${encodeURIComponent(id)}`;
        const responseMessage = async (response) => {
            const result = await response.json();
            if (!response.ok) {
                const message = result.errors ? Object.values(result.errors).flat()[0] : result.message;
                throw new Error(message || 'The request could not be completed.');
            }
            return result;
        };
        const showAlert = (options) => {
            if (!window.Swal) {
                console.error('SweetAlert is unavailable; cannot show APA action feedback.');
                return Promise.resolve({ isConfirmed: false });
            }
            return window.Swal.fire(options);
        };

        savedAreaList.addEventListener('click', async (event) => {
            const button = event.target.closest('button[data-area-action]');
            if (!button) return;

            const article = button.closest('[data-area-id]');
            const areaId = article.dataset.areaId;
            const area = savedAreas.find((saved) => String(saved.id) === areaId);
            const currentArea = area ?? {
                id: areaId,
                place: article.querySelector('h3').textContent,
                reason: article.querySelector('p').textContent,
            };

            if (button.dataset.areaAction === 'edit') {
                const prompt = await showAlert({
                    title: 'Edit accident-prone area',
                    input: 'text',
                    inputLabel: currentArea.place,
                    inputValue: currentArea.reason,
                    inputAttributes: { maxlength: 255, autocapitalize: 'sentences' },
                    showCancelButton: true,
                    confirmButtonText: 'Save changes',
                    inputValidator: (value) => value.trim() ? undefined : 'Enter a reason.',
                });
                if (!prompt.isConfirmed) return;

                try {
                    const response = await fetch(areaUrl(areaId), {
                        method: 'PUT',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ reason: prompt.value }),
                    });
                    const result = await responseMessage(response);
                    article.querySelector('p').textContent = result.area.reason;
                    markerLayer.eachLayer((marker) => {
                        if (marker.areaId === Number(areaId)) {
                            marker.setPopupContent(popupContent(result.area));
                        }
                    });
                    const savedIndex = savedAreas.findIndex((saved) => String(saved.id) === areaId);
                    if (savedIndex >= 0) savedAreas[savedIndex] = result.area;
                    await showAlert({ icon: 'success', title: 'Updated', text: result.message, timer: 1600, showConfirmButton: false });
                } catch (error) {
                    await showAlert({ icon: 'error', title: 'Update failed', text: error.message });
                }
                return;
            }

            const confirmation = await showAlert({
                icon: 'warning',
                title: 'Delete accident-prone area?',
                text: `Delete ${currentArea.place}? This cannot be undone.`,
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#dc2626',
            });
            if (!confirmation.isConfirmed) return;

            try {
                const response = await fetch(areaUrl(areaId), {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                });
                const result = await responseMessage(response);
                article.remove();
                markerLayer.eachLayer((marker) => {
                    if (marker.areaId === Number(areaId)) markerLayer.removeLayer(marker);
                });
                savedAreaTotal--;
                savedAreaCount.textContent = `${savedAreaTotal} saved locations`;
                if (savedAreaTotal === 0) {
                    const emptyMessage = document.createElement('p');
                    emptyMessage.id = 'apa-no-saved-areas';
                    emptyMessage.className = 'text-sm text-slate-400';
                    emptyMessage.textContent = 'No locations saved yet. Click inside a barangay boundary on the map to add one.';
                    savedAreaList.append(emptyMessage);
                }
                await showAlert({ icon: 'success', title: 'Deleted', text: result.message, timer: 1600, showConfirmButton: false });
            } catch (error) {
                await showAlert({ icon: 'error', title: 'Delete failed', text: error.message });
            }
        });

        const closeAreaDialog = () => {
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
            document.documentElement.classList.remove('apa-dialog-open');
        };

        map.on('click', (event) => {
            const location = resolveLocation(event.latlng.lat, event.latlng.lng);
            const mapClickStatus = document.getElementById('apa-map-click-status');
            if (!location) {
                mapClickStatus.textContent = 'Choose a point inside Bantayan, Madridejos, or Santa Fe to add an accident-prone area.';
                mapClickStatus.hidden = false;
                void showAlert({
                    icon: 'warning',
                    title: 'Outside the supported area',
                    text: 'Select a point inside Bantayan, Madridejos, or Santa Fe to add an accident-prone area.',
                    confirmButtonText: 'Got it',
                });
                return;
            }
            mapClickStatus.textContent = '';
            mapClickStatus.hidden = true;
            document.getElementById('apa-area-latitude').value = event.latlng.lat.toFixed(7);
            document.getElementById('apa-area-longitude').value = event.latlng.lng.toFixed(7);
            form.elements.reason.value = 'Accident Prone Area';
            formStatus.textContent = `Selected: ${location.barangay}, ${location.municipality}, Cebu, Philippines`;
            document.getElementById('apa-area-place').value = `${location.barangay}, ${location.municipality}, Cebu, Philippines`;
            saveButton.disabled = false;
            document.documentElement.classList.add('apa-dialog-open');
            dialog.classList.remove('hidden');
            dialog.classList.add('flex');
            form.elements.reason.focus();
        });

        document.getElementById('apa-area-cancel').addEventListener('click', closeAreaDialog);
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) closeAreaDialog();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !dialog.classList.contains('hidden')) closeAreaDialog();
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            saveButton.disabled = true;
            formStatus.textContent = 'Saving location…';
            try {
                const response = await fetch(@json(route('analytics.apa.locations.store')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(form),
                });
                const result = await responseMessage(response);

                renderSavedArea(result.area);
                formStatus.textContent = result.message;
                closeAreaDialog();
                await showAlert({ icon: 'success', title: 'Saved', text: result.message, timer: 1600, showConfirmButton: false });
            } catch (error) {
                console.error('Unable to save accident-prone area.', error);
                formStatus.textContent = error.message;
                saveButton.disabled = false;
                await showAlert({ icon: 'error', title: 'Save failed', text: error.message });
            }
        });

        document.getElementById('apa-zoom-in').addEventListener('click', () => map.zoomIn());
        document.getElementById('apa-zoom-out').addEventListener('click', () => map.zoomOut());
        window.BrakeSafeMapLayout?.restoreView(map, 'brakesafe-map-apa', apaBounds);

        const themeToggle = document.getElementById('apa-theme-toggle');
        themeToggle.addEventListener('click', () => {
            const light = document.getElementById('apa-page').classList.toggle('apa-light');
            themeToggle.setAttribute('aria-label', light ? 'Switch to dark theme' : 'Switch to light theme');
            themeToggle.setAttribute('title', light ? 'Switch to dark theme' : 'Switch to light theme');
            themeToggle.querySelector('span').textContent = light ? 'Light theme' : 'Dark theme';
        });

        const notificationsToggle = document.getElementById('apa-notifications-toggle');
        const controlStatus = document.getElementById('apa-control-status');
        notificationsToggle.addEventListener('click', async () => {
            if (notificationsToggle.getAttribute('aria-pressed') === 'true') {
                window.BrakeSafeNearbyAlerts?.disable();
                notificationsToggle.setAttribute('aria-pressed', 'false');
                notificationsToggle.setAttribute('aria-label', 'Enable nearby notifications');
                notificationsToggle.setAttribute('title', 'Nearby notifications off');
                notificationsToggle.querySelector('span').textContent = 'Nearby notifications off';
                notificationsToggle.classList.add('text-slate-400');
                notificationsToggle.classList.remove('text-emerald-300');
                notificationsToggle.querySelector('.notification-slash').style.display = '';
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
            notificationsToggle.setAttribute('title', 'Nearby notifications on');
            notificationsToggle.querySelector('span').textContent = 'Nearby notifications on';
            notificationsToggle.classList.remove('text-slate-400');
            notificationsToggle.classList.add('text-emerald-300');
            notificationsToggle.querySelector('.notification-slash').style.display = 'none';
            controlStatus.textContent = 'Nearby notifications on.';
        });
    });
</script>
<style>
    #apa-map.leaflet-container { background: #17191d; }
    #apa-page #apa-map-frame .apa-map-zoom-controls {
        border: 1px solid rgba(255, 255, 255, 0.32);
        border-radius: 0.75rem;
        background: #111317;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.55);
    }
    #apa-page #apa-map-frame .apa-map-zoom-controls button {
        width: 2.75rem !important;
        height: 2.75rem !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: #111317 !important;
        color: #fff !important;
        font-size: 1.75rem !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        -webkit-text-fill-color: #fff !important;
    }
    #apa-page #apa-map-frame .apa-map-zoom-controls button:first-child {
        border-bottom: 1px solid rgba(255, 255, 255, 0.25) !important;
        border-radius: 0.7rem 0.7rem 0 0 !important;
    }
    #apa-page #apa-map-frame .apa-map-zoom-controls button:last-child {
        border-radius: 0 0 0.7rem 0.7rem !important;
    }
    #apa-page #apa-map-frame .apa-map-zoom-controls button:hover {
        background: #303640 !important;
    }
    @media (max-width: 639px) {
        #apa-map-frame {
            position: relative;
            z-index: 0;
            isolation: isolate;
        }
    }
    #apa-page .apa-card { transition: background-color .2s, border-color .2s; }
    #apa-page.apa-light .apa-card { background: #f8fafc; border-color: #cbd5e1; }
    #apa-page.apa-light h1,
    #apa-page.apa-light h2 { color: #0f172a; }
    #apa-page.apa-light .apa-card p { color: #475569; }
    #apa-page.apa-light .apa-card > p { background: #fff; color: #0f172a; border-color: #cbd5e1; }
    html.apa-dialog-open #apa-page header {
        z-index: 100 !important;
    }
    #apa-area-dialog {
        z-index: 1200;
    }
    #apa-page.apa-light #apa-area-dialog,
    #apa-page.apa-light #apa-area-dialog-panel {
        background-color: #f8fafc;
        border-color: #cbd5e1;
    }
    #apa-page.apa-light #apa-area-dialog h2,
    #apa-page.apa-light #apa-saved-areas h2,
    #apa-page.apa-light .apa-saved-area h3 {
        color: #0f172a;
    }
    #apa-page.apa-light .apa-saved-area button {
        background-color: #fff;
        border-color: #cbd5e1;
        color: #334155;
    }
    #apa-page.apa-light #apa-area-dialog p,
    #apa-page.apa-light #apa-saved-areas p {
        color: #475569;
    }
    #apa-page.apa-light #apa-area-dialog input,
    #apa-page.apa-light #apa-area-dialog button:not([type="submit"]),
    #apa-page.apa-light #apa-saved-areas button,
    #apa-page.apa-light .apa-saved-area {
        background-color: #fff;
        border-color: #cbd5e1;
        color: #334155;
    }
    #sidebar .nav-item[aria-current="page"] { background-color: #1c2026 !important; color: #f8fafc !important; box-shadow: none !important; }
    #sidebar .nav-item[aria-current="page"] svg { color: #f8fafc !important; }
    #sidebar.sidebar-collapsed #sidebar-close { display: none !important; }
    #sidebar.sidebar-collapsed .nav-item[aria-current="page"] { background-color: #1c2026 !important; color: #f8fafc !important; box-shadow: none !important; }
    #sidebar.sidebar-collapsed .nav-item[aria-current="page"] svg { color: #f8fafc !important; }
</style>
@endpush
