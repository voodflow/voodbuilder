/**
 * Chrome layout shell preview inside the Site Page GrapesJS editor.
 * Layout chrome is read-only; only the content slot is editable.
 */

import {
    isSiteFooterBlock,
    isSiteNavBlock,
    lockDynamicPreviewContent,
    normalizeSiteNavChromeButtons,
} from './plugins/voodbuilder-grapesjs.js';
import { removeTopDropSpacer, clearCanvasDragArtifacts } from './canvas-block-drag.js';
import { resolveBlockSettingsTarget } from './block-settings/index.js';
import {
    patchChromeZoneLayerIcons,
    registerChromeLayerIconPatch,
} from './chrome-editor-guards.js';
import {
    CHROME_SHELL_LOCKED_ATTR,
    CHROME_SHELL_PART_ATTR,
    CONTENT_SLOT_ATTR,
    findChromeContentSlotComponents,
    findPageContentSlotInEditor,
    isChromeShellEditorProtectedComponent,
    isChromeShellPartComponent,
    isInsideChromeShellPartComponent,
    isPageContentSlotComponent,
    looksLikeSiteChromeStructure,
    PAGE_CONTENT_ATTR,
    purgeChromeBleedFromContentSlot,
} from './chrome-content-slot-utils.js';

const CHROME_SHELL_ATTR = 'data-voodbuilder-chrome-shell';
const CHROME_SHELL_BLOCK_PREFIXES = ['site_nav_', 'site_footer_', 'site_header'];

function isPageContentSlot(component) {
    return isPageContentSlotComponent(component);
}

function findPageContentSlots(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return [];
    }

    return findChromeContentSlotComponents(wrapper);
}

function findPageContentSlot(editor) {
    return findPageContentSlotInEditor(editor);
}

function isInsidePageContentSlot(component) {
    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        if (isPageContentSlot(current)) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

function isInsideChromeShellPart(component) {
    return isInsideChromeShellPartComponent(component);
}

function isChromeShellPart(component) {
    return isChromeShellPartComponent(component);
}

function isChromeShellWrapper(component) {
    const attrs = component?.getAttributes?.() ?? {};

    return Boolean(attrs[CHROME_SHELL_ATTR]) && ! attrs[CHROME_SHELL_PART_ATTR];
}

function isChromeShellBlock(component) {
    if (isChromeShellPart(component)) {
        return false;
    }

    const attrs = component?.getAttributes?.() ?? {};
    const blockId = String(attrs['data-voodbuilder-block'] ?? '');

    if (! blockId && attrs['data-voodbuilder-gjs-site-header']) {
        return false;
    }

    if (attrs['data-voodbuilder-gjs-site-header'] && blockId.startsWith('site_nav_')) {
        return true;
    }

    return CHROME_SHELL_BLOCK_PREFIXES.some((prefix) => blockId === prefix || blockId.startsWith(prefix));
}

function isTopLevelShellZone(component, wrapper) {
    if (! component || ! wrapper || component.parent?.() !== wrapper) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    return Boolean(attrs[CHROME_SHELL_PART_ATTR] || attrs[PAGE_CONTENT_ATTR] || attrs[CONTENT_SLOT_ATTR]);
}

function setLayerLocked(editor, component, locked = true) {
    if (! component) {
        return;
    }

    component.set({ locked }, { silent: true });
    editor.Layers?.setLocked?.(component, locked);
}

function unlockPageContentChildren(editor, slot) {
    slot.components().forEach((child) => {
        ensurePageContentBlockEditable(editor, child);
    });
}

function ensurePageContentBlockEditable(editor, component) {
    if (! component || isPageContentSlot(component)) {
        return;
    }

    if (! isInsidePageContentSlot(component)) {
        return;
    }

    const parent = component.parent?.();

    if (! isPageContentSlot(parent)) {
        component.set({
            layerable: false,
        }, { silent: true });

        return;
    }

    component.set({
        locked: false,
        removable: true,
        copyable: true,
        draggable: true,
        selectable: true,
        hoverable: true,
        highlightable: true,
        layerable: true,
    }, { silent: true });
    editor.Layers?.setLocked?.(component, false);
    lockDynamicPreviewContent(component);
}

function lockChromeShellComponent(component) {
    component.addAttributes({
        [CHROME_SHELL_LOCKED_ATTR]: '1',
    });

    component.set({
        removable: false,
        draggable: false,
        copyable: false,
        selectable: false,
        hoverable: false,
        highlightable: false,
        editable: false,
        stylable: false,
        layerable: false,
        droppable: false,
        toolbar: [],
    }, { silent: true });

    component.components().forEach((child) => {
        if (isPageContentSlot(child)) {
            return;
        }

        lockChromeShellComponent(child);
    });
}

function configureChromeShellPartWrapper(editor, component, part) {
    const name = part === 'before' ? 'Header' : 'Footer';

    component.addAttributes({
        [CHROME_SHELL_PART_ATTR]: part,
        [CHROME_SHELL_LOCKED_ATTR]: '1',
    });

    component.set({
        name,
        removable: false,
        draggable: false,
        copyable: false,
        selectable: false,
        hoverable: false,
        highlightable: false,
        editable: false,
        stylable: false,
        layerable: true,
        droppable: false,
        locked: true,
        toolbar: [],
    }, { silent: true });

    setLayerLocked(editor, component, true);

    component.components().forEach((child) => {
        lockChromeShellComponent(child);

        const blockId = child.getAttributes?.()['data-voodbuilder-block'];

        if (isSiteNavBlock(blockId) || isSiteFooterBlock(blockId)) {
            lockDynamicPreviewContent(child);
        }
    });
}

function configurePageContentSlot(editor, slot, placeholderLabel = '') {
    slot.set({
        name: 'Page content',
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
    }, { silent: true });

    slot.addAttributes({
        [PAGE_CONTENT_ATTR]: '1',
        [CONTENT_SLOT_ATTR]: slot.getAttributes()[CONTENT_SLOT_ATTR] ?? 'main',
        'data-placeholder': placeholderLabel,
        class: [
            'voodbuilder-page-content-slot',
            'voodbuilder-chrome-content-slot',
            String(slot.getAttributes().class ?? '').trim(),
        ]
            .filter(Boolean)
            .join(' ')
            .split(/\s+/)
            .filter((token) => ! ['min-h-[12rem]', 'min-h-[4rem]', 'flex-1'].includes(token))
            .join(' '),
    });

    editor.Layers?.setLocked?.(slot, false);
    unlockPageContentChildren(editor, slot);
}

function purgeChromeBleedFromSlot(slot) {
    purgeChromeBleedFromContentSlot(slot);

    slot.components().forEach((component) => {
        if (isChromeShellBlock(component) || isChromeShellPart(component) || looksLikeSiteChromeStructure(component)) {
            component.remove();
        }
    });
}

function dedupePageContentSlots(editor) {
    const slots = findPageContentSlots(editor).filter((slot) => isPageContentSlot(slot));

    if (slots.length <= 1) {
        return slots[0] ?? null;
    }

    const [primary, ...duplicates] = slots;

    duplicates.forEach((duplicate) => duplicate.remove());

    return primary;
}

function buildChromeShellPartMarkup(part, innerHtml, subTheme = '') {
    const subThemeAttr = subTheme ? ` data-voodbuilder-sub-theme="${subTheme}"` : '';

    return `<div data-voodbuilder-chrome-shell-part="${part}" data-voodbuilder-chrome-shell-locked="1" data-voodbuilder-chrome-shell="1"${subThemeAttr}>${innerHtml}</div>`;
}

function findChromeShellPartAtWrapper(wrapper, part) {
    return wrapper.components().find((component) => component.getAttributes?.()[CHROME_SHELL_PART_ATTR] === part) ?? null;
}

function chromeShellPartNeedsSync(partComponent, innerHtml) {
    const html = String(innerHtml ?? '').trim();

    if (! html) {
        return false;
    }

    if (! partComponent) {
        return true;
    }

    if (partComponent.components().length === 0) {
        return true;
    }

    return ! partComponent.components().some((child) => {
        if (isChromeShellBlock(child) || looksLikeSiteChromeStructure(child)) {
            return true;
        }

        const blockId = child.getAttributes?.()['data-voodbuilder-block'];

        return isSiteNavBlock(blockId) || isSiteFooterBlock(blockId);
    });
}

function syncChromeShellPartInnerHtml(partComponent, innerHtml) {
    const html = String(innerHtml ?? '').trim();

    if (! partComponent || ! html) {
        return;
    }

    partComponent.components(html);
}

function ensurePageContentSlot(editor, wrapper, placeholderLabel = '') {
    let slot = dedupePageContentSlots(editor);

    if (slot) {
        return slot;
    }

    const slotHtml = `<div data-voodbuilder-content-slot="main" data-voodbuilder-page-content="1" class="voodbuilder-page-content-slot voodbuilder-chrome-content-slot"></div>`;

    wrapper.append(slotHtml);
    slot = findPageContentSlot(editor);

    if (slot) {
        configurePageContentSlot(editor, slot, placeholderLabel);
    }

    return slot;
}

function ensureChromeShellPart(editor, wrapper, part, innerHtml, subTheme = '') {
    const html = String(innerHtml ?? '').trim();
    let component = findChromeShellPartAtWrapper(wrapper, part);

    if (! html && ! component) {
        return null;
    }

    if (! component && html) {
        const at = part === 'before' ? 0 : wrapper.components().length;

        wrapper.append(buildChromeShellPartMarkup(part, html, subTheme), { at });
        component = findChromeShellPartAtWrapper(wrapper, part);
    } else if (component && html && chromeShellPartNeedsSync(component, html)) {
        syncChromeShellPartInnerHtml(component, html);
    }

    return component;
}

function ensureChromeShellOrder(wrapper, before, slot, after) {
    [before, slot, after].filter(Boolean).forEach((component, index) => {
        if (component.index() !== index) {
            component.move(wrapper, { at: index });
        }
    });
}

function wrapTopLevelChromeBlocks(editor, wrapper, before, after) {
    const navBlocks = [];
    const footerBlocks = [];

    wrapper.components().forEach((child) => {
        if (isPageContentSlot(child) || isChromeShellPart(child)) {
            return;
        }

        const blockId = String(child.getAttributes?.()['data-voodbuilder-block'] ?? '');

        if (isSiteNavBlock(blockId) || blockId === 'site_header' || blockId.startsWith('site_nav_')) {
            navBlocks.push(child);

            return;
        }

        if (isSiteFooterBlock(blockId) || looksLikeSiteChromeStructure(child)) {
            footerBlocks.push(child);
        }
    });

    if (navBlocks.length && before) {
        navBlocks.forEach((block) => {
            if (block.parent?.() !== before) {
                block.move(before, { at: before.components().length });
            }
        });
    }

    if (footerBlocks.length && after) {
        footerBlocks.forEach((block) => {
            if (block.parent?.() !== after) {
                block.move(after, { at: after.components().length });
            }
        });
    }
}

function relocateTopLevelOrphans(editor, wrapper, slot, before, after) {
    wrapTopLevelChromeBlocks(editor, wrapper, before, after);

    const orphans = [];

    wrapper.components().forEach((child) => {
        if (child === slot || child === before || child === after) {
            return;
        }

        if (isChromeShellWrapper(child) || isChromeShellPart(child)) {
            child.remove();

            return;
        }

        if (isChromeShellBlock(child) || looksLikeSiteChromeStructure(child)) {
            return;
        }

        orphans.push(child);
    });

    orphans.forEach((child) => {
        child.move(slot, { at: slot.components().length });
    });
}

function ensureChromeShellStructure(editor, options = {}) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return null;
    }

    const parts = options.chromeShellParts ?? {};
    const subTheme = String(options.subTheme ?? editor.__voodbuilderSubTheme ?? '').trim();
    const placeholderLabel = String(options.pageContentPlaceholder ?? '');

    const slot = ensurePageContentSlot(editor, wrapper, placeholderLabel);

    if (! slot) {
        return null;
    }

    const before = ensureChromeShellPart(editor, wrapper, 'before', parts.before, subTheme);
    const after = ensureChromeShellPart(editor, wrapper, 'after', parts.after, subTheme);

    relocateTopLevelOrphans(editor, wrapper, slot, before, after);
    ensureChromeShellOrder(wrapper, before, slot, after);

    return { before, slot, after };
}

function applyChromeShellLocks(editor, options = {}) {
    const structure = ensureChromeShellStructure(editor, options);
    const wrapper = editor.getWrapper?.();
    const slot = structure?.slot ?? dedupePageContentSlots(editor);

    if (! wrapper || ! slot) {
        return;
    }

    configurePageContentSlot(editor, slot, String(options.pageContentPlaceholder ?? ''));
    purgeChromeBleedFromSlot(slot);
    normalizeSiteNavChromeButtons(wrapper);

    wrapper.set({
        droppable: false,
        removable: false,
        draggable: false,
        copyable: false,
    }, { silent: true });

    wrapper.components().forEach((child) => {
        if (child === slot || isPageContentSlot(child)) {
            return;
        }

        const part = child.getAttributes?.()[CHROME_SHELL_PART_ATTR];

        if (part === 'before' || part === 'after') {
            configureChromeShellPartWrapper(editor, child, part);

            return;
        }

        if (isChromeShellWrapper(child)) {
            child.set({
                removable: false,
                draggable: false,
                copyable: false,
                selectable: false,
                hoverable: false,
                highlightable: false,
                editable: false,
                stylable: false,
                layerable: true,
                droppable: false,
            }, { silent: true });

            child.components().forEach((shellChild) => {
                const shellPart = shellChild.getAttributes?.()[CHROME_SHELL_PART_ATTR];

                if (shellPart === 'before' || shellPart === 'after') {
                    configureChromeShellPartWrapper(editor, shellChild, shellPart);

                    return;
                }

                if (isPageContentSlot(shellChild)) {
                    return;
                }

                lockChromeShellComponent(shellChild);
            });

            return;
        }

        if (isChromeShellBlock(child) || looksLikeSiteChromeStructure(child)) {
            if (before && (isSiteNavBlock(String(child.getAttributes?.()['data-voodbuilder-block'] ?? '')) || String(child.getAttributes?.()['data-voodbuilder-block'] ?? '').startsWith('site_nav_'))) {
                child.move(before, { at: before.components().length });

                return;
            }

            if (after) {
                child.move(after, { at: after.components().length });

                return;
            }

            child.remove();
        }
    });

    patchChromeZoneLayerIcons(editor);
}

function isChromeBleedComponent(component) {
    if (! component) {
        return false;
    }

    if (looksLikeSiteChromeStructure(component)) {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();
    const type = String(component.get?.('type') ?? '');
    const gjsType = String(attrs['data-gjs-type'] ?? '');

    if (
        type === 'voodbuilder-chrome-button'
        || gjsType === 'voodbuilder-chrome-button'
        || attrs['data-mobile-nav-toggle']
        || attrs['data-theme-toggle']
        || attrs['data-voodbuilder-notification-bell-preview']
        || tag === 'button'
    ) {
        return true;
    }

    const text = String(component.get('content') ?? component.get('text') ?? '').replace(/\s+/g, '');

    return /^(?:Button|Notifications)+$/.test(text);
}

function promoteComponentIntoContentSlot(editor, component) {
    if (! component || isPageContentSlot(component)) {
        return false;
    }

    if (isInsidePageContentSlot(component)) {
        return false;
    }

    const slot = findPageContentSlot(editor);

    if (! slot) {
        return false;
    }

    if (isChromeShellPart(component) || isChromeShellWrapper(component)) {
        return false;
    }

    if (isChromeShellBlock(component) && ! isInsideChromeShellPart(component)) {
        component.remove();

        return true;
    }

    if (isChromeBleedComponent(component)) {
        component.remove();

        return true;
    }

    component.move(slot, { at: slot.components().length });
    component.set({ locked: false }, { silent: true });
    editor.Layers?.setLocked?.(component, false);
    editor.select(component);

    return true;
}

function hideChromeShellBlocks(editor) {
    const blockManager = editor.BlockManager;

    if (! blockManager) {
        return;
    }

    blockManager.getAll().forEach((block) => {
        const blockId = String(block.get('id') ?? block.id ?? '');

        if (
            blockId === 'site_header'
            || blockId.startsWith('site_nav_')
            || blockId.startsWith('site_footer_')
        ) {
            block.set('visible', false);
        }
    });
}

export function extractChromeShellPageHtml(editor) {
    const slot = findPageContentSlot(editor);

    if (! slot) {
        return null;
    }

    const parts = [];

    slot.components().forEach((component) => {
        parts.push(component.toHTML());
    });

    return parts.join('');
}

export function registerChromeShellEditor(editor, options = {}) {
    if (! options.chromeShellMode || editor.__voodbuilderChromeShellRegistered) {
        return;
    }

    editor.__voodbuilderChromeShellRegistered = true;
    editor.__voodbuilderChromeShellMode = true;
    editor.__voodbuilderChromeShellParts = options.chromeShellParts ?? { before: '', after: '' };
    editor.__voodbuilderSubTheme = options.subTheme ?? editor.__voodbuilderSubTheme ?? '';

    registerChromeLayerIconPatch(editor);

    let refreshTimer = null;
    let bootstrapping = true;

    const shellOptions = {
        chromeShellParts: editor.__voodbuilderChromeShellParts,
        subTheme: editor.__voodbuilderSubTheme,
        pageContentPlaceholder: String(options.pageContentPlaceholder ?? ''),
    };

    const refresh = () => {
        removeTopDropSpacer(editor);
        applyChromeShellLocks(editor, shellOptions);
        hideChromeShellBlocks(editor);
        editor.Layers?.render?.();
        patchChromeZoneLayerIcons(editor);
    };

    const scheduleRefresh = () => {
        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(refresh, 32);
    };

    const finishBootstrap = () => {
        refresh();
        bootstrapping = false;
    };

    editor.on('load', finishBootstrap);
    editor.on('canvas:frame:load', scheduleRefresh);
    editor.on('voodbuilder:site-chrome-updated', scheduleRefresh);

    window.requestAnimationFrame(() => {
        if (editor.getWrapper?.()) {
            finishBootstrap();
        }
    });

    editor.on('component:selected', (component) => {
        if (! component || ! isChromeShellEditorProtectedComponent(component, editor)) {
            return;
        }

        const { descriptor } = resolveBlockSettingsTarget(component, editor);

        if (descriptor) {
            return;
        }

        component.set('toolbar', []);

        window.requestAnimationFrame(() => {
            if (editor.getSelected?.() === component) {
                editor.select(undefined);
            }
        });
    });

    editor.on('component:add', (component) => {
        window.requestAnimationFrame(() => {
            ensurePageContentBlockEditable(editor, component);

            if (promoteComponentIntoContentSlot(editor, component)) {
                if (! bootstrapping) {
                    scheduleRefresh();
                }

                return;
            }

            if (
                ! bootstrapping
                && ! isInsidePageContentSlot(component)
                && ! isInsideChromeShellPart(component)
                && (isChromeShellBlock(component) || looksLikeSiteChromeStructure(component))
            ) {
                component.remove();
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
        clearCanvasDragArtifacts(editor);

        window.requestAnimationFrame(() => {
            const slot = findPageContentSlot(editor);

            if (slot) {
                purgeChromeBleedFromSlot(slot);
            }

            promoteComponentIntoContentSlot(editor, component);
            scheduleRefresh();
        });
    });

    editor.on('sorter:drag:end', ({ target }) => {
        if (! target) {
            return;
        }

        window.requestAnimationFrame(() => {
            if (isPageContentSlot(target)) {
                scheduleRefresh();

                return;
            }

            if (! isInsidePageContentSlot(target)) {
                promoteComponentIntoContentSlot(editor, target);
            }

            scheduleRefresh();
        });
    });
}
