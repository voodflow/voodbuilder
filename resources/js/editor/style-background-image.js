/**
 * Model: photo always paints at 100% as the bottom layer. Color and gradient
 * sit ABOVE it as overlays with their own alpha — never fade the url() itself.
 * That avoids a solid `background-color` flash on the frontend while the image
 * is still loading (tint comes only from overlay layers in background-image).
 *
 * Attr `data-vb-style-bg-opacity` stores *photo visibility* (1 = pure photo,
 * 0.65 = 35% color overlay). Overlay alpha = 1 − visibility.
 *
 * Directional gradients keep true stop alphas (`from-transparent` → transparent,
 * `to-black` → opaque) so fades into the next section work. Photo visibility is
 * a separate uniform scrim under that gradient.
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
 * Resolve a from-/via-/to-* (or bg-/text-) utility to a CSS color.
 *
 * @param {string} utility
 * @returns {string} hex, `transparent`, or ''
 */
export function cssColorFromGradientStopUtility(utility) {
    const token = String(utility ?? '').trim();

    if (token === '' || token.endsWith('-none') || token === 'none') {
        return '';
    }

    if (
        token === 'from-transparent'
        || token === 'via-transparent'
        || token === 'to-transparent'
        || token === 'bg-transparent'
        || token.endsWith('-transparent')
    ) {
        return 'transparent';
    }

    if (
        token === 'from-black'
        || token === 'via-black'
        || token === 'to-black'
        || token === 'bg-black'
        || token === 'text-black'
    ) {
        return '#000000';
    }

    if (
        token === 'from-white'
        || token === 'via-white'
        || token === 'to-white'
        || token === 'bg-white'
        || token === 'text-white'
    ) {
        return '#ffffff';
    }

    return hexForUtility(token) ?? '';
}

/**
 * Gradient overlay above a photo: directional stops keep their own alpha
 * (`from-transparent` → transparent, `to-black` → opaque black) so fades into
 * the next section work. Photo visibility is a separate uniform scrim (see
 * {@link composeDecorationBackgroundImageCss}), not applied to every stop.
 *
 * Optional stop positions (0–100) map to Tailwind `from-40%` / `via-50%` / `to-90%`.
 *
 * @param {{
 *   directionUtility?: string,
 *   fromUtility?: string,
 *   viaUtility?: string,
 *   toUtility?: string,
 *   fromPos?: number|null,
 *   viaPos?: number|null,
 *   toPos?: number|null,
 *   photoVisibility?: number|string,
 * }} opts
 * @returns {string}
 */
/**
 * Soft transparent↔opaque ramp: linear CSS looks banded because alpha jumps
 * too fast perceptually. Extra mid stops ease the fade across the span.
 *
 * @param {{ paint: string, pct: number, color: string }} a
 * @param {{ paint: string, pct: number, color: string }} b
 * @returns {Array<{ paint: string, pct: number }>}
 */
function softTransparentOpaqueStops(a, b) {
    const clear = a.color === 'transparent' ? a : b;
    const solid = a.color === 'transparent' ? b : a;
    const solidRgba = toRgbaWithAlpha(solid.color, 1);

    if (! solidRgba || clear.color !== 'transparent') {
        return [a, b];
    }

    const start = Math.min(clear.pct, solid.pct);
    const end = Math.max(clear.pct, solid.pct);
    const span = end - start;
    const goingToSolid = clear.pct <= solid.pct;

    if (span < 5) {
        return goingToSolid
            ? [{ paint: 'transparent', pct: start }, { paint: solidRgba, pct: end }]
            : [{ paint: solidRgba, pct: start }, { paint: 'transparent', pct: end }];
    }

    const at = (t) => Math.round(start + span * t);
    // Ease-in alphas so color gathers later in the span (less “hard purple line”).
    const alphas = goingToSolid
        ? [0, 0.12, 0.32, 0.62, 1]
        : [1, 0.62, 0.32, 0.12, 0];
    const ts = [0, 0.28, 0.52, 0.76, 1];

    return ts.map((t, i) => ({
        paint: alphas[i] <= 0
            ? 'transparent'
            : (toRgbaWithAlpha(solid.color, alphas[i]) ?? solidRgba),
        pct: at(t),
    }));
}

export function composePhotoAwareGradientLayer(opts = {}) {
    const direction = GRADIENT_DIRECTION_CSS[String(opts.directionUtility ?? '').trim()];

    if (! direction) {
        return '';
    }

    const stop = (utility, pos, fallbackPos) => {
        const color = cssColorFromGradientStopUtility(utility);

        if (color === '') {
            return null;
        }

        const paint = color === 'transparent'
            ? 'transparent'
            : (toRgbaWithAlpha(color, 1) ?? color);
        const rawPos = pos != null && Number.isFinite(Number(pos))
            ? Number(pos)
            : fallbackPos;
        const pct = Math.min(100, Math.max(0, Math.round(rawPos)));

        return { paint, pct, color };
    };

    let entries = [
        stop(opts.fromUtility, opts.fromPos, 0),
        stop(opts.viaUtility, opts.viaPos, 50),
        stop(opts.toUtility, opts.toPos, 100),
    ].filter(Boolean);

    if (entries.length === 0) {
        return '';
    }

    // Sort by % so From 75% + Via 45% does not create a hard CSS band.
    entries.sort((a, b) => a.pct - b.pct);

    // Two-stop transparent↔color: insert eased mid-stops (Via empty).
    if (
        entries.length === 2
        && (
            (entries[0].color === 'transparent' && entries[1].color !== 'transparent')
            || (entries[1].color === 'transparent' && entries[0].color !== 'transparent')
        )
    ) {
        entries = softTransparentOpaqueStops(entries[0], entries[1]);
    }

    const parts = entries.map((entry) => `${entry.paint} ${entry.pct}%`);

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

    // Theme tokens: bg-vp-brand-1 → var(--color-vp-brand-1)
    const theme = token.match(/^bg-(vp-[\w-]+)$/);

    if (theme) {
        return `var(--color-${theme[1]})`;
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
        // Directional fade on top (e.g. transparent → black into the next section).
        layers.push(...composeGradientOverlayLayers(gradientLayer));
    }

    if (image !== '' && overlayAlpha > 0.001) {
        // Uniform photo-visibility scrim under the directional gradient.
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
