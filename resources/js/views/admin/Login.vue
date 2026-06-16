<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-sm">
            <div class="text-center mb-6">
                <img src="/logo.svg" alt="Lift2Event" class="h-12 mx-auto" />
            </div>

            <form @submit.prevent="submit" class="space-y-4">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-[--color-primary] focus:ring-2 focus:ring-[--color-primary]/20"
                    />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ t('auth.password') }}
                    </label>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-[--color-primary] focus:ring-2 focus:ring-[--color-primary]/20"
                    />
                </div>

                <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full mt-2 py-2 px-4 bg-[--color-primary] hover:bg-[--color-primary-dark] text-white rounded font-medium transition-colors disabled:opacity-60"
                >
                    {{ loading ? '…' : t('auth.login') }}
                </button>
            </form>

            <div class="mt-4 flex justify-between text-sm">
                <RouterLink :to="{ name: 'password.request' }" class="text-[--color-primary] hover:underline">
                    {{ t('auth.forgot_password') }}
                </RouterLink>
                <RouterLink :to="{ name: 'register' }" class="text-[--color-primary] hover:underline">
                    {{ t('auth.register') }}
                </RouterLink>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const { t } = useI18n();
const router = useRouter();
const { login } = useAuth();

const email    = ref('');
const password = ref('');
const error    = ref('');
const loading  = ref(false);

async function submit() {
    error.value   = '';
    loading.value = true;
    try {
        await login(email.value, password.value);
        router.push({ name: 'admin.events' });
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Anmeldung fehlgeschlagen.';
    } finally {
        loading.value = false;
    }
}
</script>
