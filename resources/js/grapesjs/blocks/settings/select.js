/**
 * Inspector selection resolution for block settings.
 */

import { safeFindComponents } from '../../tailwind-visual-style.js';
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
            return findPrimaryBlockInChromeDropZone(current, (container) => {
                const blocks = safeFindComponents(container, `[${ATTR.block}]`);

                return blocks[0] ?? null;
            });
        }

        const blockId = readBlockId(current);

        if (blockId !== '' && isInsideChromeDropZone(current)) {
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
 * Restore layer/selection flags on a block root so inspector settings can target it.
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

    if (zone) {
        return findPrimaryBlockInChromeDropZone(component, (container) => {
            const blocks = safeFindComponents(container, `[${ATTR.block}]`);

            return blocks[0] ?? null;
        }) ?? findBlockRoot(component);
    }

    const nestedInSelection = findPrimaryBlockInContainer(component, (container) => {
        const blocks = safeFindComponents(container, `[${ATTR.block}]`);

        return blocks[0] ?? null;
    });

    if (nestedInSelection) {
        return nestedInSelection;
    }

    const blockRoot = findBlockRoot(component);

    if (blockRoot) {
        return blockRoot;
    }

    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (current.getAttributes?.()?.[ATTR.dropZone]) {
            return findPrimaryBlockInContainer(current, (container) => {
                const blocks = safeFindComponents(container, `[${ATTR.block}]`);

                return blocks[0] ?? null;
            });
        }

        const nested = findPrimaryBlockInContainer(current, (container) => {
            const blocks = safeFindComponents(container, `[${ATTR.block}]`);

            return blocks[0] ?? null;
        });

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

    const rawId = readBlockId(raw);
    const rootId = readBlockId(root);

    if (rootId === '') {
        return false;
    }

    return rawId === '' || rawId !== rootId;
}
