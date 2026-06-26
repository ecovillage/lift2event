const SUPPORTED = ['de', 'en', 'fr', 'zh'];

export function getBrowserLocale() {
    const lang = navigator.languages?.[0] ?? navigator.language;
    if (!lang) return 'de';
    const base = lang.split('-')[0].toLowerCase();
    return SUPPORTED.includes(base) ? base : 'en';
}
