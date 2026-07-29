/**
 * Editor blocks that represent full page templates (vb-page-template-*).
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

export function templateIdFromBlockId(blockId) {
    const id = String(blockId ?? '');

    if (! isPageTemplateBlockId(id)) {
        return '';
    }

    return id.slice(PAGE_TEMPLATE_BLOCK_PREFIX.length);
}

export function isPageTemplateBlockElement(editor, blockEl) {
    if (! blockEl) {
        return false;
    }

    if (blockEl.classList?.contains('voodbuilder-editor-page-template-block')) {
        return true;
    }

    if (blockEl.hasAttribute?.('data-voodbuilder-template-id')) {
        return true;
    }

    const blockId = blockEl.getAttribute?.('data-gjs-block-id')
        ?? blockEl.querySelector?.('[data-gjs-block-id]')?.getAttribute?.('data-gjs-block-id')
        ?? blockEl.id
        ?? '';

    if (isPageTemplateBlockId(blockId)) {
        return true;
    }

    return Boolean(blockEl.closest?.('.voodbuilder-editor-page-template-block'));
}

export function isPageTemplateCategoryElement(categoryEl) {
    if (! categoryEl) {
        return false;
    }

    if (categoryEl.classList.contains('voodbuilder-editor-page-template-category')) {
        return true;
    }

    return isPageTemplateCategoryId(categoryEl.getAttribute('data-voodbuilder-category-id') ?? '');
}

export function pageTemplateCategoryAttributes(categoryId) {
    return {
        class: 'voodbuilder-editor-page-template-category',
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

function findCatalogEntry(catalog, templateId) {
    const key = String(templateId ?? '').trim();

    if (key === '') {
        return null;
    }

    return catalog.find((entry) => String(entry.id) === key) ?? null;
}

function catalogForResolve(editor, catalog) {
    if (Array.isArray(catalog) && catalog.length > 0) {
        return catalog;
    }

    const fromEditor = editor?.__voodbuilderPageTemplatesCatalog;

    return Array.isArray(fromEditor) ? fromEditor : [];
}

/**
 * Resolve a template catalog entry (or a synthetic `{ id }` fallback) from a block DOM node.
 * Selection must work even when catalog lookup lags behind BlockManager DOM.
 */
export function resolveTemplateFromBlockElement(editor, blockEl, catalog = []) {
    if (! blockEl) {
        return null;
    }

    const list = catalogForResolve(editor, catalog);
    const candidates = [];

    const stampedId = String(
        blockEl.getAttribute?.('data-voodbuilder-template-id')
            ?? '',
    ).trim();

    if (stampedId !== '') {
        candidates.push(stampedId);
    }

    const domBlockId = String(
        blockEl.getAttribute?.('data-gjs-block-id')
            ?? blockEl.id
            ?? '',
    ).trim();

    const fromDom = templateIdFromBlockId(domBlockId);

    if (fromDom !== '') {
        candidates.push(fromDom);
    }

    const block = resolvePageTemplateBlockFromElement(editor, blockEl);

    if (isPageTemplateBlock(block)) {
        const fromModel = templateIdFromBlockId(block.get?.('id') ?? block.id);

        if (fromModel !== '') {
            candidates.push(fromModel);
        }

        const content = String(block.get?.('content') ?? '');
        const dropMatch = content.match(new RegExp(`${PAGE_TEMPLATE_DROP_ATTR}="([^"]+)"`));

        if (dropMatch?.[1]) {
            candidates.push(String(dropMatch[1]).trim());
        }
    }

    for (const templateId of candidates) {
        const entry = findCatalogEntry(list, templateId);

        if (entry) {
            return entry;
        }
    }

    // Synthetic fallback — enough for selection Set / delete by id.
    if (candidates.length > 0 && candidates[0] !== '') {
        return { id: candidates[0] };
    }

    return null;
}

export function tagPageTemplateBlockElements(editor) {
    editor.BlockManager?.getAll?.()?.forEach((block) => {
        const blockId = String(block.get?.('id') ?? block.id ?? '');

        if (! isPageTemplateBlockId(blockId)) {
            return;
        }

        const el = block.view?.el;

        if (! el) {
            return;
        }

        const templateId = templateIdFromBlockId(blockId);

        el.classList.add('voodbuilder-editor-page-template-block');
        el.setAttribute('data-gjs-block-id', blockId);
        el.setAttribute('data-voodbuilder-page-template-block', '1');

        if (templateId !== '') {
            el.setAttribute('data-voodbuilder-template-id', templateId);
        }
    });
}
