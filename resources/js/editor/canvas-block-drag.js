/**
 * Block drag UX: compact label chip, top/bottom drop spacers, scroll room for page-level drops.
 */

import { findPageContentSlotInEditor, isPageContentSlotComponent } from './chrome-content-slot-utils.js';
import { findDropZoneAtPointer, findLayoutDropZoneForPointer, insertBlockIntoLayoutZone } from './chrome/layout/drag.js';
import { safeFindComponents } from './tailwind-visual-style.js';

const DRAG_CHIP_CLASS = 'voodbuilder-editor-drag-chip';
const DRAG_BODY_CLASS = 'voodbuilder-editor-block-dragging';
const SECTION_GAP_DROP_CLASS = 'voodbuilder-editor-section-gap-drop';
const SPACER_ATTR = 'data-voodbuilder-top-drop-spacer';
const SPACER_TYPE = 'voodbuilder-top-drop-spacer';
const BOTTOM_SPACER_ATTR = 'data-voodbuilder-bottom-drop-spacer';
const BOTTOM_SPACER_TYPE = 'voodbuilder-bottom-drop-spacer';
const TOP_DROP_EDGE_PX = 72;
const BOTTOM_DROP_EDGE_PX = 96;

function isPointerNearCanvasTop(clientX, clientY, frame, fromFrameWindow = false) {
    if (! frame) {
        return false;
    }

    const frameRect = frame.getBoundingClientRect();
    const yInFrame = fromFrameWindow ? clientY : (clientY - frameRect.top);
    const xInFrame = fromFrameWindow ? clientX : (clientX - frameRect.left);

    if (! fromFrameWindow) {
        if (clientX < frameRect.left || clientX > frameRect.right) {
            return false;
        }

        if (clientY < frameRect.top || clientY > frameRect.bottom) {
            return false;
        }
    } else if (xInFrame < 0 || yInFrame < 0) {
        return false;
    }

    return yInFrame <= TOP_DROP_EDGE_PX;
}

function getTopDropSpacer(editor) {
    return editor.Canvas?.getDocument?.()?.querySelector?.(`[${SPACER_ATTR}]`) ?? null;
}

function setTopDropSpacerActive(editor, active) {
    getTopDropSpacer(editor)?.classList?.toggle('is-active', active);
}

/**
 * True when the pointer is on the top spacer or in the top canvas band
 * (where the blue Grapes line sits under the chrome nav).
 *
 * @param {object} editor
 * @param {number} clientX
 * @param {number} clientY
 * @param {boolean} [fromFrameWindow]
 * @returns {boolean}
 */
function wantsTopDropAtPoint(editor, clientX, clientY, fromFrameWindow = false) {
    const frame = editor.Canvas?.getFrameEl?.();

    if (isPointerOverTopSpacer(editor, clientX, clientY, fromFrameWindow)) {
        return true;
    }

    if (isPointerNearCanvasTop(clientX, clientY, frame, fromFrameWindow)) {
        return true;
    }

    // Grapes blue-line placeholder sits above the spacer and steals elementFromPoint.
    const doc = editor.Canvas?.getDocument?.();
    const spacer = getTopDropSpacer(editor);

    if (! frame || ! doc || ! spacer) {
        return false;
    }

    const frameRect = frame.getBoundingClientRect();
    const x = fromFrameWindow ? clientX : (clientX - frameRect.left);
    const y = fromFrameWindow ? clientY : (clientY - frameRect.top);
    const target = doc.elementFromPoint(x, y);

    if (! target?.closest?.('.gjs-placeholder, .gjs-com-placeholder, .gjs-plh')) {
        return false;
    }

    const spacerRect = spacer.getBoundingClientRect();

    return y >= (spacerRect.top - 16)
        && y <= (spacerRect.bottom + 16)
        && x >= (spacerRect.left - 4)
        && x <= (spacerRect.right + 4);
}

function updateTopDropSpacerState(editor, clientX, clientY, fromFrameWindow = false) {
    const frame = editor.Canvas?.getFrameEl?.();
    const shouldReveal = wantsTopDropAtPoint(editor, clientX, clientY, fromFrameWindow);

    setTopDropSpacerActive(editor, shouldReveal);

    if (! shouldReveal || ! frame?.contentWindow) {
        return;
    }

    const scrollY = frame.contentWindow.scrollY ?? 0;

    if (scrollY <= 4) {
        return;
    }

    try {
        frame.contentWindow.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    } catch {
        frame.contentWindow.scrollTo(0, 0);
    }
}

function isPointerOverTopSpacer(editor, clientX, clientY, fromFrameWindow = false) {
    const frame = editor.Canvas?.getFrameEl?.();
    const doc = editor.Canvas?.getDocument?.();

    if (! frame || ! doc) {
        return false;
    }

    const frameRect = frame.getBoundingClientRect();
    // elementFromPoint uses the iframe viewport — do not add scroll offsets.
    const x = fromFrameWindow ? clientX : (clientX - frameRect.left);
    const y = fromFrameWindow ? clientY : (clientY - frameRect.top);
    const target = doc.elementFromPoint(x, y);

    return Boolean(target?.closest?.(`[${SPACER_ATTR}]`));
}

function isTopDropSpacerComponent(component) {
    return Boolean(component?.getAttributes?.()?.[SPACER_ATTR])
        || component?.get?.('type') === SPACER_TYPE;
}

function isBottomDropSpacerComponent(component) {
    return Boolean(component?.getAttributes?.()?.[BOTTOM_SPACER_ATTR])
        || component?.get?.('type') === BOTTOM_SPACER_TYPE;
}

function isEditorDropSpacerComponent(component) {
    return isTopDropSpacerComponent(component) || isBottomDropSpacerComponent(component);
}

function getBottomDropSpacer(editor) {
    return editor.Canvas?.getDocument?.()?.querySelector?.(`[${BOTTOM_SPACER_ATTR}]`) ?? null;
}

function setBottomDropSpacerActive(editor, active) {
    getBottomDropSpacer(editor)?.classList?.toggle('is-active', active);
}

function isPointerOverBottomSpacer(editor, clientX, clientY, fromFrameWindow = false) {
    const frame = editor.Canvas?.getFrameEl?.();
    const doc = editor.Canvas?.getDocument?.();

    if (! frame || ! doc) {
        return false;
    }

    const frameRect = frame.getBoundingClientRect();
    const x = fromFrameWindow ? clientX : (clientX - frameRect.left);
    const y = fromFrameWindow ? clientY : (clientY - frameRect.top);
    const target = doc.elementFromPoint(x, y);

    return Boolean(target?.closest?.(`[${BOTTOM_SPACER_ATTR}]`));
}

/**
 * True when the pointer is on the bottom spacer or in the band above it
 * (easier target between the last section and chrome footer).
 */
function wantsBottomDropAtPoint(editor, clientX, clientY, fromFrameWindow = false) {
    if (isPointerOverBottomSpacer(editor, clientX, clientY, fromFrameWindow)) {
        return true;
    }

    const frame = editor.Canvas?.getFrameEl?.();
    const bottomSpacer = getBottomDropSpacer(editor);

    if (! frame || ! bottomSpacer) {
        return false;
    }

    const frameRect = frame.getBoundingClientRect();
    const x = fromFrameWindow ? clientX : (clientX - frameRect.left);
    const y = fromFrameWindow ? clientY : (clientY - frameRect.top);
    const spacerRect = bottomSpacer.getBoundingClientRect();

    if (x < spacerRect.left - 8 || x > spacerRect.right + 8) {
        return false;
    }

    return y >= (spacerRect.top - BOTTOM_DROP_EDGE_PX) && y <= (spacerRect.bottom + 16);
}

function updateBottomDropSpacerState(editor, clientX, clientY, fromFrameWindow = false) {
    setBottomDropSpacerActive(editor, wantsBottomDropAtPoint(editor, clientX, clientY, fromFrameWindow));
}

function pageContentInsertIndex(editor, slot, { preferTop = false, preferBottom = false } = {}) {
    const kids = slot.components?.() ?? [];
    const first = kids.at?.(0) ?? kids[0];
    const bottomIndex = [...kids].findIndex((child) => isBottomDropSpacerComponent(child));

    if (preferTop) {
        return isTopDropSpacerComponent(first) ? 1 : 0;
    }

    if (preferBottom && bottomIndex >= 0) {
        return bottomIndex;
    }

    return bottomIndex >= 0 ? bottomIndex : (kids.length ?? 0);
}

/**
 * Ensure a newly dropped block sits just before the bottom spacer (last page block).
 *
 * @param {object} editor
 * @param {object} component
 * @returns {boolean}
 */
function relocateBlockFromBottomSpacer(editor, component) {
    if (! component || isEditorDropSpacerComponent(component)) {
        return false;
    }

    if (editor.__voodbuilderChromeLayoutMode) {
        return false;
    }

    if (typeof component.move !== 'function' || component.isRemoved?.()) {
        return false;
    }

    const host = editor.__voodbuilderChromeShellMode
        ? findPageContentSlotInEditor(editor)
        : editor.getWrapper?.();

    if (! host) {
        return false;
    }

    const parent = component.parent?.();
    const kids = host.components?.() ?? [];
    const bottomIndex = [...kids].findIndex((child) => isBottomDropSpacerComponent(child));

    if (bottomIndex < 0) {
        return false;
    }

    if (isBottomDropSpacerComponent(parent)) {
        if (parent.parent?.() !== host && editor.__voodbuilderChromeShellMode) {
            return false;
        }

        component.move(host, { at: bottomIndex });
        editor.select?.(component);

        return true;
    }

    const compIndex = kids.indexOf(component);
    const pointerWantsBottom = editor.__voodbuilderPointerOverBottomSpacer === true;

    if (pointerWantsBottom && parent !== host && compIndex >= 0 && compIndex !== bottomIndex - 1) {
        component.move(host, { at: bottomIndex });
        editor.select?.(component);

        return true;
    }

    if (parent === host && compIndex > bottomIndex) {
        component.move(host, { at: bottomIndex });
        editor.select?.(component);

        return true;
    }

    return false;
}

/**
 * Ensure a newly dropped block sits right after the top spacer (first real page block).
 *
 * @param {object} editor
 * @param {object} component
 * @returns {boolean}
 */
function relocateBlockFromTopSpacer(editor, component) {
    if (! component || isTopDropSpacerComponent(component)) {
        return false;
    }

    if (editor.__voodbuilderChromeLayoutMode) {
        return false;
    }

    if (typeof component.move !== 'function' || component.isRemoved?.()) {
        return false;
    }

    const parent = component.parent?.();

    if (! parent) {
        return false;
    }

    const host = editor.__voodbuilderChromeShellMode
        ? findPageContentSlotInEditor(editor)
        : editor.getWrapper?.();

    if (! host) {
        return false;
    }

    // Dropped inside the spacer itself → promote to host after spacer.
    if (isTopDropSpacerComponent(parent)) {
        if (parent.parent?.() !== host && editor.__voodbuilderChromeShellMode) {
            return false;
        }

        const spacerIndex = host.components().indexOf(parent);
        const at = Math.max(0, spacerIndex) + 1;

        component.move(host, { at });
        editor.select?.(component);

        return true;
    }

    if (parent !== host) {
        return false;
    }

    const kids = host.components?.() ?? [];
    const spacerIndex = [...kids].findIndex((child) => isTopDropSpacerComponent(child));
    const compIndex = kids.indexOf(component);

    if (spacerIndex < 0 || compIndex < 0) {
        return false;
    }

    // Inserted at/before the spacer (blue line at the very top) → move just after it.
    if (compIndex <= spacerIndex) {
        component.move(host, { at: spacerIndex + (compIndex < spacerIndex ? 0 : 1) });
        editor.select?.(component);

        return true;
    }

    return false;
}

function insertBlockAtTop(editor, block) {
    const wrapper = editor.getWrapper?.();
    const content = block?.get?.('content') ?? block?.getContent?.();

    if (! wrapper || ! content) {
        return null;
    }

    if (editor.__voodbuilderChromeShellMode) {
        const slot = findPageContentSlotInEditor(editor);

        if (slot) {
            const at = pageContentInsertIndex(editor, slot, {
                preferTop: editor.__voodbuilderPointerOverTopSpacer === true,
                preferBottom: editor.__voodbuilderPointerOverBottomSpacer === true,
            });
            const added = slot.append(content, { at });
            const component = Array.isArray(added) ? added[0] : added;

            if (component) {
                markTopDropHandled(editor);
                editor.select?.(component);
                // Defer: drag session may still hold CssRebuildDragLock / ActiveBlockDrag.
                window.setTimeout(() => editor.__voodbuilderSchedulePageCssRebuild?.(0), 120);
            }

            return component ?? null;
        }
    }

    if (editor.__voodbuilderChromeLayoutMode) {
        const zone = findDropZoneAtPointer(editor);

        if (zone) {
            const added = zone.append(content);
            const component = Array.isArray(added) ? added[0] : added;

            if (component) {
                markTopDropHandled(editor);
                editor.select?.(component);
                window.setTimeout(() => editor.__voodbuilderSchedulePageCssRebuild?.(0), 120);
            }

            return component ?? null;
        }

        return insertBlockIntoLayoutZone(editor, block);
    }

    const first = wrapper.components?.().at?.(0);
    const at = isTopDropSpacerComponent(first) ? 1 : 0;
    const added = wrapper.append(content, { at });
    const component = Array.isArray(added) ? added[0] : added;

    if (component) {
        markTopDropHandled(editor);
        editor.select?.(component);
        window.setTimeout(() => editor.__voodbuilderSchedulePageCssRebuild?.(0), 120);
    }

    return component ?? null;
}

/**
 * When Grapes cancels a library drop over the page-content slot (overlay stole
 * the hit target), still insert the block at the end of the slot.
 *
 * @param {object} editor
 * @param {object} block
 * @returns {object|null}
 */
function insertBlockIntoPageContent(editor, block) {
    if (! editor?.__voodbuilderChromeShellMode || ! block) {
        return null;
    }

    const content = block?.get?.('content') ?? block?.getContent?.();
    const slot = findPageContentSlotInEditor(editor);

    if (! slot || ! content) {
        return null;
    }

    const first = slot.components?.().at?.(0);
    const preferTop = editor.__voodbuilderPointerOverTopSpacer === true;
    const preferBottom = editor.__voodbuilderPointerOverBottomSpacer === true;
    const at = pageContentInsertIndex(editor, slot, { preferTop, preferBottom });
    const added = slot.append(content, { at });
    const component = Array.isArray(added) ? added[0] : added;

    if (component) {
        markTopDropHandled(editor);
        editor.select?.(component);
        window.setTimeout(() => editor.__voodbuilderSchedulePageCssRebuild?.(0), 120);
    }

    return component ?? null;
}

function isPointerOverPageContent(editor) {
    if (! editor?.__voodbuilderChromeShellMode) {
        return false;
    }

    const doc = editor.Canvas?.getDocument?.();
    const frame = editor.Canvas?.getFrameEl?.();
    const point = editor.__voodbuilderLastDragPoint ?? editor.__voodbuilderLastDropPoint;

    if (! doc || ! frame || ! point) {
        return Boolean(
            editor.__voodbuilderPointerOverTopSpacer
            || editor.__voodbuilderPointerOverBottomSpacer,
        );
    }

    const rect = frame.getBoundingClientRect();
    const x = point.fromFrameWindow ? point.x : (point.x - rect.left);
    const y = point.fromFrameWindow ? point.y : (point.y - rect.top);
    const target = doc.elementFromPoint(x, y);

    return Boolean(target?.closest?.('[data-voodbuilder-page-content]'));
}

function bindBlockDragPointerTracking(editor) {
    const track = (event) => {
        const frame = editor.Canvas?.getFrameEl?.();
        const fromFrameWindow = event.view === frame?.contentWindow
            || event.currentTarget === frame?.contentWindow;

        editor.__voodbuilderLastDragPoint = {
            x: event.clientX,
            y: event.clientY,
            fromFrameWindow,
        };
        editor.__voodbuilderLastDragPointAt = Date.now();

        // Include the top canvas band: Grapes paints the blue line there even when
        // elementFromPoint misses the spacer (placeholder overlay / nav chrome).
        editor.__voodbuilderPointerOverTopSpacer = wantsTopDropAtPoint(
            editor,
            event.clientX,
            event.clientY,
            fromFrameWindow,
        );
        editor.__voodbuilderPointerOverBottomSpacer = wantsBottomDropAtPoint(
            editor,
            event.clientX,
            event.clientY,
            fromFrameWindow,
        );
        updateTopDropSpacerState(editor, event.clientX, event.clientY, fromFrameWindow);
        updateBottomDropSpacerState(editor, event.clientX, event.clientY, fromFrameWindow);
        syncChromeDropZoneHighlight(editor);

        if (editor.__voodbuilderActiveBlockDrag) {
            armDragSessionWatchdog(editor);
        }
    };

    editor.__voodbuilderBlockDragPointerTrack = track;
    document.addEventListener('pointermove', track, true);

    const frame = editor.Canvas?.getFrameEl?.();

    frame?.contentWindow?.addEventListener('pointermove', track, true);
}

function unbindBlockDragPointerTracking(editor) {
    const track = editor.__voodbuilderBlockDragPointerTrack;

    if (! track) {
        return;
    }

    document.removeEventListener('pointermove', track, true);

    const frame = editor.Canvas?.getFrameEl?.();

    frame?.contentWindow?.removeEventListener('pointermove', track, true);
    delete editor.__voodbuilderBlockDragPointerTrack;
    delete editor.__voodbuilderPointerOverTopSpacer;
    delete editor.__voodbuilderPointerOverBottomSpacer;
}

function beginTopDropSession(editor, block) {
    editor.__voodbuilderActiveBlockDrag = block ?? true;
    delete editor.__voodbuilderTopDropHandled;
    editor.__voodbuilderDragBlockLabel = String(
        block?.get?.('label') ?? block?.getLabel?.() ?? editor.__voodbuilderDragBlockLabel ?? '',
    ).trim();
    syncDropSpacers(editor);
    setCanvasDragState(editor, true);
    bindBlockDragPointerTracking(editor);
    startDragHighlightLoop(editor);

    if (editor.__voodbuilderDragBlockLabel) {
        startDragChipLoop(editor);
    }

    armDragSessionWatchdog(editor);
}

/**
 * Keep the drop-zone UI alive for long drags; force-clean if the session goes stale.
 *
 * @param {object} editor
 */
function armDragSessionWatchdog(editor) {
    window.clearTimeout(editor.__voodbuilderDragSessionWatchdog);
    editor.__voodbuilderDragSessionWatchdog = window.setTimeout(() => {
        const lastMoveAt = Number(editor.__voodbuilderLastDragPointAt ?? 0);
        const recentlyMoved = (Date.now() - lastMoveAt) < 2500;

        // Still an active drag with recent pointer motion — keep affordances, re-arm once.
        if (editor.__voodbuilderActiveBlockDrag && recentlyMoved) {
            armDragSessionWatchdog(editor);

            return;
        }

        // Stale session (missed drag:stop) or idle — clear so later blocks stay visible/interactive.
        stopEditorIdleMotionLoops(editor);
    }, 5000);
}

/**
 * True when an ancestor is an intentional nest host (layout, dropzone, catalog section).
 * Catalog heroes use `voodbuilder-layout-container` / dropzones — not only `data-voodbuilder-layout`.
 *
 * @param {object} ancestor
 * @returns {boolean}
 */
export function isIntentionalNestHost(ancestor) {
    if (! ancestor?.get) {
        return false;
    }

    const attrs = ancestor.getAttributes?.() ?? {};
    const layout = String(attrs['data-voodbuilder-layout'] ?? '');
    const type = String(ancestor.get?.('type') ?? '');
    const role = String(attrs['data-voodbuilder-role'] ?? '');
    const className = String(attrs.class ?? '');

    if (attrs['data-voodbuilder-dropzone']) {
        return true;
    }

    if (role === 'content') {
        return true;
    }

    // Catalog section (Hero, Features, …) — Basic elements nest inside these.
    if (attrs['data-voodbuilder-section-block']) {
        return true;
    }

    if (
        layout === 'block'
        || layout === 'div'
        || layout === 'container'
        || layout === 'section'
    ) {
        return true;
    }

    if (
        type === 'voodbuilder-layout-block'
        || type === 'voodbuilder-layout-div'
        || type === 'voodbuilder-container'
        || type === 'voodbuilder-layout-container'
        || type === 'voodbuilder-dropzone'
        || type === 'voodbuilder-section'
        || type === 'voodbuilder-section-dropzones'
    ) {
        return true;
    }

    if (/\b(flex|inline-flex|grid|voodbuilder-editor-container|vb-layout-block|vb-layout-div)\b/.test(className)) {
        return true;
    }

    return false;
}

/**
 * True when the component sits inside a Bricks-like Layout host, dropzone, or catalog section.
 * Those hosts are meant to receive nested content — do not promote out of them.
 *
 * @param {object} component
 * @returns {boolean}
 */
export function isNestedInLayoutStructure(component) {
    let ancestor = component?.parent?.();

    while (ancestor && ancestor.get?.('type') !== 'wrapper') {
        if (isIntentionalNestHost(ancestor)) {
            return true;
        }

        ancestor = ancestor.parent?.();
    }

    return false;
}

/**
 * Keep chrome-shell drops inside the page content slot.
 *
 * Do NOT yank Basic elements (heading, button, icon, …) out of catalog Heroes —
 * only relocate orphans that landed in nav/footer chrome. Nested catalog sections
 * are promoted by {@see registerSectionNestingGuard}, not here.
 *
 * @param {object} editor
 * @param {object} component
 * @returns {object|null}
 */
function ensurePageContentSlotPlacement(editor, component) {
    if (! editor?.__voodbuilderChromeShellMode || ! component) {
        return component;
    }

    if (isTopDropSpacerComponent(component) || isBottomDropSpacerComponent(component)) {
        return component;
    }

    const slot = findPageContentSlotInEditor(editor);

    if (! slot) {
        return component;
    }

    const parent = component.parent?.();

    // Nested inside the top spacer — always become the first real page block.
    if (isTopDropSpacerComponent(parent)) {
        try {
            const spacerIndex = slot.components().indexOf(parent);
            component.move(slot, { at: Math.max(0, spacerIndex) + 1 });
            editor.select?.(component);

            return component;
        } catch {
            component.remove?.();

            return null;
        }
    }

    if (parent === slot) {
        return component;
    }

    if (component.getAttributes?.()?.['data-voodbuilder-page-content']) {
        return component;
    }

    // Already nested under layout / dropzone / catalog section — keep the blue-line drop.
    if (isNestedInLayoutStructure(component)) {
        return component;
    }

    let ancestor = parent;
    let underSlot = false;

    while (ancestor && ancestor.get?.('type') !== 'wrapper') {
        if (ancestor === slot) {
            underSlot = true;
            break;
        }

        ancestor = ancestor.parent?.();
    }

    if (underSlot) {
        // Nested under the page slot but not in a recognized nest host (rare).
        // Leave in place — section-nesting-guard handles catalog-section nesting.
        return component;
    }

    // Dropped outside the page content (nav/footer/chrome) — move into the slot.
    try {
        const first = slot.components?.().at?.(0);
        const at = isTopDropSpacerComponent(first) ? 1 : (slot.components?.()?.length ?? 0);
        // Prefer top when the pointer was over the top spacer during this drag.
        const insertAt = editor.__voodbuilderPointerOverTopSpacer
            ? (isTopDropSpacerComponent(first) ? 1 : 0)
            : at;
        component.move(slot, { at: insertAt });
        editor.select?.(component);

        return component;
    } catch {
        component.remove?.();

        return null;
    }
}

function endTopDropSession(editor) {
    window.clearTimeout(editor.__voodbuilderDragSessionWatchdog);
    delete editor.__voodbuilderDragSessionWatchdog;
    clearDragChip(editor);
    clearDragHighlightLoop(editor);
    clearCanvasDragArtifacts(editor);
    setCanvasDragState(editor, false);
    setTopDropSpacerActive(editor, false);

    if (editor.__voodbuilderLastDragPoint) {
        editor.__voodbuilderLastDropPoint = { ...editor.__voodbuilderLastDragPoint };
    }

    unbindBlockDragPointerTracking(editor);
    clearChromeDropZoneHighlight(editor);
    delete editor.__voodbuilderActiveBlockDrag;
    delete editor.__voodbuilderTopDropHandled;
}

function markTopDropHandled(editor) {
    editor.__voodbuilderTopDropHandled = true;
}

function consumeTopDropHandled(editor) {
    const handled = editor.__voodbuilderTopDropHandled === true;
    delete editor.__voodbuilderTopDropHandled;

    return handled;
}

function setCanvasDragState(editor, active) {
    const doc = editor.Canvas?.getDocument?.();

    doc?.body?.classList?.toggle(DRAG_BODY_CLASS, active);

    if (! active) {
        setTopDropSpacerActive(editor, false);
        setBottomDropSpacerActive(editor, false);
        setSectionGapDropState(editor, false);
    }
}

function isPageContentHost(component) {
    return isPageContentSlotComponent(component);
}

function isSectionLikeComponent(component) {
    if (! component?.get) {
        return false;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const attrs = component.getAttributes?.() ?? {};
    const type = String(component.get('type') ?? '');

    return tag === 'section'
        || attrs['data-voodbuilder-section-block'] != null
        || attrs['data-voodbuilder-layout'] === 'section'
        || type === 'voodbuilder-section'
        || type === SPACER_TYPE
        || type === BOTTOM_SPACER_TYPE
        || attrs[SPACER_ATTR] != null
        || attrs[BOTTOM_SPACER_ATTR] != null;
}

/**
 * Page-content section/slot placements use a dashed rectangle placeholder
 * (see EditorCanvas / editor-theme section-gap-drop styles).
 */
function isSectionGapDrop(payload = {}) {
    const target = payload.targetModel;
    const placement = String(payload?.pos?.placement ?? '');

    if (! target) {
        return false;
    }

    if (placement === 'inside') {
        return isPageContentHost(target);
    }

    if (! isSectionLikeComponent(target)) {
        return false;
    }

    return isPageContentHost(target.parent?.());
}

function setSectionGapDropState(editor, active) {
    const doc = editor.Canvas?.getDocument?.();

    doc?.body?.classList?.toggle(SECTION_GAP_DROP_CLASS, active === true);
    document.body.classList.toggle(SECTION_GAP_DROP_CLASS, active === true);
}

function createDragChipElement(label) {
    const chip = document.createElement('div');
    chip.className = DRAG_CHIP_CLASS;
    chip.dataset.voodbuilderDragLabel = label;
    chip.textContent = label;
    chip.style.position = 'fixed';
    chip.style.top = '-1000px';
    chip.style.left = '-1000px';
    chip.style.pointerEvents = 'none';

    return chip;
}

function shouldSkipDragChip(element) {
    if (! element?.closest) {
        return false;
    }

    return Boolean(
        element.closest('[data-voodbuilder-chrome-shell-locked]')
        || element.closest('[data-voodbuilder-editor-site-header]')
        || element.closest('[data-voodbuilder-block^="site_nav_"]')
        || element.closest('[data-voodbuilder-block^="site_footer_"]')
        || element.closest('[data-mobile-nav]')
        || element.closest('button[data-mobile-nav-toggle]'),
    );
}

function clearChromeDropZoneHighlight(editor) {
    const doc = editor.Canvas?.getDocument?.();

    doc?.querySelectorAll?.('.voodbuilder-chrome-drop-active').forEach((zone) => {
        zone.classList.remove('voodbuilder-chrome-drop-active');
    });
}

export function clearCanvasDragArtifacts(editor) {
    const doc = editor.Canvas?.getDocument?.();

    doc?.querySelectorAll?.('.gjs-plh').forEach((node) => {
        node.remove();
    });

    doc?.body?.classList?.remove?.(DRAG_BODY_CLASS);
    clearChromeDropZoneHighlight(editor);
}

/**
 * Only allow Editor AutoScroller during a real user drag.
 * Programmatic sorter/move during layout boot left `dragging=true` and spun
 * setTimeout(50)+rAF forever while the tab was focused.
 *
 * @param {object} editor
 */
export function gateGrapesAutoscrollToRealDrags(editor) {
    const canvas = editor?.Canvas;

    if (! canvas || canvas.__voodbuilderAutoscrollGated) {
        return;
    }

    canvas.__voodbuilderAutoscrollGated = true;

    const originalStart = typeof canvas.startAutoscroll === 'function'
        ? canvas.startAutoscroll.bind(canvas)
        : null;

    if (! originalStart) {
        return;
    }

    canvas.startAutoscroll = (frame) => {
        if (! isRealCanvasPointerDrag(editor)) {
            return;
        }

        hardenGrapesAutoScrollers(editor);
        originalStart(frame);
    };
}

function isRealCanvasPointerDrag(editor) {
    if (editor?.__voodbuilderActiveBlockDrag) {
        return true;
    }

    try {
        const body = editor?.Canvas?.getBody?.();

        if (body?.classList?.contains('gjs-is__grabbing')) {
            return true;
        }
    } catch {
        // Canvas body may not exist yet.
    }

    return Boolean(document.querySelector('.gjs-is__grabbing'));
}

function collectGrapesAutoScrollers(editor) {
    const scrollers = [];

    try {
        const frameView = editor.Canvas?.getFrame?.()?.view
            ?? editor.em?.getCurrentFrame?.()?.view
            ?? editor.Canvas?.getCanvasView?.()?.frame;

        if (frameView?.autoScroller) {
            scrollers.push(frameView.autoScroller);
        }
    } catch {
        // Frame not ready.
    }

    try {
        if (editor.Canvas?.autoScroller) {
            scrollers.push(editor.Canvas.autoScroller);
        }
    } catch {
        // Canvas module not ready.
    }

    return scrollers;
}

/**
 * Editor AutoScroller.autoscroll() keeps scheduling setTimeout(50)+rAF while
 * `dragging === true` even when `lastClientY` is undefined (no pointer yet).
 * That loop only runs while the tab is focused (rAF pauses in background).
 * Patch instances so a stuck idle scroll self-terminates.
 *
 * @param {object} editor
 */
export function hardenGrapesAutoScrollers(editor) {
    if (! editor) {
        return;
    }

    collectGrapesAutoScrollers(editor).forEach((scroller) => {
        if (! scroller || scroller.__voodbuilderIdleAutoscrollPatched) {
            return;
        }

        scroller.__voodbuilderIdleAutoscrollPatched = true;
        const originalAutoscroll = scroller.autoscroll.bind(scroller);
        let idleWithoutPointer = 0;

        scroller.autoscroll = function voodbuilderGuardedAutoscroll() {
            // Grapes may call autoscroll without a bound `this` (detached timer/rAF).
            const self = scroller;

            if (! self) {
                return;
            }

            // Stop stuck scrollers even when lastClientY is set (mouse over canvas).
            if (self.dragging && ! isRealCanvasPointerDrag(editor)) {
                idleWithoutPointer = 0;
                self.stop?.();

                return;
            }

            if (self.dragging && self.lastClientY === undefined) {
                idleWithoutPointer += 1;

                // ~150–250ms of "waiting for pointer" with no real drag → stuck loop.
                if (idleWithoutPointer >= 4 && ! editor.__voodbuilderActiveBlockDrag) {
                    idleWithoutPointer = 0;
                    self.stop?.();

                    return;
                }
            } else {
                idleWithoutPointer = 0;
            }

            return originalAutoscroll();
        };
    });
}

/**
 * Force-stop Editor AutoScroller + our drag rAF loops.
 *
 * @param {object} editor
 */
export function stopEditorIdleMotionLoops(editor) {
    if (! editor) {
        return;
    }

    endTopDropSession(editor);
    hardenGrapesAutoScrollers(editor);

    try {
        editor.Canvas?.stopAutoscroll?.();
    } catch {
        // Canvas/frame may not be ready yet.
    }

    collectGrapesAutoScrollers(editor).forEach((scroller) => {
        try {
            scroller.stop?.();
        } catch {
            // Ignore.
        }
    });

    try {
        const frameView = editor.Canvas?.getFrame?.()?.view
            ?? editor.em?.getCurrentFrame?.()?.view
            ?? editor.Canvas?.getCanvasView?.()?.frame;

        frameView?.stopAutoscroll?.();
    } catch {
        // Ignore missing frame helpers across Editor versions.
    }
}

function syncChromeDropZoneHighlight(editor) {
    if (! editor.__voodbuilderChromeLayoutMode && ! editor.__voodbuilderChromeShellMode) {
        return;
    }

    const doc = editor.Canvas?.getDocument?.();

    if (! doc || ! editor.__voodbuilderActiveBlockDrag) {
        clearChromeDropZoneHighlight(editor);

        return;
    }

    clearChromeDropZoneHighlight(editor);

    if (editor.__voodbuilderChromeLayoutMode) {
        const activeZone = findLayoutDropZoneForPointer(editor);
        const activeEl = activeZone?.getEl?.() ?? null;

        if (activeEl) {
            activeEl.classList.add('voodbuilder-chrome-drop-active');
        }

        return;
    }

    const pageSlot = doc.querySelector('[data-voodbuilder-page-content]');

    if (! pageSlot) {
        return;
    }

    const frame = editor.Canvas?.getFrameEl?.();
    const point = editor.__voodbuilderLastDragPoint ?? editor.__voodbuilderLastDropPoint;

    if (! frame || ! point) {
        // Empty / spacer-only pages: still show the page-content affordance while dragging.
        if (isPageContentSlotDropAffordable(pageSlot)) {
            pageSlot.classList.add('voodbuilder-chrome-drop-active');
        }

        return;
    }

    const rect = frame.getBoundingClientRect();
    // elementFromPoint uses the iframe viewport — do not add scroll offsets.
    const x = point.fromFrameWindow ? point.x : (point.x - rect.left);
    const y = point.fromFrameWindow ? point.y : (point.y - rect.top);
    const target = doc.elementFromPoint(x, y);

    if (target?.closest?.('[data-voodbuilder-page-content]')) {
        pageSlot.classList.add('voodbuilder-chrome-drop-active');

        return;
    }

    // Wrapper is not droppable — treat the gap between chrome parts as page content
    // when the pointer is not over nav/footer chrome.
    const overChromePart = target?.closest?.('[data-voodbuilder-chrome-shell-part]');

    if (! overChromePart && isPageContentSlotDropAffordable(pageSlot)) {
        pageSlot.classList.add('voodbuilder-chrome-drop-active');
    }
}

/**
 * True when the page-content slot has no author blocks yet (empty or only editor spacers).
 *
 * @param {HTMLElement|null|undefined} pageSlot
 * @returns {boolean}
 */
function isPageContentSlotDropAffordable(pageSlot) {
    if (! pageSlot) {
        return false;
    }

    const children = [...(pageSlot.children ?? [])];

    if (children.length === 0) {
        return true;
    }

    return children.every((child) => (
        child.hasAttribute?.('data-voodbuilder-top-drop-spacer')
        || child.hasAttribute?.('data-voodbuilder-bottom-drop-spacer')
        || child.hasAttribute?.('data-voodbuilder-inner-drop')
    ));
}

function isChromeDropTargetElement(element, editor) {
    if (! element?.closest) {
        return false;
    }

    if (editor?.__voodbuilderChromeShellMode) {
        return Boolean(element.closest('[data-voodbuilder-page-content]'));
    }

    return Boolean(
        element.closest('[data-voodbuilder-page-content]')
        || element.closest('[data-voodbuilder-content-slot]')
        || element.closest('[data-voodbuilder-chrome-drop-zone]'),
    );
}

function applyDragChip(editor, element) {
    const label = editor.__voodbuilderDragBlockLabel;

    if (! label || ! element?.classList || shouldSkipDragChip(element)) {
        return;
    }

    element.classList.add(DRAG_CHIP_CLASS);
    element.dataset.voodbuilderDragLabel = label;
}

function findDragElements(editor) {
    const doc = editor.Canvas?.getDocument?.();

    if (! doc) {
        return [];
    }

    const elements = new Set();

    if (editor.__voodbuilderChromeLayoutMode || editor.__voodbuilderChromeShellMode) {
        doc.querySelectorAll(`.${DRAG_CHIP_CLASS}`).forEach((node) => elements.add(node));

        doc.querySelectorAll('.gjs-plh').forEach((node) => {
            if (isChromeDropTargetElement(node, editor)) {
                elements.add(node);
            }
        });

        return [...elements];
    }

    doc.querySelectorAll(`.${DRAG_CHIP_CLASS}`).forEach((node) => elements.add(node));

    doc.querySelectorAll('.gjs-freezed, .gjs-comp-selected, .gjs-plh').forEach((node) => {
        if (node.closest?.(`[${SPACER_ATTR}]`)) {
            return;
        }

        elements.add(node);
    });

    const selected = editor.getSelected?.();

    if (selected?.getEl?.()) {
        const el = selected.getEl();

        if (editor.__voodbuilderDragBlockLabel && el?.isConnected) {
            elements.add(el);
        }
    }

    return [...elements];
}

function syncDragChip(editor) {
    const label = editor.__voodbuilderDragBlockLabel;

    if (! label) {
        return;
    }

    findDragElements(editor).forEach((element) => applyDragChip(editor, element));
}

function clearDragChip(editor) {
    const doc = editor.Canvas?.getDocument?.();

    doc?.querySelectorAll?.(`.${DRAG_CHIP_CLASS}`).forEach((node) => {
        node.classList.remove(DRAG_CHIP_CLASS);
        delete node.dataset.voodbuilderDragLabel;
    });

    delete editor.__voodbuilderDragBlockLabel;

    if (editor.__voodbuilderDragChipRaf) {
        cancelAnimationFrame(editor.__voodbuilderDragChipRaf);
        delete editor.__voodbuilderDragChipRaf;
    }
}

function startDragHighlightLoop(editor) {
    if (editor.__voodbuilderDragHighlightRaf) {
        cancelAnimationFrame(editor.__voodbuilderDragHighlightRaf);
    }

    const tick = () => {
        if (! editor.__voodbuilderActiveBlockDrag) {
            return;
        }

        syncChromeDropZoneHighlight(editor);
        editor.__voodbuilderDragHighlightRaf = requestAnimationFrame(tick);
    };

    syncChromeDropZoneHighlight(editor);
    tick();
}

function clearDragHighlightLoop(editor) {
    if (editor.__voodbuilderDragHighlightRaf) {
        cancelAnimationFrame(editor.__voodbuilderDragHighlightRaf);
        delete editor.__voodbuilderDragHighlightRaf;
    }

    clearChromeDropZoneHighlight(editor);
}

function startDragChipLoop(editor) {
    if (editor.__voodbuilderDragChipRaf) {
        cancelAnimationFrame(editor.__voodbuilderDragChipRaf);
    }

    const tick = () => {
        if (! editor.__voodbuilderDragBlockLabel) {
            return;
        }

        syncDragChip(editor);
        editor.__voodbuilderDragChipRaf = requestAnimationFrame(tick);
    };

    tick();
}

export function registerTopDropSpacerType(editor) {
    if (editor.__voodbuilderTopDropSpacerTypeRegistered) {
        return;
    }

    editor.__voodbuilderTopDropSpacerTypeRegistered = true;

    editor.DomComponents.addType(SPACER_TYPE, {
        isComponent: (element) => element?.hasAttribute?.(SPACER_ATTR) === true,
        model: {
            defaults: {
                type: SPACER_TYPE,
                tagName: 'div',
                name: 'Drop zone (top)',
                draggable: false,
                // Droppable so Grapes accepts the blue-line drop on the top band; anything
                // nested inside is immediately promoted after the spacer via relocate.
                droppable: true,
                selectable: false,
                highlightable: false,
                // Never hoverable/badgable: at high zoom a height:0 spacer still
                // paints a 1px Editor outline (black square under the header).
                hoverable: false,
                badgable: false,
                highlightable: false,
                removable: false,
                copyable: false,
                layerable: false,
                stylable: false,
                attributes: {
                    [SPACER_ATTR]: '1',
                    class: 'voodbuilder-editor-top-drop-spacer',
                },
            },
        },
    });

    editor.DomComponents.addType(BOTTOM_SPACER_TYPE, {
        isComponent: (element) => element?.hasAttribute?.(BOTTOM_SPACER_ATTR) === true,
        model: {
            defaults: {
                type: BOTTOM_SPACER_TYPE,
                tagName: 'div',
                name: 'Drop zone (bottom)',
                draggable: false,
                droppable: true,
                selectable: false,
                highlightable: false,
                hoverable: false,
                badgable: false,
                removable: false,
                copyable: false,
                layerable: false,
                stylable: false,
                attributes: {
                    [BOTTOM_SPACER_ATTR]: '1',
                    class: 'voodbuilder-editor-bottom-drop-spacer',
                },
            },
        },
    });
}

export function ensureBottomDropSpacer(editor) {
    if (editor.__voodbuilderChromeLayoutMode) {
        removeBottomDropSpacer(editor);

        return;
    }

    const host = editor.__voodbuilderChromeShellMode
        ? findPageContentSlotInEditor(editor)
        : editor.getWrapper?.();

    if (! host) {
        removeBottomDropSpacer(editor);

        return;
    }

    if (editor.__voodbuilderChromeShellMode) {
        const wrapper = editor.getWrapper?.();

        if (wrapper) {
            for (const spacer of safeFindComponents(wrapper, `[${BOTTOM_SPACER_ATTR}]`)) {
                if (spacer.parent?.() !== host) {
                    spacer.remove();
                }
            }
        }
    }

    const existing = [...safeFindComponents(host, `[${BOTTOM_SPACER_ATTR}]`)];
    const children = host.components?.();
    const last = children?.at?.((children?.length ?? 0) - 1);

    // Must be idempotent: a blind remove + append fires component:remove on every
    // call, which re-enters spacer maintenance on the next frame and locks the editor.
    if (existing.length === 1 && existing[0] === last) {
        return;
    }

    for (const spacer of existing) {
        spacer.remove();
    }

    host.append({
        type: BOTTOM_SPACER_TYPE,
    });
}

/**
 * Single entry point for drop-sentinel maintenance.
 *
 * The flag makes our own remove/add events inert: GrapesJS fires them synchronously,
 * so listeners that call back into this function cannot start a feedback loop.
 *
 * @param {object} editor
 */
export function syncDropSpacers(editor) {
    if (editor.__voodbuilderDropSpacerSyncing) {
        return;
    }

    editor.__voodbuilderDropSpacerSyncing = true;

    try {
        ensureTopDropSpacer(editor);
        ensureBottomDropSpacer(editor);
    } finally {
        editor.__voodbuilderDropSpacerSyncing = false;
    }
}

export function removeBottomDropSpacer(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    for (const spacer of safeFindComponents(wrapper, `[${BOTTOM_SPACER_ATTR}]`)) {
        spacer.remove();
    }
}

export function ensureTopDropSpacer(editor) {
    if (editor.__voodbuilderChromeLayoutMode) {
        removeTopDropSpacer(editor);

        return;
    }

    // Chrome shell: keep the spacer as first child of the page-content slot
    // (right under the nav), not on the document wrapper.
    if (editor.__voodbuilderChromeShellMode) {
        const slot = findPageContentSlotInEditor(editor);

        if (! slot) {
            removeTopDropSpacer(editor);

            return;
        }

        // Drop any wrapper-level spacers left over from non-shell mode.
        const wrapper = editor.getWrapper?.();

        if (wrapper) {
            for (const spacer of safeFindComponents(wrapper, `[${SPACER_ATTR}]`)) {
                if (spacer.parent?.() !== slot) {
                    spacer.remove();
                }
            }
        }

        const first = slot.components?.().at?.(0);

        if (first?.getAttributes?.()?.[SPACER_ATTR]) {
            return;
        }

        slot.append({
            type: SPACER_TYPE,
        }, { at: 0 });

        return;
    }

    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const children = wrapper.components?.();
    const first = children?.at?.(0);

    if (first?.getAttributes?.()?.[SPACER_ATTR]) {
        return;
    }

    wrapper.append({
        type: SPACER_TYPE,
    }, { at: 0 });
}

export function removeTopDropSpacer(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    for (const spacer of safeFindComponents(wrapper, `[${SPACER_ATTR}]`)) {
        spacer.remove();
    }

    removeBottomDropSpacer(editor);
}

export function detachTopDropSpacerForExport(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const topSpacer = safeFindComponents(wrapper, `[${SPACER_ATTR}]`)[0];
    const bottomSpacer = safeFindComponents(wrapper, `[${BOTTOM_SPACER_ATTR}]`)[0];

    if (topSpacer) {
        editor.__voodbuilderDetachedTopDropSpacer = topSpacer;
        topSpacer.remove();
    }

    if (bottomSpacer) {
        editor.__voodbuilderDetachedBottomDropSpacer = bottomSpacer;
        bottomSpacer.remove();
    }
}

export function restoreTopDropSpacerAfterExport(editor) {
    const topSpacer = editor.__voodbuilderDetachedTopDropSpacer;
    const bottomSpacer = editor.__voodbuilderDetachedBottomDropSpacer;

    if (editor.__voodbuilderChromeShellMode) {
        const slot = findPageContentSlotInEditor(editor);

        if (slot) {
            if (topSpacer) {
                slot.append(topSpacer, { at: 0 });
                delete editor.__voodbuilderDetachedTopDropSpacer;
            }

            if (bottomSpacer) {
                slot.append(bottomSpacer);
                delete editor.__voodbuilderDetachedBottomDropSpacer;
            }

            return;
        }
    }

    if (topSpacer) {
        editor.getWrapper?.()?.append(topSpacer, { at: 0 });
        delete editor.__voodbuilderDetachedTopDropSpacer;
    }

    if (bottomSpacer) {
        editor.getWrapper?.()?.append(bottomSpacer);
        delete editor.__voodbuilderDetachedBottomDropSpacer;
    }
}

export function registerCanvasBlockDrag(editor) {
    if (editor.__voodbuilderCanvasBlockDragRegistered) {
        return;
    }

    editor.__voodbuilderCanvasBlockDragRegistered = true;

    registerTopDropSpacerType(editor);
    gateGrapesAutoscrollToRealDrags(editor);

    editor.on('load', () => {
        syncDropSpacers(editor);
        gateGrapesAutoscrollToRealDrags(editor);
        hardenGrapesAutoScrollers(editor);
        stopEditorIdleMotionLoops(editor);
    });

    editor.on('canvas:frame:load', () => {
        hardenGrapesAutoScrollers(editor);
    });

    editor.on('voodbuilder:chrome-layout-ready', () => {
        hardenGrapesAutoScrollers(editor);
        stopEditorIdleMotionLoops(editor);
    });

    editor.on('voodbuilder:dynamic-blocks-refreshed', () => {
        stopEditorIdleMotionLoops(editor);
    });

    editor.on('sorter:drag:end', () => {
        setSectionGapDropState(editor, false);

        if (! editor.__voodbuilderActiveBlockDrag) {
            stopEditorIdleMotionLoops(editor);
        }
    });

    editor.on('block:drag:stop', () => {
        // endTopDropSession is also called below; still kill AutoScroller.
        window.setTimeout(() => {
            if (! editor.__voodbuilderActiveBlockDrag) {
                stopEditorIdleMotionLoops(editor);
            }
        }, 0);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && ! editor.__voodbuilderActiveBlockDrag) {
            hardenGrapesAutoScrollers(editor);
            stopEditorIdleMotionLoops(editor);
        }
    });

    // Watchdog: kill stuck AutoScroller if no real block drag is active.
    window.clearInterval(editor.__voodbuilderIdleMotionWatchdog);
    editor.__voodbuilderIdleMotionWatchdog = window.setInterval(() => {
        if (document.visibilityState !== 'visible' || editor.__voodbuilderActiveBlockDrag) {
            return;
        }

        try {
            hardenGrapesAutoScrollers(editor);
            editor.Canvas?.stopAutoscroll?.();
            collectGrapesAutoScrollers(editor).forEach((scroller) => {
                if (scroller?.dragging && scroller.lastClientY === undefined) {
                    scroller.stop();
                }
            });
        } catch {
            // Ignore.
        }
    }, 750);

    editor.on('component:add', (component) => {
        if (isEditorDropSpacerComponent(component) || editor.__voodbuilderDropSpacerSyncing) {
            return;
        }

        window.requestAnimationFrame(() => {
            if (relocateBlockFromTopSpacer(editor, component)) {
                markTopDropHandled(editor);

                return;
            }

            if (relocateBlockFromBottomSpacer(editor, component)) {
                markTopDropHandled(editor);

                return;
            }

            syncDropSpacers(editor);
        });
    });

    editor.on('component:remove', (component) => {
        if (editor.__voodbuilderDropSpacerSyncing || ! isEditorDropSpacerComponent(component)) {
            return;
        }

        window.requestAnimationFrame(() => {
            syncDropSpacers(editor);
        });
    });

    editor.on('block:drag:start', (block, event) => {
        beginTopDropSession(editor, block);

        document.querySelectorAll('[data-voodbuilder-component-block-toolbar]').forEach((toolbar) => {
            toolbar.hidden = true;
        });

        if (! event?.dataTransfer || ! editor.__voodbuilderDragBlockLabel) {
            return;
        }

        const chip = createDragChipElement(editor.__voodbuilderDragBlockLabel);
        document.body.appendChild(chip);
        event.dataTransfer.setDragImage(chip, Math.round(chip.offsetWidth / 2), Math.round(chip.offsetHeight / 2));
        window.requestAnimationFrame(() => chip.remove());
    });

    editor.on('sorter:drag:start', (source) => {
        hardenGrapesAutoScrollers(editor);

        // Never start a top-drop session from sorter events alone — Editor fires
        // sorter:drag:start during programmatic move/reorder (layout bootstrap,
        // dynamic block refresh), which left the drag highlight rAF loop running forever.
        if (! editor.__voodbuilderActiveBlockDrag) {
            return;
        }

        const label = editor.__voodbuilderDragBlockLabel;
        const element = source?.element;

        if (! label || ! element?.classList) {
            return;
        }

        applyDragChip(editor, element);
    });

    editor.on('sorter:drag', (payload) => {
        if (editor.__voodbuilderLayerTreeSorting) {
            setSectionGapDropState(editor, false);

            return;
        }

        setSectionGapDropState(editor, isSectionGapDrop(payload));
    });

    editor.on('component:drag:end', () => {
        setSectionGapDropState(editor, false);
    });

    editor.on('block:drag:stop', (component, block) => {
        const point = editor.__voodbuilderLastDragPoint;
        const overTopSpacer = editor.__voodbuilderPointerOverTopSpacer === true
            || (
                point
                && wantsTopDropAtPoint(
                    editor,
                    point.x,
                    point.y,
                    point.fromFrameWindow === true,
                )
            );

        const overBottomSpacer = editor.__voodbuilderPointerOverBottomSpacer === true
            || (
                point
                && wantsBottomDropAtPoint(
                    editor,
                    point.x,
                    point.y,
                    point.fromFrameWindow === true,
                )
            );

        if (component) {
            component = ensurePageContentSlotPlacement(editor, component);
        }

        if (component) {
            if (relocateBlockFromTopSpacer(editor, component)) {
                markTopDropHandled(editor);
            } else if (relocateBlockFromBottomSpacer(editor, component)) {
                markTopDropHandled(editor);
            }
        } else if ((overTopSpacer || overBottomSpacer) && block) {
            delete editor.__voodbuilderTopDropHandled;

            if (overTopSpacer) {
                insertBlockAtTop(editor, block);
            } else {
                insertBlockIntoPageContent(editor, block);
            }
        } else if (block && isPointerOverPageContent(editor)) {
            // Grapes cancelled over the content slot (decorative overlays stole the hit).
            // Do not fallback-insert when a real block was already placed (e.g. saved components).
            if (! component) {
                delete editor.__voodbuilderTopDropHandled;
                insertBlockIntoPageContent(editor, block);
            }
        }

        endTopDropSession(editor);

        // Belt-and-suspenders: never leave the canvas stuck in drag mode
        // (pointer-events:none on section children hides / blocks the next block).
        window.requestAnimationFrame(() => {
            if (! editor.__voodbuilderActiveBlockDrag) {
                setCanvasDragState(editor, false);
            }
        });

        document.querySelectorAll('[data-voodbuilder-component-block-toolbar]').forEach((toolbar) => {
            toolbar.hidden = editor.__voodbuilderComponentSelectionMode === true;
        });
    });

    editor.on('sorter:drag:end', () => {
        window.requestAnimationFrame(() => {
            if (
                editor.__voodbuilderPointerOverTopSpacer
                && ! consumeTopDropHandled(editor)
            ) {
                const block = editor.BlockManager?._dragBlock
                    ?? editor.__voodbuilderActiveBlockDrag
                    ?? null;

                if (block) {
                    insertBlockAtTop(editor, block);
                }
            }

            endTopDropSession(editor);
        });
    });
}
