import { reactive, computed } from 'vue';
import api from '@/api/axios';
import { i18n } from '@/i18n/instance';
import { getBrowserLocale } from '@/i18n/locale';

const state = reactive({
    user: null,
    ready: false,
});

export function useAuth() {
    const isAuthenticated = computed(() => !!state.user);
    const isAdmin = computed(() => state.user?.is_admin === true);

    async function fetchUser() {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            state.ready = true;
            return;
        }
        try {
            const { data } = await api.get('/user');
            state.user = data;
            i18n.global.locale.value = data.preferred_language;
        } catch {
            localStorage.removeItem('auth_token');
            state.user = null;
        } finally {
            state.ready = true;
        }
    }

    async function login(email, password) {
        const { data } = await api.post('/login', { email, password });
        localStorage.setItem('auth_token', data.token);
        state.user = data.user;
        i18n.global.locale.value = data.user.preferred_language;
    }

    async function logout() {
        try {
            await api.post('/logout');
        } catch {
            // token already invalid – proceed with local cleanup
        } finally {
            localStorage.removeItem('auth_token');
            state.user = null;
            i18n.global.locale.value = getBrowserLocale();
        }
    }

    return { state, isAuthenticated, isAdmin, fetchUser, login, logout };
}
