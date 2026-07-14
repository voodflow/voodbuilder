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

const CONTENT_SLOT_BLOCK_ID = 'chrome_content_slot';
const CHROME_SHELL_ATTR = 'data-voodbuilder-chrome-shell';
const EDITOR_SCOPE_ATTR = 'data-voodbuilder-editor-scope';
const CHROME_LAYOUT_SCOPE = 'chrome_layout';

const NAV_ZONE_PLACEHOLDER = 'Drop blocks here';
const FOOTER_ZONE_PLACEHOLDER = 'Drop blocks here';

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
        [...shell.components().models ?? shell.components()].forEach((child) => {
            child.move(wrapper, { at: wrapper.components().length });
        });

        shell.remove();
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
        locked: true,
        badgable: false,
        toolbar: [],
    }, { silent: true });

    component.addAttributes({
        [CHROME_DROP_ZONE_ATTR]: zone,
        'data-placeholder': placeholder,
        class: `voodbuilder-chrome-drop-zone voodbuilder-chrome-drop-zone--${zone}`,
    });
}

function configureLayoutContentSlot(slot) {
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
    }, { silent: true });

    const existingClass = String(slot.getAttributes().class ?? '').trim();
    const classes = new Set(existingClass.split(/\s+/).filter(Boolean));

    classes.add('voodbuilder-chrome-content-slot');

    const placeholder = slot.getAttributes()['data-placeholder']
        ?? 'Plugin content loads in this area (docs, tutorials, blog, pages…).';

    slot.addAttributes({
        [CONTENT_SLOT_ATTR]: slot.getAttributes()[CONTENT_SLOT_ATTR] ?? 'main',
        'data-placeholder': placeholder,
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

        nested.move(zone, { at: child.index() });
        child.remove();
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
    const keep = new Set([navZone, slot, footerZone].filter(Boolean));

    wrapper.components().forEach((child) => {
        if (keep.has(child) || isDropZone(child)) {
            return;
        }

        if (isContentSlot(child)) {
            return;
        }

        if (navZone && child.index() < slot.index()) {
            child.move(navZone, { at: navZone.components().length });

            return;
        }

        if (footerZone) {
            child.move(footerZone, { at: footerZone.components().length });
        }
    });
}

function ensureContentSlot(editor, wrapper) {
    let slot = findContentSlot(editor);

    if (! slot) {
        const navZone = findDropZone(editor, 'nav');
        const at = navZone ? navZone.index() + 1 : 0;

        wrapper.append(
            '<div data-voodbuilder-content-slot="main" data-gjs-type="voodbuilder-chrome-content-slot" class="voodbuilder-chrome-content-slot flex min-h-[12rem] flex-1 flex-col items-center justify-center border border-dashed border-vp-divider bg-vp-bg-alt/40 px-6 py-10 text-center text-sm text-vp-text-3"></div>',
            { at },
        );
        slot = findContentSlot(editor);
    }

    if (slot) {
        configureLayoutContentSlot(slot);
    }

    return slot;
}

function ensureChromeLayoutStructure(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    unwrapChromeShellWrapper(editor);

    const navZone = ensureDropZone(editor, wrapper, 'nav', NAV_ZONE_PLACEHOLDER, 'Header');
    const slot = ensureContentSlot(editor, wrapper);
    const footerZone = ensureDropZone(editor, wrapper, 'footer', FOOTER_ZONE_PLACEHOLDER, 'Footer');

    relocateOrphanTopLevelBlocks(wrapper, navZone, slot, footerZone);

    [navZone, slot, footerZone].filter(Boolean).forEach((component, index) => {
        if (component.index() !== index) {
            component.move(wrapper, { at: index });
        }
    });

    normalizeZoneChildren(navZone);
    normalizeZoneChildren(footerZone);

    return { navZone, slot, footerZone };
}

function configureLayoutCanvas(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    ensureChromeLayoutStructure(editor);

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

export function findDropZoneAtPointer(editor) {
    const doc = editor.Canvas?.getDocument?.();
    const frame = editor.Canvas?.getFrameEl?.();
    const point = editor.__voodbuilderLastDragPoint;

    if (! doc || ! frame || ! point) {
        return null;
    }

    const rect = frame.getBoundingClientRect();
    const x = point.x - rect.left + (frame.contentWindow?.scrollX ?? 0);
    const y = point.y - rect.top + (frame.contentWindow?.scrollY ?? 0);
    const target = doc.elementFromPoint(x, y);
    const zoneEl = target?.closest?.('[data-voodbuilder-chrome-drop-zone]');

    if (! zoneEl) {
        return null;
    }

    const zone = zoneEl.getAttribute('data-voodbuilder-chrome-drop-zone');

    return findDropZone(editor, zone);
}

function relocateLayoutBlock(editor, component) {
    if (! component || isDropZone(component) || isContentSlot(component)) {
        return false;
    }

    const zone = resolveTargetDropZone(editor, component);

    if (! zone || component.parent?.() === zone) {
        if (isDropZone(component.parent?.())) {
            normalizeZoneChildren(component.parent?.());
        }

        return false;
    }

    component.move(zone, { at: zone.components().length });
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

    if (existing && existing !== component) {
        component.remove();
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

    registerChromeLayerIconPatch(editor);

    let refreshTimer = null;
    let bootstrapping = true;

    const refresh = () => {
        configureLayoutCanvas(editor);
        applyEditorScopeBlockVisibility(editor);
        refreshChromeLayoutBlockCatalog(editor);
        editor.Layers?.render?.();
        patchChromeZoneLayerIcons(editor);
    };

    const scheduleRefresh = () => {
        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(refresh, 32);
    };

    editor.on('load', () => {
        refresh();
        bootstrapping = false;
    });
    editor.on('canvas:frame:load', scheduleRefresh);
    editor.on('voodbuilder:site-chrome-updated', scheduleRefresh);

    editor.on('component:selected', (component) => {
        if (isContentSlot(component) || isDropZone(component)) {
            component.set('toolbar', []);
        }
    });

    editor.on('component:add', (component) => {
        window.requestAnimationFrame(() => {
            dedupeContentSlots(editor, component);
            relocateLayoutBlock(editor, component);

            if (isContentSlot(component) || component.parent?.() === findContentSlot(editor)) {
                sanitizeChromeContentSlotChildren(findContentSlot(editor));
            }

            if (! bootstrapping) {
                scheduleRefresh();
            }
        });
    });

    editor.on('component:remove', () => {
        if (! bootstrapping) {
            scheduleRefresh();
        }
    });

    editor.on('block:drag:stop', (component) => {
        window.requestAnimationFrame(() => {
            if (component) {
                relocateLayoutBlock(editor, component);
            }

            scheduleRefresh();
        });
    });

    editor.on('sorter:drag:end', ({ target }) => {
        if (! target) {
            return;
        }

        window.requestAnimationFrame(() => {
            relocateLayoutBlock(editor, target);
            scheduleRefresh();
        });
    });
}
