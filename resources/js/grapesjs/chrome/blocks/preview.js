/**
 * Unified chrome preview lock — dispatches to nav/footer handlers.
 */

import { isFooterBlock, isHeaderBlock, isNavBlock } from '../ids.js';
import { lockNavPreview } from './nav/preview.js';
import { lockFooterPreview } from './footer/preview.js';

/**
 * @param {object} component
 * @param {object} [editor]
 * @param {object} [opts]
 */
export function lockChromePreview(component, editor, opts = {}) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];

    if (isFooterBlock(blockId)) {
        return lockFooterPreview(component, editor, opts);
    }

    if (isNavBlock(blockId) || isHeaderBlock(blockId)) {
        return lockNavPreview(component, editor, opts);
    }

    return false;
}

/** @deprecated */
export const lockDynamicPreviewContent = lockChromePreview;
