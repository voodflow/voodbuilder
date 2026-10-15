/**
 * Dynamic-block refresh scope.
 *
 * A chrome-shell canvas embeds server-rendered nav/footer parts (the chrome
 * layout) next to the author's page content. Those chrome parts are already
 * final HTML, so re-fetching them only remounts Maps embeds and re-renders the
 * Layers tree for no gain.
 *
 * Page-content blocks are a different story: declarative `data-vb-field`
 * markers exist *only* in the rendered editor preview, never in the saved
 * page HTML. Skipping the refresh therefore left the Content inspector with no
 * fields at all ("No settings for this element") for every configurable block.
 */

import { isHeaderBlock, isFooterBlock } from '../chrome/ids.js';

export const BLOCK_ATTR = 'data-voodbuilder-block';

/**
 * Chrome-shell parts (nav / header / footer) shipped by the chrome layout.
 *
 * @param {string} blockId
 * @returns {boolean}
 */
export function isChromeShellBlockId(blockId) {
    return isHeaderBlock(blockId) || isFooterBlock(blockId);
}

/**
 * Page-content dynamic blocks worth refreshing on a chrome-shell canvas.
 *
 * @param {object[]} components
 * @returns {object[]}
 */
export function selectContentRefreshBlocks(components) {
    return (Array.isArray(components) ? components : []).filter((component) => {
        const blockId = component?.getAttributes?.()?.[BLOCK_ATTR] ?? '';

        return typeof blockId === 'string'
            && blockId !== ''
            && ! isChromeShellBlockId(blockId);
    });
}
