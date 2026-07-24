/**
 * Block drag UX: compact label chip, top drop spacer, scroll room above first block.
 */

import { findPageContentSlotInEditor } from './chrome-content-slot-utils.js';
import { findDropZoneAtPointer, findLayoutDropZoneForPointer, insertBlockIntoLayoutZone } from './chrome/layout/drag.js';
import { safeFindComponents } from './tailwind-visual-style.js';

const DRAG_CHIP_CLASS = 'voodbuilder-gjs-drag-chip';
const DRAG_BODY_CLASS = 'voodbuilder-gjs-block-dragging';
const SPACER_ATTR = 'data-voodbuilder-top-drop-spacer';
const SPACER_TYPE = 'voodbuilder-top-drop-spacer';
const TOP_DROP_EDGE_PX = 72;

function isPointerNearCanvasTop(clientX, clientY, frame) {
    if (! frame) {
        return false;
    }

    const frameRect = frame.getBoundingClientRect();

    if (clientX < frameRect.left || clientX > frameRect.right) {
        return false;
    }

    if (clientY < frameRect.top || clientY > frameRect.bottom) {
        return false;
    }

    return (clientY - frameRect.top) <= TOP_DROP_EDGE_PX;
}

function getTopDropSpacer(editor) {
    return editor.Canvas?.getDocument?.()?.querySelector?.(`[${SPACER_ATTR}]`) ?? null;
}

function setTopDropSpacerActive(editor, active) {
    getTopDropSpacer(editor)?.classList?.toggle('is-active', active);
}

function updateTopDropSpacerState(editor, clientX, clientY) {
    const frame = editor.Canvas?.getFrameEl?.();
    const overSpacer = isPointerOverTopSpacer(editor, clientX, clientY);
    const nearTopEdge = isPointerNearCanvasTop(clientX, clientY, frame);
    const shouldReveal = overSpacer || nearTopEdge;

    setTopDropSpacerActive(editor, shouldReveal);

    if (! nearTopEdge || ! frame?.contentWindow) {
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

function isPointerOverTopSpacer(editor, clientX, clientY) {
    const frame = editor.Canvas?.getFrameEl?.();
    const doc = editor.Canvas?.getDocument?.();

    if (! frame || ! doc) {
        return false;
    }

    const frameRect = frame.getBoundingClientRect();
    const x = clientX - frameRect.left + (frame.contentWindow?.scrollX ?? 0);
    const y = clientY - frameRect.top + (frame.contentWindow?.scrollY ?? 0);
    const target = doc.elementFromPoint(x, y);

    return Boolean(target?.closest?.(`[${SPACER_ATTR}]`));
}

function relocateBlockFromTopSpacer(editor, component) {
    if (! component || component.getAttributes?.()?.[SPACER_ATTR]) {
        return false;
    }

    if (editor.__voodbuilderChromeShellMode || editor.__voodbuilderChromeLayoutMode) {
        return false;
    }

    const wrapper = editor.getWrapper?.();
    const parent = component.parent?.();

    if (! wrapper || ! parent) {
        return false;
    }

    if (typeof component.move !== 'function' || component.isRemoved?.()) {
        return false;
    }

    if (parent.getAttributes?.()?.[SPACER_ATTR]) {
        const spacerIndex = wrapper.components().indexOf(parent);
        component.move(wrapper, { at: spacerIndex + 1 });
        editor.select?.(component);

        return true;
    }

    const first = wrapper.components?.().at?.(0);

    if (first?.getAttributes?.()?.[SPACER_ATTR] && wrapper.components().indexOf(component) === 0) {
        component.move(wrapper, { at: 1 });
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
            const added = slot.append(content);
            const component = Array.isArray(added) ? added[0] : added;

            if (component) {
                markTopDropHandled(editor);
                editor.select?.(component);
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
            }

            return component ?? null;
        }

        return insertBlockIntoLayoutZone(editor, block);
    }

    const first = wrapper.components?.().at?.(0);
    const at = first?.getAttributes?.()?.[SPACER_ATTR] ? 1 : 0;
    const added = wrapper.append(content, { at });
    const component = Array.isArray(added) ? added[0] : added;

    if (component) {
        markTopDropHandled(editor);
        editor.select?.(component);
    }

    return component ?? null;
}

function bindBlockDragPointerTracking(editor) {
    const track = (event) => {
        editor.__voodbuilderLastDragPoint = {
            x: event.clientX,
            y: event.clientY,
        };
        editor.__voodbuilderLastDragPointAt = Date.now();

        editor.__voodbuilderPointerOverTopSpacer = isPointerOverTopSpacer(
            editor,
            event.clientX,
            event.clientY,
        );
        updateTopDropSpacerState(editor, event.clientX, event.clientY);
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
}

function beginTopDropSession(editor, block) {
    editor.__voodbuilderActiveBlockDrag = block ?? true;
    delete editor.__voodbuilderTopDropHandled;
    editor.__voodbuilderDragBlockLabel = String(
        block?.get?.('label') ?? block?.getLabel?.() ?? editor.__voodbuilderDragBlockLabel ?? '',
    ).trim();
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
 * True when the component sits inside a Bricks-like Layout host (Section/Container/Block/Div).
 * Those hosts are meant to receive nested content — do not promote out of them.
 *
 * @param {object} component
 * @returns {boolean}
 */
function isNestedInLayoutStructure(component) {
    let ancestor = component?.parent?.();

    while (ancestor && ancestor.get?.('type') !== 'wrapper') {
        const attrs = ancestor.getAttributes?.() ?? {};
        const layout = String(attrs['data-voodbuilder-layout'] ?? '');
        const type = String(ancestor.get?.('type') ?? '');

        if (
            layout === 'block'
            || layout === 'div'
            || layout === 'container'
            || layout === 'section'
            || type === 'voodbuilder-layout-block'
            || type === 'voodbuilder-layout-div'
            || type === 'voodbuilder-container'
        ) {
            return true;
        }

        ancestor = ancestor.parent?.();
    }

    return false;
}

/**
 * If a block lands nested under the page content slot (inside another section),
 * promote it to a sibling of that section instead of discarding it.
 *
 * Layout Section/Container/Block are intentional nest hosts — never promote out of them.
 *
 * @param {object} editor
 * @param {object} component
 * @returns {object|null}
 */
function ensurePageContentSlotPlacement(editor, component) {
    if (! editor?.__voodbuilderChromeShellMode || ! component) {
        return component;
    }

    const slot = findPageContentSlotInEditor(editor);

    if (! slot) {
        return component;
    }

    if (component.parent?.() === slot) {
        return component;
    }

    if (component.getAttributes?.()?.['data-voodbuilder-page-content']) {
        return component;
    }

    if (isNestedInLayoutStructure(component)) {
        return component;
    }

    let ancestor = component.parent?.();
    let hostSection = null;
    let underSlot = false;

    while (ancestor && ancestor.get?.('type') !== 'wrapper') {
        if (ancestor === slot) {
            underSlot = true;
            break;
        }

        const tag = String(ancestor.get?.('tagName') ?? '').toLowerCase();

        if (! hostSection && tag === 'section') {
            hostSection = ancestor;
        }

        ancestor = ancestor.parent?.();
    }

    if (! underSlot) {
        // Dropped outside the page content (nav/footer/chrome) — move into the slot.
        try {
            const at = slot.components?.()?.length ?? 0;
            component.move(slot, { at });
            editor.select?.(component);

            return component;
        } catch {
            component.remove?.();

            return null;
        }
    }

    // Nested inside a catalog/page section: promote as sibling after the host section.
    try {
        const insertAt = hostSection
            ? (slot.components().indexOf(hostSection) + 1)
            : (slot.components?.()?.length ?? 0);

        component.move(slot, { at: Math.max(0, insertAt) });
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
    }
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
        || element.closest('[data-voodbuilder-gjs-site-header]')
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
 * Only allow GrapesJS AutoScroller during a real user drag.
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
 * GrapesJS AutoScroller.autoscroll() keeps scheduling setTimeout(50)+rAF while
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
 * Force-stop GrapesJS AutoScroller + our drag rAF loops.
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
        // Ignore missing frame helpers across GrapesJS versions.
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
        return;
    }

    const rect = frame.getBoundingClientRect();
    const x = point.x - rect.left + (frame.contentWindow?.scrollX ?? 0);
    const y = point.y - rect.top + (frame.contentWindow?.scrollY ?? 0);
    const target = doc.elementFromPoint(x, y);

    if (target?.closest?.('[data-voodbuilder-page-content]')) {
        pageSlot.classList.add('voodbuilder-chrome-drop-active');
    }
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
                name: 'Drop zone',
                draggable: false,
                droppable: true,
                selectable: false,
                highlightable: false,
                // Never hoverable/badgable: at high zoom a height:0 spacer still
                // paints a 1px GrapesJS outline (black square under the header).
                hoverable: false,
                badgable: false,
                highlightable: false,
                removable: false,
                copyable: false,
                layerable: false,
                stylable: false,
                attributes: {
                    [SPACER_ATTR]: '1',
                    class: 'voodbuilder-gjs-top-drop-spacer',
                },
            },
        },
    });
}

function ensureTopDropSpacer(editor) {
    if (editor.__voodbuilderChromeShellMode || editor.__voodbuilderChromeLayoutMode) {
        removeTopDropSpacer(editor);

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
}

export function detachTopDropSpacerForExport(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const spacer = safeFindComponents(wrapper, `[${SPACER_ATTR}]`)[0];

    if (! spacer) {
        return;
    }

    editor.__voodbuilderDetachedTopDropSpacer = spacer;
    spacer.remove();
}

export function restoreTopDropSpacerAfterExport(editor) {
    const spacer = editor.__voodbuilderDetachedTopDropSpacer;

    if (! spacer) {
        return;
    }

    editor.getWrapper?.()?.append(spacer, { at: 0 });
    delete editor.__voodbuilderDetachedTopDropSpacer;
}

export function registerCanvasBlockDrag(editor) {
    if (editor.__voodbuilderCanvasBlockDragRegistered) {
        return;
    }

    editor.__voodbuilderCanvasBlockDragRegistered = true;

    registerTopDropSpacerType(editor);
    gateGrapesAutoscrollToRealDrags(editor);

    editor.on('load', () => {
        ensureTopDropSpacer(editor);
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
        if (component?.getAttributes?.()?.[SPACER_ATTR]) {
            return;
        }

        window.requestAnimationFrame(() => {
            if (editor.__voodbuilderActiveBlockDrag || editor.__voodbuilderPointerOverTopSpacer) {
                markTopDropHandled(editor);
            }

            if (relocateBlockFromTopSpacer(editor, component)) {
                return;
            }

            ensureTopDropSpacer(editor);
        });
    });

    editor.on('component:remove', (component) => {
        if (component?.getAttributes?.()?.[SPACER_ATTR]) {
            window.requestAnimationFrame(() => ensureTopDropSpacer(editor));
        }
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

        // Never start a top-drop session from sorter events alone — GrapesJS fires
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

    editor.on('block:drag:stop', (component, block) => {
        if (component) {
            component = ensurePageContentSlotPlacement(editor, component);
        }

        if (component) {
            markTopDropHandled(editor);
            window.requestAnimationFrame(() => relocateBlockFromTopSpacer(editor, component));
        } else if (editor.__voodbuilderPointerOverTopSpacer && block && ! consumeTopDropHandled(editor)) {
            insertBlockAtTop(editor, block);
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
