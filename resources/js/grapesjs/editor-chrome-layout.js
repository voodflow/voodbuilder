/**
 * Chrome layout editor — fixed NAV / CONTENT / FOOTER zones.
 */

import {
    CONTENT_SLOT_ATTR,
    findChromeContentSlotComponents,
    isChromeLayoutModeEditor,
    sanitizeChromeContentSlotChildren,
} from './chrome-content-slot-utils.js';
import {
    isChromeLayoutContentSlot,
    isChromeLayoutFooterBlock,
    isChromeLayoutNavBlock,
    patchChromeZoneLayerIcons,
    registerChromeLayerIconPatch,
} from './chrome-editor-guards.js';
import { refreshBlocksLibraryUi } from './editor-layout.js';
import { isSiteFooterBlock, isSiteNavBlock } from './plugins/voodbuilder-grapesjs.js';

const CONTENT_SLOT_BLOCK_ID = 'chrome_content_slot';
const CHROME_SHELL_ATTR = 'data-voodbuilder-chrome-shell';
const EDITOR_SCOPE_ATTR = 'data-voodbuilder-editor-scope';
const CHROME_LAYOUT_SCOPE = 'chrome_layout';
const CHROME_DROP_ZONE_ATTR = 'data-voodbuilder-chrome-drop-zone';

const CHROME_LAYOUT_BLOCK_PREFIXES = ['site_header', 'site_nav_', 'site_footer_'];
const NAV_ZONE_PLACEHOLDER = 'Drop a navbar block here';
const FOOTER_ZONE_PLACEHOLDER = 'Drop a footer block here';

function isChromeLayoutMode(editor) {
    return isChromeLayoutModeEditor(editor);
}

function isContentSlot(component) {
    return isChromeLayoutContentSlot(component);
}

function isChromeShellWrapper(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CHROME_SHELL_ATTR]) && ! attrs['data-voodbuilder-chrome-shell-part'];
}

function isDropZone(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CHROME_DROP_ZONE_ATTR]);
}

function findContentSlot(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    const slots = findChromeContentSlotComponents(wrapper, `[${CONTENT_SLOT_ATTR}]`);

    return slots[0] ?? null;
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

    const shellWrappers = wrapper.components().filter((component) => isChromeShellWrapper(component));

    for (const shell of shellWrappers) {
        const children = [...shell.components().models ?? shell.components()];

        for (const child of children) {
            child.move(wrapper, { at: wrapper.components().length });
        }

        shell.remove();
    }
}

function buildDropZoneMarkup(zone, placeholder) {
    return `<div data-voodbuilder-chrome-drop-zone="${zone}" data-placeholder="${placeholder}" class="voodbuilder-chrome-drop-zone voodbuilder-chrome-drop-zone--${zone}"></div>`;
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

function collectTopLevelNavBlocks(wrapper) {
    return wrapper.components().filter((component) => isChromeLayoutNavBlock(component));
}

function collectTopLevelFooterBlocks(wrapper) {
    return wrapper.components().filter((component) => isChromeLayoutFooterBlock(component));
}

function ensureDropZone(editor, wrapper, zone, placeholder, name) {
    let dropZone = findDropZone(editor, zone);

    if (! dropZone) {
        const at = zone === 'nav' ? 0 : wrapper.components().length;

        wrapper.append(buildDropZoneMarkup(zone, placeholder), { at });
        dropZone = findDropZone(editor, zone);
    }

    if (dropZone) {
        configureDropZone(dropZone, zone, placeholder, name);
    }

    return dropZone;
}

function moveNavBlocksIntoZone(wrapper, navZone) {
    if (! navZone) {
        return;
    }

    collectTopLevelNavBlocks(wrapper).forEach((component) => {
        if (component.parent?.() === navZone) {
            return;
        }

        component.move(navZone, { at: navZone.components().length });
    });

    navZone.components().slice(1).forEach((duplicate) => duplicate.remove());
}

function moveFooterBlocksIntoZone(wrapper, footerZone) {
    if (! footerZone) {
        return;
    }

    collectTopLevelFooterBlocks(wrapper).forEach((component) => {
        if (component.parent?.() === footerZone) {
            return;
        }

        component.move(footerZone, { at: footerZone.components().length });
    });

    footerZone.components().slice(1).forEach((duplicate) => duplicate.remove());
}

function ensureContentSlot(editor, wrapper) {
    let slot = findContentSlot(editor);

    if (! slot) {
        const navZone = findDropZone(editor, 'nav');
        const at = navZone ? navZone.index() + 1 : 0;

        wrapper.append(
            '<div data-voodbuilder-content-slot="main" class="voodbuilder-chrome-content-slot flex min-h-[12rem] flex-1 flex-col items-center justify-center border border-dashed border-vp-divider bg-vp-bg-alt/40 px-6 py-10 text-center text-sm text-vp-text-3"></div>',
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

    moveNavBlocksIntoZone(wrapper, navZone);
    moveFooterBlocksIntoZone(wrapper, footerZone);

    [navZone, slot, footerZone].filter(Boolean).forEach((component, index) => {
        if (component.index() !== index) {
            component.move(wrapper, { at: index });
        }
    });

    wrapper.components().forEach((child) => {
        if (child === navZone || child === slot || child === footerZone || isDropZone(child)) {
            return;
        }

        if (isChromeLayoutNavBlock(child) && navZone) {
            child.move(navZone, { at: navZone.components().length });

            return;
        }

        if (isChromeLayoutFooterBlock(child) && footerZone) {
            child.move(footerZone, { at: footerZone.components().length });
        }
    });

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

    blockManager.getAll().forEach((block) => {
        const id = String(block.get('id') ?? block.id ?? '');
        const scope = String(block.get('attributes')?.[EDITOR_SCOPE_ATTR] ?? '');

        if (scope === CHROME_LAYOUT_SCOPE) {
            block.set('visible', true);

            return;
        }

        const allowed = CHROME_LAYOUT_BLOCK_PREFIXES.some(
            (prefix) => id === prefix || id.startsWith(prefix),
        );

        block.set('visible', allowed);
    });
}

function relocateLayoutBlock(editor, component) {
    if (! component || isDropZone(component) || isContentSlot(component)) {
        return false;
    }

    const wrapper = editor.getWrapper?.();
    const blockId = String(component.getAttributes?.()['data-voodbuilder-block'] ?? '');

    if (! wrapper || ! blockId) {
        return false;
    }

    const navZone = findDropZone(editor, 'nav');
    const footerZone = findDropZone(editor, 'footer');

    if ((blockId.startsWith('site_nav_') || blockId === 'site_header' || isSiteNavBlock(blockId)) && navZone) {
        if (component.parent?.() !== navZone) {
            component.move(navZone, { at: navZone.components().length });
        }

        navZone.components().forEach((sibling) => {
            if (sibling !== component && isChromeLayoutNavBlock(sibling)) {
                sibling.remove();
            }
        });

        return true;
    }

    if (blockId.startsWith('site_footer_') && footerZone) {
        if (component.parent?.() !== footerZone) {
            component.move(footerZone, { at: footerZone.components().length });
        }

        footerZone.components().forEach((sibling) => {
            if (sibling !== component && isSiteFooterBlock(blockId)) {
                sibling.remove();
            }
        });

        return true;
    }

    return false;
}

export function applyEditorScopeBlockVisibility(editor) {
    applyChromeLayoutBlockFilter(editor);
}

export function refreshChromeLayoutBlockCatalog(editor) {
    if (! isChromeLayoutMode(editor)) {
        return;
    }

    const block = editor.BlockManager?.get(CONTENT_SLOT_BLOCK_ID);

    if (! block) {
        return;
    }

    const hasSlot = Boolean(findContentSlot(editor));

    block.set('visible', ! hasSlot);
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
        if (! component) {
            return;
        }

        window.requestAnimationFrame(() => {
            relocateLayoutBlock(editor, component);
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
