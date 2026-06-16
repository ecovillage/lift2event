<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-lg mx-auto px-4 py-8">
            <RouterLink
                :to="{ name: 'event.show', params: { slug: route.params.slug } }"
                class="text-sm text-gray-500 hover:text-gray-700 mb-6 inline-block"
            >&#8592; Zurück zur Mitfahrbörse</RouterLink>

            <div v-if="loading" class="text-center py-16 text-gray-400">…</div>
            <div v-else-if="fetchError" class="text-center py-16 text-red-500">{{ fetchError }}</div>
            <div v-else-if="!ride" class="text-center py-16 text-gray-400">Eintrag nicht gefunden.</div>

            <template v-else>
                <div class="bg-white rounded-lg shadow-sm p-6 border">
                    <h1 class="text-lg font-semibold text-gray-800 mb-2">Eintrag löschen</h1>
                    <p class="text-sm text-gray-600 mb-4">
                        Soll dieser Eintrag wirklich gelöscht werden?
                        <strong>{{ ride.name }}</strong> ·
                        {{ ride.type === 'offer' ? 'Angebot' : 'Gesuch' }}
                    </p>

                    <div v-if="!resolvedToken" class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Bitte gib deinen Bearbeitungstoken ein:</p>
                        <input v-model="manualToken" type="text" placeholder="Token" class="w-full px-3 py-2 border border-gray-300 rounded text-sm font-mono mb-2" />
                        <button
                            class="w-full py-2 bg-gray-700 text-white rounded text-sm hover:bg-gray-800"
                            @click="applyToken"
                        >Token bestätigen</button>
                        <p v-if="tokenError" class="mt-1 text-sm text-red-500">{{ tokenError }}</p>
                    </div>

                    <p v-if="serverError" class="text-sm text-red-500 mb-3">{{ serverError }}</p>

                    <div class="flex gap-3">
                        <RouterLink
                            :to="{ name: 'event.show', params: { slug: route.params.slug } }"
                            class="flex-1 py-2 text-center border border-gray-300 rounded text-sm hover:bg-gray-50"
                        >Abbrechen</RouterLink>
                        <button
                            :disabled="!resolvedToken || deleting"
                            class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white rounded text-sm font-medium disabled:opacity-50"
                            @click="confirmDelete"
                        >{{ deleting ? '…' : 'Löschen' }}</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route  = useRoute();
const router = useRouter();

const ride         = ref(null);
const loading      = ref(true);
const fetchError   = ref(null);
const deleting     = ref(false);
const serverError  = ref('');
const manualToken  = ref('');
const tokenError   = ref('');

const resolvedToken = ref(
    route.query.token ||
    localStorage.getItem(`ride_token_${route.params.id}`) ||
    null
);

onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/e/${route.params.slug}`, { headers: { Accept: 'application/json' } });
        ride.value = data.rides.find(r => String(r.id) === String(route.params.id)) ?? null;
    } catch { fetchError.value = 'Fehler beim Laden.'; }
    finally  { loading.value = false; }
});

function applyToken() {
    const t = manualToken.value.trim();
    if (!t) { tokenError.value = 'Bitte gib einen Token ein.'; return; }
    resolvedToken.value = t;
}

async function confirmDelete() {
    if (!resolvedToken.value) return;
    deleting.value = true;
    serverError.value = '';
    try {
        await axios.delete(`/api/e/${route.params.slug}/rides/${route.params.id}`, {
            data:    { edit_token: resolvedToken.value },
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        });
        localStorage.removeItem(`ride_token_${route.params.id}`);
        router.push({ name: 'event.show', params: { slug: route.params.slug } });
    } catch (e) {
        serverError.value = e.response?.status === 403 ? 'Ungültiger Token.' : 'Löschen fehlgeschlagen.';
    } finally {
        deleting.value = false;
    }
}
</script>
