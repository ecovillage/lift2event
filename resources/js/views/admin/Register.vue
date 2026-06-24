<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-sm">
            <div class="text-center mb-6">
                <img src="/logo.svg" alt="Lift2Event" class="h-12 mx-auto" />
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ t('auth.name') }}
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        required
                        autofocus
                        class="input"
                    />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ t('auth.email') }}
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        required
                        class="input"
                    />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ t('auth.password') }}
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="input"
                    />
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ t('auth.confirm_password') }}
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="input"
                    />
                </div>

                <p v-if="errors.length" class="text-red-600 text-sm space-y-1">
                    <span v-for="e in errors" :key="e" class="block">{{ e }}</span>
                </p>

                <button type="submit" :disabled="loading" class="w-full mt-2 py-2 px-4 bg-[var(--color-primary)] hover:bg-[var(--color-primary-dark)] text-white rounded font-medium transition-colors disabled:opacity-60">
                    {{ loading ? '…' : t('auth.register') }}
                </button>
            </form>

            <div class="mt-4 text-center text-sm">
                <RouterLink :to="{ name: 'login' }" class="text-[var(--color-primary)] hover:underline">
                    {{ t('auth.login') }}
                </RouterLink>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import api from '@/api/axios';

const { t } = useI18n();
const router = useRouter();
const { state } = useAuth();

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const errors = ref([]);
const loading = ref(false);

async function submit() {
    errors.value = [];
    loading.value = true;
    try {
        const { data } = await api.post('/register', form);
        localStorage.setItem('auth_token', data.token);
        state.user = data.user;
        router.push({ name: 'admin.events' });
    } catch (e) {
        const resp = e.response?.data;
        if (resp?.errors) {
            errors.value = Object.values(resp.errors).flat();
        } else {
            errors.value = [resp?.message ?? 'Registrierung fehlgeschlagen.'];
        }
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
