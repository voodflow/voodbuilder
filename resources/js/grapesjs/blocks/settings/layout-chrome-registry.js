/**
 * Persistent nav/footer block registry for layout editor settings.
 * Settings resolution does not depend on selection flags or DOM queries.
 *
 * Manual regression checklist (layout editor):
 * 1. Save layout with nav block → hard refresh.
 * 2. Wait for canvas boot (dynamic blocks refresh completes).
 * 3. Select nav in canvas or Layers → Content tab shows "Navbar settings".
 * 4. Change a setting → save → refresh → setting persists and panel still opens.
 * 5. Repeat for footer block settings.
 * 6. Click empty header/footer drop zone → selects block root and opens settings.
 */

import { ATTR } from '../../core/attrs.js';
import { findPrimaryBlock, readBlockId } from '../../core/block-tree.js';
import { isChromeLayoutModeEditor } from '../../chrome-content-slot-utils.js';
import { isValidGrapesComponent } from '../../core/component-model.js';

/** @typedef {'nav'|'footer'} LayoutChromeZone */

export const LAYOUT_CHROME_ZONES = /** @type {const} */ (['nav', 'footer']);

const LAYOUT_INSPECTOR_FORCE_RENDER_MS = 2000;

/**
 * @returns {{ nav: string|null, footer: string|null }}
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

    const queue = [...(wrapper.components()?.models ?? [...(wrapper.components() ?? [])])];

    while (queue.length > 0) {
        const component = queue.shift();

        if (! component) {
            continue;
        }

        if (component.getAttributes?.()?.[ATTR.dropZone] === zoneName) {
            return component;
        }

        const children = component.components?.()?.models ?? [...(component.components?.() ?? [])];
        queue.push(...children);
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
 * Resolve the current nav/footer block root by walking the live component tree.
 * Never returns a cached Component reference.
 *
 * @param {object|null|undefined} editor
 * @param {LayoutChromeZone} zone
 * @returns {object|null}
 */
export function resolveLayoutChromeBlock(editor, zone) {
    if (! isChromeLayoutModeEditor(editor)) {
        return null;
    }

    const dropZone = findLayoutDropZone(editor, zone);

    if (! dropZone) {
        return null;
    }

    let block = findPrimaryBlock(dropZone);

    if (
        ! block
        || ! isValidGrapesComponent(block)
        || block.isRemoved?.()
        || readBlockId(block) === ''
    ) {
        // Model tree can lag behind the canvas DOM after dynamic refresh.
        try {
            const matches = dropZone.find?.(`[${ATTR.block}]`);
            const fallback = matches?.[0] ?? (typeof matches?.[Symbol.iterator] === 'function' ? [...matches][0] : null);

            if (fallback && readBlockId(fallback) !== '') {
                block = fallback;
            }
        } catch {
            block = null;
        }
    }

    if (
        ! block
        || ! isValidGrapesComponent(block)
        || block.isRemoved?.()
        || readBlockId(block) === ''
    ) {
        return null;
    }

    return block;
}

/**
 * Scan layout drop zones and cache nav/footer block ids on the editor (not Component refs).
 *
 * @param {object|null|undefined} editor
 * @returns {{ nav: string|null, footer: string|null }}
 */
export function rebuildLayoutChromeBlockRegistry(editor) {
    if (! isChromeLayoutModeEditor(editor)) {
        editor.__voodbuilderLayoutChromeBlocks = emptyLayoutChromeBlockRegistry();

        return editor.__voodbuilderLayoutChromeBlocks;
    }

    /** @type {{ nav: string|null, footer: string|null }} */
    const registry = emptyLayoutChromeBlockRegistry();

    for (const zone of LAYOUT_CHROME_ZONES) {
        const block = resolveLayoutChromeBlock(editor, zone);
        registry[zone] = block ? readBlockId(block) : null;
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
    return resolveLayoutChromeBlock(editor, zone);
}

/**
 * @param {object|null|undefined} editor
 * @returns {object|null}
 */
export function getActiveLayoutSettingsRoot(editor) {
    const zone = editor?.__voodbuilderActiveSettingsZone;

    if (zone === 'nav' || zone === 'footer') {
        const fresh = resolveLayoutChromeBlock(editor, zone);

        if (fresh) {
            editor.__voodbuilderActiveSettingsRoot = fresh;

            return fresh;
        }
    }

    const root = editor?.__voodbuilderActiveSettingsRoot;

    if (root && isValidGrapesComponent(root) && ! root.isRemoved?.()) {
        const blockId = readBlockId(root);

        if (blockId === '') {
            return null;
        }

        const zoneFromRoot = resolveLayoutChromeZone(root);

        if (zoneFromRoot) {
            const fresh = resolveLayoutChromeBlock(editor, zoneFromRoot);

            if (fresh && readBlockId(fresh) === blockId) {
                editor.__voodbuilderActiveSettingsRoot = fresh;

                return fresh;
            }
        }

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

    const resolvedZone = zone ?? resolveLayoutChromeZone(root);
    const fresh = (resolvedZone === 'nav' || resolvedZone === 'footer')
        ? resolveLayoutChromeBlock(editor, resolvedZone)
        : root;

    editor.__voodbuilderActiveSettingsRoot = fresh ?? root;
    editor.__voodbuilderActiveSettingsZone = resolvedZone;
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
 * @param {object|null|undefined} editor
 * @returns {number}
 */
export function getLayoutInspectorForceRenderMs() {
    return LAYOUT_INSPECTOR_FORCE_RENDER_MS;
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
    editor.__voodbuilderLayoutDynamicRefreshPending = false;
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
    const zone = component?.getAttributes?.()?.[ATTR.dropZone];

    return zone === 'nav' || zone === 'footer';
}
