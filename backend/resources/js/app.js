import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// --- Theme manager (System / Light / Dark) ---
window.LuxTheme = (() => {
    const storageKey = 'lux_theme';
    const html = document.documentElement;

    function preferred() {
        try {
            const stored = localStorage.getItem(storageKey);
            return ['light', 'dark', 'system'].includes(stored) ? stored : 'system';
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

    function set(theme) {
        try {
            localStorage.setItem(storageKey, theme);
        } catch (e) {
            /* ignore */
        }
        apply(theme);
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
