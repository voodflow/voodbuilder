/**
 * Simplify the Layers panel for chrome layout shells.
 * Top-level header/footer/page-content stay visible (locked in editor-chrome-shell.js).
 */

import { readBlockId } from './core/block-tree.js';
import { forEachGrapesComponent, walkComponentTree, safeRenderEditorLayers } from './tailwind-visual-style.js';
import { sanitizeEditorLayerTree } from './core/component-model.js';
import { isEditorBooting, shouldSuppressLayersSync } from './editor-lifecycle.js';
import {
    isChromeDropZoneComponent,
    isChromeLayoutModeEditor,
    isChromeShellModeEditor,
    isPageContentSlotComponent,
} from './chrome-content-slot-utils.js';

const TOP_DROP_SPACER_ATTR = 'data-voodbuilder-top-drop-spacer';
const BOTTOM_DROP_SPACER_ATTR = 'data-voodbuilder-bottom-drop-spacer';
const INNER_DROP_SLOT_ATTR = 'data-voodbuilder-inner-drop';
const PAGE_CONTENT_ATTR = 'data-voodbuilder-page-content';
const CONTENT_SLOT_ATTR = 'data-voodbuilder-content-slot';
const CHROME_SHELL_PART_ATTR = 'data-voodbuilder-chrome-shell-part';
const CHROME_DROP_ZONE_ATTR = 'data-voodbuilder-chrome-drop-zone';

function shouldHideFromLayers(component) {
    const attrs = component.getAttributes?.() ?? {};
    const type = String(component.get?.('type') ?? '');

    if (attrs['data-voodbuilder-editor-site-header']) {
        return true;
    }

    if (
        attrs[TOP_DROP_SPACER_ATTR]
        || attrs[BOTTOM_DROP_SPACER_ATTR]
        || attrs[INNER_DROP_SLOT_ATTR]
        || type === 'voodbuilder-top-drop-spacer'
        || type === 'voodbuilder-bottom-drop-spacer'
        || type === 'voodbuilder-inner-drop-slot'
    ) {
        return true;
    }

    return false;
}

function hideFromLayers(component) {
    component.set({
        layerable: false,
        draggable: false,
        selectable: false,
        hoverable: false,
        highlightable: false,
        badgable: false,
        copyable: false,
        removable: false,
    }, { silent: true });
}

function isTopLevelShellNode(component, wrapper) {
    if (! component || ! wrapper || component.parent?.() !== wrapper) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    return Boolean(
        attrs[CHROME_SHELL_PART_ATTR]
        || attrs[PAGE_CONTENT_ATTR]
        || attrs[CONTENT_SLOT_ATTR]
        || attrs[CHROME_DROP_ZONE_ATTR],
    );
}

function isInsideLayoutDropZone(component) {
    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (isChromeDropZoneComponent(current)) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

function isDirectPageContentChild(component) {
    const parent = component?.parent?.();

    return isPageContentSlotComponent(parent);
}

function isNestedPageContentDescendant(component) {
    if (! component || isDirectPageContentChild(component)) {
        return false;
    }

    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (isPageContentSlotComponent(current)) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

function isLayoutBlockRoot(component, editor) {
    if (readBlockId(component) === '') {
        return false;
    }

    // Layout editor: any block root. Page editor chrome-shell: nav/footer block roots.
    if (isChromeLayoutModeEditor(editor)) {
        return true;
    }

    if (! isChromeShellModeEditor(editor)) {
        return false;
    }

    const blockId = readBlockId(component);

    return blockId.startsWith('site_nav_')
        || blockId.startsWith('site_footer_')
        || blockId === 'site_header';
}

function isChromeStructureComponent(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(
        attrs[CHROME_DROP_ZONE_ATTR]
        || attrs[CONTENT_SLOT_ATTR]
        || attrs[PAGE_CONTENT_ATTR]
        || attrs[CHROME_SHELL_PART_ATTR]
        || readBlockId(component) !== '',
    );
}

function isPageContentShellNode(component) {
    return isPageContentSlotComponent(component)
        || Boolean(component?.getAttributes?.()?.[PAGE_CONTENT_ATTR]);
}

function applyLayersChromeFilter(component, wrapper, editor, insideChromeShell = false) {
    if (! component || component.get?.('type') === 'wrapper') {
        return;
    }

    // Editor-only sentinels (top spacer / inner drop slots) must never appear in Layers,
    // even when they live under Page content (where other children stay layerable).
    if (shouldHideFromLayers(component)) {
        hideFromLayers(component);

        forEachGrapesComponent(component, (child) => {
            applyLayersChromeFilter(child, wrapper, editor, true);
        });

        return;
    }

    if (isLayoutBlockRoot(component, editor)) {
        component.set({
            selectable: true,
            hoverable: true,
            highlightable: true,
            layerable: true,
        }, { silent: true });

        forEachGrapesComponent(component, (child) => {
            applyLayersChromeFilter(child, wrapper, editor, true);
        });

        return;
    }

    // Page content nested nodes stay in Layers so authors can edit buttons/links/text.
    // Do not force layerable:false here — chrome shell locking handles nav/footer only.

    if (isTopLevelShellNode(component, wrapper)) {
        const isDropZone = isChromeDropZoneComponent(component);
        const isPageContent = isPageContentShellNode(component);

        forEachGrapesComponent(component, (child) => {
            // Header/footer chrome: nest as chrome (hide inner DOM from Layers).
            // Page content + layout drop zones: keep nested blocks fully layerable.
            const nestAsChrome = ! isDropZone && ! isPageContent;

            applyLayersChromeFilter(child, wrapper, editor, nestAsChrome);
        });

        return;
    }

    const inPageContentTree = isDirectPageContentChild(component)
        || isNestedPageContentDescendant(component);

    const inChromeShell = insideChromeShell
        && ! isInsideLayoutDropZone(component)
        && ! inPageContentTree;

    if (inChromeShell) {
        component.set({
            layerable: false,
            draggable: false,
            selectable: false,
            hoverable: false,
            highlightable: false,
        }, { silent: true });
    } else if (inPageContentTree) {
        // Undo a previous chrome pass that blanked page-content descendants
        // (Hero looked “flat” with no expandable children).
        // Never resurrect editor drop sentinels — they must stay out of Layers.
        if (component.get('layerable') === false && ! shouldHideFromLayers(component)) {
            component.set({
                layerable: true,
                selectable: true,
                hoverable: true,
                highlightable: true,
            }, { silent: true });
        }
    }

    forEachGrapesComponent(component, (child) => {
        applyLayersChromeFilter(child, wrapper, editor, inChromeShell);
    });
}

export function registerLayersChromeFilter(editor) {
    if (editor.__voodbuilderLayersChromeFilterRegistered) {
        return;
    }

    editor.__voodbuilderLayersChromeFilterRegistered = true;

    let layersRenderFrame = null;
    let layersRenderTimer = null;
    let syncAllTimer = null;
    let syncing = false;

    const shouldSuppress = () => shouldSuppressLayersSync(editor, { syncing });

    const setLayersFilterSyncing = (active) => {
        editor.__voodbuilderLayersChromeFilterSyncing = active === true;
    };

    const scheduleLayersRender = () => {
        if (layersRenderFrame != null || shouldSuppress()) {
            return;
        }

        window.clearTimeout(layersRenderTimer);
        layersRenderTimer = window.setTimeout(() => {
            layersRenderTimer = null;

            if (layersRenderFrame != null || shouldSuppress()) {
                return;
            }

            layersRenderFrame = window.requestAnimationFrame(() => {
                layersRenderFrame = null;

                if (shouldSuppress()) {
                    return;
                }

                syncing = true;
                setLayersFilterSyncing(true);

                try {
                    sanitizeEditorLayerTree(editor);
                    safeRenderEditorLayers(editor);
                } finally {
                    window.queueMicrotask(() => {
                        syncing = false;
                        setLayersFilterSyncing(false);
                    });
                }
            });
        }, 120);
    };

    const shouldSync = () => {
        if (! isChromeShellModeEditor(editor) && ! isChromeLayoutModeEditor(editor)) {
            return false;
        }

        if (isChromeLayoutModeEditor(editor) && ! editor.__voodbuilderChromeLayoutReady) {
            return false;
        }

        return ! shouldSuppress();
    };

    const syncAll = () => {
        if (! shouldSync()) {
            return;
        }

        const wrapper = editor.getWrapper?.();

        if (! wrapper) {
            return;
        }

        syncing = true;
        setLayersFilterSyncing(true);

        try {
            walkComponentTree(wrapper, (component) => {
                applyLayersChromeFilter(component, wrapper, editor);
            });

            editor.__voodbuilderAfterLayersChromeFilterSync?.();
        } finally {
            window.queueMicrotask(() => {
                syncing = false;
                setLayersFilterSyncing(false);
                scheduleLayersRender();
            });
        }
    };

    let bootLayersSyncTimer = null;

    const scheduleBootLayersSync = () => {
        if (! isChromeShellModeEditor(editor) && ! isChromeLayoutModeEditor(editor)) {
            return;
        }

        window.clearTimeout(bootLayersSyncTimer);
        bootLayersSyncTimer = window.setTimeout(() => {
            bootLayersSyncTimer = null;
            syncAll();
        }, 180);
    };

    const syncSubtree = (component) => {
        if (! shouldSync()) {
            return;
        }

        // Nested nav/footer DOM churn must not re-walk/re-render the whole layer tree.
        if (
            (isChromeLayoutModeEditor(editor) || isChromeShellModeEditor(editor))
            && ! isChromeStructureComponent(component)
            && ! isInsideLayoutDropZone(component)
            && ! isDirectPageContentChild(component)
            && ! isPageContentSlotComponent(component)
        ) {
            return;
        }

        const wrapper = editor.getWrapper?.();

        if (! component || ! wrapper) {
            return;
        }

        syncing = true;
        setLayersFilterSyncing(true);

        try {
            applyLayersChromeFilter(component, wrapper, editor);
        } finally {
            window.queueMicrotask(() => {
                syncing = false;
                setLayersFilterSyncing(false);
                scheduleLayersRender();
            });
        }
    };

    const debouncedSyncAll = () => {
        if (shouldSuppress()) {
            return;
        }

        window.clearTimeout(syncAllTimer);
        syncAllTimer = window.setTimeout(syncAll, 120);
    };

    editor.on('load', () => {
        scheduleBootLayersSync();
        // Boot splash clears within ~2s — sync layers once the tree stops churning.
        window.setTimeout(scheduleBootLayersSync, 2200);
    });
    editor.on('voodbuilder:chrome-layout-ready', syncAll);
    editor.on('voodbuilder:site-chrome-updated', debouncedSyncAll);
    editor.on('voodbuilder:dynamic-blocks-refreshed', () => {
        // Page editor chrome-shell: dynamic remounts already skipped; avoid layer thrash.
        if (isChromeShellModeEditor(editor)) {
            return;
        }

        debouncedSyncAll();
    });
    editor.on('component:add', (component) => {
        if (isEditorBooting(editor)) {
            return;
        }

        syncSubtree(component);
    });
    editor.on('component:remove', (component) => {
        if (shouldSuppress()) {
            return;
        }

        if (
            (isChromeLayoutModeEditor(editor) || isChromeShellModeEditor(editor))
            && ! isChromeStructureComponent(component)
        ) {
            return;
        }

        debouncedSyncAll();
    });
}
