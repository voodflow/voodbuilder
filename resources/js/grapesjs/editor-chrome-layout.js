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
import {
    isSiteFooterBlock,
    isSiteNavBlock,
    lockDynamicPreviewContent,
} from './plugins/voodbuilder-grapesjs.js';
import { refreshBlockSettingsUi } from './block-settings-registry.js';

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

        if (! nested || typeof nested.move !== 'function' || typeof child.index !== 'function') {
            return;
        }

        const at = child.index();

        nested.move(zone, { at });
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

        if (navZone && slot && typeof slot.index === 'function' && typeof child.index === 'function' && child.index() < slot.index()) {
            child.move(navZone, { at: navZone.components().length });

            return;
        }

        if (footerZone) {
            child.move(footerZone, { at: footerZone.components().length });
        }
    });
}

function ensureContentSlot(editor, wrapper, placeholder) {
    let slot = findContentSlot(editor);

    if (! slot) {
        const navZone = findDropZone(editor, 'nav');
        const at = navZone ? navZone.index() + 1 : 0;
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

    const zoneOrder = [navZone, slot, footerZone].filter((component) => {
        return component
            && typeof component.move === 'function'
            && ! component.isRemoved?.();
    });

    zoneOrder.forEach((component, index) => {
        try {
            if (typeof component.index === 'function' && component.index() === index) {
                return;
            }

            component.move(wrapper, { at: index });
        } catch (error) {
            console.warn('Voodbuilder: could not reorder chrome layout zone.', error);
        }
    });

    normalizeZoneChildren(navZone);
    normalizeZoneChildren(footerZone);

    unlockDropZoneChildren(editor, navZone);
    unlockDropZoneChildren(editor, footerZone);

    return { navZone, slot, footerZone };
}

function removeTopDropSpacerFromWrapper(wrapper) {
    if (! wrapper?.find) {
        return;
    }

    wrapper.find('[data-voodbuilder-top-drop-spacer]').forEach((spacer) => {
        spacer.remove();
    });
}

function configureLayoutCanvas(editor, placeholders) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    removeTopDropSpacerFromWrapper(wrapper);
    ensureChromeLayoutStructure(editor, placeholders);

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

export function findDropZoneAtPointer(editor) {
    const doc = editor.Canvas?.getDocument?.();
    const frame = editor.Canvas?.getFrameEl?.();
    const point = getLastDragPoint(editor);

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

export function findLayoutDropZoneForPointer(editor) {
    const pointerZone = findDropZoneAtPointer(editor);

    if (pointerZone) {
        return pointerZone;
    }

    const doc = editor.Canvas?.getDocument?.();
    const frame = editor.Canvas?.getFrameEl?.();
    const point = getLastDragPoint(editor);
    const navZone = findDropZone(editor, 'nav');
    const slot = findContentSlot(editor);
    const footerZone = findDropZone(editor, 'footer');

    if (! doc || ! frame || ! point || ! slot) {
        return null;
    }

    const slotEl = slot.getEl?.() ?? doc.querySelector('[data-voodbuilder-content-slot]');

    if (! slotEl) {
        return null;
    }

    const rect = frame.getBoundingClientRect();
    const y = point.y - rect.top + (frame.contentWindow?.scrollY ?? 0);
    const slotTop = slotEl.offsetTop;
    const slotBottom = slotTop + slotEl.offsetHeight;

    if (y < slotTop) {
        return navZone;
    }

    if (y > slotBottom) {
        return footerZone;
    }

    return null;
}

function resolveLayoutDropZone(editor, block) {
    const pointerZone = findLayoutDropZoneForPointer(editor);

    if (pointerZone) {
        return pointerZone;
    }

    const navZone = findDropZone(editor, 'nav');
    const footerZone = findDropZone(editor, 'footer');

    if (blockTargetsFooterZone(block)) {
        return footerZone ?? navZone;
    }

    return navZone ?? footerZone;
}

export function insertBlockIntoLayoutZone(editor, block) {
    const content = block?.get?.('content') ?? block?.getContent?.();

    if (! content) {
        return null;
    }

    const zone = resolveLayoutDropZone(editor, block);

    if (! zone) {
        return null;
    }

    const added = zone.append(content);
    const component = Array.isArray(added) ? added[0] : added;

    if (component) {
        editor.select?.(component);
    }

    return component ?? null;
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
    if (! component || isDropZone(component) || isContentSlot(component)) {
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
        editor.Layers?.render?.();
        patchChromeZoneLayerIcons(editor);
    };

    const scheduleRefresh = () => {
        if (editor.__voodbuilderActiveBlockDrag) {
            return;
        }

        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(refresh, 32);
    };

    const finishBootstrap = () => {
        refresh();
        bootstrapping = false;
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
        if (isContentSlot(component) || isDropZone(component)) {
            component.set('toolbar', []);
        }
    });

    editor.on('component:add', (component) => {
        window.requestAnimationFrame(() => {
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
                if (component?.remove) {
                    component.remove();
                }

                component = insertBlockIntoLayoutZone(editor, block);
            } else if (component) {
                relocateLayoutBlock(editor, component);
            }

            if (component) {
                const blockId = String(
                    block?.get?.('attributes')?.['data-voodbuilder-block']
                    ?? component.getAttributes?.()['data-voodbuilder-block']
                    ?? '',
                );

                try {
                    lockDynamicPreviewContent(component);
                } catch (lockError) {
                    console.warn('Voodbuilder: could not lock chrome layout block.', lockError);
                }

                if (blockId.startsWith('site_nav_') || blockId.startsWith('site_footer_') || isSiteNavBlock(blockId) || isSiteFooterBlock(blockId)) {
                    editor.trigger('voodbuilder:refresh-dynamic-block', component);
                    refreshBlockSettingsUi(editor);
                }
            }

            delete editor.__voodbuilderLastDropPoint;
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
