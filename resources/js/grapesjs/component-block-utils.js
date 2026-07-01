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
