/**
 * Atomic selection helpers for Voodbuilder block inspector settings.
 * Works in page editor, chrome layout editor, and popup editor.
 */

import { safeFindComponents } from '../../tailwind-visual-style.js';
import {
    CHROME_DROP_ZONE_ATTR,
    isChromeLayoutContentSlotComponent,
    isChromeLayoutModeEditor,
} from '../../chrome-content-slot-utils.js';

export const BLOCK_ID_ATTR = 'data-voodbuilder-block';

/**
 * @param {object|null|undefined} component
 * @returns {string}
 */
export function readBlockId(component) {
    return String(component?.getAttributes?.()?.[BLOCK_ID_ATTR] ?? '').trim();
}

/**
 * Climb the component tree to the nearest Voodbuilder block root.
 *
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function findBlockRoot(component) {
    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        if (readBlockId(current) !== '') {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * Walk the component model tree (no DOM required).
 *
 * @param {object|null|undefined} container
 * @returns {object|null}
 */
export function findPrimaryBlockInModelTree(container) {
    if (! container?.components) {
        return null;
    }

    const children = container.components?.()?.models ?? [...(container.components?.() ?? [])];

    for (const child of children) {
        if (readBlockId(child) !== '') {
            return child;
        }

        const nested = findPrimaryBlockInModelTree(child);

        if (nested) {
            return nested;
        }
    }

    return null;
}

/**
 * @param {object|null|undefined} container
 * @returns {object|null}
 */
export function findPrimaryBlockInContainer(container) {
    if (! container) {
        return null;
    }

    const modelMatch = findPrimaryBlockInModelTree(container);

    if (modelMatch) {
        return modelMatch;
    }

    const blocks = safeFindComponents(container, `[${BLOCK_ID_ATTR}]`);

    return blocks[0] ?? null;
}

/**
 * Resolve which block owns inspector settings for the current selection.
 *
 * @param {object|null|undefined} component
 * @param {object|null|undefined} editor
 * @returns {object|null}
 */
export function resolveInspectableBlockRoot(component, editor) {
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

    const zone = component.getAttributes?.()?.[CHROME_DROP_ZONE_ATTR];

    if (zone) {
        return findPrimaryBlockInContainer(component) ?? findBlockRoot(component);
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
        if (current.getAttributes?.()?.[CHROME_DROP_ZONE_ATTR]) {
            return findPrimaryBlockInContainer(current);
        }

        const nested = findPrimaryBlockInContainer(current);

        if (nested) {
            return nested;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * Whether the editor should re-select the block root for clearer canvas feedback.
 *
 * @param {object|null|undefined} raw
 * @param {object|null|undefined} root
 * @returns {boolean}
 */
export function shouldPromoteSelectionToRoot(raw, root) {
    if (! raw || ! root || raw === root || root.isRemoved?.()) {
        return false;
    }

    if (String(raw.get?.('type') ?? '') === 'voodbuilder-cta-button') {
        return false;
    }

    if (root.get?.('selectable') === false) {
        return false;
    }

    const rawId = readBlockId(raw);
    const rootId = readBlockId(root);

    if (rootId === '') {
        return false;
    }

    return rawId === '' || rawId !== rootId;
}
