import { createI18n } from 'vue-i18n';
import messages from './index';
import { getBrowserLocale } from './locale';

export const i18n = createI18n({
    legacy: false,
    locale: getBrowserLocale(),
    fallbackLocale: 'de',
    messages,
});
