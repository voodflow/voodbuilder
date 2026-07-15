/**
 * Pure component-tree helpers — no GrapesJS imports, no side effects.
 */

import { ATTR } from './attrs.js';

/**
 * @param {object|null|undefined} component
 * @returns {string}
 */
export function readBlockId(component) {
    return String(component?.getAttributes?.()?.[ATTR.block] ?? '').trim();
}

/**
 * Climb to nearest block root.
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
 * Walk model tree (no DOM).
 *
 * @param {object|null|undefined} container
 * @returns {object|null}
 */
export function findPrimaryBlock(container) {
    if (! container?.components) {
        return null;
    }

    const children = container.components?.()?.models ?? [...(container.components?.() ?? [])];

    for (const child of children) {
        if (readBlockId(child) !== '') {
            return child;
        }

        const nested = findPrimaryBlock(child);

        if (nested) {
            return nested;
        }
    }

    return null;
}

/** @deprecated Use findPrimaryBlock */
export const findPrimaryBlockInModelTree = findPrimaryBlock;

/**
 * Model tree first, optional DOM fallback via safeFindComponents.
 *
 * @param {object|null|undefined} container
 * @param {function} [domFallback]
 * @returns {object|null}
 */
export function findPrimaryBlockInContainer(container, domFallback) {
    if (! container) {
        return null;
    }

    const modelMatch = findPrimaryBlock(container);

    if (modelMatch) {
        return modelMatch;
    }

    if (typeof domFallback === 'function') {
        return domFallback(container);
    }

    return null;
}

/**
 * Primary block inside a chrome drop zone.
 *
 * @param {object|null|undefined} zone
 * @param {function} [domFallback]
 * @returns {object|null}
 */
export function findPrimaryBlockInChromeDropZone(zone, domFallback) {
    if (! zone) {
        return null;
    }

    return findPrimaryBlockInContainer(zone, domFallback);
}
