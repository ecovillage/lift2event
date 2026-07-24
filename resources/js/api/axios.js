import axios from 'axios';
import { i18n } from '@/i18n/instance';

const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    config.headers['X-Locale'] = i18n.global.locale.value;
    return config;
});

export default api;
