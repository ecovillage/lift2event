<template>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-logo">
                <img src="/logo.svg" alt="Lift2Event" height="48" />
            </div>

            <form @submit.prevent="submit">
                <div class="field">
                    <label for="email">{{ t('auth.email') }}</label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        autocomplete="email"
                        required
                        autofocus
                    />
                </div>

                <div class="field">
                    <label for="password">{{ t('auth.password') }}</label>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        autocomplete="current-password"
                        required
                    />
                </div>

                <p v-if="error" class="error-msg">{{ error }}</p>

                <button type="submit" :disabled="loading" class="btn-primary btn-block">
                    {{ loading ? '…' : t('auth.login') }}
                </button>
            </form>

            <div class="auth-links">
                <RouterLink :to="{ name: 'password.request' }">{{ t('auth.forgot_password') }}</RouterLink>
                <RouterLink :to="{ name: 'register' }">{{ t('auth.register') }}</RouterLink>
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

<style scoped>
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
}

.auth-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .1);
    padding: 2rem;
    width: 100%;
    max-width: 380px;
}

.auth-logo {
    text-align: center;
    margin-bottom: 1.5rem;
}

.field {
    margin-bottom: 1rem;
}

label {
    display: block;
    font-size: .875rem;
    margin-bottom: .25rem;
    color: #333;
}

input {
    width: 100%;
    padding: .5rem .75rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
    box-sizing: border-box;
}

input:focus {
    outline: none;
    border-color: #4a9f6e;
    box-shadow: 0 0 0 2px rgba(74, 159, 110, .2);
}

.error-msg {
    color: #c0392b;
    font-size: .875rem;
    margin: .5rem 0;
}

.btn-primary {
    background: #4a9f6e;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: .6rem 1.25rem;
    font-size: 1rem;
    cursor: pointer;
}

.btn-primary:disabled {
    opacity: .6;
    cursor: default;
}

.btn-block {
    width: 100%;
    margin-top: .5rem;
}

.auth-links {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    font-size: .875rem;
}

.auth-links a {
    color: #4a9f6e;
    text-decoration: none;
}

.auth-links a:hover {
    text-decoration: underline;
}
</style>
