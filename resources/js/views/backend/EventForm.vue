<template>
    <div>
        <h1 class="text-xl font-semibold mb-6">
            {{ isEdit ? t('event.edit') : t('event.new') }}
        </h1>

        <form novalidate @submit.prevent="submit">
            <!-- Two-column layout: form left (20%), map right (80%)       -->
            <!-- Mobile order: map first (order-1), form second (order-2)  -->
            <div class="flex flex-col md:flex-row gap-4 min-h-96">

                <!-- Map: top on mobile, right column on desktop -->
                <div class="order-1 md:order-2 h-64 md:h-auto md:flex-1 rounded overflow-hidden relative">
                    <div ref="mapEl" class="absolute inset-0"></div>
                    <RouteLegend />
                </div>

                <!-- Form: bottom on mobile, left column on desktop -->
                <div class="order-2 md:order-1 md:w-[var(--event-form-col-width)] md:min-w-[200px] space-y-4 flex flex-col">
                    <div>
                        <label class="field-label">{{ t('event.name') }}</label>
                        <input
                            v-model="form.name" type="text" required class="field-input"
                            @focus="onFocus('name')" @blur="onBlur('name')"
                        />
                        <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-400">{{ fieldErrors.name }}</p>
                    </div>

                    <div>
                        <label class="field-label">{{ t('event.start') }}</label>
                        <div class="flex gap-2">
                            <input
                                v-model="startDate" type="date" class="field-input flex-1"
                                @focus="onFocus('startDate')" @blur="onBlur('startDate')"
                            />
                            <input
                                v-model="startTime" type="time" class="field-input w-28"
                                @focus="onFocus('startTime')" @blur="onBlur('startTime')"
                            />
                        </div>
                        <p v-if="fieldErrors.startDate" class="mt-1 text-xs text-red-400">{{ fieldErrors.startDate }}</p>
                        <p v-if="fieldErrors.startTime" class="mt-1 text-xs text-red-400">{{ fieldErrors.startTime }}</p>
                    </div>

                    <div>
                        <label class="field-label">{{ t('event.end') }}</label>
                        <div class="flex gap-2">
                            <input
                                v-model="endDate" type="date" class="field-input flex-1"
                                @focus="onFocus('endDate')" @blur="onBlur('endDate')"
                            />
                            <input
                                v-model="endTime" type="time" class="field-input w-28"
                                @focus="onFocus('endTime')" @blur="onBlur('endTime')"
                            />
                        </div>
                        <p v-if="fieldErrors.endDate" class="mt-1 text-xs text-red-400">{{ fieldErrors.endDate }}</p>
                        <p v-if="fieldErrors.endTime" class="mt-1 text-xs text-red-400">{{ fieldErrors.endTime }}</p>
                    </div>

                    <!-- Address with Nominatim autocomplete -->
                    <div class="relative">
                        <label class="field-label">{{ t('event.location') }}</label>
                        <input
                            v-model="addressInput"
                            type="text"
                            :placeholder="t('event.location_placeholder')"
                            autocomplete="off"
                            class="field-input"
                            @input="onAddressInput"
                            @keydown="onAddressKeydown"
                            @focus="onFocus('address')"
                            @blur="onAddressBlur"
                        />
                        <div
                            v-if="suggestions.length"
                            class="absolute z-50 left-0 right-0 bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-hidden"
                        >
                          <ul class="max-h-48 overflow-y-auto text-sm">
                            <li
                                v-for="(s, i) in suggestions"
                                :key="s.place_id"
                                :class="['px-3 py-2 cursor-pointer leading-snug', i === highlightedIndex ? 'bg-gray-100' : 'hover:bg-gray-100']"
                                @mousedown.prevent="selectSuggestion(s)"
                            >
                                {{ s.display_name }}
                            </li>
                          </ul>
                        </div>
                        <p v-if="form.location" class="mt-1 text-xs text-gray-400 truncate">
                            {{ form.location.latitude.toFixed(4) }}, {{ form.location.longitude.toFixed(4) }}
                        </p>
                        <p v-else-if="fieldErrors.address" class="mt-1 text-xs text-red-400">{{ fieldErrors.address }}</p>
                    </div>

                    <!-- Display name for location (shown once a location is chosen) -->
                    <div v-if="form.location">
                        <label for="location-display-name" class="field-label">{{ t('event.location_display_name') }}</label>
                        <input
                            id="location-display-name"
                            v-model="locationDisplayName"
                            type="text"
                            class="field-input"
                        />
                    </div>

                    <!-- Public link (edit mode only) -->
                    <div v-if="isEdit && event">
                        <label class="field-label">{{ t('event.public_link') }}</label>
                        <div class="flex items-start gap-2">
                            <a
                                :href="publicLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-xs text-[var(--color-primary)] hover:underline break-all flex-1 min-w-0"
                            >{{ publicLink }}</a>
                            <span class="flex items-center gap-1 shrink-0 mt-0.5">
                                <a
                                    :href="publicLink"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-gray-400 hover:text-gray-600"
                                >
                                    <ExternalLink class="w-4 h-4" :stroke-width="2" />
                                </a>
                                <button
                                    type="button"
                                    class="flex items-center gap-1 px-2 py-1 border border-gray-300 rounded text-xs hover:bg-gray-50 text-gray-500 hover:text-gray-700"
                                    :aria-label="t('event.copy')"
                                    @click="copyLink"
                                >
                                    <Copy v-if="!copied" class="w-3.5 h-3.5" :stroke-width="2" />
                                    <Check v-else class="w-3.5 h-3.5 text-green-600" :stroke-width="2" />
                                </button>
                            </span>
                        </div>
                    </div>

                    <p v-for="e in errors" :key="e" class="text-sm text-red-500">{{ e }}</p>

                    <div class="flex gap-2 pt-2 mt-auto">
                        <button
                            type="button"
                            class="flex-1 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50"
                            @click="router.push({ name: 'backend.events' })"
                        >{{ t('ride.cancel') }}</button>
                        <button
                            type="submit"
                            :disabled="saving || !form.location"
                            class="flex-1 py-2 bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white rounded text-sm font-medium transition-colors disabled:opacity-60"
                        >{{ saving ? '…' : t('settings.save') }}</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Ride tiles: same cards as the public page, empty in create mode -->
        <div v-if="isEdit" data-testid="ride-tiles" class="mt-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <RideCard
                v-for="ride in rides"
                :key="ride.id"
                :ride="ride"
                :event="event"
                manageable
                @open="selectedRide = ride"
                @edit="editingRide = ride"
                @delete="deleteRide(ride)"
            />
        </div>

        <!-- Ride detail popup -->
        <Teleport to="body">
            <RidePopup
                v-if="selectedRide"
                :ride="selectedRide"
                :event="event"
                manageable
                @close="selectedRide = null"
                @edit="onPopupEdit"
                @delete="onPopupDelete"
            />
        </Teleport>

        <!-- Ride edit form (modal, no token required for admin / event creator) -->
        <Teleport to="body">
            <div
                v-if="editingRide"
                class="fixed inset-0 z-[2000] bg-black/50 flex items-end md:items-center justify-center"
                @click.self="editingRide = null"
            >
                <div class="bg-white rounded-t-2xl md:rounded-xl w-full md:max-w-lg max-h-[90vh] overflow-hidden">
                  <div class="max-h-[90vh] overflow-y-auto">
                    <RideForm
                        :event="event"
                        :ride="editingRide"
                        manage
                        @submitted="onRideUpdated"
                        @cancelled="editingRide = null"
                    />
                  </div>
                </div>
            </div>
        </Teleport>

        <!-- Created event popup (shown after new event is saved) -->
        <Teleport to="body">
            <div
                v-if="createdEvent"
                class="fixed inset-0 z-[2000] bg-black/50 flex items-center justify-center p-4"
            >
                <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-xl">
                    <h2 class="text-lg font-semibold mb-4">{{ t('event.created_title') }}</h2>

                    <div class="mb-4">
                        <label class="field-label">{{ t('event.public_link') }}</label>
                        <div class="flex items-start gap-2">
                            <a
                                :href="createdEventPublicLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm text-[var(--color-primary)] hover:underline break-all flex-1 min-w-0"
                            >{{ createdEventPublicLink }}</a>
                            <a
                                :href="createdEventPublicLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-gray-400 hover:text-gray-600 shrink-0 mt-0.5"
                            >
                                <ExternalLink class="w-4 h-4" :stroke-width="2" />
                            </a>
                        </div>
                        <button
                            type="button"
                            class="mt-2 flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 rounded text-sm hover:bg-gray-50 text-gray-600"
                            @click="copyCreatedLink"
                        >
                            <Copy v-if="!copiedCreated" class="w-4 h-4" :stroke-width="2" />
                            <Check v-else class="w-4 h-4 text-green-600" :stroke-width="2" />
                            {{ t('event.copy') }}
                        </button>
                    </div>

                    <p class="text-sm text-gray-500 mb-5">{{ t('event.created_link_hint') }}</p>

                    <button
                        type="button"
                        class="w-full py-2 bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white rounded text-sm font-medium transition-colors"
                        @click="closeCreatedModal"
                    >{{ t('event.created_close') }}</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter, useRoute } from 'vue-router';
import { ExternalLink, Copy, Check } from '@lucide/vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import api from '@/api/axios';
import { locationFromNominatim, formatLocation } from '@/utils/formatLocation';
import { useAddressAutocomplete } from '@/composables/useAddressAutocomplete';
import RideCard from '../public/RideCard.vue';
import RideForm from '../public/RideForm.vue';
import RidePopup from '../public/RidePopup.vue';
import RouteLegend from '@/components/RouteLegend.vue';
import { OFFER_COLOR, REQUEST_COLOR, BOOKED_COLOR } from '@/constants/rideColors';

const { t }  = useI18n();
const router = useRouter();
const route  = useRoute();

const isEdit  = computed(() => !!route.params.id);
const eventId = computed(() => route.params.id);

const form    = reactive({ name: '', location: null });
const startDate = ref('');
const startTime = ref('');
const endDate    = ref('');
const endTime    = ref('');
const event   = ref(null);
const rides   = ref([]);
const selectedRide = ref(null);
const editingRide  = ref(null);
const saving  = ref(false);
const errors  = ref([]);
const copied  = ref(false);

const createdEvent       = ref(null);
const copiedCreated      = ref(false);

const createdEventPublicLink = computed(() =>
    createdEvent.value ? `${window.location.origin}/events/${createdEvent.value.slug}` : ''
);

const locationDisplayName = ref('');

const {
    addressInput,
    suggestions,
    highlightedIndex,
    onAddressInput,
    onAddressKeydown,
    closeSuggestions,
    selectSuggestion,
} = useAddressAutocomplete({
    onSelect: (s) => {
        const loc = locationFromNominatim(s);
        form.location             = loc;
        addressInput.value        = s.display_name;
        locationDisplayName.value = formatLocation(loc);
        setMarker(loc.latitude, loc.longitude);
        map.setView([loc.latitude, loc.longitude], 12);
        drawRides();
    },
});

const publicLink = computed(() =>
    event.value ? `${window.location.origin}/events/${event.value.slug}` : ''
);

// ── Field validation (validate on blur, re-validate earlier fields on focus) ───

const fieldOrder = ['name', 'startDate', 'startTime', 'endDate', 'endTime', 'address'];
const touched    = reactive(Object.fromEntries(fieldOrder.map(k => [k, false])));

function onFocus(key) {
    fieldOrder.slice(0, fieldOrder.indexOf(key)).forEach(k => { touched[k] = true; });
}
function onBlur(key) {
    touched[key] = true;
}

function validateField(key) {
    switch (key) {
        case 'name':      return form.name.trim() ? null : t('error.name_required');
        case 'startDate': return startDate.value ? null : t('event.start_date_required');
        case 'startTime': return startTime.value ? null : t('event.start_time_required');
        case 'endDate':   return endDate.value ? null : t('event.end_date_required');
        case 'endTime':   return endTime.value ? null : t('event.end_time_required');
        case 'address':   return form.location ? null : t('event.location_required');
        default:          return null;
    }
}

const fieldErrors = computed(() => Object.fromEntries(
    fieldOrder.map(k => [k, touched[k] ? validateField(k) : null])
));

// Map
const mapEl = ref(null);
let map       = null;
let marker    = null;
let rideLayer = null;

const starIcon = () => L.divIcon({
    html: '<span style="font-size:22px;display:block;transform:translate(-50%,-50%)">⭐</span>',
    className: '',
    iconSize: [0, 0],
});

function setMarker(lat, lng) {
    if (marker) marker.remove();
    marker = L.marker([lat, lng], { icon: starIcon() }).addTo(map);
}

let drawGeneration = 0;
const routeCache = new Map();

// Draw a route (line + pin) for every ride, dark green for offers, lilac for requests
function drawRides() {
    if (!rideLayer || !form.location) return;
    const generation = ++drawGeneration;
    rideLayer.clearLayers();

    const evLat = form.location.latitude;
    const evLng = form.location.longitude;

    const offerPolylines = [];
    const pins = [];

    rides.value.forEach(ride => {
        if (!ride.location) return;
        const lat   = parseFloat(ride.location.latitude);
        const lng   = parseFloat(ride.location.longitude);
        const booked = ride.type === 'offer' && ride.seats === 0;
        const color  = booked ? BOOKED_COLOR : (ride.type === 'offer' ? OFFER_COLOR : REQUEST_COLOR);

        const polyline = L.polyline([[lat, lng], [evLat, evLng]], { color, weight: 4, opacity: 0.85 })
            .addTo(rideLayer);
        if (ride.type === 'offer') offerPolylines.push(polyline);
        fetchRoute(ride, generation, polyline);

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

// Upgrades the straight line to the real driving route between the saved
// event/ride locations; the straight line stays as a fallback otherwise.
async function fetchRoute(ride, generation, polyline) {
    try {
        let geometry;
        if (routeCache.has(ride.id)) {
            geometry = routeCache.get(ride.id);
        } else {
            const { data } = await api.get(`/events/${eventId.value}/rides/${ride.id}/route`);
            geometry = data.geometry ?? null;
            routeCache.set(ride.id, geometry);
        }
        if (generation !== drawGeneration || !geometry) return;
        polyline.setLatLngs(geometry.map(([lng, lat]) => [lat, lng]));
    } catch {
        // Keep the straight line.
    }
}

function fitMapToRides() {
    if (!map || !form.location) return;
    const points = [[form.location.latitude, form.location.longitude]];
    rides.value.forEach(r => {
        if (r.location) points.push([parseFloat(r.location.latitude), parseFloat(r.location.longitude)]);
    });
    if (points.length > 1) map.fitBounds(points, { padding: [40, 40] });
    else map.setView(points[0], 10);
}

function onPopupEdit() {
    editingRide.value  = selectedRide.value;
    selectedRide.value = null;
}

function onPopupDelete() {
    const ride         = selectedRide.value;
    selectedRide.value = null;
    deleteRide(ride);
}

function onRideUpdated(ride) {
    const idx = rides.value.findIndex(r => r.id === ride.id);
    if (idx !== -1) rides.value[idx] = ride;
    editingRide.value = null;
    drawRides();
}

async function deleteRide(ride) {
    if (! window.confirm(t('ride.delete_confirm'))) return;
    await api.delete(`/events/${eventId.value}/rides/${ride.id}`);
    rides.value = rides.value.filter(r => r.id !== ride.id);
    drawRides();
}

// Times are stored naively (no timezone). Slice directly.
function toNaiveInput(iso) {
    if (!iso) return '';
    return iso.substring(0, 16);
}

onMounted(async () => {
    const { data: settings } = await api.get('/settings');

    map = L.map(mapEl.value, { zoomControl: true }).setView(
        [settings.map_center_lat, settings.map_center_lng],
        settings.map_zoom
    );
    L.tileLayer(
        import.meta.env.VITE_OSM_TILE_URL || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        { attribution: '© OpenStreetMap contributors' }
    ).addTo(map);
    rideLayer = L.layerGroup().addTo(map);

    if (isEdit.value) {
        const { data } = await api.get(`/events/${eventId.value}`);
        event.value   = data;
        rides.value   = data.rides ?? [];
        form.name = data.name;
        [startDate.value, startTime.value] = toNaiveInput(data.start_at).split('T');
        [endDate.value, endTime.value]     = toNaiveInput(data.end_at).split('T');
        if (data.location) {
            form.location      = {
                address:      data.location.address,
                latitude:     parseFloat(data.location.latitude),
                longitude:    parseFloat(data.location.longitude),
                country_code: data.location.country_code,
                street:       data.location.street ?? null,
                house_number: data.location.house_number ?? null,
                postal_code:  data.location.postal_code ?? null,
                city:         data.location.city ?? null,
            };
            addressInput.value        = data.location.address;
            locationDisplayName.value = data.location.display_name ?? formatLocation(form.location);
            setMarker(form.location.latitude, form.location.longitude);
            drawRides();
            fitMapToRides();
        }
    }
});

onUnmounted(() => {
    if (map) { map.remove(); map = null; }
});

function onAddressBlur() {
    closeSuggestions();
    onBlur('address');
}

async function copyLink() {
    await navigator.clipboard.writeText(publicLink.value);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

async function copyCreatedLink() {
    await navigator.clipboard.writeText(createdEventPublicLink.value);
    copiedCreated.value = true;
    setTimeout(() => { copiedCreated.value = false; }, 2000);
}

function closeCreatedModal() {
    createdEvent.value = null;
    router.push({ name: 'backend.events' });
}

async function submit() {
    errors.value = [];
    fieldOrder.forEach(k => { touched[k] = true; });
    if (fieldOrder.some(k => fieldErrors.value[k])) return;

    const payload = {
        name:     form.name,
        start_at: `${startDate.value}T${startTime.value}`,
        end_at:   `${endDate.value}T${endTime.value}`,
        location: { ...form.location, display_name: locationDisplayName.value || null },
    };

    saving.value = true;
    try {
        if (isEdit.value) {
            await api.put(`/events/${eventId.value}`, payload);
            router.push({ name: 'backend.events' });
        } else {
            const { data } = await api.post('/events', payload);
            createdEvent.value = data;
        }
    } catch (e) {
        const resp   = e.response?.data;
        errors.value = resp?.errors
            ? Object.values(resp.errors).flat()
            : [resp?.message ?? t('event.save_error')];
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
@reference "tailwindcss";
.field-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.field-input { @apply w-full px-3 py-2 bg-white border border-gray-300 rounded text-sm focus:outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20; }
</style>
