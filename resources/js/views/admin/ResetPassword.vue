<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-sm">
            <div class="text-center mb-6">
                <img src="/logo.svg" alt="Lift2Event" class="h-12 mx-auto" />
            </div>

            <div v-if="success" class="space-y-4">
                <p class="text-sm text-gray-700">{{ t('auth.password_reset_success') }}</p>
                <RouterLink :to="{ name: 'login' }" class="block text-center text-sm text-[--color-primary] hover:underline">
                    {{ t('auth.back_to_login') }}
                </RouterLink>
            </div>

            <form v-else @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ t('auth.new_password') }}
                    </label>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        autofocus
                        class="input"
                    />
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ t('auth.confirm_password') }}
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="passwordConfirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="input"
                    />
                </div>

                <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full mt-2 py-2 px-4 bg-[--color-primary] hover:bg-[--color-primary-dark] text-white rounded font-medium transition-colors disabled:opacity-60"
                >
                    {{ loading ? '…' : t('auth.reset_password') }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import api from '@/api/axios';

const { t } = useI18n();
const route = useRoute();

const password             = ref('');
const passwordConfirmation = ref('');
const error                = ref('');
const loading              = ref(false);
const success              = ref(false);

async function submit() {
    error.value   = '';
    loading.value = true;
    try {
        await api.post('/reset-password', {
            token:                 route.query.token,
            email:                 route.query.email,
            password:              password.value,
            password_confirmation: passwordConfirmation.value,
        });
        success.value = true;
    } catch (e) {
        error.value = e.response?.data?.message ?? t('auth.invalid_token');
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
@reference "tailwindcss";

.input {
    @apply w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-[--color-primary] focus:ring-2 focus:ring-[--color-primary]/20;
}
</style>
