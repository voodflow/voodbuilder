/**
 * Apply responsive image attributes from a VoodMedia asset payload.
 *
 * Prefer `display` (optimized large) over the raw original for `src`,
 * and attach `srcset` / `sizes` when the API provides them.
 */

/**
 * @param {Record<string, unknown> | null | undefined} meta
 * @returns {{ src: string, srcset: string|null, sizes: string|null, original: string|null }}
 */
export function resolveResponsiveImageUrls(meta, fallbackSrc = '') {
    const original = String(meta?.src ?? fallbackSrc ?? '').trim();
    const display = String(meta?.display ?? '').trim();
    const srcset = String(meta?.srcset ?? '').trim();
    const sizes = String(meta?.sizes ?? '').trim();

    return {
        src: display || original,
        srcset: srcset !== '' ? srcset : null,
        sizes: sizes !== '' ? sizes : null,
        original: original !== '' ? original : null,
    };
}

/**
 * Best single URL for CSS backgrounds (no srcset).
 *
 * @param {Record<string, unknown> | null | undefined} meta
 * @param {string} [fallbackSrc]
 * @returns {string}
 */
export function resolveBackgroundImageUrl(meta, fallbackSrc = '') {
    const resolved = resolveResponsiveImageUrls(meta, fallbackSrc);

    return resolved.src || String(fallbackSrc ?? '').trim();
}

/**
 * Default sizes hint when the asset payload has none.
 *
 * @param {{ hero?: boolean, fullBleed?: boolean }} [opts]
 * @returns {string}
 */
export function defaultImageSizes(opts = {}) {
    if (opts.hero || opts.fullBleed) {
        return '100vw';
    }

    return '(max-width: 768px) 100vw, min(100vw, 1200px)';
}

/**
 * @param {import('grapesjs').Component} image
 * @param {Record<string, unknown> | null | undefined} meta
 * @param {{ url?: string, hero?: boolean, sizes?: string|null }} [options]
 */
export function applyResponsiveImageAttrs(image, meta = null, options = {}) {
    if (! image) {
        return;
    }

    const resolved = resolveResponsiveImageUrls(meta, options.url ?? '');
    const nextSrc = String(options.url ?? resolved.src ?? '').trim();
    const attrs = {
        src: nextSrc || null,
    };

    const srcset = resolved.srcset;
    const sizes = options.sizes
        ?? resolved.sizes
        ?? (nextSrc !== '' ? defaultImageSizes({ hero: options.hero === true }) : null);

    if (srcset) {
        attrs.srcset = srcset;
        attrs.sizes = sizes;
    } else {
        attrs.srcset = null;
        attrs.sizes = null;
    }

    if (typeof image.set === 'function' && nextSrc !== '') {
        image.set('src', nextSrc);
    }

    image.addAttributes?.(attrs);
}
