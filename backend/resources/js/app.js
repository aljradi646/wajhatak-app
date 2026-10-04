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
        await new Promise((resolve) => { timer = setTimeout(resolve, 250); });
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

window.LuxTheme = (() => {
    const storageKey = 'lux_theme';
    const html = document.documentElement;
    const valid = (value) => ['light', 'dark', 'system'].includes(value);

    function preferred() {
        const server = window.__LUX_THEME_SERVER__;
        if (valid(server)) return server;
        try {
            const cookie = document.cookie
                .split('; ')
                .find((part) => part.startsWith(storageKey + '='))
                ?.split('=')[1];
            if (valid(cookie)) return cookie;
            const stored = localStorage.getItem(storageKey);
            if (valid(stored)) return stored;
        } catch {}
        return 'system';
    }

    function systemDark() {
        return Boolean(window.matchMedia?.('(prefers-color-scheme: dark)').matches);
    }

    function apply(theme) {
        const chosen = valid(theme) ? theme : 'system';
        html.classList.toggle('dark', chosen === 'dark' || (chosen === 'system' && systemDark()));
        html.setAttribute('data-theme', chosen);
    }

    async function set(theme) {
        const chosen = valid(theme) ? theme : 'system';
        try { localStorage.setItem(storageKey, chosen); } catch {}
        const secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = storageKey + '=' + chosen + '; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
        apply(chosen);
        window.__LUX_THEME_SERVER__ = chosen;
        await window.LuxPreferences?.persist({ theme: chosen });
    }

    function init() {
        const media = window.matchMedia?.('(prefers-color-scheme: dark)');
        media?.addEventListener?.('change', () => { if (preferred() === 'system') apply('system'); });
        apply(preferred());
    }

    return { preferred, apply, set, init };
})();

LuxTheme.init();
Alpine.start();
