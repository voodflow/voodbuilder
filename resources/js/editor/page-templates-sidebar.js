/**
 * Page templates library — draggable layouts + sidebar actions (like Components).
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { alertDialog, componentMetaDialog, confirmDialog, promptDialog } from './editor-dialog.js';
import { editorApiHeaders, resolveApiErrorMessage } from './editor-api.js';
import { buildPayload } from './editor.js';
import { lucideIcon, tablerIcon } from './editor-icons.js';
import { applyBlocksLibraryUi, collapseLibraryCategories, readBlocksSearchQuery } from './blocks-library-sync.js';
import { refreshComponentBlocksLibrary } from './components-ui.js';
import { applyPageTemplateWithPrompt, forceTemplatePageCssRebuild } from './page-template-apply.js';
import {
    PAGE_TEMPLATE_BLOCK_PREFIX,
    PAGE_TEMPLATE_CATEGORY_PREFIX,
    PAGE_TEMPLATE_DROP_ATTR,
    isPageTemplateBlock,
    isPageTemplateBlockId,
    pageTemplateCategoryAttributes,
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
            ? `<button type="button" class="voodbuilder-editor-icon-btn voodbuilder-editor-templates-library__icon-btn" data-voodbuilder-page-template-select-toggle title="${escapeHtml(labels.pageTemplatesSelectMode ?? 'Select templates')}" aria-label="${escapeHtml(labels.pageTemplatesSelectMode ?? 'Select templates')}" aria-pressed="false">${tablerIcon('file-x', 16)}</button>`
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
        ? `<button type="button" class="voodbuilder-editor-icon-btn voodbuilder-editor-templates-selection-bar__icon" data-voodbuilder-templates-export-selected disabled title="${escapeHtml(labels.pageTemplatesExportSelected ?? 'Export selected')}" aria-label="${escapeHtml(labels.pageTemplatesExportSelected ?? 'Export selected')}">${lucideIcon('upload', 16)}</button>`
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
                    <span class="voodbuilder-editor-templates-selection-bar__count" data-voodbuilder-templates-selection-count>${escapeHtml(formatCountLabel(labels.pageTemplatesSelectedCount, 0))}</span>
                    <div class="voodbuilder-editor-templates-selection-bar__actions">
                        ${exportSelectedHtml}
                        <button type="button" class="voodbuilder-editor-icon-btn voodbuilder-editor-templates-selection-bar__icon voodbuilder-editor-templates-selection-bar__icon--danger" data-voodbuilder-templates-delete-selected disabled title="${escapeHtml(labels.pageTemplatesDeleteSelected ?? 'Delete selected')}" aria-label="${escapeHtml(labels.pageTemplatesDeleteSelected ?? 'Delete selected')}">${lucideIcon('trash-2', 16)}</button>
                        <button type="button" class="voodbuilder-editor-icon-btn voodbuilder-editor-templates-selection-bar__icon" data-voodbuilder-templates-select-cancel title="${escapeHtml(labels.pageTemplatesSelectCancel ?? 'Cancel selection')}" aria-label="${escapeHtml(labels.pageTemplatesSelectCancel ?? 'Cancel selection')}">${lucideIcon('x', 16)}</button>
                    </div>
                </div>
            </div>
            <section class="voodbuilder-editor-templates-import" data-voodbuilder-page-template-import-panel hidden>
                <h4 class="voodbuilder-editor-templates-import__title">${escapeHtml(labels.pageTemplatesImportTitle ?? 'Import templates')}</h4>
                <div class="voodbuilder-editor-templates-import__dropzone" data-voodbuilder-page-template-import-drop tabindex="0" role="button">
                    <p class="voodbuilder-editor-hint">${escapeHtml(labels.pageTemplatesImportDrop ?? 'Drop JSON files here')}</p>
                    <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--block" data-voodbuilder-page-template-import-select>${escapeHtml(labels.pageTemplatesImportSelect ?? 'Browse')}</button>
                    <input type="file" accept="application/json,.json" hidden aria-label="${escapeHtml(labels.pageTemplatesImportSelect ?? 'Browse')}" data-voodbuilder-page-template-import-input />
                </div>
                <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--ghost voodbuilder-editor-btn--block" data-voodbuilder-page-template-import-cancel>${escapeHtml(labels.pageTemplatesImportCancel ?? 'Cancel')}</button>
            </section>
            <section class="voodbuilder-editor-page-templates-catalog" data-voodbuilder-page-templates-catalog hidden></section>
            <div class="voodbuilder-editor-templates-select-grid" data-voodbuilder-templates-select-grid hidden></div>
            <div class="voodbuilder-editor-template-blocks-mount" data-voodbuilder-template-blocks-mount></div>
        </div>
    `;

    const libraryRoot = templatesMount.querySelector('[data-voodbuilder-templates-library]');
    const headerEl = templatesMount.querySelector('.voodbuilder-editor-templates-library__header');
    const blocksMountNode = templatesMount.querySelector('[data-voodbuilder-template-blocks-mount]');
    const selectGridEl = templatesMount.querySelector('[data-voodbuilder-templates-select-grid]');
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
        syncTemplateBlockDragState();
        syncSelectionModeDom();
    }

    function syncTemplateBlockDragState() {
        editor.BlockManager?.getAll?.()?.forEach((block) => {
            if (! isPageTemplateBlock(block)) {
                return;
            }

            block.set('draggable', ! selectionMode);
        });
    }

    function syncSelectionModeDom() {
        if (blocksMountNode) {
            blocksMountNode.hidden = selectionMode;
            blocksMountNode.classList.toggle('is-selection-hidden', selectionMode);
        }

        renderSelectGrid();
    }

    function filteredCatalog() {
        const query = String(readBlocksSearchQuery() ?? '').trim().toLowerCase();

        if (query === '') {
            return catalog;
        }

        return catalog.filter((template) => {
            const hay = `${template.name ?? ''} ${template.category ?? ''} ${template.description ?? ''}`.toLowerCase();

            return hay.includes(query);
        });
    }

    function renderSelectGrid() {
        if (! selectGridEl || ! canAuthorTemplates) {
            if (selectGridEl) {
                selectGridEl.hidden = true;
                selectGridEl.replaceChildren();
            }

            return;
        }

        if (! selectionMode) {
            selectGridEl.hidden = true;
            selectGridEl.replaceChildren();

            return;
        }

        const items = filteredCatalog();
        selectGridEl.hidden = false;
        selectGridEl.replaceChildren();

        if (items.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'voodbuilder-editor-hint';
            empty.textContent = labels.pageTemplatesEmpty ?? 'No page templates yet.';
            selectGridEl.append(empty);

            return;
        }

        // Group by category for scanability with many templates.
        const groups = new Map();

        for (const template of items) {
            const category = normalizeCategory(
                template.category,
                labels.pageTemplatesUncategorized ?? 'Miscellaneous',
            );

            if (! groups.has(category)) {
                groups.set(category, []);
            }

            groups.get(category).push(template);
        }

        for (const [category, templates] of groups) {
            const section = document.createElement('section');
            section.className = 'voodbuilder-editor-templates-select-grid__section';

            const title = document.createElement('h4');
            title.className = 'voodbuilder-editor-templates-select-grid__section-title';
            title.textContent = category;
            section.append(title);

            const grid = document.createElement('div');
            grid.className = 'voodbuilder-editor-templates-select-grid__cards';

            for (const template of templates) {
                const id = String(template.id ?? '').trim();

                if (id === '') {
                    continue;
                }

                const selected = selectedIds.has(id);
                const card = document.createElement('article');
                card.className = 'voodbuilder-editor-templates-select-card';
                card.classList.toggle('is-selected', selected);
                card.dataset.voodbuilderTemplateId = id;
                card.tabIndex = 0;
                card.setAttribute('role', 'button');
                card.setAttribute('aria-pressed', selected ? 'true' : 'false');

                const check = document.createElement('span');
                check.className = 'voodbuilder-editor-templates-select-card__check';
                check.classList.toggle('is-checked', selected);
                check.setAttribute('aria-hidden', 'true');
                check.innerHTML = lucideIcon('check', 12);

                const media = document.createElement('div');
                media.className = 'voodbuilder-editor-templates-select-card__media';
                media.innerHTML = thumbWrap(previewSvg(
                    '<rect x="8" y="10" width="32" height="28" rx="2.5" /><path d="M8 18h32" />',
                ));

                const label = document.createElement('div');
                label.className = 'voodbuilder-editor-templates-select-card__label';
                label.textContent = template.name ?? id;

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'voodbuilder-editor-templates-select-card__delete';
                deleteBtn.title = labels.pageTemplatesDelete ?? 'Delete';
                deleteBtn.setAttribute('aria-label', labels.pageTemplatesDelete ?? 'Delete');
                deleteBtn.innerHTML = lucideIcon('trash-2', 14);
                deleteBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    void deleteTemplatesByIds([id], {
                        confirmMessage: labels.pageTemplatesDeleteConfirm ?? 'Delete this template?',
                    });
                });

                const toggle = () => {
                    if (selectedIds.has(id)) {
                        selectedIds.delete(id);
                    } else {
                        selectedIds.add(id);
                    }

                    updateSelectionUi();
                    renderSelectGrid();
                };

                card.addEventListener('click', (event) => {
                    if (event.target.closest('.voodbuilder-editor-templates-select-card__delete')) {
                        return;
                    }

                    toggle();
                });

                card.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        toggle();
                    }
                });

                card.append(check, deleteBtn, media, label);
                grid.append(card);
            }

            section.append(grid);
            selectGridEl.append(section);
        }
    }

    async function deleteTemplatesByIds(ids, options = {}) {
        const uniqueIds = [...new Set(ids.map((id) => String(id ?? '').trim()).filter(Boolean))];

        if (uniqueIds.length === 0) {
            return false;
        }

        const confirmed = await confirmDialog({
            title: labels.dialogConfirmTitle ?? 'Confirm',
            message: options.confirmMessage
                ?? labels.pageTemplatesDeleteSelectedConfirm
                ?? 'Delete selected templates?',
            labels,
            danger: true,
            confirmLabel: labels.pageTemplatesDelete ?? 'Delete',
        });

        if (! confirmed) {
            return false;
        }

        for (const id of uniqueIds) {
            const response = await fetch(`${baseUrl}/${id}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf),
            });

            if (! response.ok && response.status !== 404) {
                await alertDialog({
                    message: labels.pageTemplatesDeleteError ?? 'Could not delete template.',
                    labels,
                });

                return false;
            }

            selectedIds.delete(id);
        }

        if (selectionMode) {
            setSelectionMode(false);
        }

        await syncCatalog();

        return true;
    }

    function setSelectionMode(active) {
        selectionMode = active;
        editor.__voodbuilderTemplateSelectionMode = active;

        if (! selectionMode) {
            selectedIds.clear();
        }

        updateSelectionUi();
        refreshTemplateLibraryUi();
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

    editor.__voodbuilderOnTemplateLibraryRefresh = refreshTemplateLibraryUi;

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

    shell.querySelector('.voodbuilder-editor-blocks-search')?.addEventListener('input', () => {
        if (selectionMode) {
            renderSelectGrid();
        }
    });

    templatesMount.querySelector('[data-voodbuilder-templates-select-cancel]')?.addEventListener('click', () => {
        setSelectionMode(false);
    });

    deleteSelectedBtn?.addEventListener('click', async () => {
        if (selectedIds.size === 0) {
            return;
        }

        await deleteTemplatesByIds([...selectedIds]);
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
            // applyPageTemplateWithPrompt already force-rebuilds; keep an extra
            // invalidate after the outer suspend unlock for drop races.
            forceTemplatePageCssRebuild(editor, 320);
        }
    });

    refreshTemplateLibraryUi();

    void syncCatalog();
    void loadCatalog();

    editor.on('load', () => {
        void syncCatalog();
        void loadCatalog();
    });
}
