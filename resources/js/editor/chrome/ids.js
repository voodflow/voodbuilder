/**
 * Chrome block id queries — no plugin imports.
 */

/**
 * @param {string} blockId
 * @returns {boolean}
 */
export function isFooterBlock(blockId) {
    return blockId === 'site_footer' || (typeof blockId === 'string' && blockId.startsWith('site_footer_'));
}

/**
 * @param {string} blockId
 * @returns {string}
 */
const LIVE_NAV_IDS = new Set(['site_nav_simple', 'site_nav_fullscreen']);

export function resolveNavId(blockId) {
    if (typeof blockId !== 'string' || blockId === '') {
        return blockId;
    }

    if (LIVE_NAV_IDS.has(blockId)) {
        return blockId;
    }

    // Retired chrome nav ids collapse onto the default simple navbar.
    if (blockId === 'site_header' || blockId.startsWith('site_nav_')) {
        return 'site_nav_simple';
    }

    return blockId;
}

/**
 * @param {string} blockId
 * @returns {boolean}
 */
export function isNavBlock(blockId) {
    if (typeof blockId !== 'string' || blockId === '') {
        return false;
    }

    return LIVE_NAV_IDS.has(resolveNavId(blockId)) || blockId.startsWith('site_nav_');
}

/**
 * @param {string} blockId
 * @returns {boolean}
 */
export function isHeaderBlock(blockId) {
    return isNavBlock(blockId) || blockId === 'site_header';
}

/** @deprecated */
export const isSiteFooterBlock = isFooterBlock;

/** @deprecated */
export const isSiteNavBlock = isNavBlock;

/** @deprecated */
export const isSiteHeaderBlock = isHeaderBlock;

/** @deprecated */
export const resolveSiteNavBlockId = resolveNavId;
