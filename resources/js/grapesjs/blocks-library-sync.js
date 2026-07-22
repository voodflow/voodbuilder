/**
 * Single source of truth for shared Elements / Components / Templates block library visibility.
 */

import { isComponentBlock, isComponentBlockElement, isComponentCategoryElement, isComponentCategoryId } from './component-block-utils.js';
import {
    isPageTemplateBlock,
    isPageTemplateBlockElement,
    isPageTemplateCategoryElement,
    isPageTemplateCategoryId,
} from './page-template-block-utils.js';

export function collapseLibraryCategories(editor, libraryId = editor.__voodbuilderActiveLibrary ?? 'blocks') {
    const showComponents = libraryId === 'components';
    const showTemplates = libraryId === 'templates';

    editor.BlockManager.getCategories?.()?.each?.((category) => {
        const categoryId = String(category.get('id') ?? '');
        const isComponentCategory = isComponentCategoryId(categoryId);
        const isTemplateCategory = isPageTemplateCategoryId(categoryId);
        const inActiveLibrary = showComponents
            ? isComponentCategory
            : showTemplates
                ? isTemplateCategory
                : ! isComponentCategory && ! isTemplateCategory;

        if (! inActiveLibrary) {
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

function resolveBlockCategoryId(block) {
    const category = block?.get?.('category');

    if (! category) {
        return '';
    }

    if (typeof category === 'string') {
        return category;
    }

    return String(category.get?.('id') ?? category.id ?? category.get?.('label') ?? '');
}

export function applyBlocksLibraryUi(editor, searchQuery = '') {
    const container = editor.BlockManager?.getContainer?.();

    if (! container) {
        return;
    }

    const libraryId = editor.__voodbuilderActiveLibrary ?? 'blocks';
    const query = String(searchQuery ?? '').trim().toLowerCase();
    const showComponents = libraryId === 'components';
    const showTemplates = libraryId === 'templates';
    const matchingCategoryIds = new Set();

    editor.BlockManager.getAll().forEach((block) => {
        const isComponent = isComponentBlock(block);
        const isTemplate = isPageTemplateBlock(block);
        const inActiveLibrary = showComponents
            ? isComponent
            : showTemplates
                ? isTemplate
                : ! isComponent && ! isTemplate;
        const label = String(block.get('label') ?? '').toLowerCase();
        const matchesQuery = ! query || label.includes(query);

        if (! inActiveLibrary || ! matchesQuery) {
            return;
        }

        const categoryId = resolveBlockCategoryId(block);

        if (categoryId !== '') {
            matchingCategoryIds.add(categoryId);
        }
    });

    container.querySelectorAll('.gjs-block').forEach((blockEl) => {
        const blockId = blockEl.getAttribute?.('data-gjs-block-id') || blockEl.id || '';
        const block = blockId ? editor.BlockManager?.get?.(blockId) : null;
        const isComponent = block ? isComponentBlock(block) : isComponentBlockElement(editor, blockEl);
        const isTemplate = block ? isPageTemplateBlock(block) : isPageTemplateBlockElement(editor, blockEl);
        const inActiveLibrary = showComponents
            ? isComponent
            : showTemplates
                ? isTemplate
                : ! isComponent && ! isTemplate;
        const label = String(block?.get?.('label') ?? blockEl.textContent ?? '').toLowerCase();
        const matchesQuery = ! query || label.includes(query);

        setBlockDisplay(blockEl, inActiveLibrary && matchesQuery);
    });

    editor.BlockManager.getCategories?.()?.each?.((category) => {
        const categoryEl = category.view?.el;
        const categoryId = String(category.get('id') ?? '');

        if (! categoryEl) {
            return;
        }

        const isComponentCategory = isComponentCategoryId(categoryId);
        const isTemplateCategory = isPageTemplateCategoryId(categoryId);

        categoryEl.classList.toggle('voodbuilder-gjs-component-category', isComponentCategory);
        categoryEl.classList.toggle('voodbuilder-gjs-page-template-category', isTemplateCategory);

        if (isComponentCategory) {
            categoryEl.setAttribute('data-voodbuilder-component-category', '1');
            categoryEl.setAttribute('data-voodbuilder-category-id', categoryId);
        } else if (isTemplateCategory) {
            categoryEl.setAttribute('data-voodbuilder-page-template-category', '1');
            categoryEl.setAttribute('data-voodbuilder-category-id', categoryId);
        } else {
            categoryEl.removeAttribute('data-voodbuilder-component-category');
            categoryEl.removeAttribute('data-voodbuilder-page-template-category');
            categoryEl.setAttribute('data-voodbuilder-category-id', categoryId);
        }

        const inActiveLibrary = showComponents
            ? isComponentCategory
            : showTemplates
                ? isTemplateCategory
                : ! isComponentCategory && ! isTemplateCategory;

        if (! inActiveLibrary) {
            setBlockDisplay(categoryEl, false);

            return;
        }

        const hasMatchingBlocks = matchingCategoryIds.has(categoryId);

        setBlockDisplay(categoryEl, hasMatchingBlocks);

        // Search only: expand categories that match. Otherwise leave collapsed/open as-is
        // (libraries start collapsed via collapseLibraryCategories).
        if (query && hasMatchingBlocks) {
            category.set('open', true);
        }
    });
}

export function readBlocksSearchQuery() {
    const shell = document.querySelector('.voodbuilder-gjs-shell');

    return shell?.querySelector('.voodbuilder-gjs-blocks-search')?.value?.trim() ?? '';
}

export function registerBlocksLibraryRenderHook(editor) {
    if (! editor || editor.__voodbuilderBlocksLibraryRenderHookBound) {
        return;
    }

    editor.__voodbuilderBlocksLibraryRenderHookBound = true;

    const reapply = () => {
        window.requestAnimationFrame(() => {
            applyBlocksLibraryUi(editor, readBlocksSearchQuery());
        });
    };

    editor.on('block:add', reapply);
    editor.on('block:remove', reapply);
    editor.on('block:update', reapply);

    const blockManager = editor.BlockManager;

    if (! blockManager?.render || blockManager.__voodbuilderRenderWrapped) {
        return;
    }

    blockManager.__voodbuilderRenderWrapped = true;
    const originalRender = blockManager.render.bind(blockManager);

    blockManager.render = (...args) => {
        const result = originalRender(...args);

        reapply();
        editor.__voodbuilderOnTemplateLibraryRefresh?.();

        return result;
    };
}
