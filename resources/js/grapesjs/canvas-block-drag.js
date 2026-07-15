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

        editor.__voodbuilderPointerOverTopSpacer = isPointerOverTopSpacer(
            editor,
            event.clientX,
            event.clientY,
        );
        updateTopDropSpacerState(editor, event.clientX, event.clientY);
        syncChromeDropZoneHighlight(editor);
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
    editor.__voodbuilderActiveBlockDrag = block ?? null;
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
}

function endTopDropSession(editor) {
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
                hoverable: true,
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

    editor.on('load', () => {
        ensureTopDropSpacer(editor);
    });

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
        const dragContent = source?.dragSource?.content ?? editor.get?.('dragSource')?.content;

        if (dragContent && ! editor.__voodbuilderActiveBlockDrag) {
            const block = editor.BlockManager?._dragBlock
                ?? editor.Canvas?.getSorter?.()?.__currentBlock
                ?? null;
            beginTopDropSession(editor, block);
        }

        const label = editor.__voodbuilderDragBlockLabel;
        const element = source?.element;

        if (! label || ! element?.classList) {
            return;
        }

        applyDragChip(editor, element);
    });

    editor.on('block:drag:stop', (component, block) => {
        if (editor.__voodbuilderChromeShellMode && component) {
            const slot = findPageContentSlotInEditor(editor);

            if (slot && component.parent?.() !== slot && ! component.getAttributes?.()?.['data-voodbuilder-page-content']) {
                if (typeof component.remove === 'function') {
                    component.remove();
                }

                component = null;
            }
        }

        if (component) {
            markTopDropHandled(editor);
            window.requestAnimationFrame(() => relocateBlockFromTopSpacer(editor, component));
        } else if (editor.__voodbuilderPointerOverTopSpacer && block && ! consumeTopDropHandled(editor)) {
            insertBlockAtTop(editor, block);
        }

        endTopDropSession(editor);

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
