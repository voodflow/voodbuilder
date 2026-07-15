/**
 * Block settings descriptor registry.
 */

import {
    findBlockRoot,
    readBlockId,
    resolveInspectableBlockRoot,
} from './selection.js';

/** @type {Map<string, object>} */
const registry = new Map();

/**
 * @param {object} descriptor
 */
export function registerBlockSettings(descriptor) {
    if (! descriptor?.id) {
        throw new Error('Block settings descriptor requires an id.');
    }

    registry.set(descriptor.id, normalizeDescriptor(descriptor));
}

/**
 * @param {object} descriptor
 */
function normalizeDescriptor(descriptor) {
    const blockIds = Array.isArray(descriptor.blockIds) ? descriptor.blockIds : [];
    const matchBlockId = typeof descriptor.matchBlockId === 'function' ? descriptor.matchBlockId : null;

    const matchesRoot = descriptor.matchesRoot ?? ((root) => {
        const blockId = readBlockId(root);

        if (blockId === '') {
            return false;
        }

        if (blockIds.includes(blockId)) {
            return true;
        }

        return matchBlockId ? matchBlockId(blockId) : false;
    });

    return {
        ...descriptor,
        blockIds,
        matchesRoot,
    };
}

/**
 * @param {object|null|undefined} root
 * @returns {object|null}
 */
export function resolveDescriptorForRoot(root) {
    if (! root) {
        return null;
    }

    for (const descriptor of registry.values()) {
        if (descriptor.matchesRoot(root)) {
            return descriptor;
        }
    }

    return null;
}

/**
 * @param {object|null|undefined} component
 * @param {object} editor
 * @returns {{ descriptor: object|null, root: object|null }}
 */
export function resolveBlockSettingsTarget(component, editor) {
    const roots = [];
    const seen = new Set();

    const pushRoot = (candidate) => {
        if (! candidate || seen.has(candidate)) {
            return;
        }

        seen.add(candidate);
        roots.push(candidate);
    };

    pushRoot(resolveInspectableBlockRoot(component, editor));
    pushRoot(findBlockRoot(component));
    pushRoot(component);

    for (const descriptor of registry.values()) {
        if (typeof descriptor.findRoot !== 'function') {
            continue;
        }

        for (const candidate of [component, ...roots]) {
            pushRoot(descriptor.findRoot(candidate, editor));
        }
    }

    for (const root of roots) {
        const descriptor = resolveDescriptorForRoot(root);

        if (descriptor) {
            return { descriptor, root };
        }
    }

    return {
        descriptor: null,
        root: resolveInspectableBlockRoot(component, editor),
    };
}

export function listRegisteredBlockSettings() {
    return [...registry.keys()];
}
