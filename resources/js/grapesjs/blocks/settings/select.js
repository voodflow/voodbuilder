/**
 * Inspector selection resolution for block settings.
 * Layout chrome resolution uses the component model tree only (no DOM).
 */

import { ATTR } from '../../core/attrs.js';
import {
    readBlockId,
    findBlockRoot,
    findPrimaryBlock,
    findPrimaryBlockInContainer,
    findPrimaryBlockInChromeDropZone,
} from '../../core/block-tree.js';
import {
    isChromeLayoutContentSlotComponent,
    isChromeLayoutModeEditor,
} from '../../chrome-content-slot-utils.js';

export {
    readBlockId,
    findBlockRoot,
    findPrimaryBlock,
    findPrimaryBlockInContainer,
    findPrimaryBlockInModelTree,
} from '../../core/block-tree.js';

export { BLOCK_ID_ATTR, ATTR } from '../../core/attrs.js';

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
function isInsideChromeDropZone(component) {
    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (current.getAttributes?.()?.[ATTR.dropZone]) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

/**
 * Resolve nav/footer block root when selection is inside a layout chrome drop zone.
 * Also accepts the block root itself (with or without a drop-zone ancestor) so
 * settings keep working after load / dynamic refresh before zones are reconciled.
 *
 * @param {object|null|undefined} component
 * @param {object|null|undefined} editor
 * @returns {object|null}
 */
export function findLayoutChromeZoneBlockRoot(component, editor) {
    if (! component || ! isChromeLayoutModeEditor(editor)) {
        return null;
    }

    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        const zone = current.getAttributes?.()?.[ATTR.dropZone];

        if (zone === 'nav' || zone === 'footer') {
            return findPrimaryBlockInChromeDropZone(current);
        }

        const blockId = readBlockId(current);

        if (blockId !== '') {
            if (isInsideChromeDropZone(current)) {
                return current;
            }

            // Top-level / pre-wrap load: block root still owns settings.
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isBlockRoot(component) {
    return readBlockId(component) !== '';
}

/**
 * Restore layer/selection flags on a block root for canvas highlight only.
 * Settings rendering does not require these flags.
 *
 * @param {object} root
 */
export function ensureRootInspectable(root) {
    if (! root || ! isBlockRoot(root)) {
        return;
    }

    root.set({
        selectable: true,
        hoverable: true,
        highlightable: true,
        layerable: true,
    }, { silent: true });
}

/**
 * @param {object|null|undefined} component
 * @param {object|null|undefined} editor
 * @returns {object|null}
 */
export function findInspectableRoot(component, editor) {
    if (! component) {
        return null;
    }

    if (
        isChromeLayoutModeEditor(editor)
        && isChromeLayoutContentSlotComponent(component)
    ) {
        return null;
    }

    const ownBlockId = readBlockId(component);

    if (ownBlockId !== '') {
        return component;
    }

    const zone = component.getAttributes?.()?.[ATTR.dropZone];

    if (zone === 'nav' || zone === 'footer') {
        return findPrimaryBlockInChromeDropZone(component) ?? findBlockRoot(component);
    }

    const nestedInSelection = findPrimaryBlockInContainer(component);

    if (nestedInSelection) {
        return nestedInSelection;
    }

    const blockRoot = findBlockRoot(component);

    if (blockRoot) {
        return blockRoot;
    }

    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        const parentZone = current.getAttributes?.()?.[ATTR.dropZone];

        if (parentZone === 'nav' || parentZone === 'footer') {
            return findPrimaryBlockInChromeDropZone(current);
        }

        const nested = findPrimaryBlockInContainer(current);

        if (nested) {
            return nested;
        }

        current = current.parent?.();
    }

    return findLayoutChromeZoneBlockRoot(component, editor);
}

/** @deprecated */
export const resolveInspectableBlockRoot = findInspectableRoot;

/**
 * Block roots stay the inspector settings target even when layer filters mark them
 * non-selectable (e.g. chrome shell children hidden by layers-chrome-filter).
 *
 * @param {object|null|undefined} raw
 * @param {object|null|undefined} root
 * @param {object|null|undefined} [editor]
 * @returns {boolean}
 */
export function shouldPromoteSelectionToRoot(raw, root, editor = null) {
    void editor;

    if (! raw || ! root || raw === root || root.isRemoved?.()) {
        return false;
    }

    // Keep smart CTA buttons selectable — they own Content traits (URL / page / menu).
    const rawType = String(raw.get?.('type') ?? '');

    if (rawType === 'voodbuilder-cta-button') {
        return false;
    }

    const rawTag = String(raw.get?.('tagName') ?? '').toLowerCase();
    const rawAttrs = raw.getAttributes?.() ?? {};

    if (
        (rawTag === 'a' && rawAttrs['data-voodbuilder-cta'] === 'true')
        || (rawTag === 'button' && rawAttrs['data-voodbuilder-cta'] === 'true')
    ) {
        return false;
    }

    const rawId = readBlockId(raw);
    const rootId = readBlockId(root);

    if (rootId === '') {
        return false;
    }

    return rawId === '' || rawId !== rootId;
}
