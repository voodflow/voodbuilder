/**
 * Shared helpers for chrome layout content slot (layout editor middle marker).
 */

import { safeFindComponents } from './tailwind-visual-style.js';

export const CONTENT_SLOT_ATTR = 'data-voodbuilder-content-slot';
export const PAGE_CONTENT_ATTR = 'data-voodbuilder-page-content';
export const CHROME_SHELL_PART_ATTR = 'data-voodbuilder-chrome-shell-part';
export const CHROME_SHELL_LOCKED_ATTR = 'data-voodbuilder-chrome-shell-locked';

export function isChromeLayoutModeEditor(editor) {
    return Boolean(editor?.__voodbuilderChromeLayoutMode);
}

export function isChromeContentSlotComponent(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CONTENT_SLOT_ATTR] || attrs['data-voodbuilder-page-content']);
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
        || tag === 'header'
        || cls.includes('voodbuilder-gjs-footer')
        || attrs['data-voodbuilder-footer-col']
        || attrs['data-voodbuilder-gjs-site-header']
        || blockId.startsWith('site_nav_')
        || blockId.startsWith('site_footer_')
        || blockId === 'site_header'
    ) {
        return true;
    }

    const el = component.getEl?.();

    if (el?.matches?.('footer, header[role="banner"], [data-voodbuilder-gjs-site-header]')) {
        return true;
    }

    return Boolean(el?.querySelector?.(
        'footer, [data-voodbuilder-footer-col], [data-voodbuilder-gjs-site-header], header[role="banner"]',
    ));
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

    if (isInsidePageContentSlotComponent(component) && looksLikeSiteChromeStructure(component)) {
        return true;
    }

    return false;
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
        },
    });
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
        || attrs['data-mobile-nav']
        || attrs['data-mobile-nav-toggle']
        || attrs['data-theme-toggle']
        || attrs['data-voodbuilder-chrome-shell-part']
        || attrs['data-voodbuilder-chrome-shell-locked']
        || type === 'voodbuilder-chrome-button'
        || gjsType === 'voodbuilder-chrome-button'
        || type === 'voodbuilder-dynamic'
    ) {
        return true;
    }

    if (tag === 'button') {
        return true;
    }

    const text = String(component.get('content') ?? component.get('text') ?? '').trim();
    const normalized = text.replace(/\s+/g, '');

    if (
        normalized === 'Button'
        || normalized === 'Notifications'
        || /^(?:Button|Notifications)+$/.test(normalized)
    ) {
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
        collectChromeBleedComponents(child, removable);
    });
}

export function sanitizeChromeContentSlotChildren(slot) {
    if (! slot?.components) {
        return;
    }

    const removable = [];

    slot.components().forEach((component) => {
        const attrs = component?.getAttributes?.() ?? {};
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
            || attrs['data-voodbuilder-gjs-site-header']
            || type === 'voodbuilder-chrome-button'
            || type === 'voodbuilder-dynamic'
        ) {
            removable.push(component);

            return;
        }

        if (tag === 'button') {
            removable.push(component);

            return;
        }

        if (tag === 'p') {
            const text = String(component.get('content') ?? '').trim();

            if (text.includes('Plugin content loads')) {
                return;
            }
        }

        removable.push(component);
    });

    removable.forEach((component) => component.remove());
}
