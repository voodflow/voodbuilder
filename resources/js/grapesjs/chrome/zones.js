/**
 * Chrome drop zone helpers.
 */

import { ATTR } from '../../core/attrs.js';
import { isChromeDropZoneComponent } from '../../chrome-content-slot-utils.js';
import { findDropZoneAtPointer, findZone as findLayoutZone } from './layout/drag.js';

export { findDropZoneAtPointer };

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isDropZone(component) {
    return isChromeDropZoneComponent(component);
}

/**
 * @param {object} editor
 * @param {string} name
 * @returns {object|null}
 */
export function findZone(editor, name) {
    return findLayoutZone(editor, name);
}

/**
 * @param {object} editor
 * @returns {object|null}
 */
export function findZoneAtPointer(editor) {
    return findDropZoneAtPointer(editor);
}

/**
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function findZoneAncestor(component) {
    let current = component?.parent?.();

    while (current) {
        if (current.getAttributes?.()?.[ATTR.dropZone]) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}
