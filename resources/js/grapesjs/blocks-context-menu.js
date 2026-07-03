/**
 * Context menu for section blocks in the Elements library sidebar.
 */

import { openContextMenu } from './context-menu.js';
import {
    isComponentBlockElement,
    resolveBlockFromElement,
    resolveCatalogItemFromComponentBlock,
} from './component-block-utils.js';
import { isPinnedBlockId, resolvePinnedSourceId } from './block-pins.js';

export function registerBlocksContextMenu(editor, shell, labels = {}) {
    if (! shell || shell.dataset.voodbuilderBlocksMenuBound === 'true') {
        return;
    }

    shell.dataset.voodbuilderBlocksMenuBound = 'true';

    shell.addEventListener('contextmenu', (event) => {
        const blockEl = event.target.closest('.gjs-block');

        if (! blockEl) {
            return;
        }

        if ((editor.__voodbuilderActiveLibrary ?? 'blocks') === 'blocks') {
            return;
        }

        if (isComponentBlockElement(editor, blockEl)) {
            if (editor.__voodbuilderActiveLibrary === 'components' && ! editor.__voodbuilderComponentSelectionMode) {
                const item = resolveCatalogItemFromComponentBlock(editor, blockEl);

                if (item && typeof editor.__voodbuilderComponentLibraryActions?.openMenu === 'function') {
                    event.preventDefault();
                    event.stopPropagation();
                    editor.__voodbuilderComponentLibraryActions.openMenu(item, event.clientX, event.clientY);

                    return;
                }
            }

            if (editor.__voodbuilderActiveLibrary === 'components') {
                event.preventDefault();
            }

            return;
        }

        const block = resolveBlockFromElement(editor, blockEl);

        if (! block) {
            return;
        }

        const blockId = String(block.get?.('id') ?? block.id);

        if (isPinnedBlockId(blockId)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const sourceId = resolvePinnedSourceId(block);
        const togglePin = editor.__voodbuilderToggleBlockPin;
        const pinnedIds = editor.__voodbuilderBlockPins?.pinnedIds ?? new Set();
        const isPinned = pinnedIds.has?.(sourceId) ?? false;

        const items = [];

        if (typeof togglePin === 'function' && sourceId) {
            items.push({
                id: 'pin',
                label: isPinned
                    ? (labels.blockUnpin ?? 'Unpin block')
                    : (labels.blockPin ?? 'Pin block'),
                onSelect: () => {
                    togglePin(sourceId);
                },
            });
        }

        if (items.length === 0) {
            return;
        }

        openContextMenu({
            x: event.clientX,
            y: event.clientY,
            items,
            context: block,
        });
    }, true);
}
