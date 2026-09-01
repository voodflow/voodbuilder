/**
 * Layer tree reorder — custom pointer DnD (Editor Layers sorter breaks after
 * Layers.render() reuses stale viewLayer + detached sorter containers).
 */

import { safeRenderEditorLayers } from './tailwind-visual-style.js';
import { resolveComponentFromLayerElement } from './component-context-menu.js';
import { isValidGrapesComponent, sanitizeEditorLayerTree } from './core/component-model.js';
import {
    isChromeLayoutContentSlot,
    isChromeLayoutFooterBlock,
    isChromeLayoutNavBlock,
} from './chrome-editor-guards.js';
import { isInsideChromeDropZoneComponent, isPageContentSlotComponent, isChromeShellPartComponent } from './chrome-content-slot-utils.js';
import { isCatalogSection } from './section-nesting-guard.js';

const SPACER_ATTR = 'data-voodbuilder-top-drop-spacer';
const BOTTOM_SPACER_ATTR = 'data-voodbuilder-bottom-drop-spacer';
const CHROME_DROP_ZONE_ATTR = 'data-voodbuilder-chrome-drop-zone';
const LAYER_SORT_MOVE_THRESHOLD_PX = 6;
const LAYER_INSERT_LINE_ATTR = 'data-voodbuilder-layer-insert-line';
const LAYER_INSERT_CUE_ATTR = 'data-voodbuilder-layer-insert-cue';
const LAYER_NEST_INDENT_PX = 12;
/** Sibling edge bands (outside → before/after). Middle is nest-into when allowed. */
const LAYER_SIBLING_EDGE_RATIO = 0.3;

function isSiteChromeBlock(component, editor) {
    const attrs = component.getAttributes?.() ?? {};
    const blockId = String(attrs['data-voodbuilder-block'] ?? '');

    if (editor?.__voodbuilderChromeLayoutMode && isInsideChromeDropZoneComponent(component)) {
        return false;
    }

    if (isPageContentSlotComponent(component) || isChromeShellPartComponent(component)) {
        return true;
    }

    if (attrs[CHROME_DROP_ZONE_ATTR]) {
        return true;
    }

    if (isChromeLayoutContentSlot(component) || isChromeLayoutNavBlock(component) || isChromeLayoutFooterBlock(component)) {
        return true;
    }

    return Boolean(attrs['data-voodbuilder-editor-site-header'])
        || attrs['data-voodbuilder-chrome-shell-locked']
        || blockId.startsWith('site_nav_')
        || blockId.startsWith('site_footer_')
        || blockId === 'site_header';
}

function isProtectedSlot(component) {
    const attrs = component.getAttributes?.() ?? {};

    return Boolean(attrs['data-voodbuilder-menu'] || attrs['data-voodbuilder-brand']);
}

function shouldEnableLayerReorder(component, editor) {
    if (! component?.get) {
        return false;
    }

    if (component.get('layerable') === false) {
        return false;
    }

    if (isProtectedSlot(component) || isSiteChromeBlock(component, editor)) {
        return false;
    }

    const wrapper = editor.getWrapper?.();

    if (! wrapper || component === wrapper) {
        return false;
    }

    if (component.getAttributes?.()?.[SPACER_ATTR] || component.getAttributes?.()?.[BOTTOM_SPACER_ATTR]) {
        return false;
    }

    return Boolean(component.parent?.());
}

function ensureLayerDraggable(component, editor) {
    if (! shouldEnableLayerReorder(component, editor)) {
        return;
    }

    if (component.get('draggable') === false) {
        component.set('draggable', true);
    }
}

function syncAllLayerDraggable(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const walk = (component) => {
        if (! component) {
            return;
        }

        ensureLayerDraggable(component, editor);
        component.viewLayer?.updateMove?.();
        component.components?.().forEach(walk);
    };

    walk(wrapper);
}

function canStartLayerSort(component, editor) {
    return Boolean(
        component?.get
        && shouldEnableLayerReorder(component, editor)
        && component.get('draggable') !== false
        && component.get('layerable') !== false,
    );
}

function isRowDragTarget(target) {
    return Boolean(
        target?.closest?.('.gjs-layer-item[data-toggle-select]')
        && ! target?.closest?.('[data-toggle-visible], [data-toggle-open], [data-name], [data-toggle-move]'),
    );
}

function clearCanvasDropCues(editor) {
    const classes = [
        'voodbuilder-editor-block-dragging',
        'voodbuilder-editor-inner-drop-dragging',
        'voodbuilder-editor-section-gap-drop',
    ];

    const canvasBody = editor?.Canvas?.getDocument?.()?.body;

    for (const className of classes) {
        canvasBody?.classList?.remove(className);
        document.body.classList.remove(className);
    }
}

/**
 * Layer-tree sorts must not activate canvas dropzones / inner-drop remounts.
 */
export function isLayerTreeSorting(editor) {
    return editor?.__voodbuilderLayerTreeSorting === true;
}

function beginLayerTreeSorting(editor) {
    if (! editor) {
        return;
    }

    editor.__voodbuilderLayerTreeSorting = true;
    clearCanvasDropCues(editor);
}

function endLayerTreeSorting(editor) {
    if (! editor) {
        return;
    }

    editor.__voodbuilderLayerTreeSorting = false;
    // component:add from move() may land in a later rAF — keep the grace window.
    editor.__voodbuilderLayerTreeSortingUntil = Date.now() + 500;
    clearCanvasDropCues(editor);
}

function isLockedLayerMoveHandle(target) {
    return Boolean(target?.closest?.('.gjs-layer-move[data-voodbuilder-shell-locked]'));
}

function isAncestorComponent(ancestor, descendant) {
    if (! ancestor || ! descendant || ancestor === descendant) {
        return false;
    }

    let current = descendant.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (current === ancestor) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

function selectFromLayers(editor, component) {
    if (! editor || ! isValidGrapesComponent(component)) {
        return;
    }

    // Editor LayerManager.componentChanged collapses every open layer that is
    // not an ancestor of the selection — unless opts.fromLayers is set.
    editor.select?.(component, { scroll: false, fromLayers: true });
}

function pinLayersSelection(editor, component) {
    if (! editor || ! isValidGrapesComponent(component)) {
        return;
    }

    editor.__voodbuilderLayersSelectionPin = component;
    editor.__voodbuilderLayersSelectionPinUntil = Date.now() + 600;
}

function clearLayersSelectionPin(editor) {
    if (! editor) {
        return;
    }

    delete editor.__voodbuilderLayersSelectionPin;
    delete editor.__voodbuilderLayersSelectionPinUntil;
}

function restorePinnedLayersSelection(editor) {
    const pinned = editor?.__voodbuilderLayersSelectionPin;

    if (! isValidGrapesComponent(pinned)) {
        clearLayersSelectionPin(editor);

        return;
    }

    if (editor.getSelected?.() === pinned) {
        return;
    }

    selectFromLayers(editor, pinned);
}

/**
 * Snapshot which layers are expanded so a drag-start select cannot collapse
 * nest targets (e.g. select Hr → Editor closes sibling Container/Div).
 *
 * @returns {object[]}
 */
function snapshotOpenLayers(editor) {
    const opened = [];
    const walk = (component) => {
        if (! component?.get) {
            return;
        }

        if (component.get('open')) {
            opened.push(component);
        }

        component.components?.().forEach(walk);
    };

    walk(editor?.getWrapper?.());

    return opened;
}

function restoreOpenLayers(opened) {
    for (const component of opened) {
        if (isValidGrapesComponent(component) && ! component.get('open')) {
            component.set('open', true);
        }
    }
}

function ensureLayerOpen(editor, component) {
    if (! isValidGrapesComponent(component)) {
        return;
    }

    if (editor?.Layers?.setOpen) {
        editor.Layers.setOpen(component, true);
    } else if (! component.get('open')) {
        component.set('open', true);
    }
}

function isSectionLikeLayer(component) {
    if (! component?.get) {
        return false;
    }

    if (isCatalogSection(component)) {
        return true;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const attrs = component.getAttributes?.() ?? {};

    return tag === 'section' || attrs['data-voodbuilder-layout'] === 'section';
}

/**
 * Same-parent section↔section: reorder is the default intent (not nest-into).
 * Nest still available via Alt (hold) while dropping on the other section.
 */
function shouldPreferSiblingReorder(source, target, { altKey = false, nestIntentX = false } = {}) {
    if (altKey) {
        return false;
    }

    if (source.parent?.() !== target.parent?.()) {
        return false;
    }

    // Right-edge nest intent still nests non-section hosts; for section↔section
    // keep reorder default — Alt is the explicit nest gesture.
    if (isSectionLikeLayer(source) && isSectionLikeLayer(target)) {
        return true;
    }

    return false;
}

function getLayerRootElement(component) {
    return component?.viewLayer?.el ?? null;
}

function ensureInsertCue(mount) {
    let cue = mount.querySelector(`[${LAYER_INSERT_CUE_ATTR}]`);

    if (cue) {
        // Drop legacy label nodes from older builds.
        cue.querySelector('.voodbuilder-editor-layer-insert-label')?.remove();

        return cue;
    }

    cue = document.createElement('div');
    cue.setAttribute(LAYER_INSERT_CUE_ATTR, '1');
    cue.className = 'voodbuilder-editor-layer-insert-cue';
    cue.hidden = true;
    cue.innerHTML = `<div class="voodbuilder-editor-layer-insert-line" ${LAYER_INSERT_LINE_ATTR}></div>`;
    mount.appendChild(cue);

    return cue;
}

function clearDropScopeHighlight(mount) {
    mount.querySelectorAll('.voodbuilder-editor-layer-drop-blocked').forEach((el) => {
        el.classList.remove('voodbuilder-editor-layer-drop-blocked');
    });
}

function hideInsertLine(mount) {
    const cue = mount.querySelector(`[${LAYER_INSERT_CUE_ATTR}]`);

    if (cue) {
        cue.hidden = true;
        cue.removeAttribute('data-mode');
        cue.removeAttribute('data-blocked');
    }

    const line = mount.querySelector(`[${LAYER_INSERT_LINE_ATTR}]`);

    if (line) {
        line.style.height = '';
        line.style.width = '';
        line.style.left = '';
        line.style.top = '';
    }

    mount.querySelectorAll(
        '.voodbuilder-editor-layer-drop-into, .voodbuilder-editor-layer-drop-beside, .voodbuilder-editor-layer-drop-blocked',
    ).forEach((el) => {
        el.classList.remove(
            'voodbuilder-editor-layer-drop-into',
            'voodbuilder-editor-layer-drop-beside',
            'voodbuilder-editor-layer-drop-blocked',
        );
    });
}

/**
 * Editor nests via padding-left on .gjs-layer-title — the title box itself
 * stays full-width. Align cues to the visible name text instead.
 */
function getLayerContentLeft(layerEl) {
    const name = layerEl.querySelector?.('[data-name]');

    if (name) {
        return name.getBoundingClientRect().left;
    }

    const inn = layerEl.querySelector?.('.gjs-layer-title-inn');

    if (inn) {
        return inn.getBoundingClientRect().left;
    }

    const title = layerEl.querySelector?.('.gjs-layer-title');

    if (title) {
        const pad = Number.parseFloat(window.getComputedStyle(title).paddingLeft) || 0;

        return title.getBoundingClientRect().left + pad;
    }

    const item = layerEl.querySelector?.('.gjs-layer-item') ?? layerEl;

    return item.getBoundingClientRect().left + 28;
}

function getLayerInsertBand(mount, layerEl, { nestInto = false } = {}) {
    const mountRect = mount.getBoundingClientRect();
    const item = layerEl.querySelector?.('.gjs-layer-item') ?? layerEl;
    const itemRect = item.getBoundingClientRect();

    let leftPx = getLayerContentLeft(layerEl);

    if (nestInto) {
        leftPx += LAYER_NEST_INDENT_PX;
    }

    // Bricks-like: indent on the left, stretch to the panel edge on the right.
    const rightPx = mountRect.right - 10;
    const width = Math.max(56, rightPx - leftPx);
    const left = Math.max(0, leftPx - mountRect.left);

    return { left, width, itemRect, mountRect };
}

function markBlockedLayerAtPoint(mount, editor, source, clientX, clientY) {
    mount.querySelectorAll('.voodbuilder-editor-layer-drop-blocked').forEach((el) => {
        el.classList.remove('voodbuilder-editor-layer-drop-blocked');
    });

    const layerEl = findLayerElementAtPoint(mount, clientX, clientY);

    if (! layerEl) {
        return;
    }

    const target = resolveComponentFromLayerElement(layerEl, editor);

    if (! isValidGrapesComponent(target) || target === source || isAncestorComponent(source, target)) {
        return;
    }

    // Only flag hard-invalid hosts (chrome / locked / catalog-into-catalog).
    const parent = target.parent?.();
    const intoBlocked = isLikelyNestHost(target) && ! canMoveIntoParent(editor, target, source);
    const besideBlocked = parent && ! canMoveIntoParent(editor, parent, source);

    if (intoBlocked || besideBlocked || isSiteChromeBlock(target, editor) || isProtectedSlot(target)) {
        layerEl.classList.add('voodbuilder-editor-layer-drop-blocked');
    }
}

function showInsertLine(mount, drop) {
    const cue = ensureInsertCue(mount);
    const nestInto = drop.mode === 'into';
    const { left, width, itemRect, mountRect } = getLayerInsertBand(mount, drop.layerEl, { nestInto });
    const line = cue.querySelector(`[${LAYER_INSERT_LINE_ATTR}]`);

    mount.querySelectorAll(
        '.voodbuilder-editor-layer-drop-into, .voodbuilder-editor-layer-drop-beside, .voodbuilder-editor-layer-drop-blocked',
    ).forEach((el) => {
        el.classList.remove(
            'voodbuilder-editor-layer-drop-into',
            'voodbuilder-editor-layer-drop-beside',
            'voodbuilder-editor-layer-drop-blocked',
        );
    });

    cue.hidden = false;
    cue.setAttribute('data-mode', drop.mode);
    cue.removeAttribute('data-blocked');

    if (nestInto) {
        drop.layerEl.classList.add('voodbuilder-editor-layer-drop-into');
        line.style.top = `${Math.max(0, itemRect.top - mountRect.top + mount.scrollTop + 3)}px`;
        line.style.height = `${Math.max(18, itemRect.height - 6)}px`;
    } else {
        drop.layerEl.classList.add('voodbuilder-editor-layer-drop-beside');
        const y = (drop.placeBefore ? itemRect.top : itemRect.bottom) - mountRect.top + mount.scrollTop;
        line.style.top = `${Math.max(0, y - 1)}px`;
        line.style.height = '';
    }

    line.style.left = `${left}px`;
    line.style.width = `${width}px`;
}

function isDroppableLayerHost(component) {
    if (! component?.get) {
        return false;
    }

    const droppable = component.get('droppable');

    if (droppable === false) {
        return false;
    }

    return true;
}

function rejectsCatalogSectionNesting(source, dropParent) {
    // Layer DnD trusts the author: catalog sections may nest into layout hosts
    // (Section / Container / Div). Only block dropping a catalog section *into*
    // another catalog section root (ambiguous / usually accidental).
    if (! isCatalogSection(source) || ! dropParent) {
        return false;
    }

    return isCatalogSection(dropParent);
}

function canMoveIntoParent(editor, parent, source, index = 0) {
    if (! parent || ! source || parent === source || isAncestorComponent(source, parent)) {
        return false;
    }

    if (isProtectedSlot(parent)) {
        return false;
    }

    // Never nest into the interior of a dynamic companion block (event listings,
    // testimonials, …). Reorder beside the block root instead.
    if (isInsideDynamicBlockInterior(parent)) {
        return false;
    }

    // Page content is the intended host for top-level sections — allow reorder
    // and drops into it. Header/footer/shell stay locked.
    if (isSiteChromeBlock(parent, editor) && ! isPageContentSlotComponent(parent)) {
        return false;
    }

    if (rejectsCatalogSectionNesting(source, parent)) {
        return false;
    }

    const droppable = parent.get?.('droppable');

    // Hard-closed hosts (e.g. text leaves). Function droppables may reject
    // sections — Layers still allows the move so authors keep full control.
    if (droppable === false) {
        return false;
    }

    return true;
}

/**
 * True when the component lives under a `[data-voodbuilder-block]` root
 * (not the root itself).
 */
function isInsideDynamicBlockInterior(component) {
    let current = component;

    while (current) {
        const attrs = current.getAttributes?.() ?? {};

        if (attrs['data-voodbuilder-block']) {
            return current !== component;
        }

        current = current.parent?.();
    }

    return false;
}

function isLikelyNestHost(component) {
    if (! isDroppableLayerHost(component)) {
        return false;
    }

    const childCount = component.components?.()?.length ?? 0;

    if (childCount > 0) {
        return true;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();
    const type = String(component.get('type') ?? '').toLowerCase();

    return ['div', 'section', 'article', 'main', 'aside', 'nav', 'header', 'footer'].includes(tag)
        || type.includes('container')
        || type.includes('layout')
        || type.includes('section');
}

function findLayerElementAtPoint(mount, clientX, clientY) {
    const stack = document.elementsFromPoint?.(clientX, clientY) ?? [document.elementFromPoint(clientX, clientY)];

    for (const el of stack) {
        if (! el || ! mount.contains(el)) {
            continue;
        }

        const item = el.classList?.contains('gjs-layer-item')
            ? el
            : el.closest?.('.gjs-layer-item');

        if (! item || ! mount.contains(item)) {
            continue;
        }

        // Prefer the row under the cursor (item's owning layer), not a nested
        // .gjs-layer accidentally matched via closest from a child node.
        const layerEl = item.parentElement?.classList?.contains('gjs-layer')
            ? item.parentElement
            : item.closest?.('.gjs-layer');

        if (layerEl && mount.contains(layerEl)) {
            return layerEl;
        }
    }

    return null;
}

function resolveDropAtPoint(mount, editor, source, clientX, clientY, pointer = {}) {
    const layerEl = findLayerElementAtPoint(mount, clientX, clientY);

    if (! layerEl) {
        return null;
    }

    const target = resolveComponentFromLayerElement(layerEl, editor);

    if (! isValidGrapesComponent(target) || target === source || isAncestorComponent(source, target)) {
        return null;
    }

    if (isSiteChromeBlock(target, editor) || isProtectedSlot(target)) {
        return null;
    }

    if (! shouldEnableLayerReorder(source, editor)) {
        return null;
    }

    const item = layerEl.querySelector?.('.gjs-layer-item') ?? layerEl;
    const rect = item.getBoundingClientRect();
    const yRatio = rect.height > 0
        ? (clientY - rect.top) / rect.height
        : 0.5;
    const nestIntentX = rect.width > 0
        ? (clientX - rect.left) / rect.width >= 0.62
        : false;
    const altKey = Boolean(pointer.altKey);
    const preferSibling = shouldPreferSiblingReorder(source, target, { altKey, nestIntentX });
    const inMiddleBand = yRatio >= LAYER_SIBLING_EDGE_RATIO && yRatio <= (1 - LAYER_SIBLING_EDGE_RATIO);

    // Nest into host (middle band). Sibling sections skip this unless Alt / right-drag.
    if (isLikelyNestHost(target) && inMiddleBand && ! preferSibling) {
        const intoIndex = target.components?.()?.length ?? 0;

        if (canMoveIntoParent(editor, target, source, intoIndex)) {
            ensureLayerOpen(editor, target);

            return {
                mode: 'into',
                parent: target,
                target,
                layerEl,
                placeBefore: false,
                index: intoIndex,
            };
        }
    }

    const parent = target.parent?.();

    if (! parent) {
        return null;
    }

    // Edge bands + middle-as-sibling when nest is declined → reliable up/down reorder.
    const placeBefore = yRatio < 0.5;

    return normalizeSiblingDrop(editor, source, parent, target, layerEl, placeBefore);
}

/**
 * Sibling inserts are always expressed as "before the layer that stays below"
 * when a next sibling exists. That way the blue cue sits on the release slot
 * (top of the lower neighbor), not under the upper neighbor.
 *
 * `index` is the Editor `move({ at })` value in *pre-removal* collection
 * coordinates (Editor itself adjusts when moving down).
 */
function normalizeSiblingDrop(editor, source, parent, target, layerEl, placeBefore) {
    const collection = parent.components?.();

    if (! collection) {
        return null;
    }

    const targetIndex = collection.indexOf(target);

    if (targetIndex < 0) {
        return null;
    }

    let anchor = target;
    let anchorEl = layerEl;
    let before = placeBefore;
    let at = before ? targetIndex : targetIndex + 1;

    if (! before) {
        const next = collectionAt(collection, targetIndex + 1);

        if (
            isValidGrapesComponent(next)
            && next !== source
            && ! isAncestorComponent(source, next)
        ) {
            const nextEl = getLayerRootElement(next);

            if (nextEl) {
                // after(target) ≡ before(next) — cue on next's top edge.
                anchor = next;
                anchorEl = nextEl;
                before = true;
                at = targetIndex + 1;
            }
        }
    }

    const fromIndex = collection.indexOf(source);

    // Editor Component.move no-ops when at === from || at === from + 1.
    // Hovering the top of the immediate next sibling while dragging down must
    // mean "move past that sibling", not a silent no-op.
    if (fromIndex >= 0 && source.parent?.() === parent && at === fromIndex + 1) {
        const pass = collectionAt(collection, fromIndex + 1);

        if (isValidGrapesComponent(pass) && pass !== source) {
            const afterPass = collectionAt(collection, fromIndex + 2);

            if (isValidGrapesComponent(afterPass) && getLayerRootElement(afterPass)) {
                anchor = afterPass;
                anchorEl = getLayerRootElement(afterPass);
                before = true;
                at = fromIndex + 2;
            } else {
                anchor = pass;
                anchorEl = getLayerRootElement(pass) ?? layerEl;
                before = false;
                at = fromIndex + 2;
            }
        }
    }

    if (! canMoveIntoParent(editor, parent, source, Math.min(at, collection.length))) {
        return null;
    }

    return {
        mode: 'sibling',
        parent,
        target: anchor,
        layerEl: anchorEl,
        placeBefore: before,
        index: at,
    };
}

function collectionAt(collection, index) {
    if (! collection || index < 0) {
        return null;
    }

    if (typeof collection.at === 'function') {
        return collection.at(index) ?? null;
    }

    return collection.models?.[index] ?? collection[index] ?? null;
}

function applyLayerMove(source, drop) {
    if (! drop?.parent || typeof source?.move !== 'function') {
        return false;
    }

    const parent = drop.parent;
    const collection = parent.components?.();

    if (! collection) {
        return false;
    }

    let at = drop.mode === 'into'
        ? collection.length
        : Number.isInteger(drop.index)
            ? drop.index
            : collection.indexOf(drop.target);

    if (drop.mode !== 'into' && ! Number.isInteger(drop.index)) {
        if (at < 0) {
            return false;
        }

        if (! drop.placeBefore) {
            at += 1;
        }
    }

    const sameParent = source.parent?.() === parent;

    if (sameParent) {
        const fromIndex = collection.indexOf(source);

        if (fromIndex < 0) {
            return false;
        }

        // Editor already compensates moving down (at > from → at-1) and treats
        // at === from / at === from+1 as no-ops. Do not pre-decrement here.
        if (at === fromIndex || at === fromIndex + 1) {
            return false;
        }

        at = Math.max(0, Math.min(at, collection.length));
    } else {
        at = Math.max(0, Math.min(at, collection.length));
    }

    // Editor move() relocates the whole subtree (parent keeps all children).
    source.move(parent, { at });

    return true;
}

function startCustomLayerReorder(editor, mount, component, event) {
    beginLayerTreeSorting(editor);
    event.preventDefault();
    event.stopPropagation();

    if (typeof event.stopImmediatePropagation === 'function') {
        event.stopImmediatePropagation();
    }

    const openSnapshot = snapshotOpenLayers(editor);

    pinLayersSelection(editor, component);
    selectFromLayers(editor, component);
    // Undo Editor auto-collapse of non-ancestor layers (Container/Div, etc.).
    restoreOpenLayers(openSnapshot);

    const sourceLayerEl = getLayerRootElement(component);
    sourceLayerEl?.classList?.add('voodbuilder-editor-layer-dragging');

    const startX = event.clientX;
    const startY = event.clientY;
    let started = false;
    let drop = null;

    const onMove = (moveEvent) => {
        const dx = Math.abs(moveEvent.clientX - startX);
        const dy = Math.abs(moveEvent.clientY - startY);

        if (! started) {
            if (dx < LAYER_SORT_MOVE_THRESHOLD_PX && dy < LAYER_SORT_MOVE_THRESHOLD_PX) {
                return;
            }

            started = true;
            mount.classList.add('voodbuilder-editor-layers-sorting');
            restoreOpenLayers(openSnapshot);
        }

        moveEvent.preventDefault();
        drop = resolveDropAtPoint(
            mount,
            editor,
            component,
            moveEvent.clientX,
            moveEvent.clientY,
            { altKey: moveEvent.altKey },
        );

        if (drop) {
            showInsertLine(mount, drop);
        } else {
            hideInsertLine(mount);
            markBlockedLayerAtPoint(mount, editor, component, moveEvent.clientX, moveEvent.clientY);
        }
    };

    const cleanup = () => {
        window.removeEventListener('pointermove', onMove, true);
        window.removeEventListener('pointerup', onUp, true);
        window.removeEventListener('pointercancel', onUp, true);
        sourceLayerEl?.classList?.remove('voodbuilder-editor-layer-dragging');
        mount.classList.remove('voodbuilder-editor-layers-sorting');
        hideInsertLine(mount);
        clearDropScopeHighlight(mount);
    };

    const onUp = () => {
        cleanup();

        if (started && drop) {
            applyLayerMove(component, drop);
            pinLayersSelection(editor, component);
            selectFromLayers(editor, component);
            restoreOpenLayers(openSnapshot);
        }

        endLayerTreeSorting(editor);
        clearCanvasDropCues(editor);
    };

    window.addEventListener('pointermove', onMove, true);
    window.addEventListener('pointerup', onUp, true);
    window.addEventListener('pointercancel', onUp, true);
}

function handleLayerMouseDown(editor, mount, event) {
    if (event.button !== 0) {
        return;
    }

    const onHandle = event.target?.closest?.('[data-toggle-move]');
    const onRow = ! onHandle && isRowDragTarget(event.target);

    if (! onHandle && ! onRow) {
        return;
    }

    if (onHandle && isLockedLayerMoveHandle(event.target)) {
        return;
    }

    const layerItem = event.target?.closest?.('.gjs-layer-item')
        ?? event.target?.closest?.('.gjs-layer');

    if (! layerItem) {
        return;
    }

    const component = resolveComponentFromLayerElement(layerItem, editor);

    if (! component) {
        return;
    }

    ensureLayerDraggable(component, editor);

    if (! canStartLayerSort(component, editor)) {
        return;
    }

    // Own the gesture — do not let Editor ItemView.startSort run (broken sorter).
    startCustomLayerReorder(editor, mount, component, event);
}

export function registerLayersDrag(editor, options = {}) {
    const mount = options.mount;

    if (! mount || editor.__voodbuilderLayersDragRegistered) {
        return;
    }

    editor.__voodbuilderLayersDragRegistered = true;

    const refreshLayers = () => {
        if (isLayerTreeSorting(editor)) {
            return;
        }

        syncAllLayerDraggable(editor);
    };

    editor.on('load', refreshLayers);
    editor.on('component:add', (component) => {
        if (isLayerTreeSorting(editor)) {
            return;
        }

        ensureLayerDraggable(component, editor);
        component.viewLayer?.updateMove?.();

        let parent = component.parent?.();

        while (parent) {
            ensureLayerDraggable(parent, editor);
            parent.viewLayer?.updateMove?.();
            parent = parent.parent?.();
        }
    });

    editor.on('voodbuilder:layers-panel:show', refreshLayers);
    editor.on('sorter:drag:end', () => endLayerTreeSorting(editor));
    editor.on('component:drag:end', () => endLayerTreeSorting(editor));

    // Keep the exact layer the author clicked — promote/hit-test must not jump to a parent
    // (common with animate-* plasma blobs inside pointer-events-none overlays).
    editor.on('component:selected', (component, opts = {}) => {
        if (opts?.fromLayers && isValidGrapesComponent(component)) {
            pinLayersSelection(editor, component);
            clearCanvasDropCues(editor);

            return;
        }

        const pinned = editor.__voodbuilderLayersSelectionPin;
        const until = Number(editor.__voodbuilderLayersSelectionPinUntil ?? 0);

        if (! pinned || Date.now() >= until) {
            if (pinned) {
                clearLayersSelectionPin(editor);
            }

            return;
        }

        if (component === pinned) {
            return;
        }

        if (isAncestorComponent(component, pinned) || ! component) {
            window.requestAnimationFrame(() => restorePinnedLayersSelection(editor));
        } else {
            clearLayersSelectionPin(editor);
        }
    });

    // Capture: stop Editor native layer sorter before it starts.
    mount.addEventListener('pointerdown', (event) => {
        handleLayerMouseDown(editor, mount, event);
    }, true);

    mount.addEventListener('click', (event) => {
        const layer = event.target.closest?.('.gjs-layer');

        if (! layer) {
            return;
        }

        const model = layer.__gjsv?.model ?? resolveComponentFromLayerElement(layer, editor);

        if (isValidGrapesComponent(model)) {
            pinLayersSelection(editor, model);

            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        sanitizeEditorLayerTree(editor);
        safeRenderEditorLayers(editor, { immediate: true });
    }, true);
}
