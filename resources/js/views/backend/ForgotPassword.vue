<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-sm">
            <div class="text-center mb-6">
                <img src="/logo.svg" alt="Lift2Event" class="h-12 mx-auto" />
            </div>

            <div v-if="sent" class="space-y-4">
                <p class="text-sm text-gray-700">{{ t('auth.reset_link_sent') }}</p>
                <RouterLink :to="{ name: 'login' }" class="block text-center text-sm text-[var(--color-primary)] hover:underline">
                    {{ t('auth.back_to_login') }}
                </RouterLink>
            </div>

            <form v-else @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ t('auth.email') }}
                    </label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        autocomplete="email"
                        required
                        autofocus
                        class="input"
                    />
                </div>

                <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full mt-2 py-2 px-4 bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white rounded font-medium transition-colors disabled:opacity-60"
                >
                    {{ loading ? '…' : t('auth.send_reset_link') }}
                </button>
            </form>

            <div v-if="!sent" class="mt-4 text-center text-sm">
                <RouterLink :to="{ name: 'login' }" class="text-[var(--color-primary)] hover:underline">
                    {{ t('auth.back_to_login') }}
                </RouterLink>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/axios';

const { t } = useI18n();

const email   = ref('');
const error   = ref('');
const loading = ref(false);
const sent    = ref(false);

async function submit() {
    error.value   = '';
    loading.value = true;
    try {
        await api.post('/forgot-password', { email: email.value });
        sent.value = true;
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Fehler beim Senden.';
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
@reference "tailwindcss";

.input {
    @apply w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary)]/20;
}
</style>
