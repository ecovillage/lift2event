<template>
    <div class="flex flex-col md:h-screen md:overflow-hidden">

        <!-- Header -->
        <div class="bg-white border-b flex-shrink-0 relative" style="border-top: 4px solid var(--color-primary)">
            <router-link
                v-if="isAuthenticated"
                :to="{ name: 'backend.home' }"
                class="absolute top-3 right-4 px-3 py-1.5 rounded text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors"
            >{{ t('nav.backend') }}</router-link>
            <div class="px-6 py-6 text-center">
                <p class="text-2xl font-bold text-gray-900 leading-tight">{{ organisationName ? `${organisationName} · ${t('event.rideshare_for')}` : t('event.rideshare_for') }}</p>
                <p class="text-sm text-gray-500 mt-3">{{ t('event.for_event') }}</p>
                <h1 class="text-2xl font-bold text-[var(--color-primary)] leading-tight mt-3">{{ event?.name ?? '…' }}</h1>
                <div v-if="event" class="mt-3 text-sm text-gray-500 flex flex-wrap justify-center gap-x-5 gap-y-1">
                    <span class="flex items-center gap-1.5">
                        <Calendar class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" />
                        <span>
                            <template v-for="(seg, i) in eventRangeSegments" :key="i"><span v-if="seg.bold" class="font-semibold">{{ seg.text }}</span><template v-else>{{ seg.text }}</template></template>
                        </span>
                    </span>
                    <span v-if="event.location" class="flex items-center gap-1.5">
                        <MapPin class="w-3.5 h-3.5 flex-shrink-0 text-gray-400" />
                        {{ event.location.display_name || formatLocation(event.location) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="bg-white border-b px-4 py-2 flex-shrink-0 flex items-center gap-2 flex-wrap">
            <!-- Type filter -->
            <div class="flex gap-1">
                <button
                    v-for="f in typeFilters" :key="f.value"
                    :class="['px-3 py-1 rounded-full text-xs font-medium transition-colors',
                        activeFilter === f.value
                            ? 'bg-[var(--color-primary)] text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200']"
                    @click="setFilter(f.value)"
                >{{ f.label }}</button>
            </div>

            <!-- Date filter -->
            <select
                v-if="rideDates.length > 0"
                v-model="dateFilter"
                class="px-2 py-1 text-xs border border-gray-200 rounded bg-white text-gray-600"
                @change="onFilterChange"
            >
                <option value="">{{ t('event.all_dates') }}</option>
                <option v-for="d in rideDates" :key="d" :value="d">{{ fmtDay(d) }}</option>
            </select>

            <div class="flex-1"></div>

            <button
                v-if="event"
                class="px-3 py-1.5 rounded text-xs font-medium text-white bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] transition-colors flex items-center gap-1"
                @click="showForm = true"
            ><Plus class="w-3.5 h-3.5" />{{ t('ride.new_entry') }}</button>
        </div>

        <!-- Loading / error -->
        <div v-if="loading" class="flex-1 flex items-center justify-center text-gray-400">…</div>
        <div v-else-if="fetchError" class="flex-1 flex items-center justify-center text-red-500">{{ fetchError }}</div>

        <!-- Two-column content -->
        <div v-else class="flex-1 flex flex-col md:flex-row md:overflow-hidden">

            <!-- Left: ride list (desktop 20% / mobile below map) -->
            <div class="order-2 md:order-1 md:w-1/5 md:min-w-[200px] md:min-h-0 md:overflow-y-auto bg-gray-50 border-r border-gray-200">
                <div v-if="hiddenRideCount > 0" class="p-3 text-xs text-amber-700 bg-amber-50 border-b border-amber-200">
                    {{ t('ride.outside_viewport', { n: hiddenRideCount }) }}
                    <button type="button" class="ml-1 font-medium underline hover:no-underline" @click="showAllRides">{{ t('ride.show_all') }}</button>
                </div>
                <div class="p-2 flex flex-col gap-2">
                    <div v-if="rides.length === 0" class="flex flex-col items-center gap-3 text-center py-6 px-2">
                        <Bus class="w-10 h-10 text-gray-300" :stroke-width="1.5" />
                        <p class="text-sm text-gray-500">{{ t('ride.none') }}</p>
                    </div>
                    <div v-else-if="viewportRides.length === 0 && hiddenRideCount === 0" class="py-6 px-2 text-sm text-gray-400 text-center">–</div>
                    <RideCard
                        v-for="ride in viewportRides"
                        :key="ride.id"
                        :ride="ride"
                        :event="event"
                        @open="selectedRide = ride"
                    />
                    <button
                        class="w-full px-3 py-2 rounded-lg text-sm font-medium text-white bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] transition-colors flex items-center justify-center gap-1.5"
                        @click="showForm = true"
                    ><Plus class="w-4 h-4" />{{ t('ride.new_entry') }}</button>
                </div>
            </div>

            <!-- Right: Leaflet map (desktop flex-1 / mobile top half) -->
            <div class="order-1 md:order-2 flex-none md:flex-1 relative h-[37.5vh] md:h-auto">
                <div ref="mapEl" class="absolute inset-0"></div>
                <RouteLegend />
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white border-t px-4 py-2 flex-shrink-0 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
            <a :href="githubUrl" target="_blank" rel="noopener" class="hover:text-gray-700 hover:underline">{{ t('footer.github') }}</a>
            <a
                v-for="(link, i) in footerLinks"
                :key="i"
                :href="link.url"
                target="_blank"
                rel="noopener"
                class="hover:text-gray-700 hover:underline"
            >{{ link.label }}</a>
        </footer>

        <!-- Ride detail popup -->
        <Teleport to="body">
            <RidePopup
                v-if="selectedRide"
                :ride="selectedRide"
                :event="event"
                @close="selectedRide = null"
            />
        </Teleport>

        <!-- Pending email confirmation popup -->
        <Teleport to="body">
            <div
                v-if="pendingConfirmation"
                class="fixed inset-0 z-[2100] bg-black/50 flex items-end md:items-center justify-center"
            >
                <div class="bg-white rounded-t-2xl md:rounded-xl w-full md:max-w-sm p-5 space-y-4">
                    <div class="flex flex-col items-center text-center gap-2">
                        <TriangleAlert class="w-10 h-10 text-amber-500 shrink-0" :stroke-width="2" />
                        <p class="text-[1.6875rem] font-semibold text-gray-900">{{ t('ride.confirmation_pending_title') }}</p>
                        <p class="text-[1.3125rem] text-gray-700">{{ t('ride.confirmation_pending') }}</p>
                    </div>
                    <div class="flex justify-center">
                        <button
                            class="px-4 py-1.5 rounded text-[1.3125rem] font-medium text-white bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] transition-colors"
                            @click="pendingConfirmation = false"
                        >{{ t('ride.confirmation_pending_dismiss') }}</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Ride create form (modal) -->
        <Teleport to="body">
            <div
                v-if="showForm"
                class="fixed inset-0 z-[2000] bg-black/50 flex items-end md:items-center justify-center"
                @click.self="showForm = false"
            >
                <div class="bg-white rounded-t-2xl md:rounded-xl w-full md:max-w-lg max-h-[90vh] overflow-hidden">
                  <div class="max-h-[90vh] overflow-y-auto">
                    <RideForm
                        :event="event"
                        :retention-days="retentionDays"
                        :organisation-name="organisationName"
                        @submitted="onRideCreated"
                        @cancelled="showForm = false"
                    />
                  </div>
                </div>
            </div>
        </Teleport>

        <FeedbackWidget />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Bus, Calendar, MapPin, Plus, TriangleAlert } from '@lucide/vue';
import RideCard from './RideCard.vue';
import RideForm from './RideForm.vue';
import RidePopup from './RidePopup.vue';
import RouteLegend from '@/components/RouteLegend.vue';
import FeedbackWidget from '@/components/FeedbackWidget.vue';
import { useEscapeKey } from '@/composables/useEscapeKey';
import { useAuth } from '@/composables/useAuth';
import { formatLocation } from '@/utils/formatLocation';

const { t, locale } = useI18n();
const route = useRoute();
const { isAuthenticated } = useAuth();

// Data
const event        = ref(null);
const rides        = ref([]);
const loading      = ref(true);
const fetchError   = ref(null);
const showForm     = ref(false);
const selectedRide = ref(null);
const mapBounds    = ref(null);

watch([event, locale], ([newEvent]) => {
    if (newEvent) {
        document.title = `${newEvent.name}: ${t('event.rideshare_for')}`;
    }
});
const activeFilter = ref('all');
const dateFilter   = ref('');
const footerLinks        = ref([]);
const retentionDays      = ref(90);
const organisationName   = ref('');
const pendingConfirmation = ref(false);

useEscapeKey(() => { if (pendingConfirmation.value) pendingConfirmation.value = false; });

const githubUrl = 'https://github.com/ecovillage/lift2event';

// Map state
const mapEl    = ref(null);
let   map      = null;
let   rideLayer = null;

// ── Filters ──────────────────────────────────────────────────────────────────

const typeFilters = computed(() => [
    { value: 'all',     label: t('ride.filter_all') },
    { value: 'offer',   label: t('ride.filter_offers') },
    { value: 'request', label: t('ride.filter_requests') },
]);

const rideDates = computed(() => {
    const set = new Set();
    rides.value.forEach(r => {
        if (r.outbound_at) set.add(r.outbound_at.substring(0, 10));
        if (r.return_at)   set.add(r.return_at.substring(0, 10));
    });
    return [...set].sort();
});

const filteredRides = computed(() => {
    let list = rides.value;
    if (activeFilter.value !== 'all') list = list.filter(r => r.type === activeFilter.value);
    if (dateFilter.value) {
        list = list.filter(r =>
            r.outbound_at?.startsWith(dateFilter.value) ||
            r.return_at?.startsWith(dateFilter.value)
        );
    }
    return list;
});

const viewportRides = computed(() => {
    const b = mapBounds.value;
    if (!b) return filteredRides.value;
    return filteredRides.value.filter(r => {
        const lat = parseFloat(r.location?.latitude);
        const lng = parseFloat(r.location?.longitude);
        return !isNaN(lat) && !isNaN(lng) && b.contains([lat, lng]);
    });
});

const hiddenRideCount = computed(() => filteredRides.value.length - viewportRides.value.length);

function showAllRides() {
    fitMap();
}

function setFilter(value) {
    activeFilter.value = value;
    onFilterChange();
}

function onFilterChange() {
    if (!map) return;
    drawRides();
    fitMap();
}

// ── Date formatting ───────────────────────────────────────────────────────────

const dayFmt = computed(() => new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeZone: 'UTC' }));
function fmtDay(ymd) { return dayFmt.value.format(new Date(ymd + 'T12:00:00Z')); }

// German and French use "18h30"-style times; other locales use their own idiomatic short time format.
function fmtEventTime(date) {
    if (locale.value === 'de' || locale.value === 'fr') {
        const parts  = new Intl.DateTimeFormat(locale.value, { hour: 'numeric', minute: '2-digit', hourCycle: 'h23', timeZone: 'UTC' }).formatToParts(date);
        const hour   = parts.find(p => p.type === 'hour').value;
        const minute = parts.find(p => p.type === 'minute').value;
        return `${hour}h${minute}`;
    }
    return new Intl.DateTimeFormat(locale.value, { timeStyle: 'short', timeZone: 'UTC' }).format(date);
}

function fmtEventDate(date) {
    return new Intl.DateTimeFormat(locale.value, { dateStyle: 'long', timeZone: 'UTC' }).format(date);
}

// Date and time values are bold; connector words ("um"/"bis" etc.) stay normal weight.
const eventRangeSegments = computed(() => {
    const ev = event.value;
    if (!ev?.start_at || !ev?.end_at) return [];

    const start = new Date(ev.start_at);
    const end   = new Date(ev.end_at);
    const atWord = t('event.date_at');
    const at     = atWord ? ` ${atWord} ` : ' ';
    const sameDay = start.toISOString().slice(0, 10) === end.toISOString().slice(0, 10);

    const segments = [
        { bold: true,  text: fmtEventDate(start) },
        { bold: false, text: at },
        { bold: true,  text: fmtEventTime(start) },
        { bold: false, text: ` ${t('event.date_until')} ` },
    ];
    if (!sameDay) {
        segments.push({ bold: true, text: fmtEventDate(end) });
        segments.push({ bold: false, text: at });
    }
    segments.push({ bold: true, text: fmtEventTime(end) });
    return segments;
});

// ── Map ───────────────────────────────────────────────────────────────────────

const offerColor   = '#7c3aed';
const requestColor = '#e07b30';
const bookedColor  = '#9ca3af';

function starIcon() {
    return L.divIcon({
        html: '<span style="font-size:22px;display:block;transform:translate(-50%,-50%)">⭐</span>',
        className: '',
        iconSize: [0, 0],
    });
}

function initMap() {
    const loc    = event.value?.location;
    const center = loc ? [parseFloat(loc.latitude), parseFloat(loc.longitude)] : [50.93, 10.55];

    map = L.map(mapEl.value, { zoomControl: true }).setView(center, 10);
    L.tileLayer(
        import.meta.env.VITE_OSM_TILE_URL || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        { attribution: '© OpenStreetMap contributors' }
    ).addTo(map);

    if (loc) {
        L.marker(center, { icon: starIcon() }).addTo(map);
    }

    rideLayer = L.layerGroup().addTo(map);

    drawRides();
    fitMap();

    map.on('moveend', () => { mapBounds.value = map.getBounds(); });
    mapBounds.value = map.getBounds();
}

let drawGeneration = 0;
const routeCache = new Map();

function drawRides() {
    if (!rideLayer) return;
    const generation = ++drawGeneration;
    rideLayer.clearLayers();

    const evLoc = event.value?.location;
    const evLat = evLoc ? parseFloat(evLoc.latitude) : null;
    const evLng = evLoc ? parseFloat(evLoc.longitude) : null;

    const offerPolylines = [];
    const pins = [];

    filteredRides.value.forEach(ride => {
        if (!ride.location) return;
        const lat   = parseFloat(ride.location.latitude);
        const lng   = parseFloat(ride.location.longitude);
        const booked = ride.type === 'offer' && ride.seats === 0;
        const color  = booked ? bookedColor : (ride.type === 'offer' ? offerColor : requestColor);

        if (evLat !== null) {
            const polyline = L.polyline([[lat, lng], [evLat, evLng]], { color, weight: 4, opacity: 0.85 })
                .addTo(rideLayer);
            if (ride.type === 'offer') offerPolylines.push(polyline);
            fetchRoute(ride, generation, polyline);
        }

        pins.push({ lat, lng, color, ride });
    });

    // Purple (offer) routes must stay visible above orange (request) routes,
    // even where both follow the exact same path.
    offerPolylines.forEach(p => p.bringToFront());

    // Pins must be added after bringToFront so they remain clickable on top of all route lines.
    pins.forEach(({ lat, lng, color, ride }) => {
        L.circleMarker([lat, lng], {
            radius: 7, color: 'white', weight: 2,
            fillColor: color, fillOpacity: 0.9,
        })
            .on('click', () => { selectedRide.value = ride; })
            .addTo(rideLayer);
    });
}

// Upgrades the straight line to the real driving route once it's loaded;
// the straight line stays as a fallback if the route can't be fetched.
async function fetchRoute(ride, generation, polyline) {
    try {
        let geometry;
        if (routeCache.has(ride.id)) {
            geometry = routeCache.get(ride.id);
        } else {
            const { data } = await axios.get(`/api/e/${route.params.slug}/rides/${ride.id}/route`);
            geometry = data.geometry ?? null;
            routeCache.set(ride.id, geometry);
        }
        if (generation !== drawGeneration || !geometry) return;
        polyline.setLatLngs(geometry.map(([lng, lat]) => [lat, lng]));
    } catch {
        // Keep the straight line.
    }
}

function fitMap() {
    if (!map) return;
    const points = [];
    const evLoc = event.value?.location;
    if (evLoc) points.push([parseFloat(evLoc.latitude), parseFloat(evLoc.longitude)]);
    filteredRides.value.forEach(r => {
        if (r.location) points.push([parseFloat(r.location.latitude), parseFloat(r.location.longitude)]);
    });
    if (points.length > 1) map.fitBounds(points, { padding: [40, 40] });
    else if (points.length === 1) map.setView(points[0], 10);
}

// Redraw when filter changes (filter buttons call onFilterChange directly)
watch(filteredRides, () => { drawRides(); });

onUnmounted(() => { if (map) { map.remove(); map = null; } });

// ── Data loading ──────────────────────────────────────────────────────────────

async function load() {
    loading.value = true;
    fetchError.value = null;
    try {
        const { data } = await axios.get(`/api/e/${route.params.slug}`, {
            headers: { Accept: 'application/json' },
        });
        event.value = data.event;
        rides.value = data.rides;
    } catch (e) {
        fetchError.value = e.response?.status === 404
            ? 'Veranstaltung nicht gefunden.'
            : 'Fehler beim Laden.';
    } finally {
        loading.value = false;
    }
}

async function loadSettings() {
    try {
        const { data } = await axios.get('/api/settings');
        footerLinks.value      = data.footer_links ?? [];
        retentionDays.value    = data.ride_data_retention_days ?? 90;
        organisationName.value = data.organisation_name ?? '';
    } catch {
        footerLinks.value = [];
    }
}

function onRideCreated(ride) {
    if (ride.edit_token) {
        localStorage.setItem(`ride_token_${ride.id}`, ride.edit_token);
    }
    const { edit_token, ...display } = ride;
    rides.value.unshift(display);
    showForm.value = false;
    pendingConfirmation.value = !ride.confirmed_at;
    if (map) {
        drawRides();
        fitMap();
    }
}

onMounted(async () => {
    loadSettings();
    await load();
    if (!fetchError.value && mapEl.value) {
        initMap();
    }
});
</script>
