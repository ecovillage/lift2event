<template>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-lg mx-auto px-4 py-8">
            <RouterLink
                :to="{ name: 'event.show', params: { slug: route.params.slug } }"
                class="text-sm text-gray-500 hover:text-gray-700 mb-6 inline-block"
            >&#8592; Zurück zur Mitfahrbörse</RouterLink>

            <div class="bg-white rounded-lg shadow-sm p-6 border">
                <h1 class="text-lg font-semibold text-gray-800 mb-3">Mitfahrt bestätigen</h1>

                <div v-if="confirming" class="text-center py-8 text-gray-400">…</div>

                <template v-else-if="!resolvedToken">
                    <p class="text-sm text-gray-600 mb-2">Bitte gib deinen Bestätigungstoken ein:</p>
                    <input v-model="manualToken" type="text" placeholder="Token" class="w-full px-3 py-2 border border-gray-300 rounded text-sm font-mono mb-2" />
                    <button
                        class="w-full py-2 bg-[var(--color-primary)] text-white rounded text-sm font-medium"
                        @click="applyToken"
                    >Bestätigen</button>
                    <p v-if="tokenError" class="mt-2 text-sm text-red-500">{{ tokenError }}</p>
                </template>

                <p v-else-if="serverError" class="text-sm text-red-500">{{ serverError }}</p>

                <p v-else-if="ride" class="text-sm text-gray-600">
                    Die Mitfahrt von <strong>{{ ride.name }}</strong> ist jetzt bestätigt und für andere Besucher sichtbar.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();

const ride         = ref(null);
const confirming   = ref(false);
const serverError  = ref('');
const manualToken  = ref('');
const tokenError   = ref('');

const resolvedToken = ref(
    route.query.token ||
    localStorage.getItem(`ride_token_${route.params.id}`) ||
    null
);

async function confirm() {
    confirming.value = true;
    serverError.value = '';
    try {
        const { data } = await axios.post(
            `/api/e/${route.params.slug}/rides/${route.params.id}/confirm`,
            { edit_token: resolvedToken.value },
            { headers: { Accept: 'application/json' } }
        );
        ride.value = data;
    } catch (e) {
        serverError.value = e.response?.status === 403 ? 'Ungültiger Token.' : 'Bestätigung fehlgeschlagen.';
    } finally {
        confirming.value = false;
    }
}

function applyToken() {
    const t = manualToken.value.trim();
    if (!t) { tokenError.value = 'Bitte gib einen Token ein.'; return; }
    resolvedToken.value = t;
}

watch(resolvedToken, (t) => { if (t) confirm(); }, { immediate: true });
</script>
