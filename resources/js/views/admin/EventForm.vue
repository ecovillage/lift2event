<template>
    <div>
        <h1 class="text-xl font-semibold mb-6">
            {{ isEdit ? t('event.edit') : t('event.new') }}
        </h1>

        <form @submit.prevent="submit">
            <!-- Two-column layout: form left (20%), map right (80%)       -->
            <!-- Mobile order: map first (order-1), form second (order-2)  -->
            <div class="flex flex-col md:flex-row gap-4" style="min-height: 400px">

                <!-- Map: top on mobile, right column on desktop -->
                <div class="order-1 md:order-2 h-64 md:h-auto md:flex-1 rounded overflow-hidden relative">
                    <div ref="mapEl" class="absolute inset-0"></div>
                </div>

                <!-- Form: bottom on mobile, left column on desktop -->
                <div class="order-2 md:order-1 md:w-1/5 md:min-w-[200px] space-y-4 flex flex-col">
                    <div>
                        <label class="field-label">{{ t('event.name') }}</label>
                        <input v-model="form.name" type="text" required class="field-input" />
                    </div>

                    <div>
                        <label class="field-label">{{ t('event.start') }}</label>
                        <input v-model="form.start_at" type="datetime-local" required class="field-input" />
                    </div>

                    <div>
                        <label class="field-label">{{ t('event.end') }}</label>
                        <input v-model="form.end_at" type="datetime-local" required class="field-input" />
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
                            @blur="closeSuggestions"
                        />
                        <ul
                            v-if="suggestions.length"
                            class="absolute z-50 left-0 right-0 bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-y-auto text-sm"
                        >
                            <li
                                v-for="s in suggestions"
                                :key="s.place_id"
                                class="px-3 py-2 hover:bg-gray-100 cursor-pointer leading-snug"
                                @mousedown.prevent="selectSuggestion(s)"
                            >
                                {{ s.display_name }}
                            </li>
                        </ul>
                        <p v-if="form.location" class="mt-1 text-xs text-gray-400 truncate">
                            {{ form.location.latitude.toFixed(4) }}, {{ form.location.longitude.toFixed(4) }}
                        </p>
                    </div>

                    <!-- Public link (edit mode only) -->
                    <div v-if="isEdit && event">
                        <label class="field-label">{{ t('event.public_link') }}</label>
                        <div class="flex gap-1">
                            <input :value="publicLink" readonly class="field-input flex-1 text-xs bg-gray-50 cursor-default" />
                            <button
                                type="button"
                                class="px-2 border border-gray-300 rounded text-xs hover:bg-gray-50 shrink-0"
                                @click="copyLink"
                            >{{ copied ? '✓' : t('event.copy') }}</button>
                        </div>
                    </div>

                    <p v-for="e in errors" :key="e" class="text-red-600 text-sm">{{ e }}</p>

                    <div class="flex gap-2 pt-2 mt-auto">
                        <button
                            type="button"
                            class="flex-1 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50"
                            @click="router.push({ name: 'admin.events' })"
                        >{{ t('ride.cancel') }}</button>
                        <button
                            type="submit"
                            :disabled="saving || !form.location"
                            class="flex-1 py-2 bg-[--color-primary] hover:bg-[--color-primary-dark] text-white rounded text-sm font-medium transition-colors disabled:opacity-60"
                        >{{ saving ? '…' : t('settings.save') }}</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Ride tiles: same cards as the public page, empty in create mode -->
        <div v-if="isEdit" class="mt-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
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
                @close="selectedRide = null"
            />
        </Teleport>

        <!-- Ride edit form (modal, no token required for admin / event creator) -->
        <Teleport to="body">
            <div
                v-if="editingRide"
                class="fixed inset-0 z-[2000] bg-black/50 flex items-end md:items-center justify-center"
                @click.self="editingRide = null"
            >
                <div class="bg-white rounded-t-2xl md:rounded-xl w-full md:max-w-lg max-h-[90vh] overflow-y-auto">
                    <RideForm
                        :event="event"
                        :ride="editingRide"
                        manage
                        @submitted="onRideUpdated"
                        @cancelled="editingRide = null"
                    />
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter, useRoute } from 'vue-router';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import api from '@/api/axios';
import RideCard from '../public/RideCard.vue';
import RideForm from '../public/RideForm.vue';
import RidePopup from '../public/RidePopup.vue';

const { t }  = useI18n();
const router = useRouter();
const route  = useRoute();

const isEdit  = computed(() => !!route.params.id);
const eventId = computed(() => route.params.id);

const form    = reactive({ name: '', start_at: '', end_at: '', location: null });
const event   = ref(null);
const rides   = ref([]);
const selectedRide = ref(null);
const editingRide  = ref(null);
const saving  = ref(false);
const errors  = ref([]);
const copied  = ref(false);

const addressInput = ref('');
const suggestions  = ref([]);
let searchTimer    = null;

const publicLink = computed(() =>
    event.value ? `${window.location.origin}/e/${event.value.slug}` : ''
);

// Map
const mapEl = ref(null);
let map       = null;
let marker    = null;
let rideLayer = null;

const offerColor   = '#4a9f6e';
const requestColor = '#e07b30';

const starIcon = () => L.divIcon({
    html: '<span style="font-size:22px;display:block;transform:translate(-50%,-50%)">⭐</span>',
    className: '',
    iconSize: [0, 0],
});

function setMarker(lat, lng) {
    if (marker) marker.remove();
    marker = L.marker([lat, lng], { icon: starIcon() }).addTo(map);
}

// Draw a route (line + pin) for every ride, green for offers, orange for requests
function drawRides() {
    if (!rideLayer || !form.location) return;
    rideLayer.clearLayers();

    const evLat = form.location.latitude;
    const evLng = form.location.longitude;

    rides.value.forEach(ride => {
        if (!ride.location) return;
        const lat   = parseFloat(ride.location.latitude);
        const lng   = parseFloat(ride.location.longitude);
        const color = ride.type === 'offer' ? offerColor : requestColor;

        L.polyline([[lat, lng], [evLat, evLng]], { color, weight: 2, opacity: 0.6 })
            .addTo(rideLayer);

        L.circleMarker([lat, lng], {
            radius: 7, color: 'white', weight: 2,
            fillColor: color, fillOpacity: 0.9,
        })
            .on('click', () => { selectedRide.value = ride; })
            .addTo(rideLayer);
    });
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

function toLocalInput(iso) {
    if (!iso) return '';
    const d   = new Date(iso);
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
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
        form.name     = data.name;
        form.start_at = toLocalInput(data.start_at);
        form.end_at   = toLocalInput(data.end_at);
        if (data.location) {
            form.location      = {
                address:      data.location.address,
                latitude:     parseFloat(data.location.latitude),
                longitude:    parseFloat(data.location.longitude),
                country_code: data.location.country_code,
            };
            addressInput.value = data.location.address;
            setMarker(form.location.latitude, form.location.longitude);
            drawRides();
            fitMapToRides();
        }
    }
});

onUnmounted(() => {
    if (map) { map.remove(); map = null; }
});

// Nominatim autocomplete (proxied through our API)
function onAddressInput() {
    clearTimeout(searchTimer);
    const q = addressInput.value.trim();
    if (q.length < 3) { suggestions.value = []; return; }
    searchTimer = setTimeout(async () => {
        try {
            const { data } = await api.get('/geocode/search', { params: { q } });
            suggestions.value = data;
        } catch { suggestions.value = []; }
    }, 350);
}

function closeSuggestions() {
    // Small delay so mousedown on suggestion fires before blur clears the list
    setTimeout(() => { suggestions.value = []; }, 150);
}

function selectSuggestion(s) {
    const lat = parseFloat(s.lat);
    const lng = parseFloat(s.lon);
    const cc  = (s.address?.country_code ?? '').toUpperCase().slice(0, 2) || null;
    form.location      = { address: s.display_name, latitude: lat, longitude: lng, country_code: cc };
    addressInput.value = s.display_name;
    suggestions.value  = [];
    setMarker(lat, lng);
    map.setView([lat, lng], 12);
    drawRides();
}

async function copyLink() {
    await navigator.clipboard.writeText(publicLink.value);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

async function submit() {
    errors.value = [];
    if (!form.location) { errors.value = [t('event.location_required')]; return; }
    saving.value = true;
    try {
        if (isEdit.value) {
            await api.put(`/events/${eventId.value}`, form);
        } else {
            await api.post('/events', form);
        }
        router.push({ name: 'admin.events' });
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
.field-input { @apply w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:border-[--color-primary] focus:ring-2 focus:ring-[--color-primary]/20; }
</style>
