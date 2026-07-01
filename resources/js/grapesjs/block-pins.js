/**
 * Pin frequently used blocks to a dedicated "Pinned" category (Bricks-style).
 */

import { isComponentBlockElement, isComponentBlockId, resolveBlockFromElement } from './component-block-utils.js';
import { lucideIcon } from './editor-icons.js';
import { resolveCategoryOrder } from './section-block-meta.js';

export const PINNED_CATEGORY_ID = 'Pinned';
export const PINNED_BLOCK_PREFIX = 'vb-pinned-';
const STORAGE_KEY_PREFIX = 'voodbuilder:block-pins:v1';

export function isPinnedBlockId(blockId) {
    return String(blockId ?? '').startsWith(PINNED_BLOCK_PREFIX);
}

export function isPinnedCategoryId(categoryId) {
    return String(categoryId ?? '') === PINNED_CATEGORY_ID;
}

export function resolvePinnedSourceId(block) {
    const blockId = String(block?.get?.('id') ?? block?.id ?? '');

    if (isPinnedBlockId(blockId)) {
        return blockId.slice(PINNED_BLOCK_PREFIX.length);
    }

    const alias = block?.get?.('attributes')?.['data-voodbuilder-pinned-alias'];

    return alias ? String(alias) : blockId;
}

function storageKey(scope) {
    return `${STORAGE_KEY_PREFIX}:${scope}`;
}

function loadPinnedIds(scope = 'global') {
    try {
        const raw = window.localStorage.getItem(storageKey(scope));

        if (! raw) {
            return [];
        }

        const parsed = JSON.parse(raw);

        return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch {
        return [];
    }
}

function savePinnedIds(scope, ids) {
    try {
        window.localStorage.setItem(storageKey(scope), JSON.stringify([...new Set(ids.map(String))]));
    } catch {
        // Ignore quota / private mode errors.
    }
}

function isPinnableBlock(editor, blockId) {
    const id = String(blockId ?? '');

    if (! id || isPinnedBlockId(id) || isComponentBlockId(id)) {
        return false;
    }

    return Boolean(editor.BlockManager?.get?.(id));
}

function ensurePinnedCategory(editor, labels = {}) {
    const categories = editor.BlockManager.getCategories?.();

    if (! categories) {
        return;
    }

    if (! categories.get?.(PINNED_CATEGORY_ID)) {
        categories.add({
            id: PINNED_CATEGORY_ID,
            label: String(labels.blockPinnedCategory ?? 'Pinned').toUpperCase(),
            order: resolveCategoryOrder(PINNED_CATEGORY_ID),
            open: true,
        });
    } else {
        const category = categories.get(PINNED_CATEGORY_ID);
        category.set('label', String(labels.blockPinnedCategory ?? 'Pinned').toUpperCase());
        category.set('order', resolveCategoryOrder(PINNED_CATEGORY_ID));
    }
}

function removePinnedAliases(editor) {
    const blockManager = editor.BlockManager;
    const toRemove = [];

    blockManager.getAll().forEach((block) => {
        const blockId = block.get?.('id') ?? block.id;

        if (isPinnedBlockId(blockId)) {
            toRemove.push(blockId);
        }
    });

    toRemove.forEach((blockId) => {
        if (blockManager.get(blockId)) {
            blockManager.remove(blockId);
        }
    });
}

function cloneBlockAsPinned(editor, sourceId) {
    const blockManager = editor.BlockManager;
    const source = blockManager.get(sourceId);

    if (! source) {
        return;
    }

    const aliasId = `${PINNED_BLOCK_PREFIX}${sourceId}`;

    if (blockManager.get(aliasId)) {
        blockManager.remove(aliasId);
    }

    blockManager.add(aliasId, {
        label: source.get('label'),
        category: PINNED_CATEGORY_ID,
        content: source.get('content'),
        media: source.get('media'),
        attributes: {
            ...(source.get('attributes') ?? {}),
            class: 'voodbuilder-gjs-pinned-block',
            'data-voodbuilder-pinned-alias': sourceId,
            title: source.get('attributes')?.title ?? source.get('label'),
        },
        select: source.get('select'),
        activate: source.get('activate'),
    });
}

export function syncPinnedBlocks(editor, labels = {}, scope = 'global') {
    const pinnedIds = loadPinnedIds(scope).filter((id) => isPinnableBlock(editor, id));

    if (pinnedIds.length !== loadPinnedIds(scope).length) {
        savePinnedIds(scope, pinnedIds);
    }

    removePinnedAliases(editor);

    if (pinnedIds.length === 0) {
        const category = editor.BlockManager.getCategories?.()?.get?.(PINNED_CATEGORY_ID);

        if (category) {
            editor.BlockManager.getCategories().remove(category);
        }

        return pinnedIds;
    }

    ensurePinnedCategory(editor, labels);

    for (const sourceId of pinnedIds) {
        cloneBlockAsPinned(editor, sourceId);
    }

    const pinnedCategory = editor.BlockManager.getCategories?.()?.get?.(PINNED_CATEGORY_ID);
    pinnedCategory?.set('open', true);

    return pinnedIds;
}

function resolveSourceIdFromElement(editor, blockEl) {
    const block = resolveBlockFromElement(editor, blockEl);

    if (! block) {
        return null;
    }

    return resolvePinnedSourceId(block);
}

function decorateBlockPins(editor, mount, pinnedIds, labels = {}) {
    if (! mount || (editor.__voodbuilderActiveLibrary ?? 'blocks') !== 'blocks') {
        return;
    }

    const pinnedSet = new Set(pinnedIds);

    mount.querySelectorAll('.gjs-block').forEach((blockEl) => {
        if (isComponentBlockElement(editor, blockEl)) {
            return;
        }

        const sourceId = resolveSourceIdFromElement(editor, blockEl);

        if (! sourceId || ! isPinnableBlock(editor, sourceId)) {
            blockEl.querySelector('.voodbuilder-gjs-block-pin')?.remove();
            blockEl.classList.remove('is-pinned');

            return;
        }

        const isPinned = pinnedSet.has(sourceId);
        blockEl.classList.toggle('is-pinned', isPinned);

        let button = blockEl.querySelector('.voodbuilder-gjs-block-pin');

        if (! button) {
            button = document.createElement('button');
            button.type = 'button';
            button.className = 'voodbuilder-gjs-block-pin';
            button.addEventListener('mousedown', (event) => {
                event.stopPropagation();
            });
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                editor.__voodbuilderToggleBlockPin?.(sourceId);
            });
            blockEl.appendChild(button);
        }

        button.dataset.voodbuilderPinSource = sourceId;
        button.setAttribute(
            'aria-label',
            isPinned
                ? (labels.blockUnpin ?? 'Unpin')
                : (labels.blockPin ?? 'Pin'),
        );
        button.setAttribute('aria-pressed', isPinned ? 'true' : 'false');
        button.classList.toggle('is-active', isPinned);
        button.innerHTML = lucideIcon(isPinned ? 'pin-off' : 'pin', 14);
        button.title = isPinned
            ? (labels.blockUnpin ?? 'Unpin')
            : (labels.blockPin ?? 'Pin');
    });
}

function refreshBlockPins(editor, { sync = true } = {}) {
    const state = editor.__voodbuilderBlockPins;

    if (! state || editor.__voodbuilderBlockPinsSyncing) {
        return;
    }

    let pinnedIds = loadPinnedIds(state.scope);

    if (sync) {
        editor.__voodbuilderBlockPinsSyncing = true;
        pinnedIds = syncPinnedBlocks(editor, state.labels, state.scope);
        editor.BlockManager?.render?.();
        editor.__voodbuilderBlockPinsSyncing = false;
    }

    const mount = editor.BlockManager?.getContainer?.() ?? state.blocksMount;
    decorateBlockPins(editor, mount, pinnedIds, state.labels);
}

let pinSyncTimer = null;

function scheduleBlockPinRefresh(editor, { sync = true, immediate = false } = {}) {
    window.clearTimeout(pinSyncTimer);

    if (immediate) {
        refreshBlockPins(editor, { sync });

        return;
    }

    pinSyncTimer = window.setTimeout(() => {
        refreshBlockPins(editor, { sync });
    }, 120);
}

export function refreshBlockPinUi(editor, options = {}) {
    refreshBlockPins(editor, options);
}

export function registerBlockPins(editor, options = {}) {
    const {
        blocksMount = null,
        labels = {},
        storageScope = 'global',
    } = options;

    if (! blocksMount || editor.__voodbuilderBlockPins) {
        return;
    }

    editor.__voodbuilderBlockPins = { blocksMount, labels, scope: storageScope };

    editor.__voodbuilderToggleBlockPin = (sourceId) => {
        const scope = editor.__voodbuilderBlockPins.scope;
        const ids = loadPinnedIds(scope);
        const next = ids.includes(String(sourceId))
            ? ids.filter((id) => id !== String(sourceId))
            : [...ids, String(sourceId)];

        savePinnedIds(scope, next);
        scheduleBlockPinRefresh(editor, { sync: true, immediate: true });
    };

    editor.on('load', () => scheduleBlockPinRefresh(editor, { sync: true, immediate: true }));
    editor.on('block:add', () => {
        if (! editor.__voodbuilderBlockPinsSyncing) {
            scheduleBlockPinRefresh(editor, { sync: true });
        }
    });
    editor.on('block:remove', () => {
        if (! editor.__voodbuilderBlockPinsSyncing) {
            scheduleBlockPinRefresh(editor, { sync: true });
        }
    });

    if (editor.getWrapper?.()) {
        scheduleBlockPinRefresh(editor, { sync: true, immediate: true });
    }
}
