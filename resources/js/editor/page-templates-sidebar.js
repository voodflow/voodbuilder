/**
 * Page templates library — draggable layouts + sidebar actions (like Components).
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { alertDialog, componentMetaDialog, confirmDialog, promptDialog } from './editor-dialog.js';
import { editorApiHeaders, resolveApiErrorMessage } from './editor-api.js';
import { buildPayload } from './editor.js';
import { lucideIcon } from './editor-icons.js';
import { applyBlocksLibraryUi, collapseLibraryCategories, expandLibraryCategories, readBlocksSearchQuery } from './blocks-library-sync.js';
import { refreshComponentBlocksLibrary } from './components-ui.js';
import { applyPageTemplateWithPrompt } from './page-template-apply.js';
import {
    PAGE_TEMPLATE_BLOCK_PREFIX,
    PAGE_TEMPLATE_CATEGORY_PREFIX,
    PAGE_TEMPLATE_DROP_ATTR,
    isPageTemplateBlock,
    isPageTemplateBlockId,
    isPageTemplateBlockElement,
    pageTemplateCategoryAttributes,
    resolveTemplateFromBlockElement,
    tagPageTemplateBlockElements,
} from './page-template-block-utils.js';
import { inspectorEmptyStateUpsellHtml } from './inspector-empty-state.js';

function formatCountLabel(template, count) {
    return String(template ?? '{count} selected')
        .replaceAll('{count}', String(count))
        .replaceAll(':count', String(count));
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function normalizeCategory(category, fallback = 'Miscellaneous') {
    const value = String(category ?? '').trim();

    if (value === '' || value === 'General') {
        return fallback;
    }

    return value;
}

function registerPageTemplateBlocks(editor, templates, uncategorizedLabel = 'General') {
    const blockManager = editor.BlockManager;
    const existing = new Set();

    for (const template of templates) {
        const id = String(template.id ?? '');

        if (id === '') {
            continue;
        }

        const blockId = `${PAGE_TEMPLATE_BLOCK_PREFIX}${id}`;
        existing.add(blockId);

        const categoryName = normalizeCategory(template.category, uncategorizedLabel);
        const categoryId = `${PAGE_TEMPLATE_CATEGORY_PREFIX}${categoryName.toLowerCase().replace(/\s+/g, '-')}`;

        blockManager.add(blockId, {
            label: template.name ?? 'Template',
            category: {
                id: categoryId,
                label: categoryName,
                attributes: pageTemplateCategoryAttributes(categoryId),
            },
            content: `<section class="voodbuilder-editor-section" ${PAGE_TEMPLATE_DROP_ATTR}="${id}"></section>`,
            media: thumbWrap(previewSvg(template.name ?? 'Template')),
            attributes: {
                class: 'voodbuilder-editor-page-template-block',
                title: template.description ?? template.name ?? '',
            },
        });
    }

    blockManager.getAll().forEach((block) => {
        const blockId = String(block.get('id') ?? '');

        if (! isPageTemplateBlockId(blockId) || existing.has(blockId)) {
            return;
        }

        blockManager.remove(blockId);
    });
}

export function registerPageTemplatesSidebar(editor, options = {}) {
    const {
        pageTemplatesUrl,
        pageTemplatesCatalogUrl = null,
        csrf,
        labels = {},
        templatesMount,
        templateCategories = [],
        defaultTemplateCategory = 'Miscellaneous',
        popupMode = false,
        templatesPluginInstalled = false,
        canAuthorTemplates = false,
        canImportTemplates = false,
        canExportTemplates = false,
        canImportTemplatesFromUrl = true,
    } = options;

    if (popupMode || ! pageTemplatesUrl || ! templatesMount) {
        return;
    }

    if (editor.__voodbuilderPageTemplatesSidebarMounted) {
        return;
    }

    editor.__voodbuilderPageTemplatesSidebarMounted = true;

    const baseUrl = pageTemplatesUrl.replace(/\/$/, '');
    const shell = templatesMount.closest('.voodbuilder-editor-shell') ?? templatesMount;
    const blocksMount = shell.querySelector('.voodbuilder-editor-blocks-mount');
    const showAuthoringToolbar = canAuthorTemplates || canImportTemplates || canExportTemplates;
    const iconActions = [
        canImportTemplates
            ? `<button type="button" class="voodbuilder-editor-icon-btn voodbuilder-editor-templates-library__icon-btn" data-voodbuilder-page-template-import-toggle title="${escapeHtml(labels.pageTemplatesImport ?? 'Import')}" aria-label="${escapeHtml(labels.pageTemplatesImport ?? 'Import')}">${lucideIcon('download', 16)}</button>`
            : '',
        canImportTemplatesFromUrl
            ? `<button type="button" class="voodbuilder-editor-icon-btn voodbuilder-editor-templates-library__icon-btn" data-voodbuilder-page-template-import-url title="${escapeHtml(labels.pageTemplatesImportUrl ?? 'Install from URL')}" aria-label="${escapeHtml(labels.pageTemplatesImportUrl ?? 'Install from URL')}">${lucideIcon('link', 16)}</button>`
            : '',
        canAuthorTemplates
            ? `<button type="button" class="voodbuilder-editor-icon-btn voodbuilder-editor-templates-library__icon-btn" data-voodbuilder-page-template-select-toggle title="${escapeHtml(labels.pageTemplatesSelectMode ?? 'Select templates')}" aria-label="${escapeHtml(labels.pageTemplatesSelectMode ?? 'Select templates')}" aria-pressed="false">${lucideIcon('box-select', 16)}</button>`
            : '',
        canExportTemplates
            ? `<button type="button" class="voodbuilder-editor-icon-btn voodbuilder-editor-templates-library__icon-btn" data-voodbuilder-page-template-export title="${escapeHtml(labels.pageTemplatesExport ?? 'Export all')}" aria-label="${escapeHtml(labels.pageTemplatesExport ?? 'Export all')}">${lucideIcon('upload', 16)}</button>`
            : '',
    ].filter(Boolean).join('');

    const saveButtonHtml = canAuthorTemplates
        ? `<button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--primary voodbuilder-editor-templates-library__save" data-voodbuilder-page-template-save>
                        <span>${escapeHtml(labels.pageTemplatesSave ?? labels.dialogSave ?? 'Save')}</span>
                    </button>`
        : '';

    const marketplaceHintHtml = ! showAuthoringToolbar && canImportTemplatesFromUrl
        ? `<p class="voodbuilder-editor-hint voodbuilder-editor-templates-library__hint">${escapeHtml(labels.pageTemplatesMarketplaceHint ?? 'Paste a marketplace install link to add a template.')}</p>`
        : '';

    const pluginHintHtml = ! templatesPluginInstalled
        ? inspectorEmptyStateUpsellHtml({
            classNameExtra: 'voodbuilder-editor-templates-library__locked',
            title: labels.pageTemplatesPluginTitle ?? 'Voodbuilder Templates',
            message: labels.pageTemplatesPluginHint
                ?? 'Add import/export, sharing and marketplace. Requires the Voodbuilder Templates plugin.',
            linkUrl: labels.marketingUrl ?? null,
            linkLabel: labels.learnMore ?? 'Learn more',
        })
        : '';

    const exportSelectedHtml = canExportTemplates
        ? `<button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--ghost voodbuilder-editor-templates-selection-bar__action" data-voodbuilder-templates-export-selected disabled>
                            ${lucideIcon('upload', 14)}
                            <span>${escapeHtml(labels.pageTemplatesExportSelected ?? 'Export selected')}</span>
                        </button>`
        : '';

    templatesMount.innerHTML = `
        <div class="voodbuilder-editor-templates-library" data-voodbuilder-templates-library>
            ${pluginHintHtml}
            ${marketplaceHintHtml}
            <div class="voodbuilder-editor-templates-library__toolbar">
                <div class="voodbuilder-editor-templates-library__header"${! saveButtonHtml && ! iconActions ? ' hidden' : ''}>
                    ${saveButtonHtml}
                    ${iconActions ? `<div class="voodbuilder-editor-templates-library__icon-actions" role="group" aria-label="${escapeHtml(labels.pageTemplatesTitle ?? 'Templates')}">${iconActions}</div>` : ''}
                </div>
                <div class="voodbuilder-editor-templates-selection-bar" data-voodbuilder-templates-selection-bar hidden>
                    <p class="voodbuilder-editor-templates-selection-bar__meta">
                        <span class="voodbuilder-editor-templates-selection-bar__count" data-voodbuilder-templates-selection-count>${escapeHtml(formatCountLabel(labels.pageTemplatesSelectedCount, 0))}</span>
                    </p>
                    <div class="voodbuilder-editor-templates-selection-bar__actions">
                        ${exportSelectedHtml}
                        <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--ghost voodbuilder-editor-templates-selection-bar__action voodbuilder-editor-templates-selection-bar__action--danger" data-voodbuilder-templates-delete-selected disabled>
                            ${lucideIcon('trash-2', 14)}
                            <span>${escapeHtml(labels.pageTemplatesDeleteSelected ?? 'Delete selected')}</span>
                        </button>
                        <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--ghost voodbuilder-editor-templates-selection-bar__action" data-voodbuilder-templates-select-cancel>
                            ${lucideIcon('x', 14)}
                            <span>${escapeHtml(labels.pageTemplatesSelectCancel ?? 'Cancel selection')}</span>
                        </button>
                    </div>
                </div>
            </div>
            <section class="voodbuilder-editor-templates-import" data-voodbuilder-page-template-import-panel hidden>
                <h4 class="voodbuilder-editor-templates-import__title">${escapeHtml(labels.pageTemplatesImportTitle ?? 'Import templates')}</h4>
                <div class="voodbuilder-editor-templates-import__dropzone" data-voodbuilder-page-template-import-drop tabindex="0" role="button">
                    <p class="voodbuilder-editor-hint">${escapeHtml(labels.pageTemplatesImportDrop ?? 'Drop JSON files here')}</p>
                    <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--block" data-voodbuilder-page-template-import-select>${escapeHtml(labels.pageTemplatesImportSelect ?? 'Browse')}</button>
                    <input type="file" accept="application/json,.json" hidden data-voodbuilder-page-template-import-input />
                </div>
                <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--ghost voodbuilder-editor-btn--block" data-voodbuilder-page-template-import-cancel>${escapeHtml(labels.pageTemplatesImportCancel ?? 'Cancel')}</button>
            </section>
            <section class="voodbuilder-editor-page-templates-catalog" data-voodbuilder-page-templates-catalog hidden></section>
            <div class="voodbuilder-editor-template-blocks-mount" data-voodbuilder-template-blocks-mount></div>
        </div>
    `;

    const libraryRoot = templatesMount.querySelector('[data-voodbuilder-templates-library]');
    const headerEl = templatesMount.querySelector('.voodbuilder-editor-templates-library__header');
    const blocksMountNode = templatesMount.querySelector('[data-voodbuilder-template-blocks-mount]');
    const catalogEl = templatesMount.querySelector('[data-voodbuilder-page-templates-catalog]');
    const importPanel = templatesMount.querySelector('[data-voodbuilder-page-template-import-panel]');
    const importDropzone = templatesMount.querySelector('[data-voodbuilder-page-template-import-drop]');
    const importInput = templatesMount.querySelector('[data-voodbuilder-page-template-import-input]');
    const selectionBar = templatesMount.querySelector('[data-voodbuilder-templates-selection-bar]');
    const selectionCountEl = templatesMount.querySelector('[data-voodbuilder-templates-selection-count]');
    const exportSelectedBtn = templatesMount.querySelector('[data-voodbuilder-templates-export-selected]');
    const deleteSelectedBtn = templatesMount.querySelector('[data-voodbuilder-templates-delete-selected]');
    const selectToggleBtn = templatesMount.querySelector('[data-voodbuilder-page-template-select-toggle]');

    const libraryMounts = {
        blocks: blocksMount,
        ...(editor.__voodbuilderLibraryMounts ?? {}),
        templateBlocks: blocksMountNode,
    };

    editor.__voodbuilderLibraryMounts = libraryMounts;

    const previousRelocate = editor.__voodbuilderRelocateLibrary;

    editor.__voodbuilderRelocateLibrary = (libraryId) => {
        const resolved = libraryId ?? editor.__voodbuilderActiveLibrary ?? 'blocks';

        previousRelocate?.(libraryId);
        refreshComponentBlocksLibrary(editor, resolved, libraryMounts);
        // refreshComponentBlocksLibrary already collapses (or keeps open in selection mode).
        // Avoid a second collapse that races with template selection mode.
        if (! (resolved === 'templates' && editor.__voodbuilderTemplateSelectionMode === true)) {
            collapseLibraryCategories(editor, resolved);
        }

        applyBlocksLibraryUi(editor, readBlocksSearchQuery());
        refreshTemplateLibraryUi();
    };

    let catalog = [];
    let applyingTemplate = false;
    let selectionMode = false;
    const selectedIds = new Set();

    editor.__voodbuilderPageTemplatesCatalog = catalog;
    editor.__voodbuilderSyncPageTemplatesCatalog = () => syncCatalog();

    async function loadTemplates() {
        const response = await fetch(baseUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error('load failed');
        }

        return (await response.json()).templates ?? [];
    }

    async function syncCatalog() {
        try {
            catalog = await loadTemplates();
        } catch {
            catalog = [];
        }

        editor.__voodbuilderPageTemplatesCatalog = catalog;
        registerPageTemplateBlocks(editor, catalog, labels.pageTemplatesUncategorized ?? 'Miscellaneous');
        tagPageTemplateBlockElements(editor);
        refreshComponentBlocksLibrary(editor, editor.__voodbuilderActiveLibrary ?? 'blocks', libraryMounts);
        applyBlocksLibraryUi(editor, readBlocksSearchQuery());
        refreshTemplateLibraryUi();
        renderEmptyState();
    }

    function renderEmptyState() {
        let emptyEl = blocksMountNode?.querySelector('[data-voodbuilder-templates-empty]');

        if (catalog.length > 0) {
            emptyEl?.remove();

            return;
        }

        if (! blocksMountNode) {
            return;
        }

        if (! emptyEl) {
            emptyEl = document.createElement('p');
            emptyEl.className = 'voodbuilder-editor-hint';
            emptyEl.dataset.voodbuilderTemplatesEmpty = '';
            blocksMountNode.prepend(emptyEl);
        }

        emptyEl.textContent = labels.pageTemplatesEmpty ?? 'No page templates yet.';
    }

    function refreshTemplateLibraryUi() {
        tagPageTemplateBlockElements(editor);
        syncTemplateBlockDragState(selectionMode);
        updateBlocksSelectionState();
    }

    function syncTemplateBlockDragState(active) {
        editor.BlockManager?.getAll?.()?.forEach((block) => {
            if (! isPageTemplateBlock(block)) {
                return;
            }

            block.set('draggable', ! active);

            // GrapesJS BlockView.render() sets the DOM `draggable` attr from DnD capability,
            // ignoring model.draggable — force the attribute so HTML5 drag cannot start.
            const el = block.view?.el;

            if (el) {
                el.draggable = ! active;
                el.setAttribute('draggable', active ? 'false' : 'true');
            }
        });
    }

    function unbindBlockSelectionHandler(blockEl) {
        const onMouseDown = blockEl.__voodbuilderOnTemplateSelect;
        const onClick = blockEl.__voodbuilderOnTemplateSelectClick;

        if (onMouseDown) {
            blockEl.removeEventListener('mousedown', onMouseDown, true);
            delete blockEl.__voodbuilderOnTemplateSelect;
        }

        if (onClick) {
            blockEl.removeEventListener('click', onClick, true);
            delete blockEl.__voodbuilderOnTemplateSelectClick;
        }

        delete blockEl.dataset.voodbuilderTemplateSelectBound;
    }

    function bindBlockSelectionHandler(blockEl) {
        // Selection is handled by the delegated BlockManager container listener
        // (same pattern as Components). Per-card capture toggles raced with GrapesJS
        // BlockView mousedown and left the container early-return with no toggle.
        unbindBlockSelectionHandler(blockEl);
        blockEl.dataset.voodbuilderTemplateSelectBound = 'true';
    }

    function updateBlocksSelectionState() {
        const container = editor.BlockManager?.getContainer?.();

        if (! container) {
            return;
        }

        container.querySelectorAll('.gjs-block').forEach((blockEl) => {
            if (! isPageTemplateBlockElement(editor, blockEl)) {
                return;
            }

            const item = resolveTemplateFromBlockElement(editor, blockEl, catalog);
            const templateId = item ? String(item.id) : String(blockEl.getAttribute('data-voodbuilder-template-id') ?? '');
            const isSelected = templateId !== '' && selectedIds.has(templateId);

            if (templateId !== '') {
                blockEl.setAttribute('data-voodbuilder-template-id', templateId);
            }

            blockEl.classList.toggle('is-selected', selectionMode && isSelected);
            blockEl.classList.toggle('is-selectable', selectionMode);
            blockEl.setAttribute('aria-pressed', selectionMode && isSelected ? 'true' : 'false');

            // Checkbox is visual-only (pointer-events: none); clicks hit the card.
            let check = blockEl.querySelector('[data-voodbuilder-template-selection-check]');
            blockEl.querySelector('[data-voodbuilder-template-selection-hit]')?.remove();

            if (selectionMode) {
                if (templateId !== '') {
                    blockEl.draggable = false;
                    blockEl.setAttribute('draggable', 'false');
                }

                if (! check) {
                    check = document.createElement('span');
                    check.className = 'voodbuilder-editor-page-template-block__selection-check';
                    check.dataset.voodbuilderTemplateSelectionCheck = '';
                    check.setAttribute('aria-hidden', 'true');
                    check.innerHTML = lucideIcon('check', 12);
                    blockEl.appendChild(check);
                }

                check.classList.toggle('is-checked', isSelected);
                check.classList.toggle('is-empty', ! isSelected);

                if (templateId !== '') {
                    bindBlockSelectionHandler(blockEl);
                }
            } else {
                unbindBlockSelectionHandler(blockEl);
                check?.remove();
            }
        });
    }

    function setSelectionMode(active) {
        selectionMode = active;
        editor.__voodbuilderTemplateSelectionMode = active;

        if (! selectionMode) {
            selectedIds.clear();
        } else {
            expandLibraryCategories(editor, 'templates');
        }

        syncTemplateBlockDragState(selectionMode);
        updateSelectionUi();
        refreshTemplateLibraryUi();

        // BlockManager may still be settling category open state / DOM after expand.
        window.requestAnimationFrame(() => {
            if (selectionMode) {
                expandLibraryCategories(editor, 'templates');
            }

            refreshTemplateLibraryUi();
            bindTemplateLibraryBlockInteractions();

            // Second frame: category open can recreate card DOM after the first paint.
            window.requestAnimationFrame(() => {
                if (editor.__voodbuilderTemplateSelectionMode !== selectionMode) {
                    return;
                }

                refreshTemplateLibraryUi();
            });
        });
    }

    function toggleTemplateSelection(item) {
        const key = String(item?.id ?? '').trim();

        if (key === '') {
            return;
        }

        if (selectedIds.has(key)) {
            selectedIds.delete(key);
        } else {
            selectedIds.add(key);
        }

        updateSelectionUi();
        updateBlocksSelectionState();
    }

    function updateSelectionUi() {
        const count = selectedIds.size;

        if (selectionCountEl) {
            selectionCountEl.textContent = formatCountLabel(labels.pageTemplatesSelectedCount, count);
        }

        if (exportSelectedBtn) {
            exportSelectedBtn.disabled = count === 0;
        }

        if (deleteSelectedBtn) {
            deleteSelectedBtn.disabled = count === 0;
        }
        selectToggleBtn?.classList.toggle('is-active', selectionMode);
        selectToggleBtn?.setAttribute('aria-pressed', selectionMode ? 'true' : 'false');
        selectionBar.hidden = ! selectionMode;
        headerEl?.toggleAttribute('hidden', selectionMode);
        libraryRoot?.classList.toggle('is-selection-mode', selectionMode);
    }

    function bindTemplateLibraryBlockInteractions() {
        // Per-card listeners are attached in updateBlocksSelectionState.
        // Keep a single delegated fallback on the BlockManager container for cards
        // that were not decorated yet (same mount point as Components).
        const attachToContainer = () => {
            const container = editor.BlockManager?.getContainer?.();

            if (! container || container.dataset.voodbuilderTemplateLibraryBound === 'v2') {
                return;
            }

            container.dataset.voodbuilderTemplateLibraryBound = 'v2';

            container.addEventListener('mousedown', (event) => {
                if (event.button !== 0) {
                    return;
                }

                if (editor.__voodbuilderActiveLibrary !== 'templates') {
                    return;
                }

                if (editor.__voodbuilderTemplateSelectionMode !== true) {
                    return;
                }

                const blockEl = event.target.closest?.('.gjs-block');

                if (! blockEl || ! container.contains(blockEl)) {
                    return;
                }

                if (! isPageTemplateBlockElement(editor, blockEl)) {
                    return;
                }

                const item = resolveTemplateFromBlockElement(editor, blockEl, catalog)
                    ?? (blockEl.getAttribute('data-voodbuilder-template-id')
                        ? { id: blockEl.getAttribute('data-voodbuilder-template-id') }
                        : null);

                if (! item?.id) {
                    return;
                }

                // Capture on the shared BlockManager container (same as Components) so we
                // run before GrapesJS BlockView drag handlers on the card.
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                toggleTemplateSelection(item);
            }, true);

            container.addEventListener('click', (event) => {
                if (editor.__voodbuilderActiveLibrary !== 'templates') {
                    return;
                }

                if (editor.__voodbuilderTemplateSelectionMode !== true) {
                    return;
                }

                const blockEl = event.target.closest?.('.gjs-block');

                if (! blockEl || ! container.contains(blockEl) || ! isPageTemplateBlockElement(editor, blockEl)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
            }, true);
        };

        refreshTemplateLibraryUi();
        attachToContainer();

        if (! editor.__voodbuilderTemplateLibraryInteractionsHooked) {
            editor.__voodbuilderTemplateLibraryInteractionsHooked = true;

            const reattach = () => {
                window.requestAnimationFrame(() => {
                    // BlockManager.render() keeps the same container node, but block cards
                    // are recreated — re-decorate and re-bind per-card handlers.
                    const container = editor.BlockManager?.getContainer?.();

                    if (container && ! container.isConnected) {
                        delete container.dataset.voodbuilderTemplateLibraryBound;
                    }

                    attachToContainer();
                    refreshTemplateLibraryUi();
                });
            };

            editor.on('block:add', reattach);
            editor.on('block:remove', reattach);
        }
    }

    editor.__voodbuilderToggleTemplateSelection = toggleTemplateSelection;
    editor.__voodbuilderOnTemplateLibraryRefresh = () => {
        refreshTemplateLibraryUi();
        bindTemplateLibraryBlockInteractions();
    };

    function setImportPanelOpen(open) {
        if (! importPanel) {
            return;
        }

        importPanel.hidden = ! open;
        importPanel.classList.toggle('is-open', open);
        importDropzone?.classList.remove('is-dragover');
    }

    async function exportTemplates(items, filename) {
        const ids = (Array.isArray(items) ? items : [])
            .map((item) => item?.id)
            .filter((id) => id != null && id !== '');

        try {
            const response = await fetch(`${baseUrl}/export`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf, { json: true }),
                body: JSON.stringify(ids.length > 0 ? { ids } : {}),
            });

            if (! response.ok) {
                throw new Error('export failed');
            }

            const payload = await response.json();
            const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = filename;
            anchor.click();
            URL.revokeObjectURL(url);
        } catch {
            await alertDialog({ message: labels.pageTemplatesExportError ?? 'Could not export.', labels });
        }
    }

    async function importTemplateBundle(parsed) {
        const response = await fetch(`${baseUrl}/import`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: editorApiHeaders(csrf, { json: true }),
            body: JSON.stringify({ import: parsed }),
        });

        if (! response.ok) {
            throw new Error('import failed');
        }

        return (await response.json()).templates ?? [];
    }

    async function handleImportFiles(files) {
        const list = [...files].filter((file) => file.type === 'application/json' || file.name.endsWith('.json'));

        if (list.length === 0) {
            await alertDialog({
                message: labels.pageTemplatesImportInvalidFile ?? 'Invalid JSON file.',
                labels,
            });

            return;
        }

        let importedCount = 0;

        for (const file of list) {
            try {
                const parsed = JSON.parse(await file.text());
                const imported = await importTemplateBundle(parsed);
                importedCount += imported.length;
            } catch {
                await alertDialog({
                    message: labels.pageTemplatesImportError ?? 'Could not import.',
                    labels,
                });

                return;
            }
        }

        await alertDialog({
            message: formatCountLabel(labels.pageTemplatesImportSuccess ?? 'Imported {count} template(s).', importedCount),
            labels,
        });

        setImportPanelOpen(false);
        await syncCatalog();
        editor.__voodbuilderSchedulePageCssRebuild?.(0);
    }

    async function loadCatalog() {
        if (! catalogEl || ! pageTemplatesCatalogUrl) {
            catalogEl?.replaceChildren();
            if (catalogEl) {
                catalogEl.hidden = true;
            }

            return;
        }

        catalogEl.hidden = false;

        try {
            const response = await fetch(pageTemplatesCatalogUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                throw new Error('catalog failed');
            }

            const entries = (await response.json()).templates ?? [];

            if (entries.length === 0) {
                catalogEl.innerHTML = `<p class="voodbuilder-editor-hint">${escapeHtml(labels.pageTemplatesCatalogEmpty ?? 'No remote templates available.')}</p>`;

                return;
            }

            catalogEl.innerHTML = `
                <h4 class="voodbuilder-editor-templates-catalog__title">${escapeHtml(labels.pageTemplatesCatalogTitle ?? 'Marketplace')}</h4>
                <div class="voodbuilder-editor-page-templates-catalog__grid"></div>
            `;

            const grid = catalogEl.querySelector('.voodbuilder-editor-page-templates-catalog__grid');

            for (const entry of entries) {
                const card = document.createElement('article');
                card.className = 'voodbuilder-editor-page-templates-catalog__card';

                const title = document.createElement('h5');
                title.className = 'voodbuilder-editor-page-templates-catalog__title';
                title.textContent = entry.name ?? '';

                const meta = document.createElement('p');
                meta.className = 'voodbuilder-editor-hint';
                meta.textContent = [entry.category, entry.price_label].filter(Boolean).join(' · ');

                const installBtn = document.createElement('button');
                installBtn.type = 'button';
                installBtn.className = 'voodbuilder-editor-btn voodbuilder-editor-btn--primary voodbuilder-editor-btn--block';
                installBtn.textContent = labels.pageTemplatesCatalogInstall ?? 'Install';
                installBtn.addEventListener('click', async () => {
                    if (! entry.bundle_url) {
                        await alertDialog({ message: labels.pageTemplatesCatalogInstallError ?? 'Could not install.', labels });

                        return;
                    }

                    installBtn.disabled = true;

                    try {
                        const response = await fetch(`${baseUrl}/install`, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: editorApiHeaders(csrf, { json: true }),
                            body: JSON.stringify({ bundle_url: entry.bundle_url }),
                        });

                        if (! response.ok) {
                            throw new Error('install failed');
                        }

                        await alertDialog({
                            message: (labels.pageTemplatesCatalogInstallSuccess ?? 'Installed :name.')
                                .replace(':name', String(entry.name ?? '')),
                            labels,
                        });

                        await syncCatalog();
                        editor.__voodbuilderSchedulePageCssRebuild?.(0);
                    } catch {
                        await alertDialog({ message: labels.pageTemplatesCatalogInstallError ?? 'Could not install.', labels });
                    } finally {
                        installBtn.disabled = false;
                    }
                });

                card.append(title, meta, installBtn);
                grid?.appendChild(card);
            }
        } catch {
            catalogEl.innerHTML = `<p class="voodbuilder-editor-hint">${escapeHtml(labels.pageTemplatesCatalogInstallError ?? 'Could not load catalog.')}</p>`;
        }
    }

    templatesMount.querySelector('[data-voodbuilder-page-template-save]')?.addEventListener('click', async () => {
        const meta = await componentMetaDialog({
            title: labels.pageTemplatesSaveTitle ?? 'Save page as template',
            labels,
            categories: templateCategories,
            defaultCategory: defaultTemplateCategory,
            categoryLabel: labels.pageTemplatesCategory ?? 'Category',
            nameLabel: labels.pageTemplatesName ?? 'Template name',
            confirmLabel: labels.pageTemplatesSave ?? 'Save',
            namePlaceholder: labels.pageTemplatesNamePlaceholder ?? 'Landing · Product',
        });

        if (! meta?.name) {
            return;
        }

        let payload;

        try {
            payload = buildPayload(editor);
        } catch {
            await alertDialog({ message: labels.pageTemplatesSaveError ?? 'Could not read page.', labels });

            return;
        }

        const response = await fetch(baseUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: editorApiHeaders(csrf, { json: true }),
            body: JSON.stringify({
                name: meta.name,
                category: meta.category,
                html: payload.html,
                css: payload.css,
                js: payload.js,
            }),
        });

        if (! response.ok) {
            await alertDialog({
                message: await resolveApiErrorMessage(response, labels.pageTemplatesSaveError ?? 'Could not save.'),
                labels,
            });

            return;
        }

        await syncCatalog();
    });

    templatesMount.querySelector('[data-voodbuilder-page-template-export]')?.addEventListener('click', async () => {
        await exportTemplates(
            catalog,
            `voodbuilder-page-templates-${new Date().toISOString().slice(0, 10)}.json`,
        );
    });

    templatesMount.querySelector('[data-voodbuilder-templates-export-selected]')?.addEventListener('click', async () => {
        const items = catalog.filter((item) => selectedIds.has(String(item.id)));

        await exportTemplates(
            items,
            `voodbuilder-page-templates-selected-${new Date().toISOString().slice(0, 10)}.json`,
        );
    });

    templatesMount.querySelector('[data-voodbuilder-page-template-import-toggle]')?.addEventListener('click', () => {
        setImportPanelOpen(importPanel?.hidden !== false);
    });

    templatesMount.querySelector('[data-voodbuilder-page-template-import-cancel]')?.addEventListener('click', () => {
        setImportPanelOpen(false);
    });

    templatesMount.querySelector('[data-voodbuilder-page-template-import-select]')?.addEventListener('click', (event) => {
        event.stopPropagation();
        importInput?.click();
    });

    importDropzone?.addEventListener('click', (event) => {
        if (event.target.closest('[data-voodbuilder-page-template-import-select]')) {
            return;
        }

        importInput?.click();
    });

    importDropzone?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            importInput?.click();
        }
    });

    importInput?.addEventListener('change', async () => {
        if (! importInput.files?.length) {
            return;
        }

        await handleImportFiles(importInput.files);
        importInput.value = '';
    });

    importDropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        importDropzone.classList.add('is-dragover');
    });

    importDropzone?.addEventListener('dragleave', () => {
        importDropzone.classList.remove('is-dragover');
    });

    importDropzone?.addEventListener('drop', async (event) => {
        event.preventDefault();
        importDropzone.classList.remove('is-dragover');

        if (event.dataTransfer?.files?.length) {
            await handleImportFiles(event.dataTransfer.files);
        }
    });

    templatesMount.querySelector('[data-voodbuilder-page-template-import-url]')?.addEventListener('click', async () => {
        const url = await promptDialog({
            title: labels.pageTemplatesImportUrl ?? 'Install from URL',
            message: labels.pageTemplatesImportUrlPrompt ?? 'Paste the HTTPS URL of a template bundle (.json).',
            labels,
            confirmLabel: labels.pageTemplatesCatalogInstall ?? 'Install',
        });

        if (! url) {
            return;
        }

        try {
            const response = await fetch(`${baseUrl}/import-url`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf, { json: true }),
                body: JSON.stringify({ url: String(url).trim() }),
            });

            if (! response.ok) {
                throw new Error('import url failed');
            }

            const payload = await response.json();
            const count = (payload.templates ?? []).length;

            await alertDialog({
                message: formatCountLabel(labels.pageTemplatesImportUrlSuccess ?? 'Installed {count} template(s).', count),
                labels,
            });

            await syncCatalog();
            editor.__voodbuilderSchedulePageCssRebuild?.(0);
        } catch {
            await alertDialog({ message: labels.pageTemplatesImportUrlError ?? 'Could not install from URL.', labels });
        }
    });

    selectToggleBtn?.addEventListener('click', () => {
        setSelectionMode(! selectionMode);
    });

    templatesMount.querySelector('[data-voodbuilder-templates-select-cancel]')?.addEventListener('click', () => {
        setSelectionMode(false);
    });

    deleteSelectedBtn?.addEventListener('click', async () => {
        if (selectedIds.size === 0) {
            return;
        }

        const confirmed = await confirmDialog({
            title: labels.dialogConfirmTitle ?? 'Confirm',
            message: labels.pageTemplatesDeleteSelectedConfirm ?? 'Delete selected templates?',
            labels,
            danger: true,
            confirmLabel: labels.pageTemplatesDelete ?? 'Delete',
        });

        if (! confirmed) {
            return;
        }

        for (const id of [...selectedIds]) {
            const response = await fetch(`${baseUrl}/${id}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf),
            });

            if (! response.ok && response.status !== 404) {
                await alertDialog({ message: labels.pageTemplatesDeleteError ?? 'Could not delete template.', labels });

                return;
            }
        }

        setSelectionMode(false);
        await syncCatalog();
    });

    editor.on('component:add', async (component) => {
        if (applyingTemplate || selectionMode) {
            return;
        }

        const attrs = component.getAttributes?.() ?? {};
        const templateId = attrs['data-voodbuilder-page-template-drop'];

        if (! templateId) {
            return;
        }

        const template = catalog.find((entry) => String(entry.id) === String(templateId));

        applyingTemplate = true;
        editor.__voodbuilderSetCssRebuildSuspended?.(true);

        try {
            component.remove({ children: true });

            if (! template) {
                return;
            }

            await applyPageTemplateWithPrompt(editor, template, labels, { alreadySuspended: true });
        } finally {
            applyingTemplate = false;
            editor.__voodbuilderFlushCssRebuildOnResume = true;
            editor.__voodbuilderSetCssRebuildSuspended?.(false);
            // Force compile after template HTML lands (schedule-if-missing can no-op
            // when a partial live CSS fingerprint already matches).
            window.requestAnimationFrame(() => {
                editor.__voodbuilderInvalidatePageCss?.();
            });
        }
    });

    bindTemplateLibraryBlockInteractions();

    void syncCatalog();
    void loadCatalog();

    editor.on('load', () => {
        void syncCatalog();
        void loadCatalog();
    });
}
