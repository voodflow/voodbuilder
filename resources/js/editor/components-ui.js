/**
 * Reusable components library — draggable instances like blocks.
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { openContextMenu } from './context-menu.js';
import { openComponentCodeImportDialog, openComponentCodeEditorDialog } from './component-code-import.js';
import { normalizeComponentCategory, resolveComponentCategories } from './component-categories.js';
import { stripEmbeddableMediaFromHtml } from './component-media.js';
import { alertDialog, confirmDialog } from './editor-dialog.js';
import { editorApiHeaders, resolveApiErrorMessage } from './editor-api.js';
import { lucideIcon } from './editor-icons.js';
import { resolveCategoryOrder } from './section-block-meta.js';
import {
    COMPONENT_BLOCK_PREFIX,
    COMPONENT_CATEGORY_PREFIX,
    componentCategoryAttributes,
    isComponentBlock,
    isComponentBlockId,
    isComponentBlockElement,
    isComponentCategoryId,
    resolveBlockFromElement,
    resolveCatalogItemFromComponentBlock,
} from './component-block-utils.js';
import { refreshBlockPinUi } from './block-pins.js';
import { saveComponentToCatalog } from './component-catalog-actions.js';
import { applyBlocksLibraryUi, collapseLibraryCategories, expandLibraryCategories, readBlocksSearchQuery } from './blocks-library-sync.js';
import { tagPageTemplateBlockElements } from './page-template-block-utils.js';
import {
    extractBackgroundUtilityClasses,
    isBackgroundUtilityClass,
    isClearedBackground,
    stripBackgroundClasses,
} from './theme-tokens.js';
import {
    COMPONENT_ATTR,
    COMPONENT_HYDRATED_KEY,
    COMPONENT_SCOPE_ATTR,
    COMPONENT_TYPE,
    PROPS_ATTR,
    registerComponentInstanceType,
} from './component-instance-type.js';
import { scopeComponentCssToInstance } from './component-instance-css-scope.js';

export { registerComponentInstanceType } from './component-instance-type.js';
const BLOCK_PREFIX = COMPONENT_BLOCK_PREFIX;
const CATEGORY_PREFIX = COMPONENT_CATEGORY_PREFIX;

export function registerComponentsUi(editor, options = {}) {
    const {
        componentsUrl,
        csrf,
        labels = {},
        componentsMount,
        componentPropsMount,
        canvasStyles = [],
        componentCategories = [],
    } = options;

    const categories = resolveComponentCategories(componentCategories);
    const uncategorizedLabel = labels.componentsUncategorized ?? 'General';

    const resolveCategory = (value) => normalizeComponentCategory(
        value,
        categories,
        uncategorizedLabel,
    );

    const normalizeCatalogEntry = (entry) => {
        if (! entry || typeof entry !== 'object') {
            return entry;
        }

        return {
            ...entry,
            category: resolveCategory(entry.category),
            html: stripEmbeddableMediaFromHtml(String(entry.html ?? '')),
        };
    };

    if (! componentsUrl || ! componentsMount) {
        editor.__voodbuilderComponentCatalogCssReady = Promise.resolve();

        return;
    }

    if (editor.__voodbuilderComponentsLibraryMounted) {
        return;
    }

    editor.__voodbuilderComponentsLibraryMounted = true;

    editor.__voodbuilderComponentCatalogCssReady = Promise.resolve();

    let catalog = [];
    let selectionMode = false;
    let catalogLoadVersion = 0;
    const selectedIds = new Set();
    const deletingIds = new Set();

    editor.__voodbuilderComponentsCatalog = catalog;
    editor.__voodbuilderHydrateComponentInstance = (component, catalogArg = catalog) => {
        hydrateComponentInstance(component, catalogArg, editor);
    };

    registerComponentInstanceType(editor, () => catalog);

    componentsMount.innerHTML = `
        <div class="voodbuilder-editor-components-library" data-voodbuilder-components-library>
            <div class="voodbuilder-editor-components-library__toolbar">
                <div class="voodbuilder-editor-components-library__header">
                    <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--primary voodbuilder-editor-components-library__save" data-voodbuilder-save-component>
                        <span>${labels.componentsSave ?? 'Save'}</span>
                    </button>
                    <div class="voodbuilder-editor-components-library__icon-actions" role="group" aria-label="${escapeHtml(labels.componentsTitle ?? 'Components')}">
                        <button
                            type="button"
                            class="voodbuilder-editor-icon-btn voodbuilder-editor-components-library__icon-btn"
                            data-voodbuilder-components-code-import
                            title="${escapeHtml(labels.componentsCodeImport ?? 'Import from code')}"
                            aria-label="${escapeHtml(labels.componentsCodeImport ?? 'Import from code')}"
                        >${lucideIcon('code', 16)}</button>
                        <button
                            type="button"
                            class="voodbuilder-editor-icon-btn voodbuilder-editor-components-library__icon-btn"
                            data-voodbuilder-components-import-toggle
                            title="${escapeHtml(labels.componentsImport ?? 'Import')}"
                            aria-label="${escapeHtml(labels.componentsImport ?? 'Import')}"
                        >${lucideIcon('download', 16)}</button>
                        <button
                            type="button"
                            class="voodbuilder-editor-icon-btn voodbuilder-editor-components-library__icon-btn"
                            data-voodbuilder-components-select-toggle
                            title="${escapeHtml(labels.componentsSelectMode ?? 'Select components')}"
                            aria-label="${escapeHtml(labels.componentsSelectMode ?? 'Select components')}"
                            aria-pressed="false"
                        >${lucideIcon('box-select', 16)}</button>
                        <button
                            type="button"
                            class="voodbuilder-editor-icon-btn voodbuilder-editor-components-library__icon-btn"
                            data-voodbuilder-components-export
                            title="${escapeHtml(labels.componentsExportAll ?? labels.componentsExport ?? 'Export all')}"
                            aria-label="${escapeHtml(labels.componentsExportAll ?? labels.componentsExport ?? 'Export all')}"
                        >${lucideIcon('upload', 16)}</button>
                    </div>
                </div>
                <div class="voodbuilder-editor-components-selection-bar" data-voodbuilder-components-selection-bar hidden>
                    <p class="voodbuilder-editor-components-selection-bar__meta">
                        <span class="voodbuilder-editor-components-selection-bar__count" data-voodbuilder-components-selection-count>
                            ${escapeHtml(labels.componentsSelectedCount?.replace('{count}', '0') ?? '0 selected')}
                        </span>
                    </p>
                    <div class="voodbuilder-editor-components-selection-bar__actions">
                        <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--ghost voodbuilder-editor-components-selection-bar__action" data-voodbuilder-components-export-selected disabled>
                            ${lucideIcon('upload', 14)}
                            <span>${escapeHtml(labels.componentsExportSelected ?? 'Export selected')}</span>
                        </button>
                        <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--ghost voodbuilder-editor-components-selection-bar__action voodbuilder-editor-components-selection-bar__action--danger" data-voodbuilder-components-delete-selected disabled>
                            ${lucideIcon('trash-2', 14)}
                            <span>${escapeHtml(labels.componentsDeleteSelected ?? 'Delete selected')}</span>
                        </button>
                        <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--ghost voodbuilder-editor-components-selection-bar__action" data-voodbuilder-components-select-cancel>
                            ${lucideIcon('x', 14)}
                            <span>${escapeHtml(labels.componentsSelectCancel ?? 'Cancel selection')}</span>
                        </button>
                    </div>
                </div>
            </div>
            <section class="voodbuilder-editor-components-import" data-voodbuilder-components-import-panel hidden>
                <h4 class="voodbuilder-editor-components-import__title">${escapeHtml(labels.componentsImportTitle ?? 'Import: components')}</h4>
                <div
                    class="voodbuilder-editor-components-import__dropzone"
                    data-voodbuilder-components-import-drop
                    tabindex="0"
                    role="button"
                >
                    <p class="voodbuilder-editor-hint">${escapeHtml(labels.componentsImportDrop ?? 'Drop JSON files here')}</p>
                    <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--block" data-voodbuilder-components-import-select>
                        ${escapeHtml(labels.componentsImportSelect ?? 'Browse')}
                    </button>
                    <input
                        type="file"
                        accept=".json,application/json"
                        multiple
                        hidden
                        aria-label="${escapeHtml(labels.componentsImportSelect ?? 'Browse')}"
                        data-voodbuilder-components-import-input
                    />
                </div>
                <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--ghost voodbuilder-editor-btn--block" data-voodbuilder-components-import-cancel>
                    ${escapeHtml(labels.componentsImportCancel ?? 'Cancel')}
                </button>
            </section>
            <div class="voodbuilder-editor-components-empty" data-voodbuilder-components-empty hidden>
                <span class="voodbuilder-editor-components-empty__icon" aria-hidden="true">${lucideIcon('boxes', 28)}</span>
                <p class="voodbuilder-editor-components-empty__title">${labels.componentsEmpty ?? 'No components yet'}</p>
                <p class="voodbuilder-editor-components-empty__hint">${labels.componentsEmptyHint ?? 'Select an element on the canvas and save it as a reusable component.'}</p>
            </div>
            <div class="voodbuilder-editor-components-blocks-mount" data-voodbuilder-component-blocks-mount></div>
        </div>
    `;

    const shell = componentsMount.closest('.voodbuilder-editor-shell') ?? componentsMount;
    const blocksMount = shell.querySelector('.voodbuilder-editor-blocks-mount');
    const componentsBlocksMount = componentsMount.querySelector('[data-voodbuilder-component-blocks-mount]');
    const emptyStateEl = componentsMount.querySelector('[data-voodbuilder-components-empty]');
    const importPanel = componentsMount.querySelector('[data-voodbuilder-components-import-panel]');
    const importDropzone = componentsMount.querySelector('[data-voodbuilder-components-import-drop]');
    const importInput = componentsMount.querySelector('[data-voodbuilder-components-import-input]');
    const libraryMounts = { blocks: blocksMount, componentsBlocks: componentsBlocksMount };
    blocksMount?.setAttribute('data-voodbuilder-blocks-library', 'blocks');
    editor.__voodbuilderLibraryMounts = libraryMounts;
    editor.__voodbuilderRelocateLibrary = (libraryId) => {
        const resolvedLibraryId = libraryId ?? getActiveLibraryId();

        refreshComponentBlocksLibrary(editor, resolvedLibraryId, libraryMounts);
        updateEmptyState();
        updateBlocksSelectionState();
        applyBlocksLibraryUi(editor, readBlocksSearchQuery());

        if (resolvedLibraryId === 'components') {
            ensureComponentsLibraryVisible(editor, libraryMounts, resolvedLibraryId);
        }
    };

    const preserveComponentsLibraryTab = () => {
        const componentsTab = shell.querySelector('[data-voodbuilder-library="components"]');

        if (! componentsTab) {
            return;
        }

        if (! componentsTab.classList.contains('voodbuilder-editor-library-tab--active')) {
            componentsTab.click();
        }

        editor.__voodbuilderActiveLibrary = 'components';
        ensureComponentsLibraryVisible(editor, libraryMounts, 'components');
        updateEmptyState();
    };

    const getActiveLibraryId = () => {
        const activeTab = shell.querySelector('[data-voodbuilder-library].voodbuilder-editor-library-tab--active');

        return activeTab?.dataset.voodbuilderLibrary ?? 'blocks';
    };

    const focusComponentsLibrary = () => {
        const search = shell.querySelector('.voodbuilder-editor-blocks-search');

        if (search) {
            search.value = '';
        }

        const componentsTab = shell.querySelector('[data-voodbuilder-library="components"]');

        if (componentsTab && ! componentsTab.classList.contains('voodbuilder-editor-library-tab--active')) {
            componentsTab.click();
        }

        refreshComponentBlocksLibrary(editor, 'components', libraryMounts);
        updateEmptyState();
        search?.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const updateEmptyState = () => {
        const onComponentsTab = getActiveLibraryId() === 'components';
        const isEmpty = catalog.length === 0;

        emptyStateEl?.toggleAttribute('hidden', ! onComponentsTab || ! isEmpty);
        componentsBlocksMount?.classList.toggle('is-hidden', onComponentsTab && isEmpty);
    };

    const syncCatalog = () => {
        editor.__voodbuilderComponentsCatalog = catalog;
        editor.__voodbuilderSyncComponentsCatalog = syncCatalog;
        const libraryId = getActiveLibraryId();
        editor.__voodbuilderActiveLibrary = libraryId;
        registerComponentBlocks(editor, catalog, labels, categories, uncategorizedLabel);
        hydrateComponentInstances(editor, catalog);
        injectComponentCatalogCss(editor, catalog);
        updateEmptyState();
        relocateComponentBlocksLibrary(editor, libraryId, libraryMounts);
        updateBlocksSelectionState();
        tagComponentBlockElements(editor);
        tagComponentCategoryElements(editor);
        applyBlocksLibraryUi(editor, readBlocksSearchQuery());

        window.requestAnimationFrame(() => {
            refreshComponentBlocksLibrary(editor, getActiveLibraryId(), libraryMounts);
            tagComponentBlockElements(editor);
            tagComponentCategoryElements(editor);
            applyBlocksLibraryUi(editor, readBlocksSearchQuery());
            ensureComponentsLibraryVisible(editor, libraryMounts, getActiveLibraryId());
        });

        if (libraryId === 'components') {
            preserveComponentsLibraryTab();
        }
    };

    const setImportPanelOpen = (open) => {
        if (! importPanel) {
            return;
        }

        importPanel.hidden = ! open;
        importPanel.classList.toggle('is-open', open);
        importDropzone?.classList.remove('is-dragover');
    };

    const exportComponents = async (items, filename) => {
        if (! items.length) {
            return;
        }

        const ids = items
            .map((item) => item?.id)
            .filter((id) => id != null && id !== '');

        try {
            const response = await fetch(`${componentsUrl.replace(/\/$/, '')}/export`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf, { json: true }),
                body: JSON.stringify({ ids }),
            });

            if (! response.ok) {
                await alertDialog({
                    message: await resolveApiErrorMessage(
                        response,
                        labels.componentsExportError ?? 'Could not export components.',
                        labels,
                    ),
                    labels,
                });

                return;
            }

            const payload = await response.json();
            downloadComponentsExportPayload(payload, filename);
        } catch {
            await alertDialog({
                message: labels.componentsExportError ?? 'Could not export components.',
                labels,
            });
        }
    };

    const exportCatalog = async () => {
        await exportComponents(catalog, exportBundleFilename());
    };

    const exportSelectedComponents = async () => {
        const items = catalog.filter((item) => selectedIds.has(String(item.id)));
        await exportComponents(items, exportBundleFilename('selected'));
    };

    const exportComponent = async (item) => {
        await exportComponents([item], exportComponentFilename(item.name));
    };

    const setSelectionMode = (active) => {
        selectionMode = active;
        editor.__voodbuilderComponentSelectionMode = active;

        if (! selectionMode) {
            selectedIds.clear();
        } else {
            expandLibraryCategories(editor, 'components');
        }

        syncComponentBlockDragState(editor, selectionMode);
        tagComponentBlockElements(editor);
        updateSelectionUi();
        updateBlocksSelectionState();
        syncComponentBlockQuickActions(editor);
    };

    const toggleComponentSelection = (item) => {
        const key = String(item.id);

        if (selectedIds.has(key)) {
            selectedIds.delete(key);
        } else {
            selectedIds.add(key);
        }

        updateSelectionUi();
        updateBlocksSelectionState();
    };

    const resolveCatalogItemFromBlockElement = (blockEl) => resolveCatalogItemFromComponentBlock(editor, blockEl);

    const updateBlocksSelectionState = () => {
        const container = editor.BlockManager?.getContainer?.();

        if (! container) {
            return;
        }

        container.querySelectorAll('.gjs-block').forEach((blockEl) => {
            if (! isComponentBlockElement(editor, blockEl)) {
                return;
            }

            const item = resolveCatalogItemFromBlockElement(blockEl);
            const isSelected = item ? selectedIds.has(String(item.id)) : false;

            blockEl.classList.toggle('is-selected', selectionMode && isSelected);
            blockEl.classList.toggle('is-selectable', selectionMode);
            blockEl.setAttribute('aria-pressed', selectionMode && isSelected ? 'true' : 'false');

            let badge = blockEl.querySelector('[data-voodbuilder-component-selection-badge]');

            if (selectionMode) {
                if (! badge) {
                    badge = document.createElement('span');
                    badge.className = 'voodbuilder-editor-component-block__selection-badge';
                    badge.dataset.voodbuilderComponentSelectionBadge = '';
                    badge.setAttribute('aria-hidden', 'true');
                    badge.innerHTML = lucideIcon('check', 12);
                    blockEl.appendChild(badge);
                }

                badge.hidden = ! isSelected;
            } else {
                badge?.remove();
            }
        });
    };

    const updateSelectionUi = () => {
        const library = componentsMount.querySelector('[data-voodbuilder-components-library]');
        const bar = componentsMount.querySelector('[data-voodbuilder-components-selection-bar]');
        const header = componentsMount.querySelector('.voodbuilder-editor-components-library__header');
        const toggle = componentsMount.querySelector('[data-voodbuilder-components-select-toggle]');
        const countEl = componentsMount.querySelector('[data-voodbuilder-components-selection-count]');
        const exportSelectedButton = componentsMount.querySelector('[data-voodbuilder-components-export-selected]');
        const deleteSelectedButton = componentsMount.querySelector('[data-voodbuilder-components-delete-selected]');
        const count = selectedIds.size;
        const countLabel = (labels.componentsSelectedCount ?? '{count} selected').replace('{count}', String(count));

        library?.classList.toggle('is-selection-mode', selectionMode);
        bar?.toggleAttribute('hidden', ! selectionMode);
        header?.toggleAttribute('hidden', selectionMode);
        toggle?.classList.toggle('is-active', selectionMode);
        toggle?.setAttribute('aria-pressed', selectionMode ? 'true' : 'false');

        if (countEl) {
            countEl.textContent = countLabel;
        }

        if (exportSelectedButton) {
            exportSelectedButton.disabled = count === 0;
        }

        if (deleteSelectedButton) {
            deleteSelectedButton.disabled = count === 0;
        }
    };

    const importComponents = async (entries, importMeta = null) => {
        if (! Array.isArray(entries) || entries.length === 0) {
            await alertDialog({
                message: labels.componentsImportInvalidFile ?? 'Invalid JSON file.',
                labels,
            });

            return;
        }

        try {
            const response = await fetch(`${componentsUrl.replace(/\/$/, '')}/import`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf, { json: true }),
                body: JSON.stringify({
                    components: entries,
                    import_meta: importMeta,
                }),
            });

            if (! response.ok) {
                await alertDialog({
                    message: labels.componentsImportError ?? 'Could not import components.',
                    labels,
                });

                return;
            }

            const payload = await response.json();
            const imported = payload.components ?? [];

            if (imported.length === 0) {
                await alertDialog({
                    message: labels.componentsImportError ?? 'Could not import components.',
                    labels,
                });

                return;
            }

            for (const item of imported) {
                upsertCatalogEntry(item);
            }

            focusComponentsLibrary();
            syncCatalog();
            setImportPanelOpen(false);

            const message = (labels.componentsImportSuccess ?? 'Imported {count} components.')
                .replace('{count}', String(imported.length));

            await alertDialog({ message, labels });
        } catch {
            await alertDialog({
                message: labels.componentsImportError ?? 'Could not import components.',
                labels,
            });
        }
    };

    const handleImportFiles = async (files) => {
        const list = [...files].filter((file) => file.type === 'application/json' || file.name.endsWith('.json'));

        if (list.length === 0) {
            await alertDialog({
                message: labels.componentsImportInvalidFile ?? 'Invalid JSON file.',
                labels,
            });

            return;
        }

        const entries = [];
        let importMeta = null;

        for (const file of list) {
            try {
                const text = await file.text();
                const parsed = JSON.parse(text);

                if (importMeta === null) {
                    importMeta = resolveImportMeta(parsed);
                }

                entries.push(...parseImportPayload(parsed));
            } catch {
                await alertDialog({
                    message: labels.componentsImportInvalidFile ?? 'Invalid JSON file.',
                    labels,
                });

                return;
            }
        }

        await importComponents(entries, importMeta);
    };

    const removeComponentFromLibrary = async (item, { skipConfirm = false } = {}) => {
        const componentId = item?.id;

        if (! componentId) {
            await alertDialog({
                message: labels.componentsDeleteError ?? 'Could not delete component.',
                labels,
            });

            return false;
        }

        if (deletingIds.has(String(componentId))) {
            return false;
        }

        if (! skipConfirm) {
            const message = (labels.componentsDeleteConfirm ?? 'Delete “{name}” from the library?')
                .replace('{name}', item.name ?? '');

            const confirmed = await confirmDialog({
                title: labels.componentsDelete ?? 'Delete from library',
                message,
                labels,
                confirmLabel: labels.dialogDelete ?? labels.componentsDelete ?? 'Delete',
                danger: true,
            });

            if (! confirmed) {
                return false;
            }
        }

        deletingIds.add(String(componentId));

        try {
            const response = await fetch(componentItemUrl(componentsUrl, componentId), {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf),
            });

            if (! response.ok && response.status !== 404) {
                await alertDialog({
                    message: await resolveApiErrorMessage(
                        response,
                        labels.componentsDeleteError ?? 'Could not delete component.',
                        labels,
                    ),
                    labels,
                });

                return false;
            }

            catalog = catalog.filter((entry) => String(entry.id) !== String(componentId));
            selectedIds.delete(String(componentId));

            try {
                syncCatalog();
                updateSelectionUi();
                preserveComponentsLibraryTab();
            } catch (syncError) {
                console.error('VoodBuilder: component deleted but library UI failed to refresh.', syncError);
                updateSelectionUi();
            }

            return true;
        } catch {
            await alertDialog({
                message: labels.componentsDeleteError ?? 'Could not delete component.',
                labels,
            });

            return false;
        } finally {
            deletingIds.delete(String(componentId));
        }
    };

    const deleteComponent = async (item) => {
        await removeComponentFromLibrary(item);
    };

    const deleteSelectedComponents = async () => {
        const items = catalog.filter((entry) => selectedIds.has(String(entry.id)));

        if (items.length === 0) {
            return;
        }

        const message = (labels.componentsDeleteSelectedConfirm ?? 'Delete {count} components from the library?')
            .replace('{count}', String(items.length));

        const confirmed = await confirmDialog({
            title: labels.componentsDeleteSelected ?? 'Delete selected',
            message,
            labels,
            confirmLabel: labels.dialogDelete ?? labels.componentsDelete ?? 'Delete',
            danger: true,
        });

        if (! confirmed) {
            return;
        }

        for (const item of items) {
            await removeComponentFromLibrary(item, { skipConfirm: true });
        }

        if (selectedIds.size === 0) {
            setSelectionMode(false);
        }
    };

    const editComponent = async (item) => {
        const updated = await openComponentCodeEditorDialog({
            componentsUrl,
            csrf,
            labels,
            canvasStyles,
            componentCategories: categories,
            component: item,
        });

        if (! updated) {
            return;
        }

        upsertCatalogEntry(updated);
        focusComponentsLibrary();
        syncCatalog();
        reapplyComponentInstancesOnCanvas(editor, updated);
    };

    const openComponentMenu = (item, x, y) => {
        openContextMenu({
            x,
            y,
            context: item,
            items: [
                {
                    id: 'edit',
                    label: labels.componentsEdit ?? 'Edit component',
                    onSelect: (component) => editComponent(component),
                },
                {
                    id: 'export',
                    label: labels.componentsExportOne ?? 'Export component',
                    onSelect: (component) => exportComponent(component),
                },
                {
                    id: 'delete',
                    label: labels.componentsDelete ?? 'Delete from library',
                    danger: true,
                    onSelect: (component) => deleteComponent(component),
                },
            ],
        });
    };

    componentsMount.querySelector('[data-voodbuilder-components-code-import]')?.addEventListener('click', async () => {
        const created = await openComponentCodeImportDialog({
            componentsUrl,
            csrf,
            labels,
            canvasStyles,
            componentCategories: categories,
        });

        if (! created) {
            return;
        }

        upsertCatalogEntry(created);
        focusComponentsLibrary();
        syncCatalog();
    });

    componentsMount.querySelector('[data-voodbuilder-components-import-toggle]')?.addEventListener('click', () => {
        setImportPanelOpen(importPanel?.hidden !== false);
    });

    componentsMount.querySelector('[data-voodbuilder-components-import-cancel]')?.addEventListener('click', () => {
        setImportPanelOpen(false);
    });

    componentsMount.querySelector('[data-voodbuilder-components-export]')?.addEventListener('click', () => {
        exportCatalog();
    });

    componentsMount.querySelector('[data-voodbuilder-components-select-toggle]')?.addEventListener('click', () => {
        setSelectionMode(! selectionMode);
    });

    componentsMount.querySelector('[data-voodbuilder-components-select-cancel]')?.addEventListener('click', () => {
        setSelectionMode(false);
    });

    componentsMount.querySelector('[data-voodbuilder-components-export-selected]')?.addEventListener('click', () => {
        exportSelectedComponents();
    });

    componentsMount.querySelector('[data-voodbuilder-components-delete-selected]')?.addEventListener('click', () => {
        void deleteSelectedComponents();
    });

    componentsMount.querySelector('[data-voodbuilder-components-import-select]')?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        importInput?.click();
    });

    importDropzone?.addEventListener('click', (event) => {
        if (event.target.closest('[data-voodbuilder-components-import-select]')) {
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

        if (! event.dataTransfer?.files?.length) {
            return;
        }

        await handleImportFiles(event.dataTransfer.files);
    });

    const loadCatalog = async () => {
        const loadId = ++catalogLoadVersion;
        let response = null;

        for (let attempt = 0; attempt < 2; attempt++) {
            try {
                response = await fetch(componentsUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                break;
            } catch {
                if (loadId !== catalogLoadVersion) {
                    return;
                }

                if (attempt === 1) {
                    emptyStateEl?.removeAttribute('hidden');
                    emptyStateEl?.querySelector('.voodbuilder-editor-components-empty__title')
                        ?.replaceChildren(document.createTextNode(labels.componentsLoadError ?? 'Could not load components.'));
                    emptyStateEl?.querySelector('.voodbuilder-editor-components-empty__hint')
                        ?.replaceChildren(document.createTextNode(labels.componentsLoadErrorHint ?? 'Check your connection and reload the editor.'));
                    markComponentCatalogCssReady(editor);

                    return;
                }

                await new Promise((resolve) => window.setTimeout(resolve, 750));
            }
        }

        try {
            if (! response?.ok || loadId !== catalogLoadVersion) {
                if (loadId === catalogLoadVersion && response && ! response.ok) {
                    emptyStateEl?.removeAttribute('hidden');
                    emptyStateEl?.querySelector('.voodbuilder-editor-components-empty__title')
                        ?.replaceChildren(document.createTextNode(labels.componentsLoadError ?? 'Could not load components.'));
                    emptyStateEl?.querySelector('.voodbuilder-editor-components-empty__hint')
                        ?.replaceChildren(document.createTextNode(labels.componentsLoadErrorHint ?? 'Check your connection and reload the editor.'));
                }

                markComponentCatalogCssReady(editor);

                return;
            }

            const payload = await response.json();
            const remote = (payload.components ?? []).map(normalizeCatalogEntry);
            const remoteIds = new Set(remote.map((item) => String(item.id)));
            const localOnly = catalog.filter((item) => ! remoteIds.has(String(item.id)));

            catalog = [...remote, ...localOnly];
            syncCatalog();
            updateEmptyState();
            ensureComponentsLibraryVisible(editor, libraryMounts, getActiveLibraryId());
        } catch (error) {
            console.error('Voodbuilder Editor: could not sync component catalog.', error);

            if (loadId !== catalogLoadVersion) {
                return;
            }

            emptyStateEl?.removeAttribute('hidden');
            emptyStateEl?.querySelector('.voodbuilder-editor-components-empty__title')
                ?.replaceChildren(document.createTextNode(labels.componentsLoadError ?? 'Could not load components.'));
            markComponentCatalogCssReady(editor);
        }
    };

    const upsertCatalogEntry = (entry) => {
        const normalized = normalizeCatalogEntry(entry);
        const index = catalog.findIndex((item) => String(item.id) === String(normalized.id));

        if (index === -1) {
            catalog.push(normalized);

            return normalized;
        }

        catalog[index] = normalized;

        return normalized;
    };

    componentsMount.querySelector('[data-voodbuilder-save-component]')?.addEventListener('click', async () => {
        const selected = editor.getSelected();

        if (! selected) {
            await alertDialog({
                message: labels.componentsSaveNeedSelection
                    ?? labels.selectComponent
                    ?? 'Select an element on the canvas to save it as a reusable component.',
                labels,
            });

            return;
        }

        const saved = await saveComponentToCatalog(editor, selected, {
            componentsUrl,
            csrf,
            labels,
            categories,
            uncategorizedLabel,
            onSaved: (entry) => {
                upsertCatalogEntry(entry);
                focusComponentsLibrary();
                syncCatalog();
            },
        });

        if (! saved) {
            return;
        }
    });

    const bootstrapComponentBlocksLibrary = () => {
        hydrateComponentInstances(editor, catalog);
        refreshComponentBlocksLibrary(editor, getActiveLibraryId(), libraryMounts);
        updateEmptyState();
        applyBlocksLibraryUi(editor, readBlocksSearchQuery());
    };

    editor.on('load', bootstrapComponentBlocksLibrary);

    if (editor.getWrapper?.()) {
        bootstrapComponentBlocksLibrary();
    }

    editor.__voodbuilderComponentLibraryActions = {
        edit: editComponent,
        delete: deleteComponent,
        export: exportComponent,
        openMenu: openComponentMenu,
        saveFromCanvas: async (component) => {
            const target = component ?? editor.getSelected();

            if (! target) {
                await alertDialog({
                    message: labels.componentsSaveNeedSelection
                        ?? labels.selectComponent
                        ?? 'Select an element on the canvas to save it as a reusable component.',
                    labels,
                });

                return;
            }

            await saveComponentToCatalog(editor, target, {
                componentsUrl,
                csrf,
                labels,
                categories,
                uncategorizedLabel,
                onSaved: (entry) => {
                    upsertCatalogEntry(entry);
                    focusComponentsLibrary();
                    syncCatalog();
                },
            });
        },
    };
    editor.__voodbuilderToggleComponentSelection = toggleComponentSelection;

    bindComponentLibraryBlockInteractions(editor);

    editor.on('component:add', (component) => {
        const attrs = component.getAttributes?.({ noClass: true, noStyle: true }) ?? {};

        if (attrs[COMPONENT_ATTR]) {
            window.requestAnimationFrame(() => hydrateComponentInstance(component, catalog, editor));
        }
    });
    editor.on('canvas:frame:load', () => injectComponentCatalogCss(editor, catalog));

    registerComponentPropsUi(editor, {
        mount: componentPropsMount,
        getCatalog: () => catalog,
        labels,
    });

    updateSelectionUi();

    editor.on('load', () => {
        window.requestAnimationFrame(() => {
            applyBlocksLibraryUi(editor, readBlocksSearchQuery());
        });
    });

    void loadCatalog();
}

function registerComponentBlocks(editor, catalog, labels = {}, componentCategories = [], uncategorizedLabel = 'General') {
    const blockManager = editor.BlockManager;
    const categories = blockManager.getCategories?.();

    const blockIdsToRemove = [];

    blockManager.getAll().forEach((block) => {
        const blockId = block.get?.('id') ?? block.id;

        if (isComponentBlockId(blockId)) {
            blockIdsToRemove.push(blockId);
        }
    });

    blockIdsToRemove.forEach((blockId) => {
        if (blockManager.get(blockId)) {
            blockManager.remove(blockId);
        }
    });

    const categoryIdsToRemove = [];

    categories?.each?.((category) => {
        const id = String(category.get('id') ?? '');

        if (id.startsWith(CATEGORY_PREFIX)) {
            categoryIdsToRemove.push(id);
        }
    });

    categoryIdsToRemove.forEach((categoryId) => {
        const category = categories?.get?.(categoryId);

        if (category) {
            categories.remove(category);
        }
    });

    const seenCategories = new Set();

    for (const item of catalog) {
        const categoryLabel = normalizeComponentCategory(item.category, componentCategories, uncategorizedLabel);
        const categoryId = `${CATEGORY_PREFIX}${slugify(categoryLabel)}`;

        if (! seenCategories.has(categoryId)) {
            const categoryAttributes = componentCategoryAttributes(categoryId);
            const existingCategory = categories?.get?.(categoryId);

            if (existingCategory) {
                existingCategory.set({
                    open: false,
                    attributes: categoryAttributes,
                });
            } else {
                blockManager.getCategories().add({
                    id: categoryId,
                    label: categoryLabel.toUpperCase(),
                    order: resolveCategoryOrder(categoryLabel),
                    open: false,
                    attributes: categoryAttributes,
                });
            }

            seenCategories.add(categoryId);
        }

        const blockId = `${BLOCK_PREFIX}${item.id}`;

        if (blockManager.get(blockId)) {
            blockManager.remove(blockId);
        }

        blockManager.add(blockId, {
            label: item.name,
            category: categoryId,
            content: buildComponentContent(item),
            media: buildComponentBlockMedia(item),
            attributes: {
                id: blockId,
                class: 'voodbuilder-editor-component-block',
                'data-voodbuilder-component-block': '1',
                'data-gjs-block-id': blockId,
            },
            select: true,
            activate: false,
        });
    }

    const activeLibrary = editor.__voodbuilderActiveLibrary ?? 'blocks';

    if (! editor.__voodbuilderComponentBlocksHooked) {
        editor.__voodbuilderComponentBlocksHooked = true;
        editor.on('block:add', () => {
            window.requestAnimationFrame(() => {
                applyBlocksLibraryUi(editor, readBlocksSearchQuery());
            });
        });
        editor.on('block:remove', () => {
            applyBlocksLibraryUi(editor, readBlocksSearchQuery());
        });
    }

    if (blockManager.getContainer()) {
        const mounts = editor.__voodbuilderLibraryMounts;
        const activeLibrary = editor.__voodbuilderActiveLibrary ?? 'blocks';

        if (mounts) {
            refreshComponentBlocksLibrary(editor, activeLibrary, mounts);
        }
    }
}

function scheduleTagComponentBlockElements(editor) {
    window.requestAnimationFrame(() => {
        tagComponentBlockElements(editor);
        tagComponentCategoryElements(editor);
        applyBlocksLibraryUi(editor, readBlocksSearchQuery());
    });
}

function bindComponentLibraryBlockInteractions(editor) {
    const attachToContainer = () => {
        const container = editor.BlockManager?.getContainer?.();

        if (! container || container.dataset.voodbuilderComponentLibraryBound === 'true') {
            return;
        }

        container.dataset.voodbuilderComponentLibraryBound = 'true';

        container.addEventListener('contextmenu', (event) => {
            if (editor.__voodbuilderActiveLibrary !== 'components') {
                return;
            }

            if (editor.__voodbuilderComponentSelectionMode === true) {
                return;
            }

            if (event.target.closest('[data-voodbuilder-component-block-toolbar]')) {
                return;
            }

            const blockEl = event.target.closest('.gjs-block');

            if (! blockEl || ! isComponentBlockElement(editor, blockEl)) {
                return;
            }

            const item = resolveCatalogItemFromComponentBlock(editor, blockEl);

            if (! item) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            editor.__voodbuilderComponentLibraryActions?.openMenu?.(item, event.clientX, event.clientY);
        }, true);

        container.addEventListener('mousedown', (event) => {
            if (event.button !== 0) {
                return;
            }

            if (editor.__voodbuilderActiveLibrary !== 'components') {
                return;
            }

            if (editor.__voodbuilderComponentSelectionMode !== true) {
                return;
            }

            if (event.target.closest('[data-voodbuilder-component-block-toolbar]')) {
                return;
            }

            const blockEl = event.target.closest('.gjs-block');

            if (! blockEl || ! isComponentBlockElement(editor, blockEl)) {
                return;
            }

            const item = resolveCatalogItemFromComponentBlock(editor, blockEl);

            if (! item) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            editor.__voodbuilderToggleComponentSelection?.(item);
        }, true);

        container.addEventListener('click', (event) => {
            if (editor.__voodbuilderActiveLibrary !== 'components') {
                return;
            }

            if (editor.__voodbuilderComponentSelectionMode !== true) {
                return;
            }

            const blockEl = event.target.closest('.gjs-block');

            if (! blockEl || ! isComponentBlockElement(editor, blockEl)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
        }, true);
    };

    attachToContainer();

    if (! editor.__voodbuilderComponentLibraryInteractionsHooked) {
        editor.__voodbuilderComponentLibraryInteractionsHooked = true;

        editor.on('block:add', () => {
            window.requestAnimationFrame(attachToContainer);
        });

        editor.on('block:remove', () => {
            window.requestAnimationFrame(attachToContainer);
        });
    }
}

function syncComponentBlockDragState(editor, selectionMode) {
    editor.BlockManager?.getAll?.()?.forEach((block) => {
        const blockId = block.get?.('id') ?? block.id;

        if (! isComponentBlockId(blockId)) {
            return;
        }

        block.set('draggable', ! selectionMode);
    });
}

function syncComponentBlockQuickActions(editor) {
    const actions = editor.__voodbuilderComponentLibraryActions;
    const container = editor.BlockManager?.getContainer?.();

    if (! actions || ! container) {
        return;
    }

    const selectionMode = editor.__voodbuilderComponentSelectionMode === true;

    container.querySelectorAll('.gjs-block').forEach((blockEl) => {
        if (! isComponentBlockElement(editor, blockEl)) {
            return;
        }

        const item = resolveCatalogItemFromComponentBlock(editor, blockEl);

        if (! item) {
            return;
        }

        let toolbar = blockEl.querySelector('[data-voodbuilder-component-block-toolbar]');

        if (toolbar && (
            ! toolbar.querySelector('[data-voodbuilder-component-block-export]')
            || toolbar.querySelector('[data-voodbuilder-component-block-menu]')
        )) {
            toolbar.remove();
            toolbar = null;
        }

        if (! toolbar) {
            toolbar = document.createElement('div');
            toolbar.className = 'voodbuilder-editor-component-block__toolbar';
            toolbar.dataset.voodbuilderComponentBlockToolbar = '';
            toolbar.setAttribute('role', 'toolbar');

            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.className = 'voodbuilder-editor-component-block__toolbar-btn';
            editButton.dataset.voodbuilderComponentBlockEdit = '';
            editButton.title = 'Edit';
            editButton.setAttribute('aria-label', 'Edit');
            editButton.innerHTML = lucideIcon('pencil', 16);

            const exportButton = document.createElement('button');
            exportButton.type = 'button';
            exportButton.className = 'voodbuilder-editor-component-block__toolbar-btn';
            exportButton.dataset.voodbuilderComponentBlockExport = '';
            exportButton.title = 'Export';
            exportButton.setAttribute('aria-label', 'Export');
            exportButton.innerHTML = lucideIcon('download', 16);

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'voodbuilder-editor-component-block__toolbar-btn voodbuilder-editor-component-block__toolbar-btn--danger';
            deleteButton.dataset.voodbuilderComponentBlockDelete = '';
            deleteButton.title = 'Delete';
            deleteButton.setAttribute('aria-label', 'Delete');
            deleteButton.innerHTML = lucideIcon('trash-2', 16);

            toolbar.append(editButton, exportButton, deleteButton);
            blockEl.appendChild(toolbar);

            editButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                actions.edit?.(item);
            });

            exportButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                actions.export?.(item);
            });

            deleteButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                actions.delete?.(item);
            });

            blockEl.addEventListener('contextmenu', (event) => {
                if (editor.__voodbuilderComponentSelectionMode === true) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                actions.openMenu?.(item, event.clientX, event.clientY);
            });
        }

        toolbar.hidden = selectionMode;
    });

    if (editor.__voodbuilderComponentSelectionMode === true) {
        editor.BlockManager?.getContainer?.()?.querySelectorAll('.gjs-block').forEach((blockEl) => {
            if (! isComponentBlockElement(editor, blockEl)) {
                return;
            }

            blockEl.classList.toggle('is-selectable', true);
        });
    }
}

function ensureComponentsLibraryVisible(editor, mounts, libraryId = 'components') {
    if (libraryId !== 'components' || ! mounts?.componentsBlocks) {
        return;
    }

    const container = editor.BlockManager?.getContainer?.();

    if (! container) {
        return;
    }

    editor.__voodbuilderActiveLibrary = 'components';
    markLibraryMounts(mounts, 'components');

    if (container.parentElement !== mounts.componentsBlocks) {
        mounts.componentsBlocks.appendChild(container);
    }

    editor.BlockManager?.render?.();
    tagComponentBlockElements(editor);
    tagComponentCategoryElements(editor);
    collapseLibraryCategories(editor, 'components');
    applyBlocksLibraryUi(editor, readBlocksSearchQuery());
}

function tagComponentCategoryElements(editor) {
    editor.BlockManager?.getCategories?.()?.forEach?.((category) => {
        const categoryId = String(category.get?.('id') ?? category.id ?? '');

        if (! isComponentCategoryId(categoryId)) {
            return;
        }

        const categoryEl = category.view?.el;

        if (! categoryEl) {
            return;
        }

        categoryEl.classList.add('voodbuilder-editor-component-category');
        categoryEl.setAttribute('data-voodbuilder-component-category', '1');
        categoryEl.setAttribute('data-voodbuilder-category-id', categoryId);
        categoryEl.dataset.voodbuilderCategoryId = categoryId;
    });

    const container = editor.BlockManager?.getContainer?.();

    if (! container) {
        return;
    }

    container.querySelectorAll('.gjs-block-category').forEach((categoryEl) => {
        if (categoryEl.hasAttribute('data-voodbuilder-component-category')) {
            return;
        }

        let matched = false;
        let categoryId = '';

        editor.BlockManager?.getCategories?.()?.each?.((category) => {
            if (category.view?.el !== categoryEl) {
                return;
            }

            categoryId = String(category.get('id') ?? '');
            matched = isComponentCategoryId(categoryId);
        });

        if (! matched) {
            return;
        }

        categoryEl.classList.add('voodbuilder-editor-component-category');
        categoryEl.setAttribute('data-voodbuilder-component-category', '1');

        if (categoryId) {
            categoryEl.setAttribute('data-voodbuilder-category-id', categoryId);
            categoryEl.dataset.voodbuilderCategoryId = categoryId;
        }
    });
}

function tagComponentBlockElements(editor) {
    editor.BlockManager?.getAll?.()?.forEach((block) => {
        const blockId = block.get?.('id') ?? block.id;

        if (! isComponentBlockId(blockId)) {
            return;
        }

        block.view?.el?.classList?.add('voodbuilder-editor-component-block');
        block.view?.el?.setAttribute?.('data-voodbuilder-component-block', '1');
        block.view?.el?.setAttribute?.('data-gjs-block-id', String(blockId));
    });

    syncComponentBlockQuickActions(editor);
}

function componentItemUrl(componentsUrl, id) {
    return `${componentsUrl.replace(/\/$/, '')}/${id}`;
}

function downloadComponentsExportPayload(payload, filename) {
    const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    URL.revokeObjectURL(url);
}

function resolveImportMeta(payload) {
    if (! payload || typeof payload !== 'object') {
        return null;
    }

    if (payload.format !== 'voodbuilder-components') {
        return null;
    }

    return {
        format: payload.format,
        format_version: payload.format_version ?? payload.version ?? null,
        generator: payload.generator ?? null,
    };
}

function exportBundleFilename(suffix = '') {
    const date = new Date().toISOString().slice(0, 10);
    const part = suffix ? `-${suffix}` : '';

    return `voodbuilder-components${part}-${date}.json`;
}

function exportComponentFilename(name) {
    const date = new Date().toISOString().slice(0, 10);

    return `voodbuilder-component-${slugify(name || 'component')}-${date}.json`;
}

function markLibraryMounts(mounts, libraryId) {
    const shell = mounts.blocks?.closest('.voodbuilder-editor-shell')
        ?? mounts.componentsBlocks?.closest('.voodbuilder-editor-shell')
        ?? mounts.templateBlocks?.closest('.voodbuilder-editor-shell');

    shell?.setAttribute('data-voodbuilder-active-library', libraryId);

    mounts.blocks?.removeAttribute('data-voodbuilder-blocks-library');
    mounts.componentsBlocks?.removeAttribute('data-voodbuilder-blocks-library');
    mounts.templateBlocks?.removeAttribute('data-voodbuilder-blocks-library');

    if (libraryId === 'blocks') {
        mounts.blocks?.setAttribute('data-voodbuilder-blocks-library', 'blocks');
    } else if (libraryId === 'templates') {
        mounts.templateBlocks?.setAttribute('data-voodbuilder-blocks-library', 'templates');
    } else {
        mounts.componentsBlocks?.setAttribute('data-voodbuilder-blocks-library', 'components');
    }
}


export function refreshComponentBlocksLibrary(editor, libraryId, mounts = {}) {
    const container = editor.BlockManager?.getContainer?.();
    const target = libraryId === 'components'
        ? mounts.componentsBlocks
        : libraryId === 'templates'
            ? mounts.templateBlocks
            : mounts.blocks;

    if (! container || ! target) {
        return;
    }

    editor.__voodbuilderActiveLibrary = libraryId;
    markLibraryMounts(mounts, libraryId);

    if (container.parentElement !== target && target instanceof Element && container instanceof Node) {
        target.appendChild(container);
    }

    editor.BlockManager?.render?.();
    tagComponentBlockElements(editor);
    tagComponentCategoryElements(editor);

    collapseLibraryCategories(editor, libraryId);

    scheduleTagComponentBlockElements(editor);

    if (libraryId === 'blocks') {
        refreshBlockPinUi(editor, { sync: false });
    }

    if (libraryId === 'components') {
        syncComponentBlockDragState(editor, editor.__voodbuilderComponentSelectionMode === true);
        syncComponentBlockQuickActions(editor);
        bindComponentLibraryBlockInteractions(editor);
    }

    if (libraryId === 'templates') {
        tagPageTemplateBlockElements(editor);
        // Refresh stamps / selection chrome and (re)bind per-card + container listeners.
        editor.__voodbuilderOnTemplateLibraryRefresh?.();
    }

    applyBlocksLibraryUi(editor, readBlocksSearchQuery());
}

export function relocateComponentBlocksLibrary(editor, libraryId, mounts = {}) {
    refreshComponentBlocksLibrary(editor, libraryId, mounts);
}

function parseImportPayload(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (payload && typeof payload === 'object') {
        if (Array.isArray(payload.components)) {
            return payload.components;
        }

        if (typeof payload.html === 'string' && typeof payload.name === 'string') {
            return [payload];
        }
    }

    throw new Error('Invalid component import payload.');
}

function hydrateComponentInstances(editor, catalog) {
    editor.getWrapper().find(`[${COMPONENT_ATTR}]`).forEach((component) => {
        hydrateComponentInstance(component, catalog, editor);
    });
}

function reapplyComponentInstancesOnCanvas(editor, item) {
    if (! item?.id || ! item?.html) {
        return;
    }

    const componentId = String(item.id);
    const matches = editor.getWrapper().find(`[${COMPONENT_ATTR}="${componentId}"]`);

    matches.forEach((component) => {
        component.set(COMPONENT_HYDRATED_KEY, false, { silent: true });
        component.set({
            type: COMPONENT_TYPE,
            name: formatComponentInstanceName(item.name),
            draggable: true,
            droppable: true,
            removable: true,
            copyable: true,
            layerable: true,
            selectable: true,
            highlightable: true,
            editable: false,
        });
        component.components(item.html);
        component.set(COMPONENT_HYDRATED_KEY, true, { silent: true });
        component.addAttributes({
            [COMPONENT_ATTR]: componentId,
            class: 'voodbuilder-editor-component-instance',
        });
    });

    if (matches.length > 0) {
        editor.trigger('update');
    }
}

function hasMeaningfulComponentBody(component) {
    const children = component.components?.() ?? [];

    if (children.length === 0) {
        return false;
    }

    if (component.find?.('.voodbuilder-pasted-component').length > 0) {
        return true;
    }

    return children.some((child) => {
        const tag = String(child.get?.('tagName') ?? '').toLowerCase();

        if (tag && ! ['text', 'textnode', '#text'].includes(tag)) {
            return true;
        }

        const content = String(child.get?.('content') ?? '').trim();

        return content.length > 0;
    });
}

function hydrateComponentInstance(component, catalog, editor = null) {
    const attrs = component.getAttributes?.({ noClass: true, noStyle: true }) ?? {};
    const componentId = attrs[COMPONENT_ATTR];

    if (! componentId) {
        return;
    }

    const item = catalog.find((entry) => String(entry.id) === String(componentId));

    if (! item?.html) {
        return;
    }

    if (component.get(COMPONENT_HYDRATED_KEY)) {
        ensureComponentScopeAttribute(component);
        applyComponentInstancePresentation(component, item);

        return;
    }

    if (hasMeaningfulComponentBody(component) && component.find('.voodbuilder-pasted-component').length > 0) {
        component.set(COMPONENT_HYDRATED_KEY, true, { silent: true });
        ensureComponentScopeAttribute(component);
        applyComponentInstancePresentation(component, item);
        editor?.__voodbuilderScheduleComponentCssRebuild?.(200);

        return;
    }

    component.set({
        type: COMPONENT_TYPE,
        name: formatComponentInstanceName(item.name),
        draggable: true,
        droppable: true,
        removable: true,
        copyable: true,
        layerable: true,
        selectable: true,
        highlightable: true,
        editable: false,
    });

    component.components(item.html);
    component.set(COMPONENT_HYDRATED_KEY, true, { silent: true });

    component.addAttributes({
        [COMPONENT_ATTR]: String(componentId),
        [COMPONENT_SCOPE_ATTR]: String(componentId),
        [PROPS_ATTR]: attrs[PROPS_ATTR] ?? JSON.stringify(defaultProps(item)),
        class: 'voodbuilder-editor-component-instance',
    });

    editor?.__voodbuilderScheduleComponentCssRebuild?.(200);
}

function ensureComponentScopeAttribute(component) {
    const attrs = component.getAttributes?.({ noClass: true, noStyle: true }) ?? {};
    const componentId = attrs[COMPONENT_ATTR];

    if (! componentId || attrs[COMPONENT_SCOPE_ATTR]) {
        return;
    }

    component.addAttributes({
        [COMPONENT_SCOPE_ATTR]: String(componentId),
    });
}

function formatComponentInstanceName(name) {
    return String(name ?? 'Component').trim() || 'Component';
}

function applyComponentInstancePresentation(component, item) {
    component.set({
        type: COMPONENT_TYPE,
        name: formatComponentInstanceName(item?.name),
        draggable: true,
        droppable: true,
        removable: true,
        copyable: true,
        layerable: true,
        selectable: true,
        highlightable: true,
        editable: false,
    });
}

function buildComponentContent(item) {
    return {
        type: COMPONENT_TYPE,
        name: formatComponentInstanceName(item?.name),
        attributes: {
            [COMPONENT_ATTR]: String(item.id),
            [COMPONENT_SCOPE_ATTR]: String(item.id),
            [PROPS_ATTR]: JSON.stringify(defaultProps(item)),
            class: 'voodbuilder-editor-component-instance',
        },
        components: item?.html ?? '',
    };
}

function defaultProps(item) {
    const props = {};

    for (const property of item.properties ?? []) {
        props[property.id] = property.default ?? '';
    }

    return props;
}

function registerComponentPropsUi(editor, options = {}) {
    const { mount, getCatalog, labels = {} } = options;

    if (! mount) {
        return;
    }

    const renderProps = () => {
        const selected = editor.getSelected();
        const componentId = selected?.getAttributes?.()?.[COMPONENT_ATTR];

        if (! selected || ! componentId) {
            mount.replaceChildren();
            mount.hidden = true;

            return;
        }

        const component = getCatalog().find((item) => String(item.id) === String(componentId));
        const props = parseProps(selected.getAttributes()[PROPS_ATTR]);

        if (! component?.properties?.length) {
            mount.replaceChildren();
            mount.hidden = true;

            return;
        }

        mount.hidden = false;

        mount.innerHTML = `
            <section class="voodbuilder-editor-component-props">
                <h3 class="voodbuilder-editor-panel-subtitle">${labels.componentsProps ?? 'Instance properties'}</h3>
                <div class="voodbuilder-editor-form-stack" data-voodbuilder-component-props-form></div>
            </section>
        `;

        const form = mount.querySelector('[data-voodbuilder-component-props-form]');

        for (const property of component.properties) {
            const field = document.createElement('label');
            field.className = 'voodbuilder-editor-form-field';

            const fieldLabel = document.createElement('span');
            fieldLabel.className = 'voodbuilder-editor-form-field__label';
            fieldLabel.textContent = property.label;

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'voodbuilder-editor-input';
            input.value = props[property.id] ?? property.default ?? '';
            input.addEventListener('change', () => {
                const next = {
                    ...parseProps(selected.getAttributes()[PROPS_ATTR]),
                    [property.id]: input.value,
                };
                selected.addAttributes({ [PROPS_ATTR]: JSON.stringify(next) });
            });

            field.append(fieldLabel, input);
            form.appendChild(field);
        }
    };

    editor.on('component:selected', renderProps);
    editor.on('component:deselected', renderProps);
}

function parseProps(raw) {
    if (! raw) {
        return {};
    }

    try {
        return JSON.parse(raw);
    } catch {
        return {};
    }
}

const COMPONENT_THEME_TOKEN_BRIDGE = `
.voodbuilder-editor-component-instance .voodbuilder-pasted-component,
.voodbuilder-component-rendered .voodbuilder-pasted-component,
.VPRichPage .voodbuilder-pasted-component,
.voodbuilder-pasted-component {
    --color-vp-brand-1: inherit;
    --color-vp-brand-2: inherit;
    --color-vp-brand-3: inherit;
    --color-vp-text-1: inherit;
    --color-vp-text-2: inherit;
    --color-vp-text-3: inherit;
    --color-vp-bg: inherit;
    --color-vp-bg-alt: inherit;
    --color-vp-bg-elv: inherit;
    --color-vp-divider: inherit;
    --color-vp-gray-soft: inherit;
    color: var(--color-vp-text-1);
}
`.trim();

function markComponentCatalogCssReady(editor) {
    if (typeof editor.__voodbuilderResolveComponentCatalogCssReady === 'function') {
        editor.__voodbuilderResolveComponentCatalogCssReady();
        editor.__voodbuilderResolveComponentCatalogCssReady = null;
    }
}

function injectComponentCatalogCss(editor, catalog) {
    const frame = editor.Canvas?.getFrameEl?.();

    if (! frame) {
        return;
    }

    const doc = frame.contentDocument ?? frame.contentWindow?.document;

    if (! doc) {
        markComponentCatalogCssReady(editor);

        return;
    }

    let styleEl = doc.getElementById('voodbuilder-component-catalog-css');

    if (! styleEl) {
        styleEl = doc.createElement('style');
        styleEl.id = 'voodbuilder-component-catalog-css';
        doc.head.appendChild(styleEl);
    }

    const catalogCss = catalog
        .map((item) => {
            const css = String(item.css ?? '').trim();

            if (! css) {
                return '';
            }

            return [
                scopeComponentCssToInstance(css, String(item.id)),
                scopeComponentCssToInstance(COMPONENT_THEME_TOKEN_BRIDGE, String(item.id)),
            ].filter(Boolean).join('\n\n');
        })
        .filter(Boolean)
        .join('\n\n');

    styleEl.textContent = catalogCss;
    markComponentCatalogCssReady(editor);
}

function buildComponentBlockMedia(item) {
    const name = String(item?.name ?? '').toLowerCase();

    if (name.includes('article') || name.includes('blog') || name.includes('post')) {
        return thumbWrap(previewSvg(
            '<path d="M10 14h28M10 20h24M10 26h18" />'
            + '<rect x="10" y="31" width="28" height="7" rx="1.5" />',
        ));
    }

    if (name.includes('hero') || name.includes('header')) {
        return thumbWrap(previewSvg(
            '<path d="M10 16h28M12 22h20" />'
            + '<rect x="14" y="28" width="20" height="5" rx="1.5" />',
        ));
    }

    if (name.includes('pricing') || name.includes('price') || name.includes('plan')) {
        return thumbWrap(previewSvg(
            '<rect x="9" y="12" width="12" height="20" rx="2" />'
            + '<rect x="23" y="8" width="16" height="24" rx="2" />',
        ));
    }

    if (name.includes('stat') || name.includes('metric')) {
        return thumbWrap(previewSvg(
            '<path d="M10 16h28M12 22h20" />'
            + '<rect x="14" y="28" width="20" height="5" rx="1.5" />',
        ));
    }

    if (name.includes('footer')) {
        return thumbWrap(previewSvg(
            '<rect x="8" y="30" width="9" height="8" rx="1.5" />'
            + '<rect x="19.5" y="30" width="9" height="8" rx="1.5" />'
            + '<rect x="31" y="30" width="9" height="8" rx="1.5" />',
        ));
    }

    return thumbWrap(previewSvg(
        '<rect x="11" y="13" width="26" height="22" rx="2.5" />',
    ));
}

function slugify(value) {
    return String(value ?? 'general')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'general';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Embed catalog HTML into empty component instances so getHtml() matches the canvas.
 */
export function ensureComponentInstancesForExport(editor) {
    const catalog = editor.__voodbuilderComponentsCatalog;

    if (! editor?.getWrapper || ! Array.isArray(catalog) || catalog.length === 0) {
        return;
    }

    editor.getWrapper().find(`[${COMPONENT_ATTR}]`).forEach((component) => {
        if (! hasMeaningfulComponentBody(component)) {
            hydrateComponentInstance(component, catalog, editor);
        }
    });
}

/**
 * Normalize component instance attributes before save (keep inner HTML for per-page edits).
 */
export function syncComponentInstancesForExport(editor) {
    if (! editor?.getWrapper) {
        return;
    }

    editor.getWrapper().find(`[${COMPONENT_ATTR}]`).forEach((component) => {
        const attrs = component.getAttributes?.({ noClass: true, noStyle: true }) ?? {};
        const componentId = attrs[COMPONENT_ATTR];

        if (! componentId) {
            return;
        }

        const props = attrs[PROPS_ATTR];
        const existingClasses = (component.getClasses?.() ?? [])
            .filter((className) => className && className !== 'voodbuilder-editor-component-instance');
        const nextAttributes = {
            [COMPONENT_ATTR]: String(componentId),
            [COMPONENT_SCOPE_ATTR]: String(componentId),
            class: ['voodbuilder-editor-component-instance', ...existingClasses].join(' ').trim(),
        };

        if (props) {
            nextAttributes[PROPS_ATTR] = props;
        }

        component.addAttributes(nextAttributes);
        syncInstanceBackgroundClassesToInner(component);

        const inlineStyle = component.getStyle?.({ inline: true }) ?? {};

        if (Object.keys(inlineStyle).length > 0) {
            component.addStyle(inlineStyle, { inline: true });
        }
    });
}

function syncInstanceBackgroundClassesToInner(instance) {
    const wrapperClasses = (instance.getClasses?.() ?? [])
        .filter((className) => className && className !== 'voodbuilder-editor-component-instance');
    const backgroundClasses = extractBackgroundUtilityClasses(wrapperClasses);
    const pastedComponents = instance.find?.('.voodbuilder-pasted-component') ?? [];

    if (pastedComponents.length === 0) {
        return;
    }

    const pastedRoot = pastedComponents[0];
    const existingClasses = pastedRoot.getClasses?.() ?? [];
    const withoutBackground = existingClasses.filter((className) => {
        return ! isBackgroundUtilityClass(className)
            && className !== 'bg-primary'
            && ! /^bg-vp-brand-\d+$/.test(className);
    });

    if (backgroundClasses.length === 0) {
        return;
    }

    pastedRoot.setClass([...withoutBackground, ...backgroundClasses]);
}

/**
 * Bake forwarded background styles into component instance HTML and drop conflicting bg-* classes.
 */
export function syncComponentInstancePaintForExport(editor) {
    if (! editor?.getWrapper) {
        return;
    }

    editor.getWrapper().find(`[${COMPONENT_ATTR}]`).forEach((instance) => {
        const nodes = [instance, ...instance.find('*')];

        for (const component of nodes) {
            const inline = component.getStyle?.({ inline: true }) ?? {};
            const background = inline['background-color'] ?? inline.background;

            if (background == null || background === '' || isClearedBackground(background)) {
                continue;
            }

            stripBackgroundClasses(component);
            component.addStyle({
                'background-color': background,
            }, { inline: true });
        }
    });
}
