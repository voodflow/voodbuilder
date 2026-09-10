/**
 * Shared media-hero block IDs (Core + Elements catalog).
 * Image heroes share the same sync / vmedia / bindings path as vb-bg-image.
 */

export const BACKGROUND_IMAGE_HERO_IDS = Object.freeze([
    'vb-bg-image',
    'vb-hero-cinematic',
]);

export const MEDIA_HERO_IDS = Object.freeze([
    ...BACKGROUND_IMAGE_HERO_IDS,
    'vb-bg-video',
]);

/**
 * @param {unknown} raw
 * @returns {string}
 */
export function normalizeMediaHeroId(raw) {
    return String(raw ?? '').trim();
}

/**
 * @param {unknown} raw
 * @returns {boolean}
 */
export function isBackgroundImageHeroId(raw) {
    const id = normalizeMediaHeroId(raw);

    if (id === '') {
        return false;
    }

    return BACKGROUND_IMAGE_HERO_IDS.some((known) => id === known || id.includes(known));
}

/**
 * @param {unknown} raw
 * @returns {boolean}
 */
export function isMediaHeroId(raw) {
    const id = normalizeMediaHeroId(raw);

    if (id === '') {
        return false;
    }

    return MEDIA_HERO_IDS.some((known) => id === known || id.includes(known));
}

/**
 * @param {object|null|undefined} component
 * @returns {string}
 */
export function resolveComponentMediaHeroId(component) {
    if (! component) {
        return '';
    }

    const attrs = component.getAttributes?.() ?? {};

    return normalizeMediaHeroId(
        attrs['data-voodbuilder-block']
        ?? attrs['data-voodbuilder-section-block']
        ?? component.get?.('type')
        ?? '',
    );
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isBackgroundImageHeroComponent(component) {
    return isBackgroundImageHeroId(resolveComponentMediaHeroId(component));
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isMediaHeroComponent(component) {
    return isMediaHeroId(resolveComponentMediaHeroId(component));
}

/**
 * CSS selector covering all registered media-hero section roots.
 *
 * @returns {string}
 */
export function mediaHeroSectionSelector() {
    return MEDIA_HERO_IDS
        .flatMap((id) => [
            `[data-voodbuilder-section-block="${id}"]`,
            `[data-voodbuilder-block="${id}"]`,
        ])
        .join(', ');
}
