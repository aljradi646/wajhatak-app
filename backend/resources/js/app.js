import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.LuxPreferences = (() => {
    const endpoint = window.__LUX_PREFERENCES_ENDPOINT__ || '';
    let timer = null;
    let queued = {};

    async function persist(values) {
        if (!endpoint || !values || typeof values !== 'object') return false;
        queued = { ...queued, ...values };
        clearTimeout(timer);
        await new Promise(resolve => { timer = setTimeout(resolve, 250); });
        const payload = queued;
        queued = {};
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });
            return response.ok;
        } catch { return false; }
    }

    return { persist };
})();

// --- Theme manager (System / Light / Dark) ---
window.LuxTheme = (() => {
    const storageKey = 'lux_theme';
    const html = document.documentElement;

    function preferred() {
        try {
            try {
                const cookie = document.cookie.split('; ').find(part => part.startsWith(storageKey + '='))?.split('=')[1];
                if (['light', 'dark', 'system'].includes(cookie)) return cookie;
                const stored = localStorage.getItem(storageKey);
                if (['light', 'dark', 'system'].includes(stored)) return stored;
            } catch {}
            const server = window.__LUX_THEME_SERVER__;
            return ['light', 'dark', 'system'].includes(server) ? server : 'system';
        } catch {
            return 'system';
        }
    }

    function systemDark() {
        return (
            window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches
        );
    }

    function apply(theme) {
        const dark = theme === 'dark' || (theme === 'system' && systemDark());
        html.classList.toggle('dark', dark);
        html.setAttribute('data-theme', theme);
    }

    async function set(theme) {
        const chosen = ['light', 'dark', 'system'].includes(theme) ? theme : 'system';
        try { localStorage.setItem(storageKey, chosen); } catch (e) {}
        const secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = storageKey + '=' + chosen + '; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
        apply(chosen);
        window.__LUX_THEME_SERVER__ = chosen;
        await window.LuxPreferences?.persist({ theme: chosen });
    }

    function init() {
        if (window.matchMedia) {
            window
                .matchMedia('(prefers-color-scheme: dark)')
                .addEventListener('change', () => {
                    if (preferred() === 'system') apply('system');
                });
        }
        apply(preferred());
    }

    return { preferred, apply, set, init };
})();

LuxTheme.init();

Alpine.start();
