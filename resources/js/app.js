import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import router from './router';
import messages from './i18n';

const i18n = createI18n({
    legacy: false,
    locale: document.documentElement.lang || 'de',
    fallbackLocale: 'de',
    messages,
});

createApp(App)
    .use(router)
    .use(i18n)
    .mount('#app');
