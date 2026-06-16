import { reactive, computed } from 'vue';
import api from '@/api/axios';

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
    }

    async function logout() {
        try {
            await api.post('/logout');
        } catch {
            // token already invalid – proceed with local cleanup
        } finally {
            localStorage.removeItem('auth_token');
            state.user = null;
        }
    }

    return { state, isAuthenticated, isAdmin, fetchUser, login, logout };
}
