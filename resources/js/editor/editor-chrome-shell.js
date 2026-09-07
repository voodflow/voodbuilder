/**
 * Chrome layout shell preview inside the Site Page visual editor.
 * Layout chrome is read-only; only the content slot is editable.
 */

import { isFooterBlock, isNavBlock } from './chrome/ids.js';
import { lockChromePreview } from './chrome/blocks/preview.js';
import { normalizeSiteNavChromeButtons } from './plugins/voodbuilder-editor.js';
import { clearCanvasDragArtifacts, syncDropSpacers } from './canvas-block-drag.js';
import { withoutUndo } from './editor-undo.js';
import {
    isEditorDropSentinelComponent,
    isOrphanPageContentNode,
    purgeOrphanPageContentNodes,
} from './page-content-orphans.js';
import {
    forEachGrapesComponent,
    isGrapesComponent,
    safeFindComponents,
    safeRenderEditorLayers,
} from './tailwind-visual-style.js';
import {
    patchChromeZoneLayerIcons,
    registerChromeLayerIconPatch,
} from './chrome-editor-guards.js';
import {
    CHROME_DROP_ZONE_ATTR,
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
import { findRichTextHost, isRichTextComponent } from './text-elements.js';
import {
    canMoveGrapesComponent,
    isValidMoveTarget,
    safeComponentIndex,
    safeMoveToEnd,
    safeReorderComponent,
} from './core/component-model.js';

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

function isInsideRichTextHost(component) {
    if (! component || isRichTextComponent(component)) {
        return false;
    }

    return Boolean(findRichTextHost(component));
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

    if (! blockId && attrs['data-voodbuilder-editor-site-header']) {
        return false;
    }

    if (attrs['data-voodbuilder-editor-site-header'] && blockId.startsWith('site_nav_')) {
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
    if (! isGrapesComponent(component)) {
        return;
    }

    component.set({ locked }, { silent: true });
    editor.Layers?.setLocked?.(component, locked);
}

function unlockPageContentChildren(editor, slot) {
    forEachGrapesComponent(slot, (child) => {
        ensurePageContentBlockEditable(editor, child);
    });
}

function isEditorDropSentinel(component) {
    const attrs = component?.getAttributes?.() ?? {};
    const type = String(component?.get?.('type') ?? '');

    return Boolean(
        attrs['data-voodbuilder-top-drop-spacer']
        || attrs['data-voodbuilder-bottom-drop-spacer']
        || attrs['data-voodbuilder-inner-drop']
        || type === 'voodbuilder-top-drop-spacer'
        || type === 'voodbuilder-bottom-drop-spacer'
        || type === 'voodbuilder-inner-drop-slot',
    );
}

function ensurePageContentBlockEditable(editor, component) {
    if (! component || isPageContentSlot(component)) {
        return;
    }

    if (! isInsidePageContentSlot(component)) {
        return;
    }

    // Editor-only drop sentinels must stay non-layerable / non-selectable.
    if (isEditorDropSentinel(component)) {
        component.set({
            locked: true,
            removable: false,
            copyable: false,
            draggable: false,
            selectable: false,
            hoverable: false,
            highlightable: false,
            layerable: false,
            badgable: false,
            toolbar: [],
        }, { silent: true });

        return;
    }

    // Inner RichText markup is owned by the Content RTE — never unlock/promote it.
    if (isInsideRichTextHost(component)) {
        return;
    }

    // Nested layout content (Section → Container → Block → Text/Button) must stay
    // movable/duplicable — locking drag to top-level sections broke Bricks-like editing
    // and left tlb-clone copies without the move handle.
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

    const parent = component.parent?.();

    if (isPageContentSlot(parent)) {
        lockChromePreview(component);
    }
}

function lockChromeShellComponent(component) {
    if (! isGrapesComponent(component)) {
        return;
    }

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

    forEachGrapesComponent(component, (child) => {
        if (isPageContentSlot(child)) {
            return;
        }

        lockChromeShellComponent(child);
    });
}

function configureChromeShellPartWrapper(editor, component, part) {
    if (! isGrapesComponent(component)) {
        return;
    }

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
        // Selectable so the page editor can show “edit this in layout X”.
        selectable: true,
        hoverable: true,
        highlightable: true,
        editable: false,
        stylable: false,
        layerable: true,
        droppable: false,
        locked: true,
        toolbar: [],
    }, { silent: true });

    setLayerLocked(editor, component, true);

    forEachGrapesComponent(component, (child) => {
        lockChromeShellComponent(child);

        const blockId = child.getAttributes?.()['data-voodbuilder-block'];

        if (isNavBlock(blockId) || isFooterBlock(blockId)) {
            lockChromePreview(child);
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
    if (! isGrapesComponent(slot)) {
        return;
    }

    purgeChromeBleedFromContentSlot(slot);

    purgeOrphanPageContentNodes(slot);

    const removable = [];

    forEachGrapesComponent(slot, (component) => {
        if (
            isChromeShellBlock(component)
            || isChromeShellPart(component)
            || looksLikeSiteChromeStructure(component)
            || isEditorHostBleedComponent(component)
        ) {
            removable.push(component);
        }
    });

    removable.forEach((component) => component.remove());
}

/**
 * Cookie consent / legal chrome belongs to another plugin — keep it out of the page editor.
 */
function sanitizeChromeShellHtmlForEditor(html) {
    let sanitized = String(html ?? '').trim();

    if (sanitized === '') {
        return '';
    }

    sanitized = sanitized.replace(
        /<div\b[^>]*\bvoodbuilder-mobile-nav__legal\b[^>]*>[\s\S]*?<\/div>/gi,
        '',
    );
    sanitized = sanitized.replace(
        /<button\b[^>]*\bdata-cookie-preferences\b[^>]*>[\s\S]*?<\/button>/gi,
        '',
    );
    sanitized = sanitized.replace(
        /<a\b[^>]*\bvoodbuilder-mobile-nav__cookie-link\b[^>]*>[\s\S]*?<\/a>/gi,
        '',
    );

    return sanitized.trim();
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
        if (! isGrapesComponent(child)) {
            return false;
        }

        if (isChromeShellBlock(child) || looksLikeSiteChromeStructure(child)) {
            return true;
        }

        const blockId = child.getAttributes?.()['data-voodbuilder-block'];

        return isNavBlock(blockId) || isFooterBlock(blockId);
    });
}

function syncChromeShellPartInnerHtml(partComponent, innerHtml) {
    const html = sanitizeChromeShellHtmlForEditor(innerHtml);

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
    const html = sanitizeChromeShellHtmlForEditor(innerHtml);
    let component = findChromeShellPartAtWrapper(wrapper, part);

    if (! html && ! component) {
        return null;
    }

    // Drop stale shell parts when the layout no longer provides HTML for this zone
    // (e.g. footer removed/unsaved → empty after would otherwise keep a broken ghost).
    if (! html && component) {
        try {
            component.remove();
        } catch {
            // Already detached.
        }

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
    if (! isValidMoveTarget(wrapper)) {
        return;
    }

    [before, slot, after].filter((component) => canMoveGrapesComponent(component)).forEach((component, index) => {
        safeReorderComponent(component, wrapper, index);
    });
}

function wrapTopLevelChromeBlocks(editor, wrapper, before, after) {
    const navBlocks = [];
    const footerBlocks = [];

    forEachGrapesComponent(wrapper, (child) => {
        if (isPageContentSlot(child) || isChromeShellPart(child)) {
            return;
        }

        const blockId = String(child.getAttributes?.()['data-voodbuilder-block'] ?? '');

        if (isNavBlock(blockId) || blockId === 'site_header' || blockId.startsWith('site_nav_')) {
            navBlocks.push(child);

            return;
        }

        if (isFooterBlock(blockId) || looksLikeSiteChromeStructure(child)) {
            footerBlocks.push(child);
        }
    });

    if (navBlocks.length && isValidMoveTarget(before)) {
        navBlocks.forEach((block) => {
            if (block.parent?.() !== before) {
                safeMoveToEnd(block, before);
            }
        });
    }

    if (footerBlocks.length && isValidMoveTarget(after)) {
        footerBlocks.forEach((block) => {
            if (block.parent?.() !== after) {
                safeMoveToEnd(block, after);
            }
        });
    }
}

function relocateTopLevelOrphans(editor, wrapper, slot, before, after) {
    wrapTopLevelChromeBlocks(editor, wrapper, before, after);

    const orphans = [];
    const staleShellParts = [];

    forEachGrapesComponent(wrapper, (child) => {
        if (child === slot || child === before || child === after) {
            return;
        }

        if (isChromeShellWrapper(child) || isChromeShellPart(child)) {
            staleShellParts.push(child);

            return;
        }

        if (isChromeShellBlock(child) || looksLikeSiteChromeStructure(child)) {
            return;
        }

        orphans.push(child);
    });

    staleShellParts.forEach((child) => child.remove());

    orphans.forEach((child) => {
        if (! isValidMoveTarget(slot)) {
            return;
        }

        safeMoveToEnd(child, slot);
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

function purgeLayoutDropZonesFromTree(root) {
    for (const zone of safeFindComponents(root, `[${CHROME_DROP_ZONE_ATTR}]`)) {
        const parent = zone.parent?.();

        if (! parent) {
            zone.remove();

            continue;
        }

        [...zone.components().models ?? zone.components()]
            .filter((child) => canMoveGrapesComponent(child))
            .forEach((child) => {
                safeMoveToEnd(child, parent);
            });

        try {
            zone.remove();
        } catch {
            // Drop zone may already be detached during shell refresh.
        }
    }
}

function applyChromeShellLocks(editor, options = {}) {
    const structure = ensureChromeShellStructure(editor, options);
    const wrapper = editor.getWrapper?.();
    const slot = structure?.slot ?? dedupePageContentSlots(editor);
    const before = structure?.before ?? null;
    const after = structure?.after ?? null;

    if (! wrapper || ! slot) {
        return;
    }

    configurePageContentSlot(editor, slot, String(options.pageContentPlaceholder ?? ''));
    purgeChromeBleedFromSlot(slot);
    purgeLayoutDropZonesFromTree(wrapper);
    normalizeSiteNavChromeButtons(wrapper);

    wrapper.set({
        droppable: false,
        removable: false,
        draggable: false,
        copyable: false,
    }, { silent: true });

    const staleTopLevelChrome = [];

    forEachGrapesComponent(wrapper, (child) => {
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

            forEachGrapesComponent(child, (shellChild) => {
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
            const blockId = String(child.getAttributes?.()?.['data-voodbuilder-block'] ?? '');

            if (isValidMoveTarget(before) && (isNavBlock(blockId) || blockId.startsWith('site_nav_'))) {
                if (safeMoveToEnd(child, before)) {
                    return;
                }
            }

            if (isValidMoveTarget(after) && safeMoveToEnd(child, after)) {
                return;
            }

            staleTopLevelChrome.push(child);
        }
    });

    staleTopLevelChrome.forEach((child) => {
        if (! isGrapesComponent(child)) {
            return;
        }

        try {
            child.remove();
        } catch {
            // Stale chrome block may already be detached.
        }
    });

    patchChromeZoneLayerIcons(editor);
}

function isChromeBleedComponent(component) {
    if (! component?.get) {
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
        || attrs[CHROME_DROP_ZONE_ATTR]
        || type === 'voodbuilder-chrome-drop-zone'
        || gjsType === 'voodbuilder-chrome-drop-zone'
        || attrs['data-mobile-nav-toggle']
        || attrs['data-theme-toggle']
        || attrs['data-voodbuilder-notification-bell-preview']
        || attrs['data-voodbuilder-nav-dropdown-toggle']
    ) {
        return true;
    }

    const text = String(component.get?.('content') ?? component.get?.('text') ?? '').replace(/\s+/g, '');

    return /^(?:Notifications)+$/.test(text);
}

function promoteComponentIntoContentSlot(editor, component) {
    if (! component || isPageContentSlot(component)) {
        return false;
    }

    if (editor.__voodbuilderRichTextWriting || editor.__voodbuilderBulkStructureUpdate) {
        return false;
    }

    // Never yank RichText inner nodes (p/strong/a…) out to the page slot.
    if (isRichTextComponent(component) || isInsideRichTextHost(component)) {
        return false;
    }

    if (isInsidePageContentSlot(component)) {
        return false;
    }

    // Never yank nodes out of locked header/footer chrome into page content.
    // Doing so (e.g. cookie-settings <button> morph → <a>) emptied the nav and
    // spawned thousands of voodbuilder-cta-button layers.
    if (isInsideChromeShellPart(component) || isChromeShellPart(component) || isChromeShellWrapper(component)) {
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

    if (isChromeBleedComponent(component) || isEditorHostBleedComponent(component)) {
        component.remove();

        return true;
    }

    if (! canMoveGrapesComponent(component) || ! isValidMoveTarget(slot)) {
        return false;
    }

    if (! safeMoveToEnd(component, slot)) {
        return false;
    }

    component.set({ locked: false }, { silent: true });
    editor.Layers?.setLocked?.(component, false);
    editor.select(component);

    return true;
}

/**
 * Fallback net for companion overlays that reach the canvas.
 *
 * The actual fix is server-side: `EditorHostChrome::REQUEST_ATTRIBUTE` tells packages that
 * inject public-page UI to stand down before the host page renders, so nothing is left to
 * clean up. This stays for installs pinned to companion versions that predate the contract,
 * and it matches structural markers only.
 *
 * It deliberately no longer matches button labels. Deleting any CTA whose text read
 * "Cookie settings" also deleted the perfectly legitimate footer button an author had
 * placed to reopen consent preferences — silent data loss on every refresh.
 */
function isEditorHostBleedComponent(component) {
    const attrs = component.getAttributes?.() ?? {};
    const classes = component.getClasses?.() ?? [];

    return Boolean(
        // Documented opt-out for companions that render into the host page.
        attrs['data-voodbuilder-host-overlay']
        || attrs['data-cookie-preferences']
        || attrs['data-cc']
        || classes.includes('cc-revoke')
        || classes.includes('cc-window')
        || classes.includes('cc-banner')
        || classes.includes('voodbuilder-mobile-nav__cookie-link'),
    );
}

/**
 * Remove cookie-settings CTA clones that leaked into the page-content slot.
 */
export function purgeLeakedChromeCtaButtons(editor) {
    const slot = findPageContentSlot(editor);

    if (! slot) {
        return 0;
    }

    const leaked = [];

    const visit = (component) => {
        if (! component) {
            return;
        }

        if (isEditorHostBleedComponent(component)) {
            leaked.push(component);
        }

        component.components?.().forEach?.((child) => visit(child));
    };

    visit(slot);

    leaked.forEach((component) => {
        try {
            component.remove();
        } catch {
            // Already detached.
        }
    });

    return leaked.length;
}

function hideChromeShellBlocks(editor) {
    const blockManager = editor.BlockManager;

    if (! blockManager) {
        return;
    }

    blockManager.getAll().forEach((block) => {
        if (! block?.get) {
            return;
        }

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

function chromeShellStructureFingerprint(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return '';
    }

    const parts = [];

    forEachGrapesComponent(wrapper, (child) => {
        const attrs = child.getAttributes?.() ?? {};
        const part = attrs[CHROME_SHELL_PART_ATTR] ?? attrs[CONTENT_SLOT_ATTR] ?? '';
        const childCount = child.components?.()?.length ?? 0;
        const cid = child.cid ?? child.getId?.() ?? '';

        parts.push(`${part}:${cid}:${childCount}`);
    });

    return parts.join('|');
}

export function extractChromeShellPageHtml(editor) {
    const slot = findPageContentSlot(editor);

    if (! slot) {
        return null;
    }

    const parts = [];
    const collection = slot.components?.();

    if (collection?.forEach) {
        collection.forEach((component) => {
            if (! isGrapesComponent(component)) {
                return;
            }

            if (isEditorDropSentinelComponent(component) || isOrphanPageContentNode(component)) {
                return;
            }

            parts.push(component.toHTML({
                keepInlineStyle: true,
                withProps: false,
            }));
        });
    } else {
        forEachGrapesComponent(slot, (component) => {
            parts.push(component.toHTML({
                keepInlineStyle: true,
                withProps: false,
            }));
        });
    }

    const joined = parts.join('');

    if (joined.trim() !== '') {
        return joined;
    }

    // Grapes sometimes reports children while forEach yields nothing — unwrap slot HTML.
    const wrapped = String(slot.toHTML?.({
        keepInlineStyle: true,
        withProps: false,
    }) ?? '').trim();

    if (wrapped === '') {
        return '';
    }

    const doc = new DOMParser().parseFromString(`<body>${wrapped}</body>`, 'text/html');
    const root = doc.body.firstElementChild;

    if (! root) {
        return '';
    }

    return root.innerHTML.trim();
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
        if (editor.__voodbuilderChromeShellRefreshing || editor.__voodbuilderActiveBlockDrag) {
            return;
        }

        editor.__voodbuilderChromeShellRefreshing = true;

        let structureChanged = false;

        try {
            const beforeIds = chromeShellStructureFingerprint(editor);

            // None of this is an author edit: it locks shell parts, hides chrome blocks
            // and evicts host-page nodes that bled into the canvas. Recording it spent
            // the author's undo steps on invisible bookkeeping.
            withoutUndo(editor, () => {
                applyChromeShellLocks(editor, shellOptions);
                hideChromeShellBlocks(editor);
                purgeLeakedChromeCtaButtons(editor);
                // After shell reshuffle — keep a drop target before the first page block.
                syncDropSpacers(editor);
                purgeOrphanPageContentNodes(findPageContentSlot(editor));
            });

            structureChanged = beforeIds !== chromeShellStructureFingerprint(editor);

            // Re-render layers only when the shell tree actually changed — otherwise
            // Layers.render() rebinds touchstart on every idle refresh and floods the console.
            if (structureChanged || ! editor.__voodbuilderChromeShellLayersReady) {
                safeRenderEditorLayers(editor, { immediate: true });
                editor.__voodbuilderChromeShellLayersReady = true;
            }

            patchChromeZoneLayerIcons(editor);
        } finally {
            editor.__voodbuilderChromeShellRefreshing = false;
        }
    };

    const scheduleRefresh = () => {
        if (
            bootstrapping
            || editor.__voodbuilderChromeShellRefreshing
            || editor.__voodbuilderActiveBlockDrag
            || editor.__voodbuilderDynamicBlockRefreshing
            || editor.__voodbuilderBulkStructureUpdate
        ) {
            return;
        }

        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(refresh, 48);
    };

    const finishBootstrap = () => {
        if (! bootstrapping && editor.__voodbuilderChromeShellLayersReady) {
            return;
        }

        refresh();
        purgeLeakedChromeCtaButtons(editor);
        bootstrapping = false;
    };

    editor.on('load', finishBootstrap);
    // Frame reloads (stylesheet link settle) must not reshuffle chrome forever.
    editor.on('canvas:frame:load', () => {
        if (bootstrapping) {
            scheduleRefresh();
        }
    });
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

        component.set('toolbar', []);

        window.requestAnimationFrame(() => {
            if (editor.getSelected?.() === component) {
                editor.select(undefined);
            }
        });
    });

    editor.on('component:add', (component) => {
        if (
            editor.__voodbuilderBulkStructureUpdate
            || editor.__voodbuilderRichTextWriting
            // Drop sentinels are editor chrome: never promote them, never reshuffle for them.
            || isEditorDropSentinelComponent(component)
        ) {
            return;
        }

        window.requestAnimationFrame(() => {
            if (
                ! component
                || editor.__voodbuilderBulkStructureUpdate
                || editor.__voodbuilderRichTextWriting
            ) {
                return;
            }

            // Drop host-overlay bleed immediately — do not promote into page content.
            if (isEditorHostBleedComponent(component)) {
                try {
                    component.remove();
                } catch {
                    // Already detached.
                }

                return;
            }

            ensurePageContentBlockEditable(editor, component);

            if (promoteComponentIntoContentSlot(editor, component)) {
                if (! bootstrapping && ! editor.__voodbuilderChromeShellRefreshing) {
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

            // Nested nav/footer remounts must not reshuffle the shell (Layers.render loop).
            if (
                isInsideChromeShellPart(component)
                && ! isInsidePageContentSlot(component)
                && ! isPageContentSlotComponent(component)
            ) {
                return;
            }

            if (
                ! bootstrapping
                && ! editor.__voodbuilderChromeShellRefreshing
                && ! editor.__voodbuilderDynamicBlockRefreshing
            ) {
                scheduleRefresh();
            }
        });
    });

    editor.on('component:remove', (component) => {
        if (
            bootstrapping
            || editor.__voodbuilderChromeShellRefreshing
            || editor.__voodbuilderBulkStructureUpdate
            || isEditorDropSentinelComponent(component)
        ) {
            return;
        }

        // Ignore nested chrome churn (nav/footer refresh); only reshuffle for page-content tree.
        if (
            isInsideChromeShellPart(component)
            && ! isInsidePageContentSlot(component)
            && ! isPageContentSlotComponent(component)
        ) {
            return;
        }

        scheduleRefresh();
    });

    editor.on('block:drag:stop', (component) => {
        clearCanvasDragArtifacts(editor);

        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                const slot = findPageContentSlot(editor);

                if (slot) {
                    purgeChromeBleedFromSlot(slot);
                    purgeOrphanPageContentNodes(slot);
                }

                promoteComponentIntoContentSlot(editor, component);
                scheduleRefresh();
            });
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
