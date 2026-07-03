/**
 * Single source of truth for shared Elements / Components block library visibility.
 */

import { isComponentBlock, isComponentBlockElement, isComponentCategoryElement, isComponentCategoryId } from './component-block-utils.js';

export function collapseLibraryCategories(editor, libraryId = editor.__voodbuilderActiveLibrary ?? 'blocks') {
    const showComponents = libraryId === 'components';

    editor.BlockManager.getCategories?.()?.each?.((category) => {
        const categoryId = String(category.get('id') ?? '');
        const isComponentCategory = isComponentCategoryId(categoryId);

        if (showComponents !== isComponentCategory) {
            return;
        }

        category.set('open', false);
    });
}

export function collapseAllBlockCategories(editor) {
    editor.BlockManager.getCategories?.()?.each?.((category) => {
        category.set('open', false);
    });
}

function setBlockDisplay(blockEl, visible) {
    if (! blockEl) {
        return;
    }

    if (visible) {
        blockEl.style.removeProperty('display');
    } else {
        blockEl.style.display = 'none';
    }
}

export function applyBlocksLibraryUi(editor, searchQuery = '') {
    const container = editor.BlockManager?.getContainer?.();

    if (! container) {
        return;
    }

    const libraryId = editor.__voodbuilderActiveLibrary ?? 'blocks';
    const query = String(searchQuery ?? '').trim().toLowerCase();
    const showComponents = libraryId === 'components';
    const openCategories = new Set();

    editor.BlockManager.getAll().forEach((block) => {
        const isComponent = isComponentBlock(block);
        const inActiveLibrary = showComponents ? isComponent : ! isComponent;
        const label = String(block.get('label') ?? '').toLowerCase();
        const matchesQuery = ! query || label.includes(query);

        if (! inActiveLibrary || ! matchesQuery) {
            return;
        }

        const category = block.get('category');
        const categoryId = category?.get?.('id') ?? category;

        if (categoryId) {
            openCategories.add(String(categoryId));
        }
    });

    container.querySelectorAll('.gjs-block').forEach((blockEl) => {
        const isComponent = isComponentBlockElement(editor, blockEl);
        const inActiveLibrary = showComponents ? isComponent : ! isComponent;
        const label = blockEl.textContent?.toLowerCase() ?? '';
        const matchesQuery = ! query || label.includes(query);

        setBlockDisplay(blockEl, inActiveLibrary && matchesQuery);
    });

    editor.BlockManager.getCategories?.()?.each?.((category) => {
        const categoryId = String(category.get('id') ?? '');
        const isComponentCategory = isComponentCategoryId(categoryId);
        const categoryEl = category.view?.el;

        if (! categoryEl) {
            return;
        }

        categoryEl.classList.toggle('voodbuilder-gjs-component-category', isComponentCategory);

        if (isComponentCategory) {
            categoryEl.setAttribute('data-voodbuilder-component-category', '1');
            categoryEl.setAttribute('data-voodbuilder-category-id', categoryId);
        } else {
            categoryEl.removeAttribute('data-voodbuilder-component-category');
            categoryEl.removeAttribute('data-voodbuilder-category-id');
        }

        const inActiveLibrary = showComponents ? isComponentCategory : ! isComponentCategory;

        if (! inActiveLibrary) {
            setBlockDisplay(categoryEl, false);

            return;
        }

        const hasVisible = [...categoryEl.querySelectorAll('.gjs-block')].some(
            (blockEl) => blockEl.style.display !== 'none',
        );

        setBlockDisplay(categoryEl, hasVisible);

        if (query && hasVisible) {
            category.set('open', openCategories.has(categoryId));
        }
    });

    container.querySelectorAll('.gjs-block-category').forEach((categoryEl) => {
        const isComponentCategory = isComponentCategoryElement(categoryEl, editor);

        categoryEl.classList.toggle('voodbuilder-gjs-component-category', isComponentCategory);

        const inActiveLibrary = showComponents ? isComponentCategory : ! isComponentCategory;

        if (! inActiveLibrary) {
            setBlockDisplay(categoryEl, false);

            return;
        }

        const hasVisible = [...categoryEl.querySelectorAll('.gjs-block')].some(
            (blockEl) => blockEl.style.display !== 'none',
        );

        setBlockDisplay(categoryEl, hasVisible);
    });
}

export function readBlocksSearchQuery() {
    const shell = document.querySelector('.voodbuilder-gjs-shell');

    return shell?.querySelector('.voodbuilder-gjs-blocks-search')?.value?.trim() ?? '';
}
