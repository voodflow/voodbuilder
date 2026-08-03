/**
 * Shared helpers for chrome layout content slot (layout editor middle marker).
 */

import { isValidGrapesComponent } from './core/component-model.js';
import { safeFindComponents } from './tailwind-visual-style.js';
import {
    ATTR,
    BLOCK_ID_ATTR,
    CHROME_DROP_ZONE_ATTR,
    CONTENT_SLOT_ATTR,
    PAGE_CONTENT_ATTR,
    CHROME_SHELL_PART_ATTR,
    CHROME_SHELL_LOCKED_ATTR,
} from './core/attrs.js';
import {
    findPrimaryBlockInChromeDropZone as findPrimaryInZone,
} from './core/block-tree.js';

export {
    ATTR,
    BLOCK_ID_ATTR,
    CHROME_DROP_ZONE_ATTR,
    CONTENT_SLOT_ATTR,
    PAGE_CONTENT_ATTR,
    CHROME_SHELL_PART_ATTR,
    CHROME_SHELL_LOCKED_ATTR,
} from './core/attrs.js';

export function isChromeLayoutModeEditor(editor) {
    return Boolean(editor?.__voodbuilderChromeLayoutMode);
}

export function isChromeContentSlotComponent(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CONTENT_SLOT_ATTR] || attrs['data-voodbuilder-page-content']);
}

export function isChromeLayoutContentSlotComponent(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CONTENT_SLOT_ATTR]) && ! attrs['data-voodbuilder-page-content'];
}

export function findPrimaryBlockInChromeDropZone(zone) {
    return findPrimaryInZone(zone, (container) => {
        const blocks = safeFindComponents(container, `[${BLOCK_ID_ATTR}]`);

        return blocks[0] ?? null;
    });
}

export function isChromeShellModeEditor(editor) {
    return Boolean(editor?.__voodbuilderChromeShellMode);
}

export function isPageContentSlotComponent(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs['data-voodbuilder-page-content']);
}

export function isChromeShellPartComponent(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CHROME_SHELL_PART_ATTR] || attrs[CHROME_SHELL_LOCKED_ATTR]);
}

export function isInsideChromeShellPartComponent(component) {
    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        const attrs = current.getAttributes?.() ?? {};

        if (attrs[CHROME_SHELL_PART_ATTR]) {
            return true;
        }

        if (isPageContentSlotComponent(current)) {
            return false;
        }

        current = current.parent?.();
    }

    return false;
}

export function looksLikeSiteChromeStructure(component) {
    if (! component?.get) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};
    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const blockId = String(attrs['data-voodbuilder-block'] ?? '');
    const cls = String(attrs.class ?? '');

    if (
        tag === 'footer'
        || cls.includes('voodbuilder-editor-footer')
        || attrs['data-voodbuilder-footer-col']
        || attrs['data-voodbuilder-editor-site-header']
        || blockId.startsWith('site_nav_')
        || blockId.startsWith('site_footer_')
        || blockId === 'site_header'
    ) {
        return true;
    }

    // Only treat <header> as chrome when it is the live site banner — bare
    // catalog/marketing <header> roots must remain droppable page content.
    if (tag === 'header' && (
        attrs['role'] === 'banner'
        || attrs['data-voodbuilder-editor-site-header'] != null
        || blockId.startsWith('site_nav_')
        || blockId === 'site_header'
    )) {
        return true;
    }

    const el = component.getEl?.();

    if (el?.matches?.('[data-voodbuilder-editor-site-header], header[role="banner"][data-voodbuilder-chrome], footer.voodbuilder-editor-footer')) {
        return true;
    }

    return false;
}

export function isChromeDropZoneComponent(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CHROME_DROP_ZONE_ATTR]);
}

export function isInsideChromeDropZoneComponent(component) {
    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (isChromeDropZoneComponent(current)) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

export function isChromeShellEditorProtectedComponent(component, editor) {
    if (! component || ! isChromeShellModeEditor(editor)) {
        return false;
    }

    if (isChromeShellPartComponent(component) || isPageContentSlotComponent(component)) {
        return true;
    }

    if (isInsideChromeShellPartComponent(component)) {
        return true;
    }

    return false;
}

export function shouldBlockChromeLayerContextMenu(component, editor) {
    if (! component || ! editor) {
        return false;
    }

    if (isChromeShellModeEditor(editor)) {
        return isChromeShellEditorProtectedComponent(component, editor);
    }

    if (! isChromeLayoutModeEditor(editor)) {
        return false;
    }

    if (isChromeDropZoneComponent(component) || isChromeLayoutContentSlot(component)) {
        return true;
    }

    return false;
}

function isChromeLayoutContentSlot(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CONTENT_SLOT_ATTR]) && ! attrs[PAGE_CONTENT_ATTR];
}

function isInsidePageContentSlotComponent(component) {
    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        if (isPageContentSlotComponent(current)) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

export function shouldSuppressChromeShellToolbar(component, editor) {
    return isChromeShellEditorProtectedComponent(component, editor);
}

export function shouldSuppressChromeSlotInspector(component, editor) {
    if (shouldSuppressChromeShellToolbar(component, editor)) {
        return true;
    }

    if (! isChromeContentSlotComponent(component)) {
        return false;
    }

    if (isChromeLayoutModeEditor(editor)) {
        return true;
    }

    return isChromeShellModeEditor(editor) && isPageContentSlotComponent(component);
}

export function findChromeContentSlotComponents(wrapper, selector = `[${PAGE_CONTENT_ATTR}], [${CONTENT_SLOT_ATTR}]`) {
    return safeFindComponents(wrapper, selector);
}

export function findPageContentSlotInEditor(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    const slots = findChromeContentSlotComponents(wrapper);

    return slots.find((slot) => isPageContentSlotComponent(slot)) ?? slots[0] ?? null;
}

export function registerChromeContentSlotType(editor) {
    if (editor.__voodbuilderChromeContentSlotTypeRegistered) {
        return;
    }

    editor.__voodbuilderChromeContentSlotTypeRegistered = true;

    editor.DomComponents.addType('voodbuilder-chrome-content-slot', {
        isComponent: (element) => {
            if (
                element?.hasAttribute?.(CONTENT_SLOT_ATTR)
                || element?.hasAttribute?.(PAGE_CONTENT_ATTR)
            ) {
                return { type: 'voodbuilder-chrome-content-slot' };
            }

            return false;
        },
        model: {
            defaults: {
                tagName: 'div',
                name: 'Page content',
                draggable: false,
                droppable: false,
                removable: false,
                copyable: false,
                selectable: false,
                hoverable: false,
                highlightable: false,
                editable: false,
                stylable: false,
                layerable: true,
                badgable: false,
                toolbar: [],
            },
            init() {
                if (this.getAttributes()[PAGE_CONTENT_ATTR]) {
                    this.set({
                        droppable: true,
                        locked: false,
                        hoverable: true,
                        highlightable: true,
                    });
                }
            },
        },
    });

    editor.DomComponents.addType('voodbuilder-chrome-drop-zone', {
        isComponent: (element) => {
            if (element?.hasAttribute?.(CHROME_DROP_ZONE_ATTR)) {
                return { type: 'voodbuilder-chrome-drop-zone' };
            }

            return false;
        },
        model: {
            defaults: {
                tagName: 'div',
                name: 'Drop zone',
                draggable: false,
                droppable: true,
                removable: false,
                copyable: false,
                selectable: false,
                hoverable: true,
                highlightable: true,
                editable: false,
                stylable: false,
                layerable: true,
                badgable: false,
                toolbar: [],
            },
        },
    });
}

export function registerChromeDropZoneType(editor) {
    registerChromeContentSlotType(editor);
}

export function purgeChromeBleedFromContentSlot(slot) {
    if (! slot?.components) {
        return;
    }

    const removable = [];

    slot.components().forEach((component) => {
        collectChromeBleedComponents(component, removable);
    });

    removable.forEach((component) => component.remove());
}

function isChromeBleedComponent(component) {
    const attrs = component?.getAttributes?.() ?? {};
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();
    const blockId = String(attrs['data-voodbuilder-block'] ?? '');
    const type = String(component.get?.('type') ?? '');
    const gjsType = String(attrs['data-gjs-type'] ?? '');

    if (looksLikeSiteChromeStructure(component)) {
        return true;
    }

    if (
        blockId.startsWith('site_nav_')
        || blockId.startsWith('site_footer_')
        || blockId === 'site_header'
        || attrs[ATTR.dropZone]
        || type === 'voodbuilder-chrome-drop-zone'
        || gjsType === 'voodbuilder-chrome-drop-zone'
        || attrs['data-mobile-nav']
        || attrs['data-mobile-nav-toggle']
        || attrs['data-theme-toggle']
        || attrs['data-voodbuilder-chrome-shell-part']
        || attrs['data-voodbuilder-chrome-shell-locked']
        || type === 'voodbuilder-chrome-button'
        || gjsType === 'voodbuilder-chrome-button'
    ) {
        return true;
    }

    // Only purge chrome chrome-toggle <button>s — never author Basic/CTA buttons.
    if (
        tag === 'button'
        && (
            attrs['data-mobile-nav-toggle'] != null
            || attrs['data-theme-toggle'] != null
            || attrs['data-voodbuilder-nav-dropdown-toggle'] != null
            || attrs['data-voodbuilder-notification-bell-preview'] != null
            || type === 'voodbuilder-chrome-button'
            || gjsType === 'voodbuilder-chrome-button'
        )
    ) {
        return true;
    }

    if (! component?.get) {
        return false;
    }

    const text = String(component.get('content') ?? component.get('text') ?? '').trim();
    const normalized = text.replace(/\s+/g, '');

    // Stock Grapes "Notifications" chrome preview label — not author "Button" CTAs.
    if (normalized === 'Notifications' || /^(?:Notifications)+$/.test(normalized)) {
        return true;
    }

    return false;
}

function collectChromeBleedComponents(component, removable) {
    if (! component) {
        return;
    }

    if (isChromeBleedComponent(component)) {
        removable.push(component);

        return;
    }

    component.components?.().forEach((child) => {
        if (! isValidGrapesComponent(child)) {
            return;
        }

        collectChromeBleedComponents(child, removable);
    });
}

export function sanitizeChromeContentSlotChildren(slot) {
    if (! slot?.components) {
        return;
    }

    const removable = [];

    slot.components().forEach((component) => {
        if (! isValidGrapesComponent(component)) {
            return;
        }

        const attrs = component.getAttributes?.() ?? {};
        const tag = String(component.get?.('tagName') ?? '').toLowerCase();
        const blockId = String(attrs['data-voodbuilder-block'] ?? '');
        const type = String(component.get?.('type') ?? '');

        if (
            blockId.startsWith('site_nav_')
            || blockId.startsWith('site_footer_')
            || blockId === 'site_header'
            || attrs['data-mobile-nav']
            || attrs['data-mobile-nav-toggle']
            || attrs['data-theme-toggle']
            || attrs['data-voodbuilder-editor-site-header']
            || type === 'voodbuilder-chrome-button'
        ) {
            removable.push(component);

            return;
        }

        if (
            tag === 'button'
            && (
                attrs['data-mobile-nav-toggle'] != null
                || attrs['data-theme-toggle'] != null
                || attrs['data-voodbuilder-nav-dropdown-toggle'] != null
                || type === 'voodbuilder-chrome-button'
            )
        ) {
            removable.push(component);

            return;
        }

        if (tag === 'p' && component?.get) {
            const text = String(component.get('content') ?? '').trim();

            if (text.includes('Plugin content loads')) {
                return;
            }
        }

        removable.push(component);
    });

    removable.forEach((component) => component.remove());
}
