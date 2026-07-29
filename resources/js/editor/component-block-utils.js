/**
 * Shared helpers for Editor blocks that represent saved components (vb-component-*).
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
        class: 'voodbuilder-editor-component-category',
        'data-voodbuilder-component-category': '1',
        'data-voodbuilder-category-id': String(categoryId),
    };
}

export function isComponentCategoryElement(categoryEl, editor = null) {
    if (! categoryEl) {
        return false;
    }

    if (categoryEl.classList.contains('voodbuilder-editor-component-category')) {
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

    const domBlockId = blockEl.getAttribute?.('data-gjs-block-id') || blockEl.id;

    if (isComponentBlockId(domBlockId)) {
        const byId = editor.BlockManager.get(domBlockId);

        if (byId) {
            return byId;
        }
    }

    let match = null;

    editor.BlockManager.getAll().forEach((candidate) => {
        if (match) {
            return;
        }

        const viewEl = candidate.view?.el;

        if (viewEl === blockEl || viewEl?.contains?.(blockEl) || blockEl?.contains?.(viewEl)) {
            match = candidate;
        }
    });

    return match;
}

export function resolveCatalogItemFromComponentBlock(editor, blockEl) {
    if (! editor || ! blockEl) {
        return null;
    }

    const catalog = editor.__voodbuilderComponentsCatalog ?? [];
    const domBlockId = blockEl.getAttribute?.('data-gjs-block-id') || blockEl.id;

    if (isComponentBlockId(domBlockId)) {
        const itemId = domBlockId.slice(COMPONENT_BLOCK_PREFIX.length);

        return catalog.find((entry) => String(entry.id) === itemId) ?? null;
    }

    const block = resolveBlockFromElement(editor, blockEl);

    if (! isComponentBlock(block)) {
        return null;
    }

    const itemId = String(block.get?.('id') ?? block.id).slice(COMPONENT_BLOCK_PREFIX.length);

    return catalog.find((entry) => String(entry.id) === itemId) ?? null;
}

export function isComponentBlock(block) {
    return isComponentBlockId(block?.get?.('id') ?? block?.id);
}

export function isComponentBlockElement(editor, blockEl) {
    if (! blockEl) {
        return false;
    }

    if (blockEl.classList?.contains('voodbuilder-editor-component-block')) {
        return true;
    }

    if (blockEl.hasAttribute?.('data-voodbuilder-component-block')) {
        return true;
    }

    const domBlockId = blockEl.getAttribute?.('data-gjs-block-id') || blockEl.id;

    if (isComponentBlockId(domBlockId)) {
        return true;
    }

    return isComponentBlock(resolveBlockFromElement(editor, blockEl));
}
