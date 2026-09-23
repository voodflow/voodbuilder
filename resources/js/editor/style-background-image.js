/**
 * Style panel decoration background-image helpers.
 * Opacity fades the image toward the solid background-color underneath
 * via a color overlay layered above the url().
 * Gradient + image stack as: gradient, [color fade], url(...).
 */

export const STYLE_BG_OPACITY_ATTR = 'data-vb-style-bg-opacity';
export const STYLE_BG_SRC_ATTR = 'data-vb-style-bg-src';

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

/** Tailwind bg-gradient-to-* → CSS linear-gradient direction. */
export const GRADIENT_DIRECTION_CSS = {
    'bg-gradient-to-t': 'to top',
    'bg-gradient-to-tr': 'to top right',
    'bg-gradient-to-r': 'to right',
    'bg-gradient-to-br': 'to bottom right',
    'bg-gradient-to-b': 'to bottom',
    'bg-gradient-to-bl': 'to bottom left',
    'bg-gradient-to-l': 'to left',
    'bg-gradient-to-tl': 'to top left',
};

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
 * When Style panel opacity was saved as a color overlay gradient, recover the
 * image opacity (1 − overlay alpha) from the composed background-image CSS.
 *
 * @param {unknown} raw
 * @returns {number|null}
 */
export function inferBackgroundImageOpacityFromCss(raw) {
    const value = String(raw ?? '').trim();

    if (value === '' || ! /url\s*\(/i.test(value)) {
        return null;
    }

    const rgba = value.match(
        /linear-gradient\(\s*rgba?\(\s*[\d.]+\s*[, ]\s*[\d.]+\s*[, ]\s*[\d.]+\s*[,/]\s*([\d.]+)/i,
    );

    if (rgba) {
        const fade = Number.parseFloat(rgba[1]);

        if (Number.isFinite(fade)) {
            return normalizeBackgroundImageOpacity(1 - fade);
        }
    }

    const colorMix = value.match(
        /linear-gradient\(\s*color-mix\(\s*in\s+srgb\s*,\s*[^,]+?\s+([\d.]+)%\s*,\s*transparent/i,
    );

    if (colorMix) {
        const fadePct = Number.parseFloat(colorMix[1]);

        if (Number.isFinite(fadePct)) {
            return normalizeBackgroundImageOpacity(1 - (fadePct / 100));
        }
    }

    return null;
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
 * Build a Tailwind-compatible gradient layer that reads --tw-gradient-* from classes.
 *
 * @param {string} directionUtility e.g. bg-gradient-to-r
 * @returns {string}
 */
export function composeTailwindGradientLayer(directionUtility) {
    const direction = GRADIENT_DIRECTION_CSS[String(directionUtility ?? '').trim()];

    if (! direction) {
        return '';
    }

    return `linear-gradient(${direction}, var(--tw-gradient-stops, var(--tw-gradient-from, transparent), var(--tw-gradient-to, transparent)))`;
}

/**
 * Build background-image CSS layers: optional gradient, optional color fade, url().
 *
 * @param {string} url
 * @param {number|string} [opacity=1]
 * @param {string} [fadeColor='']
 * @param {{ gradientLayer?: string }} [options]
 * @returns {string}
 */
export function composeDecorationBackgroundImageCss(url, opacity = 1, fadeColor = '', options = {}) {
    const image = cssBackgroundImageUrlValue(url);
    const gradientLayer = String(options?.gradientLayer ?? '').trim();
    const layers = [];

    if (gradientLayer) {
        layers.push(gradientLayer);
    }

    if (image === '' && layers.length === 0) {
        return '';
    }

    const op = normalizeBackgroundImageOpacity(opacity);

    // Solid-color fade toward bg color (same as Color + Image). Skip when a
    // gradient already paints the overlay — gradient sits above the photo.
    if (image !== '' && op < 0.999 && ! gradientLayer) {
        const fade = 1 - op;
        const color = String(fadeColor ?? '').trim() || 'var(--color-vp-bg, #0f172a)';
        const rgba = toRgbaWithAlpha(color, fade);

        if (rgba) {
            layers.push(`linear-gradient(${rgba}, ${rgba})`);
        } else {
            const pct = Math.round(fade * 1000) / 10;
            layers.push(
                `linear-gradient(color-mix(in srgb, ${color} ${pct}%, transparent), color-mix(in srgb, ${color} ${pct}%, transparent))`,
            );
        }
    }

    if (image !== '') {
        layers.push(image);
    }

    return layers.join(', ');
}
