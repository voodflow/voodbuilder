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
export function resolveNavId(blockId) {
    if (typeof blockId !== 'string' || blockId === '') {
        return blockId;
    }

    if (blockId === 'site_header' || (blockId.startsWith('site_nav_') && blockId !== 'site_nav_simple')) {
        return 'site_nav_simple';
    }

    return blockId;
}

/**
 * @param {string} blockId
 * @returns {boolean}
 */
export function isNavBlock(blockId) {
    return resolveNavId(blockId) === 'site_nav_simple';
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
