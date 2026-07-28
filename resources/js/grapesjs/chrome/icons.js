/**
 * Inline Tabler outline icons used by site chrome (nav header buttons).
 * Frontend/editor load only this subset — never the full Tabler catalog.
 *
 * Paths from @tabler/icons outline (viewBox 0 0 24 24).
 */

/** @type {Record<string, string>} */
export const CHROME_TABLER_PATHS = {
    search: '<path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/>',
    bell: '<path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/>',
    user: '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>',
    'menu-2': '<path d="M4 6l16 0"/><path d="M4 12l16 0"/><path d="M4 18l16 0"/>',
};

/**
 * @param {keyof typeof CHROME_TABLER_PATHS | string} name
 * @param {{ className?: string }} [opts]
 * @returns {string}
 */
export function chromeTablerIconSvg(name, opts = {}) {
    const key = Object.prototype.hasOwnProperty.call(CHROME_TABLER_PATHS, name) ? name : 'user';
    const className = opts.className ? ` class="${opts.className}"` : '';

    return `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"${className} data-vb-chrome-icon="${key}">${CHROME_TABLER_PATHS[key]}</svg>`;
}

/**
 * @param {Record<string, unknown>} attrs
 * @returns {string}
 */
export function chromeIconSvgForAttrs(attrs = {}) {
    if (attrs['data-voodbuilder-search-open'] != null) {
        return chromeTablerIconSvg('search');
    }

    if (attrs['data-voodbuilder-notification-bell-preview'] != null) {
        return chromeTablerIconSvg('bell');
    }

    if (attrs['data-voodbuilder-profile-menu-toggle'] != null) {
        return chromeTablerIconSvg('user');
    }

    if (attrs['data-mobile-nav-toggle'] != null || attrs['data-mobile-nav-close'] != null) {
        return chromeTablerIconSvg('menu-2', { className: 'h-6 w-6' });
    }

    return chromeTablerIconSvg('user');
}

export const CHROME_ICON_PLACEHOLDER_TEXT = new Set(['Send', 'Button', 'Notifications']);

/**
 * @param {string} text
 * @returns {boolean}
 */
export function isChromeIconPlaceholderText(text) {
    const value = String(text ?? '').replace(/\s+/g, '');

    if (value === '') {
        return false;
    }

    return CHROME_ICON_PLACEHOLDER_TEXT.has(value)
        || /^(?:Button|Notifications|Send)+$/i.test(value)
        || /Button{2,}/i.test(value);
}
