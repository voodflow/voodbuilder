/**
 * Shared helpers for GrapesJS blocks that represent saved components (vb-component-*).
 */

export const COMPONENT_BLOCK_PREFIX = 'vb-component-';
export const COMPONENT_CATEGORY_PREFIX = 'vb-components-';

export function isComponentBlockId(blockId) {
    return String(blockId ?? '').startsWith(COMPONENT_BLOCK_PREFIX);
}

export function isComponentCategoryId(categoryId) {
    return String(categoryId ?? '').startsWith(COMPONENT_CATEGORY_PREFIX);
}

export function componentCategoryAttributes(categoryId) {
    return {
        class: 'voodbuilder-gjs-component-category',
        'data-voodbuilder-component-category': '1',
        'data-voodbuilder-category-id': String(categoryId),
    };
}

export function isComponentCategoryElement(categoryEl, editor = null) {
    if (! categoryEl) {
        return false;
    }

    if (categoryEl.classList.contains('voodbuilder-gjs-component-category')) {
        return true;
    }

    if (categoryEl.hasAttribute('data-voodbuilder-component-category')) {
        return true;
    }

    if (isComponentCategoryId(categoryEl.getAttribute('data-voodbuilder-category-id') ?? '')) {
        return true;
    }

    if (! editor?.BlockManager?.getCategories) {
        return false;
    }

    let matched = false;

    editor.BlockManager.getCategories().each((category) => {
        if (category.view?.el === categoryEl) {
            matched = isComponentCategoryId(String(category.get('id') ?? ''));
        }
    });

    return matched;
}

export function resolveBlockFromElement(editor, blockEl) {
    if (! blockEl || ! editor?.BlockManager) {
        return null;
    }

    return editor.BlockManager.getAll().find((candidate) => {
        const viewEl = candidate.view?.el;

        return viewEl === blockEl || viewEl?.contains?.(blockEl);
    }) ?? null;
}

export function isComponentBlock(block) {
    return isComponentBlockId(block?.get?.('id') ?? block?.id);
}

export function isComponentBlockElement(editor, blockEl) {
    if (blockEl?.classList?.contains('voodbuilder-gjs-component-block')) {
        return true;
    }

    if (blockEl?.hasAttribute?.('data-voodbuilder-component-block')) {
        return true;
    }

    return isComponentBlock(resolveBlockFromElement(editor, blockEl));
}
