<template>
    <div>
        <h1 class="text-xl font-semibold mb-6">{{ t('nav.settings') }}</h1>

        <!-- Map section -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <p class="text-sm text-gray-600 mb-3">{{ t('settings.map_default') }}</p>
            <div class="px-[calc(var(--event-form-col-width)/2)] mb-4">
                <div ref="mapEl" class="aspect-[3/2] rounded overflow-hidden" data-testid="settings-map"></div>
            </div>
            <div class="flex items-center gap-3">
                <button
                    class="px-4 py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:bg-[var(--color-primary-dark)] disabled:opacity-60"
                    :disabled="saving.map"
                    data-testid="save-map-btn"
                    @click="saveMap"
                >{{ saving.map ? '…' : t('settings.save') }}</button>
                <span v-if="saved.map" class="text-sm text-green-600">✓ {{ t('settings.saved') }}</span>
                <span v-if="errors.map" class="text-sm text-red-500">{{ errors.map }}</span>
            </div>
        </div>

        <!-- Footer links section -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">{{ t('settings.footer_links') }}</h2>

            <div v-for="(link, i) in footerLinks" :key="i" class="flex gap-2 mb-2 items-center">
                <input
                    v-model="link.label"
                    type="text"
                    placeholder="Label"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:border-[var(--color-primary)]"
                    :data-testid="`footer-link-label-${i}`"
                />
                <input
                    v-model="link.url"
                    type="url"
                    placeholder="https://…"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:border-[var(--color-primary)]"
                    :data-testid="`footer-link-url-${i}`"
                />
                <button
                    class="px-2 py-2 text-red-400 hover:text-red-600 text-sm"
                    :data-testid="`remove-footer-link-${i}`"
                    @click="footerLinks.splice(i, 1)"
                >✕</button>
            </div>

            <button
                class="text-sm text-[var(--color-primary)] hover:underline mt-1"
                data-testid="add-footer-link-btn"
                @click="footerLinks.push({ label: '', url: '' })"
            >+ {{ t('settings.add_link') }}</button>

            <div class="mt-4 flex items-center gap-3">
                <button
                    class="px-4 py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:bg-[var(--color-primary-dark)] disabled:opacity-60"
                    :disabled="saving.footer"
                    data-testid="save-footer-btn"
                    @click="saveFooter"
                >{{ saving.footer ? '…' : t('settings.save') }}</button>
                <span v-if="saved.footer" class="text-sm text-green-600">✓ {{ t('settings.saved') }}</span>
                <span v-if="errors.footer" class="text-sm text-red-500">{{ errors.footer }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import api from '@/api/axios';

const { t } = useI18n();

const mapEl      = ref(null);
const footerLinks = ref([]);
const saving     = reactive({ map: false, footer: false });
const saved      = reactive({ map: false, footer: false });
const errors     = reactive({ map: '', footer: '' });

let map = null;

onMounted(async () => {
    const { data: settings } = await api.get('/settings');

    footerLinks.value = settings.footer_links ? JSON.parse(JSON.stringify(settings.footer_links)) : [];

    map = L.map(mapEl.value, { zoomControl: true }).setView(
        [settings.map_center_lat, settings.map_center_lng],
        settings.map_zoom
    );
    L.tileLayer(
        import.meta.env.VITE_OSM_TILE_URL || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        { attribution: '© OpenStreetMap contributors' }
    ).addTo(map);
});

onUnmounted(() => {
    if (map) { map.remove(); map = null; }
});

async function saveMap() {
    saving.map  = true;
    errors.map  = '';
    saved.map   = false;
    try {
        const center = map.getCenter();
        await api.put('/settings', {
            map_center_lat: center.lat,
            map_center_lng: center.lng,
            map_zoom:       map.getZoom(),
        });
        saved.map = true;
        setTimeout(() => { saved.map = false; }, 3000);
    } catch {
        errors.map = t('event.save_error');
    } finally {
        saving.map = false;
    }
}

async function saveFooter() {
    saving.footer = true;
    errors.footer = '';
    saved.footer  = false;
    try {
        await api.put('/settings', { footer_links: footerLinks.value });
        saved.footer = true;
        setTimeout(() => { saved.footer = false; }, 3000);
    } catch {
        errors.footer = t('event.save_error');
    } finally {
        saving.footer = false;
    }
}
</script>
