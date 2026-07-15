/**
 * Chrome layout editor — fixed Header / Page content / Footer drop zones.
 * Any block type can be dropped into header and footer zones.
 */

import {
    CHROME_DROP_ZONE_ATTR,
    CONTENT_SLOT_ATTR,
    findChromeContentSlotComponents,
    isChromeDropZoneComponent,
    isChromeLayoutModeEditor,
    sanitizeChromeContentSlotChildren,
} from './chrome-content-slot-utils.js';
import {
    patchChromeZoneLayerIcons,
    registerChromeLayerIconPatch,
} from './chrome-editor-guards.js';
import { refreshBlocksLibraryUi } from './editor-layout.js';
import { clearCanvasDragArtifacts } from './canvas-block-drag.js';
import { isFooterBlock, isNavBlock } from './chrome/ids.js';
import { lockChromePreview } from './chrome/blocks/preview.js';
import {
    findDropZoneAtPointer,
    findLayoutDropZoneForPointer,
    insertBlockIntoLayoutZone,
} from './chrome/layout/drag.js';
import { resolveBlockLayerLabel } from './layer-display-name.js';
import { refreshBlockSettingsUi } from './blocks/settings/index.js';
import { safeRenderEditorLayers, safeFindComponents } from './tailwind-visual-style.js';
import {
    canIndexGrapesComponent,
    canMoveGrapesComponent,
    isValidGrapesComponent,
    isValidMoveTarget,
    safeComponentIndex,
    safeComponentMove,
    safeMoveToEnd,
    safeReorderComponent,
} from './core/component-model.js';

export {
    findDropZoneAtPointer,
    findLayoutDropZoneForPointer,
    insertBlockIntoLayoutZone,
} from './chrome/layout/drag.js';

const CONTENT_SLOT_BLOCK_ID = 'chrome_content_slot';
const CHROME_SHELL_ATTR = 'data-voodbuilder-chrome-shell';
const EDITOR_SCOPE_ATTR = 'data-voodbuilder-editor-scope';
const CHROME_LAYOUT_SCOPE = 'chrome_layout';

function layoutPlaceholders(options = {}) {
    return {
        contentSlot: String(
            options.layoutContentSlotPlaceholder
            ?? 'Page content — filled automatically by each page.',
        ),
        nav: String(
            options.layoutNavZonePlaceholder
            ?? 'Drop header blocks here',
        ),
        footer: String(
            options.layoutFooterZonePlaceholder
            ?? 'Drop footer blocks here',
        ),
    };
}

function getLastDragPoint(editor) {
    return editor.__voodbuilderLastDragPoint ?? editor.__voodbuilderLastDropPoint ?? null;
}

function blockTargetsFooterZone(block) {
    const blockId = String(
        block?.get?.('attributes')?.['data-voodbuilder-block']
        ?? block?.get?.('id')
        ?? block?.id
        ?? '',
    ).toLowerCase();

    return blockId.includes('footer') || blockId.startsWith('site_footer');
}

function isChromeLayoutMode(editor) {
    return isChromeLayoutModeEditor(editor);
}

function isContentSlot(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CONTENT_SLOT_ATTR]) && ! attrs['data-voodbuilder-page-content'];
}

function isDropZone(component) {
    return isChromeDropZoneComponent(component);
}

function findContentSlot(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    const slots = findChromeContentSlotComponents(wrapper, `[${CONTENT_SLOT_ATTR}]`);

    return slots.find((slot) => isContentSlot(slot)) ?? slots[0] ?? null;
}

function findDropZone(editor, zone) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    return wrapper.components().find((component) => component.getAttributes?.()[CHROME_DROP_ZONE_ATTR] === zone) ?? null;
}

function unwrapChromeShellWrapper(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    wrapper.components().filter((component) => {
        const attrs = component.getAttributes?.() ?? {};

        return Boolean(attrs[CHROME_SHELL_ATTR]) && ! attrs['data-voodbuilder-chrome-shell-part'];
    }).forEach((shell) => {
        if (! isValidGrapesComponent(shell)) {
            return;
        }

        [...shell.components().models ?? shell.components()].forEach((child) => {
            safeMoveToEnd(child, wrapper);
        });

        try {
            shell.remove();
        } catch {
            // Shell may already be detached during concurrent layout refresh.
        }
    });
}

function buildDropZoneMarkup(zone, placeholder, name) {
    return `<div data-voodbuilder-chrome-drop-zone="${zone}" data-placeholder="${placeholder}" class="voodbuilder-chrome-drop-zone voodbuilder-chrome-drop-zone--${zone}" data-gjs-type="voodbuilder-chrome-drop-zone"></div>`;
}

function configureDropZone(component, zone, placeholder, name) {
    component.set({
        name,
        type: 'voodbuilder-chrome-drop-zone',
        removable: false,
        draggable: false,
        copyable: false,
        selectable: false,
        hoverable: true,
        highlightable: true,
        editable: false,
        stylable: false,
        layerable: true,
        droppable: true,
        locked: false,
        badgable: false,
        toolbar: [],
        traits: [],
    }, { silent: true });

    component.addAttributes({
        [CHROME_DROP_ZONE_ATTR]: zone,
        'data-placeholder': placeholder,
        class: `voodbuilder-chrome-drop-zone voodbuilder-chrome-drop-zone--${zone}`,
    });
}

function unlockDropZoneChildren(editor, zone) {
    if (! zone?.components) {
        return;
    }

    zone.components().forEach((child) => {
        if (! isValidGrapesComponent(child)) {
            return;
        }

        child.set({ locked: false }, { silent: true });
        editor.Layers?.setLocked?.(child, false);
    });
}

function configureLayoutContentSlot(slot, placeholder) {
    slot.set({
        name: 'Page content',
        type: 'voodbuilder-chrome-content-slot',
        removable: false,
        draggable: false,
        copyable: false,
        selectable: false,
        hoverable: true,
        highlightable: true,
        editable: false,
        stylable: false,
        layerable: true,
        droppable: false,
        locked: true,
        badgable: false,
        toolbar: [],
        traits: [],
    }, { silent: true });

    const existingClass = String(slot.getAttributes().class ?? '').trim();
    const classes = new Set(existingClass.split(/\s+/).filter(Boolean));

    classes.add('voodbuilder-chrome-content-slot');
    [
        'border',
        'border-dashed',
        'border-vp-divider',
        'bg-vp-bg-alt/40',
        'flex',
        'min-h-[12rem]',
        'flex-1',
        'flex-col',
        'items-center',
        'justify-center',
        'px-6',
        'py-10',
        'text-center',
        'text-sm',
        'text-vp-text-3',
    ].forEach((token) => classes.delete(token));

    const resolvedPlaceholder = String(placeholder ?? '').trim()
        || slot.getAttributes()['data-placeholder']
        || 'Page content — filled automatically by each page.';

    slot.addAttributes({
        [CONTENT_SLOT_ATTR]: slot.getAttributes()[CONTENT_SLOT_ATTR] ?? 'main',
        'data-placeholder': resolvedPlaceholder,
        class: [...classes].join(' '),
    });

    sanitizeChromeContentSlotChildren(slot);
}

function ensureDropZone(editor, wrapper, zone, placeholder, name) {
    let dropZone = findDropZone(editor, zone);

    if (! dropZone) {
        const at = zone === 'nav' ? 0 : wrapper.components().length;

        wrapper.append(buildDropZoneMarkup(zone, placeholder, name), { at });
        dropZone = findDropZone(editor, zone);
    }

    if (dropZone) {
        configureDropZone(dropZone, zone, placeholder, name);
        editor?.Layers?.setLocked?.(dropZone, false);
    }

    return dropZone;
}

function isDefaultWrapper(component) {
    if (! component?.get) {
        return false;
    }

    const name = String(component.getName?.() ?? component.get('name') ?? '');
    const type = String(component.get('type') ?? '');

    return type === 'default' || name === 'Default';
}

function flattenDefaultWrappers(zone) {
    if (! zone?.components) {
        return;
    }

    [...zone.components().models ?? zone.components()].forEach((child) => {
        if (! isDefaultWrapper(child)) {
            return;
        }

        const inner = child.components();

        if (inner.length !== 1) {
            return;
        }

        const nested = inner.at(0);

        if (! canMoveGrapesComponent(nested) || ! canIndexGrapesComponent(child) || ! isValidMoveTarget(zone)) {
            return;
        }

        const at = safeComponentIndex(child);

        if (at < 0) {
            return;
        }

        if (! safeComponentMove(nested, zone, { at })) {
            return;
        }

        try {
            child.remove();
        } catch {
            // Wrapper may already be removed during flatten.
        }
    });
}

function normalizeZoneChildren(zone) {
    if (! zone?.components) {
        return;
    }

    flattenDefaultWrappers(zone);

    const children = [...zone.components().models ?? zone.components()];

    children.forEach((child) => {
        if (isDefaultWrapper(child) && child.components().length === 0) {
            child.remove();
        }
    });
}

function relocateOrphanTopLevelBlocks(wrapper, navZone, slot, footerZone) {
    if (! isValidMoveTarget(wrapper)) {
        return;
    }

    const keep = new Set([navZone, slot, footerZone].filter((component) => isValidGrapesComponent(component)));
    const slotIndex = safeComponentIndex(slot);
    const children = [...wrapper.components().models ?? wrapper.components()];

    children.forEach((child) => {
        if (! isValidGrapesComponent(child) || keep.has(child) || isDropZone(child) || isContentSlot(child)) {
            return;
        }

        if (
            isValidMoveTarget(navZone)
            && slotIndex >= 0
            && canIndexGrapesComponent(child)
            && safeComponentIndex(child) < slotIndex
        ) {
            safeMoveToEnd(child, navZone);

            return;
        }

        if (isValidMoveTarget(footerZone)) {
            safeMoveToEnd(child, footerZone);
        }
    });
}

function ensureContentSlot(editor, wrapper, placeholder) {
    let slot = findContentSlot(editor);

    if (! slot) {
        const navZone = findDropZone(editor, 'nav');
        const navIndex = safeComponentIndex(navZone);
        const at = navIndex >= 0 ? navIndex + 1 : 0;
        const escapedPlaceholder = String(placeholder ?? '').replace(/"/g, '&quot;');

        wrapper.append(
            `<div data-voodbuilder-content-slot="main" data-placeholder="${escapedPlaceholder}" data-gjs-type="voodbuilder-chrome-content-slot" class="voodbuilder-chrome-content-slot"></div>`,
            { at },
        );
        slot = findContentSlot(editor);
    }

    if (slot) {
        configureLayoutContentSlot(slot, placeholder);
        editor?.Layers?.setLocked?.(slot, true);
    }

    return slot;
}

function ensureChromeLayoutStructure(editor, placeholders) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    unwrapChromeShellWrapper(editor);

    const navZone = ensureDropZone(editor, wrapper, 'nav', placeholders.nav, 'Header zone');
    const slot = ensureContentSlot(editor, wrapper, placeholders.contentSlot);
    const footerZone = ensureDropZone(editor, wrapper, 'footer', placeholders.footer, 'Footer zone');

    relocateOrphanTopLevelBlocks(wrapper, navZone, slot, footerZone);

    const zoneOrder = [navZone, slot, footerZone].filter((component) => canMoveGrapesComponent(component));

    zoneOrder.forEach((component, index) => {
        safeReorderComponent(component, wrapper, index);
    });

    normalizeZoneChildren(navZone);
    normalizeZoneChildren(footerZone);

    unlockDropZoneChildren(editor, navZone);
    unlockDropZoneChildren(editor, footerZone);

    return { navZone, slot, footerZone };
}

function removeTopDropSpacerFromWrapper(wrapper) {
    for (const spacer of safeFindComponents(wrapper, '[data-voodbuilder-top-drop-spacer]')) {
        spacer.remove();
    }
}

function lockLayoutChromeBlocks(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    for (const zone of ['nav', 'footer']) {
        const dropZone = findDropZone(editor, zone);

        if (! dropZone) {
            continue;
        }

        dropZone.components().forEach((child) => {
            if (! isValidGrapesComponent(child)) {
                return;
            }

            try {
                lockChromePreview(child, editor, {
                    resolveBlockLayerLabel,
                });
            } catch (lockError) {
                console.warn('Voodbuilder: could not lock chrome layout block.', lockError);
            }
        });
    }
}

function configureLayoutCanvas(editor, placeholders) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    removeTopDropSpacerFromWrapper(wrapper);
    ensureChromeLayoutStructure(editor, placeholders);
    lockLayoutChromeBlocks(editor);

    wrapper.set({
        droppable: false,
        removable: false,
        draggable: false,
        copyable: false,
        selectable: false,
        hoverable: false,
        highlightable: false,
    }, { silent: true });
}

function applyChromeLayoutBlockFilter(editor) {
    const blockManager = editor.BlockManager;

    if (! blockManager || ! isChromeLayoutMode(editor)) {
        return;
    }

    const hasSlot = Boolean(findContentSlot(editor));

    blockManager.getAll().forEach((block) => {
        const id = String(block.get('id') ?? block.id ?? '');
        const scope = String(block.get('attributes')?.[EDITOR_SCOPE_ATTR] ?? '');

        if (id === CONTENT_SLOT_BLOCK_ID) {
            block.set('visible', ! hasSlot);

            return;
        }

        if (scope === CHROME_LAYOUT_SCOPE) {
            block.set('visible', true);
        }
    });
}

function findParentDropZone(component) {
    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (isDropZone(current)) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

function isInsideDropZone(component) {
    return Boolean(findParentDropZone(component));
}

function resolveTargetDropZone(editor, component) {
    if (! component) {
        return null;
    }

    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        if (isDropZone(current)) {
            return current;
        }

        current = current.parent?.();
    }

    const parent = component.parent?.();

    if (isDropZone(parent)) {
        return parent;
    }

    return findDropZoneAtPointer(editor);
}

function isMisplacedLayoutBlock(editor, component) {
    if (! component) {
        return true;
    }

    if (isInsideDropZone(component)) {
        return false;
    }

    const parent = component.parent?.();

    if (isContentSlot(parent) || parent === editor.getWrapper?.()) {
        return true;
    }

    return false;
}

function relocateLayoutBlock(editor, component) {
    if (! isValidGrapesComponent(component) || isDropZone(component) || isContentSlot(component)) {
        return false;
    }

    if (isInsideDropZone(component)) {
        return false;
    }

    const zone = resolveTargetDropZone(editor, component);

    if (! zone || component.parent?.() === zone) {
        if (isDropZone(component.parent?.())) {
            normalizeZoneChildren(component.parent?.());
        }

        return false;
    }

    if (! canMoveGrapesComponent(component) || ! isValidMoveTarget(zone)) {
        return false;
    }

    if (! safeMoveToEnd(component, zone)) {
        return false;
    }

    normalizeZoneChildren(zone);

    return true;
}

export function applyEditorScopeBlockVisibility(editor) {
    applyChromeLayoutBlockFilter(editor);
}

export function refreshChromeLayoutBlockCatalog(editor) {
    if (! isChromeLayoutMode(editor)) {
        return;
    }

    applyChromeLayoutBlockFilter(editor);
    refreshBlocksLibraryUi(editor);
}

function dedupeContentSlots(editor, component) {
    if (! isContentSlot(component)) {
        return;
    }

    const existing = findContentSlot(editor);

    if (existing && existing !== component && isValidGrapesComponent(component)) {
        try {
            component.remove();
        } catch {
            // Duplicate slot may already be detached.
        }
    }
}

export function extractChromeLayoutHtml(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return '';
    }

    const parts = [];

    wrapper.components().forEach((component) => {
        if (isDropZone(component)) {
            component.components().forEach((child) => {
                parts.push(child.toHTML());
            });

            return;
        }

        parts.push(component.toHTML());
    });

    return parts.join('');
}

export function registerChromeLayoutEditor(editor, options = {}) {
    if (! options.chromeLayoutMode || editor.__voodbuilderChromeLayoutRegistered) {
        return;
    }

    editor.__voodbuilderChromeLayoutRegistered = true;
    editor.__voodbuilderChromeLayoutMode = true;
    editor.__voodbuilderChromeLayoutReady = false;

    const placeholders = layoutPlaceholders(options);

    registerChromeLayerIconPatch(editor);

    let refreshTimer = null;
    let bootstrapping = true;

    const refresh = () => {
        if (editor.__voodbuilderActiveBlockDrag) {
            return;
        }

        configureLayoutCanvas(editor, placeholders);
        applyEditorScopeBlockVisibility(editor);
        refreshChromeLayoutBlockCatalog(editor);

        if (editor.__voodbuilderChromeLayoutReady) {
            safeRenderEditorLayers(editor);
        }

        patchChromeZoneLayerIcons(editor);
    };

    const scheduleRefresh = () => {
        if (editor.__voodbuilderActiveBlockDrag) {
            return;
        }

        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(refresh, 64);
    };

    const finishBootstrap = () => {
        refresh();
        bootstrapping = false;
        editor.__voodbuilderChromeLayoutReady = true;
        editor.trigger('voodbuilder:chrome-layout-ready');
        editor.trigger('voodbuilder:site-chrome-updated');
    };

    editor.on('load', finishBootstrap);
    editor.on('canvas:frame:load', scheduleRefresh);
    editor.on('voodbuilder:site-chrome-updated', () => {
        patchChromeZoneLayerIcons(editor);
    });

    // Registration may happen after the first GrapesJS load/frame events.
    window.requestAnimationFrame(() => {
        if (editor.getWrapper?.()) {
            finishBootstrap();
        }
    });

    editor.on('component:selected', (component) => {
        if (! isValidGrapesComponent(component) || (! isContentSlot(component) && ! isDropZone(component))) {
            return;
        }

        component.set('toolbar', []);
    });

    editor.on('component:add', (component) => {
        window.requestAnimationFrame(() => {
            if (! isValidGrapesComponent(component)) {
                return;
            }

            if (isContentSlot(component)) {
                dedupeContentSlots(editor, component);
            }

            const wrapper = editor.getWrapper?.();
            const parent = component.parent?.();

            if (parent === wrapper || isContentSlot(parent)) {
                relocateLayoutBlock(editor, component);
            }

            if (isContentSlot(component) || parent === findContentSlot(editor)) {
                sanitizeChromeContentSlotChildren(findContentSlot(editor));
            }

            if (
                ! bootstrapping
                && ! editor.__voodbuilderActiveBlockDrag
                && (isDropZone(component) || isContentSlot(component) || parent === wrapper)
            ) {
                scheduleRefresh();
            }
        });
    });

    editor.on('component:remove', () => {
        if (! bootstrapping) {
            scheduleRefresh();
        }
    });

    editor.on('block:drag:stop', (component, block) => {
        clearCanvasDragArtifacts(editor);

        window.requestAnimationFrame(() => {
            if (block && isMisplacedLayoutBlock(editor, component)) {
                if (isValidGrapesComponent(component)) {
                    try {
                        component.remove();
                    } catch {
                        // Misplaced block may already be detached.
                    }
                }

                component = insertBlockIntoLayoutZone(editor, block);
            } else if (isValidGrapesComponent(component)) {
                relocateLayoutBlock(editor, component);
            }

            if (isValidGrapesComponent(component)) {
                const blockId = String(
                    block?.get?.('attributes')?.['data-voodbuilder-block']
                    ?? component.getAttributes?.()['data-voodbuilder-block']
                    ?? '',
                );

                try {
                    lockChromePreview(component, editor, {
                        resolveBlockLayerLabel,
                    });
                } catch (lockError) {
                    console.warn('Voodbuilder: could not lock chrome layout block.', lockError);
                }

                if (blockId.startsWith('site_nav_') || blockId.startsWith('site_footer_') || isNavBlock(blockId) || isFooterBlock(blockId)) {
                    refreshBlockSettingsUi(editor);
                }
            }

            delete editor.__voodbuilderLastDropPoint;
            scheduleRefresh();
        });
    });

    editor.on('sorter:drag:end', ({ target }) => {
        if (! isValidGrapesComponent(target)) {
            return;
        }

        window.requestAnimationFrame(() => {
            relocateLayoutBlock(editor, target);
            scheduleRefresh();
        });
    });
}
