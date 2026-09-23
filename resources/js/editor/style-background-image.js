/**
 * Style panel decoration background-image helpers.
 *
 * Model: photo always paints at 100% as the bottom layer. Color and gradient
 * sit ABOVE it as overlays with their own alpha — never fade the url() itself.
 * That avoids a solid `background-color` flash on the frontend while the image
 * is still loading (tint comes only from overlay layers in background-image).
 *
 * Attr `data-vb-style-bg-opacity` stores *photo visibility* (1 = pure photo,
 * 0.65 = 35% color overlay). Overlay alpha = 1 − visibility.
 */

import { hexForUtility } from './tailwind-color-palette.js';

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
 * Photo visibility (attr) → overlay alpha for color/gradient layers.
 *
 * @param {unknown} photoVisibility
 * @returns {number} 0–1
 */
export function overlayAlphaFromPhotoVisibility(photoVisibility) {
    return Math.round((1 - normalizeBackgroundImageOpacity(photoVisibility)) * 1000) / 1000;
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
 * Recover photo visibility from a composed overlay + url() background-image.
 * Overlay alpha A ⇒ visibility = 1 − A.
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
 * Solid overlay layer (color at alpha) painted above the photo.
 *
 * @param {string} color
 * @param {number} alpha 0–1
 * @returns {string}
 */
export function composeColorOverlayLayer(color, alpha) {
    const a = Math.min(1, Math.max(0, Number(alpha) || 0));

    if (a < 0.001) {
        return '';
    }

    const tint = String(color ?? '').trim() || 'var(--color-vp-bg, #0f172a)';
    const rgba = toRgbaWithAlpha(tint, a);

    if (rgba) {
        return `linear-gradient(${rgba}, ${rgba})`;
    }

    const pct = Math.round(a * 1000) / 10;

    return `linear-gradient(color-mix(in srgb, ${tint} ${pct}%, transparent), color-mix(in srgb, ${tint} ${pct}%, transparent))`;
}

/**
 * Build a Tailwind v4-compatible gradient layer that reads --tw-gradient-stops
 * from from-/via-/to-* classes (same formula as compiled `.bg-gradient-to-*`).
 *
 * Important: do NOT prefix direction (`to top`, …) — in TW4
 * `--tw-gradient-stops` already starts with `--tw-gradient-position`
 * (e.g. `to top in oklab, …`). Doubling the direction makes the gradient invalid
 * and invisible on the canvas.
 *
 * Opaque stops fully cover a photo underneath — use
 * {@link composePhotoAwareGradientLayer} when stacking over an image.
 *
 * @param {string} directionUtility e.g. bg-gradient-to-r
 * @returns {string}
 */
export function composeTailwindGradientLayer(directionUtility) {
    const token = String(directionUtility ?? '').trim();

    if (! GRADIENT_DIRECTION_CSS[token]) {
        return '';
    }

    return 'linear-gradient(var(--tw-gradient-stops))';
}

/**
 * Gradient overlay above a photo: same direction/stops as the Style panel, but
 * stop alpha = (1 − photoVisibility) so the url() remains visible.
 *
 * Opaque Tailwind `from-*` / `to-*` would otherwise fully hide the image —
 * that is CSS layering, not a Tailwind bug. Photo visibility is the control.
 *
 * @param {{
 *   directionUtility?: string,
 *   fromUtility?: string,
 *   viaUtility?: string,
 *   toUtility?: string,
 *   photoVisibility?: number|string,
 * }} opts
 * @returns {string}
 */
export function composePhotoAwareGradientLayer(opts = {}) {
    const direction = GRADIENT_DIRECTION_CSS[String(opts.directionUtility ?? '').trim()];

    if (! direction) {
        return '';
    }

    const alpha = overlayAlphaFromPhotoVisibility(opts.photoVisibility);

    if (alpha < 0.001) {
        return '';
    }

    const stop = (utility) => {
        const token = String(utility ?? '').trim();

        if (token === '' || token.endsWith('-none') || token === 'none') {
            return '';
        }

        const hex = hexForUtility(token);

        if (! hex) {
            return '';
        }

        return toRgbaWithAlpha(hex, alpha) ?? '';
    };

    const from = stop(opts.fromUtility);
    const via = stop(opts.viaUtility);
    const to = stop(opts.toUtility);
    const parts = [from, via, to].filter(Boolean);

    if (parts.length === 0) {
        // Direction without stops — soft dark scrim so photo still reads.
        return composeColorOverlayLayer('#0f172a', alpha);
    }

    if (parts.length === 1) {
        parts.push(toRgbaWithAlpha('#000000', 0) ?? 'transparent');
    }

    return `linear-gradient(${direction}, ${parts.join(', ')})`;
}

/**
 * Gradient overlay layers above the photo.
 *
 * @param {string} gradientLayer
 * @returns {string[]}
 */
export function composeGradientOverlayLayers(gradientLayer) {
    const layer = String(gradientLayer ?? '').trim();

    return layer === '' ? [] : [layer];
}

/**
 * Resolve a Tailwind `bg-*` utility to a CSS color for overlays.
 *
 * @param {string} utility e.g. bg-red-700
 * @returns {string}
 */
export function cssColorFromBackgroundUtility(utility) {
    const token = String(utility ?? '').trim();

    if (token === '' || token === 'bg-transparent' || token === 'bg-none') {
        return '';
    }

    if (token === 'bg-black') {
        return '#000000';
    }

    if (token === 'bg-white') {
        return '#ffffff';
    }

    const hex = hexForUtility(token);

    if (hex) {
        return hex;
    }

    // Theme tokens e.g. bg-[var(--color-vp-brand-1)] — keep as-is when possible.
    const arbitrary = token.match(/^bg-\[(.+)\]$/);

    if (arbitrary) {
        return arbitrary[1].replace(/^['"]|['"]$/g, '');
    }

    return '';
}

/**
 * Build background-image CSS layers: overlays first, photo url() last at 100%.
 *
 * @param {string} url
 * @param {number|string} [photoVisibility=1] How much photo shows (1 = no overlay)
 * @param {string} [fadeColor=''] Solid tint color for the overlay
 * @param {{ gradientLayer?: string }} [options]
 * @returns {string}
 */
export function composeDecorationBackgroundImageCss(url, photoVisibility = 1, fadeColor = '', options = {}) {
    const image = cssBackgroundImageUrlValue(url);
    const gradientLayer = String(options?.gradientLayer ?? '').trim();
    const overlayAlpha = overlayAlphaFromPhotoVisibility(photoVisibility);
    const layers = [];

    if (gradientLayer) {
        layers.push(...composeGradientOverlayLayers(gradientLayer));
    } else if (image !== '' && overlayAlpha > 0.001) {
        // Color tint above the photo (not a faded url, not a solid bg-color underneath).
        const overlay = composeColorOverlayLayer(
            String(fadeColor ?? '').trim() || 'var(--color-vp-bg, #0f172a)',
            overlayAlpha,
        );

        if (overlay) {
            layers.push(overlay);
        }
    }

    if (image !== '') {
        layers.push(image);
    }

    if (layers.length === 0) {
        return '';
    }

    return layers.join(', ');
}
