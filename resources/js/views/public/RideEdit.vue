<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-xl mx-auto px-4 py-8">
            <RouterLink
                :to="{ name: 'event.show', params: { slug: route.params.slug } }"
                class="text-sm text-gray-500 hover:text-gray-700 mb-6 inline-block"
            >&#8592; Zurück zur Mitfahrbörse</RouterLink>

            <div v-if="loading" class="text-center py-16 text-gray-400">…</div>
            <div v-else-if="fetchError" class="text-center py-16 text-red-500">{{ fetchError }}</div>
            <div v-else-if="!ride" class="text-center py-16 text-gray-400">Eintrag nicht gefunden.</div>

            <template v-else-if="!resolvedToken">
                <div class="bg-white rounded-lg shadow-sm p-6 border">
                    <p class="text-sm text-gray-600 mb-3">Bitte gib deinen Bearbeitungstoken ein:</p>
                    <input v-model="manualToken" type="text" placeholder="Token" class="w-full px-3 py-2 border border-gray-300 rounded text-sm font-mono mb-3" />
                    <button
                        class="w-full py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium"
                        @click="applyToken"
                    >Weiter</button>
                    <p v-if="tokenError" class="mt-2 text-sm text-red-500">{{ tokenError }}</p>
                </div>
            </template>

            <RideForm
                v-else
                :event="event"
                :ride="ride"
                :edit-token="resolvedToken"
                @submitted="router.push({ name: 'event.show', params: { slug: route.params.slug } })"
                @cancelled="router.push({ name: 'event.show', params: { slug: route.params.slug } })"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import RideForm from './RideForm.vue';

const route  = useRoute();
const router = useRouter();

const event       = ref(null);
const ride        = ref(null);
const loading     = ref(true);
const fetchError  = ref(null);
const manualToken = ref('');
const tokenError  = ref('');

// Token priority: query param → localStorage
const resolvedToken = ref(
    route.query.token ||
    localStorage.getItem(`ride_token_${route.params.id}`) ||
    null
);

onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/e/${route.params.slug}`, { headers: { Accept: 'application/json' } });
        event.value = data.event;
        ride.value  = data.rides.find(r => String(r.id) === String(route.params.id)) ?? null;
    } catch { fetchError.value = 'Fehler beim Laden.'; }
    finally  { loading.value = false; }
});

function applyToken() {
    const t = manualToken.value.trim();
    if (!t) { tokenError.value = 'Bitte gib einen Token ein.'; return; }
    resolvedToken.value = t;
}
</script>
