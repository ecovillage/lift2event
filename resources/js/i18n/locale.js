const SUPPORTED = ['de', 'en', 'fr', 'zh'];

export function getBrowserLocale() {
    const param = new URLSearchParams(window.location.search).get('lang');
    if (param && SUPPORTED.includes(param)) return param;

    const lang = navigator.languages?.[0] ?? navigator.language;
    if (!lang) return 'de';
    const base = lang.split('-')[0].toLowerCase();
    return SUPPORTED.includes(base) ? base : 'en';
}
