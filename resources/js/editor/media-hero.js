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
 * Media-hero detection driven by the authoring contract, not by block ids:
 * a section that opted into a hero background is a media hero, whatever it is
 * called. The id list above only seeds the bundled blocks.
 *
 * @param {object|null|undefined} component
 * @returns {Record<string, string>}
 */
function mediaHeroAttributes(component) {
    if (! component?.getAttributes) {
        return {};
    }

    return component.getAttributes() ?? {};
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function hasBackgroundImageSection(component) {
    return Object.prototype.hasOwnProperty.call(mediaHeroAttributes(component), 'data-vb-bg-src');
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function hasBackgroundVideoSection(component) {
    const attrs = mediaHeroAttributes(component);

    return Object.prototype.hasOwnProperty.call(attrs, 'data-vb-video-src')
        || Object.prototype.hasOwnProperty.call(attrs, 'data-vb-video-provider')
        || Object.prototype.hasOwnProperty.call(attrs, 'data-vb-video-id');
}

/**
 * True when the component owns a full-bleed media layer, even if empty
 * attributes were dropped by the HTML parser.
 *
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function hasHeroMediaLayer(component) {
    if (! component) {
        return false;
    }

    const el = component.getEl?.();

    if (el?.querySelector?.(':scope > .voodbuilder-hero-media, :scope > [data-voodbuilder-role="media"]')) {
        return true;
    }

    if (typeof component.find !== 'function') {
        return false;
    }

    return (component.find?.('.voodbuilder-hero-media, [data-voodbuilder-role="media"]')?.length ?? 0) > 0;
}

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
    return isBackgroundImageHeroId(resolveComponentMediaHeroId(component))
        || hasBackgroundImageSection(component)
        || (hasHeroMediaLayer(component) && ! hasBackgroundVideoSection(component));
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isMediaHeroComponent(component) {
    return isMediaHeroId(resolveComponentMediaHeroId(component))
        || hasBackgroundImageSection(component)
        || hasBackgroundVideoSection(component)
        || hasHeroMediaLayer(component);
}

/**
 * CSS selector covering all registered media-hero section roots.
 *
 * @returns {string}
 */
export function mediaHeroSectionSelector() {
    const byId = MEDIA_HERO_IDS
        .flatMap((id) => [
            `[data-voodbuilder-section-block="${id}"]`,
            `[data-voodbuilder-block="${id}"]`,
        ]);

    return [...byId, '[data-vb-bg-src]', '[data-vb-video-src]', '[data-voodbuilder-role="media"]'].join(', ');
}
