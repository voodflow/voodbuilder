/**
 * Persistent nav/footer block registry for layout editor settings.
 * Settings resolution does not depend on selection flags or DOM queries.
 */

import { ATTR } from '../../core/attrs.js';
import { findPrimaryBlock, readBlockId } from '../../core/block-tree.js';
import { isChromeDropZoneComponent, isChromeLayoutModeEditor } from '../../chrome-content-slot-utils.js';
import { isValidGrapesComponent } from '../../core/component-model.js';

/** @typedef {'nav'|'footer'} LayoutChromeZone */

export const LAYOUT_CHROME_ZONES = /** @type {const} */ (['nav', 'footer']);

/**
 * @param {object|null|undefined} editor
 * @returns {{ nav: object|null, footer: object|null }}
 */
export function emptyLayoutChromeBlockRegistry() {
    return { nav: null, footer: null };
}

/**
 * @param {object|null|undefined} editor
 * @param {LayoutChromeZone} zoneName
 * @returns {object|null}
 */
export function findLayoutDropZone(editor, zoneName) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper?.components) {
        return null;
    }

    const children = wrapper.components()?.models ?? [...(wrapper.components() ?? [])];

    for (const component of children) {
        if (component?.getAttributes?.()?.[ATTR.dropZone] === zoneName) {
            return component;
        }
    }

    return null;
}

/**
 * @param {object|null|undefined} component
 * @returns {LayoutChromeZone|null}
 */
export function resolveLayoutChromeZone(component) {
    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        const zone = current.getAttributes?.()?.[ATTR.dropZone];

        if (zone === 'nav' || zone === 'footer') {
            return zone;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * Scan layout drop zones and cache nav/footer block roots on the editor.
 *
 * @param {object|null|undefined} editor
 * @returns {{ nav: object|null, footer: object|null }}
 */
export function rebuildLayoutChromeBlockRegistry(editor) {
    if (! isChromeLayoutModeEditor(editor)) {
        editor.__voodbuilderLayoutChromeBlocks = emptyLayoutChromeBlockRegistry();

        return editor.__voodbuilderLayoutChromeBlocks;
    }

    /** @type {{ nav: object|null, footer: object|null }} */
    const registry = emptyLayoutChromeBlockRegistry();

    for (const zone of LAYOUT_CHROME_ZONES) {
        const dropZone = findLayoutDropZone(editor, zone);

        if (! dropZone) {
            continue;
        }

        const block = findPrimaryBlock(dropZone);

        if (
            block
            && isValidGrapesComponent(block)
            && ! block.isRemoved?.()
            && readBlockId(block) !== ''
        ) {
            registry[zone] = block;
        }
    }

    editor.__voodbuilderLayoutChromeBlocks = registry;

    return registry;
}

/**
 * @param {object|null|undefined} editor
 * @param {LayoutChromeZone} zone
 * @returns {object|null}
 */
export function getLayoutChromeBlock(editor, zone) {
    if (! isChromeLayoutModeEditor(editor)) {
        return null;
    }

    const cached = editor.__voodbuilderLayoutChromeBlocks?.[zone];

    if (cached && isValidGrapesComponent(cached) && ! cached.isRemoved?.()) {
        return cached;
    }

    rebuildLayoutChromeBlockRegistry(editor);

    const refreshed = editor.__voodbuilderLayoutChromeBlocks?.[zone];

    return refreshed && isValidGrapesComponent(refreshed) && ! refreshed.isRemoved?.()
        ? refreshed
        : null;
}

/**
 * @param {object|null|undefined} editor
 * @returns {object|null}
 */
export function getActiveLayoutSettingsRoot(editor) {
    const root = editor?.__voodbuilderActiveSettingsRoot;

    if (root && isValidGrapesComponent(root) && ! root.isRemoved?.()) {
        return root;
    }

    return null;
}

/**
 * @param {object|null|undefined} editor
 * @param {object|null|undefined} root
 * @param {LayoutChromeZone|null} [zone]
 */
export function setActiveLayoutSettingsRoot(editor, root, zone = null) {
    if (! editor) {
        return;
    }

    if (! root || root.isRemoved?.()) {
        delete editor.__voodbuilderActiveSettingsRoot;
        delete editor.__voodbuilderActiveSettingsZone;

        return;
    }

    editor.__voodbuilderActiveSettingsRoot = root;
    editor.__voodbuilderActiveSettingsZone = zone ?? resolveLayoutChromeZone(root);
}

/**
 * @param {object|null|undefined} editor
 */
export function clearActiveLayoutSettingsRoot(editor) {
    if (! editor) {
        return;
    }

    delete editor.__voodbuilderActiveSettingsRoot;
    delete editor.__voodbuilderActiveSettingsZone;
}

/**
 * @param {object|null|undefined} editor
 * @returns {boolean}
 */
export function isLayoutInspectorReady(editor) {
    return Boolean(editor?.__voodbuilderLayoutInspectorReady);
}

/**
 * Mark layout inspector bootstrap complete after structure, registry, and reconcile.
 *
 * @param {object|null|undefined} editor
 */
export function finalizeLayoutInspectorBootstrap(editor) {
    if (! isChromeLayoutModeEditor(editor) || editor.__voodbuilderLayoutInspectorReady) {
        return;
    }

    rebuildLayoutChromeBlockRegistry(editor);
    editor.__voodbuilderLayoutInspectorReady = true;
    editor.trigger?.('voodbuilder:layout-inspector-ready');
}

/**
 * @param {object|null|undefined} component
 * @param {object|null|undefined} editor
 * @returns {object|null}
 */
export function resolveLayoutChromeBlockFromSelection(component, editor) {
    if (! isChromeLayoutModeEditor(editor)) {
        return null;
    }

    const zone = resolveLayoutChromeZone(component);

    if (zone) {
        return getLayoutChromeBlock(editor, zone);
    }

    const activeZone = editor.__voodbuilderActiveSettingsZone;

    if (activeZone === 'nav' || activeZone === 'footer') {
        return getLayoutChromeBlock(editor, activeZone);
    }

    return null;
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isLayoutChromeDropZoneSelection(component) {
    return isChromeDropZoneComponent(component);
}
