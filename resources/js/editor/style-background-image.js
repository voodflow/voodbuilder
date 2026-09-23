/**
 * Style panel decoration background-image helpers.
 * Opacity fades the image toward the solid background-color underneath
 * via a color overlay layered above the url().
 */

export const STYLE_BG_OPACITY_ATTR = 'data-vb-style-bg-opacity';

export const STYLE_BG_OPACITY_OPTIONS = [
    { value: '0.25', label: '25%' },
    { value: '0.35', label: '35%' },
    { value: '0.45', label: '45%' },
    { value: '0.55', label: '55%' },
    { value: '0.65', label: '65%' },
    { value: '0.75', label: '75%' },
    { value: '0.9', label: '90%' },
    { value: '1', label: '100%' },
];

/**
 * @param {unknown} value
 * @returns {number} 0–1
 */
export function normalizeBackgroundImageOpacity(value) {
    if (value == null || value === '') {
        return 1;
    }

    const n = Number.parseFloat(String(value).trim());

    if (! Number.isFinite(n)) {
        return 1;
    }

    if (n > 1) {
        return Math.min(1, Math.max(0, n / 100));
    }

    return Math.min(1, Math.max(0, n));
}

/**
 * Extract url(...) from a background-image value that may include opacity overlays.
 *
 * @param {unknown} raw
 * @returns {string}
 */
export function extractUrlFromBackgroundImage(raw) {
    const value = String(raw ?? '').trim();

    if (value === '' || value === 'none') {
        return '';
    }

    const match = value.match(/url\(\s*(['"]?)([^'")]+)\1\s*\)/i);

    return String(match?.[2] ?? '').trim();
}

/**
 * @param {unknown} url
 * @returns {string}
 */
export function cssBackgroundImageUrlValue(url) {
    const src = String(url ?? '').trim();

    if (src === '') {
        return '';
    }

    const escaped = src.replace(/\\/g, '\\\\').replace(/'/g, "\\'");

    return `url('${escaped}')`;
}

/**
 * Convert hex / rgb(a) / named-ish colors to `rgba(r, g, b, a)`.
 * Returns null when the color cannot be resolved to channels.
 *
 * @param {string} color
 * @param {number} alpha 0–1
 * @returns {string|null}
 */
export function toRgbaWithAlpha(color, alpha) {
    const raw = String(color ?? '').trim();
    const a = Math.round(Math.min(1, Math.max(0, Number(alpha) || 0)) * 1000) / 1000;

    if (raw === '' || raw === 'transparent') {
        return null;
    }

    const hex = raw.match(/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i);

    if (hex) {
        let h = hex[1];

        if (h.length === 3) {
            h = h.split('').map((c) => c + c).join('');
        }

        const r = Number.parseInt(h.slice(0, 2), 16);
        const g = Number.parseInt(h.slice(2, 4), 16);
        const b = Number.parseInt(h.slice(4, 6), 16);

        return `rgba(${r}, ${g}, ${b}, ${a})`;
    }

    const rgb = raw.match(/^rgba?\(\s*([\d.]+)\s*[, ]\s*([\d.]+)\s*[, ]\s*([\d.]+)(?:\s*[,/]\s*[\d.%]+)?\s*\)$/i);

    if (rgb) {
        return `rgba(${rgb[1]}, ${rgb[2]}, ${rgb[3]}, ${a})`;
    }

    return null;
}

/**
 * Build background-image CSS: optional fade overlay + url().
 * Overlay alpha = 1 − imageOpacity so the solid color behind shows through.
 *
 * @param {string} url
 * @param {number|string} [opacity=1]
 * @param {string} [fadeColor='']
 * @returns {string}
 */
export function composeDecorationBackgroundImageCss(url, opacity = 1, fadeColor = '') {
    const image = cssBackgroundImageUrlValue(url);

    if (image === '') {
        return '';
    }

    const op = normalizeBackgroundImageOpacity(opacity);

    if (op >= 0.999) {
        return image;
    }

    const fade = 1 - op;
    const color = String(fadeColor ?? '').trim() || 'var(--color-vp-bg, #0f172a)';
    const rgba = toRgbaWithAlpha(color, fade);

    if (rgba) {
        return `linear-gradient(${rgba}, ${rgba}), ${image}`;
    }

    const pct = Math.round(fade * 1000) / 10;

    return `linear-gradient(color-mix(in srgb, ${color} ${pct}%, transparent), color-mix(in srgb, ${color} ${pct}%, transparent)), ${image}`;
}
