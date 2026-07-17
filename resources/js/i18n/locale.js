const SUPPORTED = ['de', 'en', 'fr', 'zh'];

export function getUrlLang() {
    const param = new URLSearchParams(window.location.search).get('lang');
    return param && SUPPORTED.includes(param) ? param : null;
}

export function getBrowserLocale() {
    const url = getUrlLang();
    if (url) return url;

    const lang = navigator.languages?.[0] ?? navigator.language;
    if (!lang) return 'de';
    const base = lang.split('-')[0].toLowerCase();
    return SUPPORTED.includes(base) ? base : 'en';
}
