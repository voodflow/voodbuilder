/**
 * Shared color / thickness resolution for the Reading progress utility.
 */

import {
    TW_COLOR_FAMILIES,
    TW_COLOR_HEX,
    TW_COLOR_SHADES,
} from './tailwind-color-palette.js';

export const READING_PROGRESS_THICKNESS = [
    { value: '2', label: '2px' },
    { value: '3', label: '3px' },
    { value: '4', label: '4px' },
    { value: '6', label: '6px' },
    { value: '8', label: '8px' },
    { value: '12', label: '12px' },
];

const BRAND_COLOR_OPTIONS = [
    { value: 'brand', label: 'Brand' },
    { value: 'brand-2', label: 'Brand 2' },
    { value: 'brand-3', label: 'Brand 3' },
    { value: 'white', label: 'White' },
    { value: 'black', label: 'Black' },
];

/**
 * Brand tokens + full Tailwind palette (slate-50 … rose-950).
 *
 * @returns {Array<{value: string, label: string, hex?: string}>}
 */
export function readingProgressColorOptions() {
    const options = [...BRAND_COLOR_OPTIONS];

    for (const family of TW_COLOR_FAMILIES) {
        for (const shade of TW_COLOR_SHADES) {
            options.push({
                value: `${family}-${shade}`,
                label: `${family}-${shade}`,
                hex: TW_COLOR_HEX[family]?.[shade],
            });
        }
    }

    return options;
}

/**
 * @param {string} raw
 * @returns {string}
 */
export function resolveReadingProgressCssColor(raw) {
    const value = String(raw || 'brand').trim();

    if (value.startsWith('#') || value.startsWith('rgb') || value.startsWith('hsl') || value.startsWith('var(')) {
        return value;
    }

    if (value === 'brand' || value === 'brand-1') {
        return 'var(--color-vp-brand-1, #6366f1)';
    }

    if (value === 'brand-2') {
        return 'var(--color-vp-brand-2, #818cf8)';
    }

    if (value === 'brand-3') {
        return 'var(--color-vp-brand-3, #a5b4fc)';
    }

    if (value === 'white') {
        return '#ffffff';
    }

    if (value === 'black') {
        return '#000000';
    }

    // Legacy presets from 0.1.22
    if (value === 'light') {
        return TW_COLOR_HEX.slate?.['200'] ?? '#e2e8f0';
    }

    if (value === 'dark') {
        return TW_COLOR_HEX.slate?.['900'] ?? '#0f172a';
    }

    const match = value.match(/^([a-z]+)-(\d+)$/);

    if (match) {
        const hex = TW_COLOR_HEX[match[1]]?.[match[2]];

        if (hex) {
            return hex;
        }
    }

    return 'var(--color-vp-brand-1, #6366f1)';
}

/**
 * @param {number|string|null|undefined} raw
 * @returns {number}
 */
export function resolveReadingProgressThicknessPx(raw) {
    const px = Number.parseInt(String(raw ?? '4'), 10);

    return Number.isFinite(px) && px > 0 ? px : 4;
}

/**
 * Apply CSS variables on a live DOM track (published page / canvas).
 *
 * @param {HTMLElement} track
 */
export function applyReadingProgressCssVars(track) {
    if (! (track instanceof HTMLElement)) {
        return;
    }

    const color = resolveReadingProgressCssColor(track.getAttribute('data-vb-progress-color') || 'brand');
    const px = resolveReadingProgressThicknessPx(track.getAttribute('data-vb-progress-thickness') || '4');

    track.style.setProperty('--vb-progress-color', color);
    track.style.setProperty('--vb-progress-height', `${px}px`);
}

const SITE_HEADER_SELECTORS = [
    'header[role="banner"]',
    '[data-voodbuilder-editor-site-header]',
    '[data-voodbuilder-site-header]',
    '[data-voodbuilder-chrome="header"]',
].join(',');

/**
 * Bottom edge (px) of any site header still covering the top of the viewport.
 * Static nav scrolled away → 0 (progress sticks to the viewport top).
 * Sticky/fixed nav docked → nav height (progress sits under it).
 *
 * @param {Document} [doc]
 * @param {Window} [win]
 * @returns {number}
 */
export function resolveReadingProgressTopPx(doc = document, win = window) {
    let maxBottom = 0;

    doc.querySelectorAll(SITE_HEADER_SELECTORS).forEach((el) => {
        if (! (el instanceof HTMLElement)) {
            return;
        }

        const style = win.getComputedStyle(el);

        if (style.display === 'none' || style.visibility === 'hidden' || Number(style.opacity) === 0) {
            return;
        }

        const rect = el.getBoundingClientRect();

        if (rect.height < 1 || rect.bottom <= 0) {
            return;
        }

        // Still covering the top edge (stuck sticky/fixed, or in-flow at page top).
        if (rect.top <= 1) {
            maxBottom = Math.max(maxBottom, rect.bottom);
        }
    });

    return Math.max(0, Math.round(maxBottom));
}

/**
 * Pin standalone progress under the visible site header, or flush to the viewport top.
 *
 * @param {HTMLElement} track
 * @param {{ doc?: Document, win?: Window }} [options]
 */
export function applyReadingProgressTop(track, options = {}) {
    if (! (track instanceof HTMLElement) || ! track.classList.contains('vb-reading-progress')) {
        return;
    }

    const doc = options.doc ?? track.ownerDocument ?? document;
    const win = options.win ?? doc.defaultView ?? window;
    const top = resolveReadingProgressTopPx(doc, win);

    track.style.setProperty('--vb-progress-top', `${top}px`);
}
