/**
 * GrapesJS blocks that represent full page templates (vb-page-template-*).
 */

export const PAGE_TEMPLATE_BLOCK_PREFIX = 'vb-page-template-';
export const PAGE_TEMPLATE_CATEGORY_PREFIX = 'vb-page-templates-';
export const PAGE_TEMPLATE_DROP_ATTR = 'data-voodbuilder-page-template-drop';

export function isPageTemplateBlockId(blockId) {
    return String(blockId ?? '').startsWith(PAGE_TEMPLATE_BLOCK_PREFIX);
}

export function isPageTemplateCategoryId(categoryId) {
    return String(categoryId ?? '').startsWith(PAGE_TEMPLATE_CATEGORY_PREFIX);
}

export function isPageTemplateBlock(block) {
    return isPageTemplateBlockId(String(block?.get?.('id') ?? block?.id ?? ''));
}

export function isPageTemplateBlockElement(editor, blockEl) {
    if (! blockEl) {
        return false;
    }

    if (blockEl.classList?.contains('voodbuilder-gjs-page-template-block')) {
        return true;
    }

    const blockId = blockEl.getAttribute?.('data-gjs-block-id') || blockEl.id || '';

    if (isPageTemplateBlockId(blockId)) {
        return true;
    }

    return Boolean(blockEl.closest?.('.voodbuilder-gjs-page-template-block'));
}

export function isPageTemplateCategoryElement(categoryEl) {
    if (! categoryEl) {
        return false;
    }

    if (categoryEl.classList.contains('voodbuilder-gjs-page-template-category')) {
        return true;
    }

    return isPageTemplateCategoryId(categoryEl.getAttribute('data-voodbuilder-category-id') ?? '');
}

export function pageTemplateCategoryAttributes(categoryId) {
    return {
        class: 'voodbuilder-gjs-page-template-category',
        'data-voodbuilder-page-template-category': '1',
        'data-voodbuilder-category-id': String(categoryId),
    };
}

export function resolvePageTemplateBlockFromElement(editor, blockEl) {
    if (! blockEl || ! editor?.BlockManager) {
        return null;
    }

    const domBlockId = blockEl.getAttribute?.('data-gjs-block-id') || blockEl.id;

    if (isPageTemplateBlockId(domBlockId)) {
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

        if (! isPageTemplateBlock(candidate)) {
            return;
        }

        const viewEl = candidate.view?.el;

        if (viewEl === blockEl || viewEl?.contains?.(blockEl) || blockEl?.contains?.(viewEl)) {
            match = candidate;
        }
    });

    return match;
}

export function resolveTemplateFromBlockElement(editor, blockEl, catalog = []) {
    if (! blockEl) {
        return null;
    }

    const domBlockId = blockEl.getAttribute?.('data-gjs-block-id') || blockEl.id;

    if (isPageTemplateBlockId(domBlockId)) {
        const templateId = domBlockId.slice(PAGE_TEMPLATE_BLOCK_PREFIX.length);

        return catalog.find((entry) => String(entry.id) === String(templateId)) ?? null;
    }

    const block = resolvePageTemplateBlockFromElement(editor, blockEl);

    if (! isPageTemplateBlock(block)) {
        return null;
    }

    const templateId = String(block.get?.('id') ?? block.id).slice(PAGE_TEMPLATE_BLOCK_PREFIX.length);

    return catalog.find((entry) => String(entry.id) === String(templateId)) ?? null;
}

export function tagPageTemplateBlockElements(editor) {
    editor.BlockManager?.getAll?.()?.forEach((block) => {
        const blockId = block.get?.('id') ?? block.id;

        if (! isPageTemplateBlockId(blockId)) {
            return;
        }

        block.view?.el?.classList?.add('voodbuilder-gjs-page-template-block');
        block.view?.el?.setAttribute?.('data-gjs-block-id', String(blockId));
    });
}
