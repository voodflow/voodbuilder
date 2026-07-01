/**
 * Reusable components library — draggable instances like blocks (Bricks-style).
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { openContextMenu } from './context-menu.js';
import { openComponentCodeImportDialog, openComponentCodeEditorDialog } from './component-code-import.js';
import { normalizeComponentCategory, resolveComponentCategories } from './component-categories.js';
import { stripEmbeddableMediaFromHtml } from './component-media.js';
import { alertDialog, componentMetaDialog, confirmDialog } from './editor-dialog.js';
import { editorApiHeaders, resolveApiErrorMessage } from './editor-api.js';
import { lucideIcon } from './editor-icons.js';
import { resolveCategoryOrder } from './section-block-meta.js';
import {
    COMPONENT_BLOCK_PREFIX,
    COMPONENT_CATEGORY_PREFIX,
    isComponentBlock,
    isComponentBlockElement,
    isComponentBlockId,
    isComponentCategoryId,
    resolveBlockFromElement,
} from './component-block-utils.js';
import { refreshBlockPinUi } from './block-pins.js';
const COMPONENT_ATTR = 'data-voodbuilder-component';
const PROPS_ATTR = 'data-voodbuilder-component-props';
const COMPONENT_TYPE = 'voodbuilder-component-instance';
const COMPONENT_HYDRATED_KEY = '__vbComponentHydrated';
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
        return;
    }

    let catalog = [];
    let selectionMode = false;
    let catalogLoadVersion = 0;
    const selectedIds = new Set();
    const deletingIds = new Set();

    editor.__voodbuilderComponentsCatalog = catalog;

    registerComponentInstanceType(editor, () => catalog);

    componentsMount.innerHTML = `
        <div class="voodbuilder-gjs-components-library" data-voodbuilder-components-library>
            <p class="voodbuilder-gjs-hint voodbuilder-gjs-components-library__hint" data-voodbuilder-components-hint hidden>
                ${labels.componentsDragHint ?? 'Drag a component onto the canvas.'}
            </p>
            <div class="voodbuilder-gjs-components-library__toolbar">
                <div class="voodbuilder-gjs-components-library__header">
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--primary voodbuilder-gjs-components-library__save" data-voodbuilder-save-component>
                        ${lucideIcon('plus', 15)}
                        <span>${labels.componentsSave ?? 'Save selection as component'}</span>
                    </button>
                    <div class="voodbuilder-gjs-components-library__icon-actions" role="group" aria-label="${escapeHtml(labels.componentsTitle ?? 'Components')}">
                        <button
                            type="button"
                            class="voodbuilder-gjs-icon-btn voodbuilder-gjs-components-library__icon-btn"
                            data-voodbuilder-components-code-import
                            title="${escapeHtml(labels.componentsCodeImport ?? 'Import from code')}"
                            aria-label="${escapeHtml(labels.componentsCodeImport ?? 'Import from code')}"
                        >${lucideIcon('code', 16)}</button>
                        <button
                            type="button"
                            class="voodbuilder-gjs-icon-btn voodbuilder-gjs-components-library__icon-btn"
                            data-voodbuilder-components-import-toggle
                            title="${escapeHtml(labels.componentsImport ?? 'Import')}"
                            aria-label="${escapeHtml(labels.componentsImport ?? 'Import')}"
                        >${lucideIcon('upload', 16)}</button>
                        <button
                            type="button"
                            class="voodbuilder-gjs-icon-btn voodbuilder-gjs-components-library__icon-btn"
                            data-voodbuilder-components-select-toggle
                            title="${escapeHtml(labels.componentsSelectMode ?? 'Select components')}"
                            aria-label="${escapeHtml(labels.componentsSelectMode ?? 'Select components')}"
                            aria-pressed="false"
                        >${lucideIcon('box-select', 16)}</button>
                        <button
                            type="button"
                            class="voodbuilder-gjs-icon-btn voodbuilder-gjs-components-library__icon-btn"
                            data-voodbuilder-components-export
                            title="${escapeHtml(labels.componentsExportAll ?? labels.componentsExport ?? 'Export all')}"
                            aria-label="${escapeHtml(labels.componentsExportAll ?? labels.componentsExport ?? 'Export all')}"
                        >${lucideIcon('download', 16)}</button>
                    </div>
                </div>
                <div class="voodbuilder-gjs-components-selection-bar" data-voodbuilder-components-selection-bar hidden>
                    <div class="voodbuilder-gjs-components-selection-bar__meta">
                        <span class="voodbuilder-gjs-components-selection-bar__badge" data-voodbuilder-components-selection-badge>0</span>
                        <span class="voodbuilder-gjs-components-selection-bar__count" data-voodbuilder-components-selection-count>
                            ${escapeHtml(labels.componentsSelectedShort ?? 'selected')}
                        </span>
                    </div>
                    <div class="voodbuilder-gjs-components-selection-bar__actions">
                        <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost voodbuilder-gjs-components-selection-bar__action" data-voodbuilder-components-export-selected disabled>
                            ${lucideIcon('download', 14)}
                            <span>${escapeHtml(labels.componentsExportSelected ?? 'Export selected')}</span>
                        </button>
                        <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost voodbuilder-gjs-components-selection-bar__action" data-voodbuilder-components-select-cancel>
                            ${lucideIcon('x', 14)}
                            <span>${escapeHtml(labels.componentsSelectCancel ?? 'Cancel selection')}</span>
                        </button>
                    </div>
                </div>
            </div>
            <section class="voodbuilder-gjs-components-import" data-voodbuilder-components-import-panel hidden>
                <h4 class="voodbuilder-gjs-components-import__title">${escapeHtml(labels.componentsImportTitle ?? 'Import: components')}</h4>
                <div
                    class="voodbuilder-gjs-components-import__dropzone"
                    data-voodbuilder-components-import-drop
                    tabindex="0"
                    role="button"
                >
                    <p class="voodbuilder-gjs-hint">${escapeHtml(labels.componentsImportDrop ?? 'Drop file(s) here (JSON)')}</p>
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--block" data-voodbuilder-components-import-select>
                        ${escapeHtml(labels.componentsImportSelect ?? 'Select file(s) to import')}
                    </button>
                    <input
                        type="file"
                        accept=".json,application/json"
                        multiple
                        hidden
                        data-voodbuilder-components-import-input
                    />
                </div>
                <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost voodbuilder-gjs-btn--block" data-voodbuilder-components-import-cancel>
                    ${escapeHtml(labels.componentsImportCancel ?? 'Cancel')}
                </button>
            </section>
            <div class="voodbuilder-gjs-components-empty" data-voodbuilder-components-empty hidden>
                <span class="voodbuilder-gjs-components-empty__icon" aria-hidden="true">${lucideIcon('boxes', 28)}</span>
                <p class="voodbuilder-gjs-components-empty__title">${labels.componentsEmpty ?? 'No components yet'}</p>
                <p class="voodbuilder-gjs-components-empty__hint">${labels.componentsEmptyHint ?? 'Select an element on the canvas and save it as a reusable component.'}</p>
            </div>
            <div class="voodbuilder-gjs-components-blocks-mount" data-voodbuilder-component-blocks-mount></div>
        </div>
    `;

    const shell = componentsMount.closest('.voodbuilder-gjs-shell') ?? componentsMount;
    const blocksMount = shell.querySelector('.voodbuilder-gjs-blocks-mount');
    const componentsBlocksMount = componentsMount.querySelector('[data-voodbuilder-component-blocks-mount]');
    const emptyStateEl = componentsMount.querySelector('[data-voodbuilder-components-empty]');
    const importPanel = componentsMount.querySelector('[data-voodbuilder-components-import-panel]');
    const importDropzone = componentsMount.querySelector('[data-voodbuilder-components-import-drop]');
    const importInput = componentsMount.querySelector('[data-voodbuilder-components-import-input]');
    const contextMenuMount = shell;

    const libraryMounts = { blocks: blocksMount, componentsBlocks: componentsBlocksMount };
    editor.__voodbuilderLibraryMounts = libraryMounts;
    editor.__voodbuilderRelocateLibrary = (libraryId) => {
        refreshComponentBlocksLibrary(editor, libraryId ?? getActiveLibraryId(), libraryMounts);
        updateEmptyState();
        updateBlocksSelectionState();
        shell.querySelector('.voodbuilder-gjs-blocks-search')?.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const getActiveLibraryId = () => {
        const activeTab = shell.querySelector('[data-voodbuilder-library].voodbuilder-gjs-library-tab--active');

        return activeTab?.dataset.voodbuilderLibrary ?? 'blocks';
    };

    const focusComponentsLibrary = () => {
        const search = shell.querySelector('.voodbuilder-gjs-blocks-search');

        if (search) {
            search.value = '';
        }

        const componentsTab = shell.querySelector('[data-voodbuilder-library="components"]');

        if (componentsTab && ! componentsTab.classList.contains('voodbuilder-gjs-library-tab--active')) {
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
        const libraryId = getActiveLibraryId();
        editor.__voodbuilderActiveLibrary = libraryId;
        registerComponentBlocks(editor, catalog, labels, categories, uncategorizedLabel);
        hydrateComponentInstances(editor, catalog);
        injectComponentCatalogCss(editor, catalog);
        updateLibraryHint();
        updateEmptyState();
        relocateComponentBlocksLibrary(editor, libraryId, libraryMounts);
        updateBlocksSelectionState();

        window.requestAnimationFrame(() => {
            refreshComponentBlocksLibrary(editor, getActiveLibraryId(), libraryMounts);
            shell.querySelector('.voodbuilder-gjs-blocks-search')?.dispatchEvent(new Event('input', { bubbles: true }));
        });
    };

    const setImportPanelOpen = (open) => {
        if (! importPanel) {
            return;
        }

        importPanel.hidden = ! open;
        importPanel.classList.toggle('is-open', open);
        importDropzone?.classList.remove('is-dragover');
    };

    const exportComponents = (items, filename) => {
        if (! items.length) {
            return;
        }

        downloadComponentsExport(items, filename);
    };

    const exportCatalog = () => {
        exportComponents(catalog, exportBundleFilename());
    };

    const exportSelectedComponents = () => {
        const items = catalog.filter((item) => selectedIds.has(String(item.id)));
        exportComponents(items, exportBundleFilename('selected'));
    };

    const exportComponent = (item) => {
        exportComponents([item], exportComponentFilename(item.name));
    };

    const setSelectionMode = (active) => {
        selectionMode = active;

        if (! selectionMode) {
            selectedIds.clear();
        }

        updateLibraryHint();
        updateSelectionUi();
        updateBlocksSelectionState();
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

    const resolveCatalogItemFromBlockElement = (blockEl) => {
        const block = resolveBlockFromElement(editor, blockEl);

        if (! isComponentBlock(block)) {
            return null;
        }

        const itemId = String(block.get?.('id') ?? block.id).slice(BLOCK_PREFIX.length);

        return catalog.find((entry) => String(entry.id) === itemId) ?? null;
    };

    const updateBlocksSelectionState = () => {
        const container = editor.BlockManager?.getContainer?.();

        if (! container) {
            return;
        }

        container.querySelectorAll('.gjs-block').forEach((blockEl) => {
            const item = resolveCatalogItemFromBlockElement(blockEl);
            const isSelected = item ? selectedIds.has(String(item.id)) : false;

            blockEl.classList.toggle('is-selected', selectionMode && isSelected);
            blockEl.setAttribute('aria-pressed', selectionMode && isSelected ? 'true' : 'false');
        });
    };

    const updateSelectionUi = () => {
        const library = componentsMount.querySelector('[data-voodbuilder-components-library]');
        const bar = componentsMount.querySelector('[data-voodbuilder-components-selection-bar]');
        const header = componentsMount.querySelector('.voodbuilder-gjs-components-library__header');
        const toggle = componentsMount.querySelector('[data-voodbuilder-components-select-toggle]');
        const countEl = componentsMount.querySelector('[data-voodbuilder-components-selection-count]');
        const badgeEl = componentsMount.querySelector('[data-voodbuilder-components-selection-badge]');
        const exportSelectedButton = componentsMount.querySelector('[data-voodbuilder-components-export-selected]');
        const count = selectedIds.size;

        library?.classList.toggle('is-selection-mode', selectionMode);
        bar?.toggleAttribute('hidden', ! selectionMode);
        header?.toggleAttribute('hidden', selectionMode);
        toggle?.classList.toggle('is-active', selectionMode);
        toggle?.setAttribute('aria-pressed', selectionMode ? 'true' : 'false');

        if (badgeEl) {
            badgeEl.textContent = String(count);
        }

        if (countEl) {
            countEl.textContent = labels.componentsSelectedShort ?? 'selected';
        }

        if (exportSelectedButton) {
            exportSelectedButton.disabled = count === 0;
        }
    };

    const updateLibraryHint = () => {
        const hint = componentsMount.querySelector('[data-voodbuilder-components-hint]');

        if (! hint) {
            return;
        }

        hint.hidden = catalog.length > 0 || selectionMode;
    };

    const importComponents = async (entries) => {
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
                body: JSON.stringify({ components: entries }),
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

        for (const file of list) {
            try {
                const text = await file.text();
                const parsed = JSON.parse(text);
                entries.push(...parseImportPayload(parsed));
            } catch {
                await alertDialog({
                    message: labels.componentsImportInvalidFile ?? 'Invalid JSON file.',
                    labels,
                });

                return;
            }
        }

        await importComponents(entries);
    };

    const deleteComponent = async (item) => {
        const componentId = item?.id;

        if (! componentId) {
            await alertDialog({
                message: labels.componentsDeleteError ?? 'Could not delete component.',
                labels,
            });

            return;
        }

        if (deletingIds.has(String(componentId))) {
            return;
        }

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
            return;
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

                return;
            }

            catalog = catalog.filter((entry) => String(entry.id) !== String(componentId));
            selectedIds.delete(String(componentId));

            try {
                syncCatalog();
                updateSelectionUi();
            } catch (syncError) {
                console.error('VoodBuilder: component deleted but library UI failed to refresh.', syncError);
                renderGrid();
                updateSelectionUi();
            }
        } catch {
            await alertDialog({
                message: labels.componentsDeleteError ?? 'Could not delete component.',
                labels,
            });
        } finally {
            deletingIds.delete(String(componentId));
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
            mount: contextMenuMount,
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

        try {
            const response = await fetch(componentsUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (! response.ok || loadId !== catalogLoadVersion) {
                return;
            }

            const payload = await response.json();
            const remote = (payload.components ?? []).map(normalizeCatalogEntry);
            const remoteIds = new Set(remote.map((item) => String(item.id)));
            const localOnly = catalog.filter((item) => ! remoteIds.has(String(item.id)));

            catalog = [...remote, ...localOnly];
            syncCatalog();
        } catch {
            if (loadId !== catalogLoadVersion) {
                return;
            }

            emptyStateEl?.removeAttribute('hidden');
            emptyStateEl?.querySelector('.voodbuilder-gjs-components-empty__title')
                ?.replaceChildren(document.createTextNode(labels.componentsLoadError ?? 'Could not load components.'));
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
                message: labels.selectComponent ?? 'Select an element first.',
                labels,
            });

            return;
        }

        const meta = await componentMetaDialog({
            title: labels.componentsSave ?? 'Save selection as component',
            labels,
            categories,
            defaultCategory: uncategorizedLabel,
            namePlaceholder: labels.componentsNamePrompt ?? 'Component name',
            categoryLabel: labels.componentsCodeImportCategory ?? 'Category',
            confirmLabel: labels.dialogConfirm ?? 'OK',
        });

        if (! meta?.name?.trim()) {
            return;
        }

        const html = selected.toHTML();
        const properties = inferProperties(selected);

        const response = await fetch(componentsUrl.replace(/\/$/, ''), {
            method: 'POST',
            credentials: 'same-origin',
            headers: editorApiHeaders(csrf, { json: true }),
            body: JSON.stringify({
                name: meta.name.trim(),
                category: meta.category || null,
                html,
                properties,
            }),
        });

        if (! response.ok) {
            await alertDialog({
                message: labels.componentsSaveError ?? 'Could not save component.',
                labels,
            });

            return;
        }

        const payload = await response.json();
        const saved = upsertCatalogEntry(payload.component);
        focusComponentsLibrary();
        syncCatalog();

        selected.addAttributes({
            [COMPONENT_ATTR]: String(saved.id),
            [PROPS_ATTR]: JSON.stringify({}),
        });
    });

    const bootstrapComponentBlocksLibrary = () => {
        hydrateComponentInstances(editor, catalog);
        refreshComponentBlocksLibrary(editor, getActiveLibraryId(), libraryMounts);
        updateEmptyState();
        shell.querySelector('.voodbuilder-gjs-blocks-search')?.dispatchEvent(new Event('input', { bubbles: true }));
    };

    editor.on('load', bootstrapComponentBlocksLibrary);

    if (editor.getWrapper?.()) {
        bootstrapComponentBlocksLibrary();
    }

    componentsBlocksMount?.addEventListener('contextmenu', (event) => {
        const blockEl = event.target.closest('.gjs-block');

        if (! blockEl || selectionMode) {
            return;
        }

        const item = resolveCatalogItemFromBlockElement(blockEl);

        if (! item) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        openComponentMenu(item, event.clientX, event.clientY);
    });

    componentsBlocksMount?.addEventListener('click', (event) => {
        if (! selectionMode) {
            return;
        }

        const blockEl = event.target.closest('.gjs-block');

        if (! blockEl) {
            return;
        }

        const item = resolveCatalogItemFromBlockElement(blockEl);

        if (! item) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        toggleComponentSelection(item);
    });

    editor.on('component:add', (component) => {
        const attrs = component.getAttributes?.({ noClass: true, noStyle: true }) ?? {};

        if (attrs[COMPONENT_ATTR]) {
            window.requestAnimationFrame(() => hydrateComponentInstance(component, catalog));
        }
    });
    editor.on('canvas:frame:load', () => injectComponentCatalogCss(editor, catalog));

    registerComponentPropsUi(editor, {
        mount: componentPropsMount,
        getCatalog: () => catalog,
        labels,
    });

    updateSelectionUi();

    void loadCatalog();
}

export function registerComponentInstanceType(editor, getCatalog) {
    if (editor.__voodbuilderComponentTypeRegistered) {
        return;
    }

    editor.__voodbuilderComponentTypeRegistered = true;

    const defaultType = editor.DomComponents.getType('default');
    const defaultDefaults = defaultType?.model?.prototype?.defaults ?? {};

    editor.DomComponents.addType(COMPONENT_TYPE, {
        extend: 'default',
        isComponent: (element) => element?.hasAttribute?.(COMPONENT_ATTR) === true,
        model: {
            defaults: {
                ...defaultDefaults,
                type: COMPONENT_TYPE,
                name: 'Component',
                tagName: 'div',
                draggable: true,
                droppable: true,
                removable: true,
                copyable: true,
                layerable: true,
                selectable: true,
                highlightable: true,
                editable: false,
                attributes: {
                    class: 'voodbuilder-gjs-component-instance',
                },
            },
            init() {
                this.on(`change:attributes:${COMPONENT_ATTR}`, () => {
                    this.set(COMPONENT_HYDRATED_KEY, false, { silent: true });
                    hydrateComponentInstance(this, getCatalog());
                });
                this.on(`change:attributes:${PROPS_ATTR}`, () => {
                    hydrateComponentInstance(this, getCatalog());
                });
            },
        },
    });
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
            if (! categories?.get?.(categoryId)) {
                blockManager.getCategories().add({
                    id: categoryId,
                    label: categoryLabel.toUpperCase(),
                    order: resolveCategoryOrder(categoryLabel),
                    open: true,
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
                class: 'voodbuilder-gjs-component-block',
                'data-voodbuilder-component-block': '1',
            },
            select: true,
            activate: false,
        });
    }

    const activeLibrary = editor.__voodbuilderActiveLibrary ?? 'blocks';
    syncBlocksLibraryVisibility(editor, activeLibrary);

    if (! editor.__voodbuilderComponentBlocksHooked) {
        editor.__voodbuilderComponentBlocksHooked = true;
        editor.on('block:add', () => {
            syncBlocksLibraryVisibility(editor, editor.__voodbuilderActiveLibrary ?? 'blocks');
            scheduleTagComponentBlockElements(editor);
        });
        editor.on('block:remove', () => {
            syncBlocksLibraryVisibility(editor, editor.__voodbuilderActiveLibrary ?? 'blocks');
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
    window.requestAnimationFrame(() => tagComponentBlockElements(editor));
}

function tagComponentBlockElements(editor) {
    editor.BlockManager?.getAll?.()?.forEach((block) => {
        const blockId = block.get?.('id') ?? block.id;

        if (! isComponentBlockId(blockId)) {
            return;
        }

        block.view?.el?.classList?.add('voodbuilder-gjs-component-block');
        block.view?.el?.setAttribute?.('data-voodbuilder-component-block', '1');
    });
}

function componentItemUrl(componentsUrl, id) {
    return `${componentsUrl.replace(/\/$/, '')}/${id}`;
}

function serializeComponentForExport(item) {
    return {
        name: item.name,
        category: item.category ?? null,
        description: item.description ?? null,
        html: item.html,
        css: item.css ?? null,
        properties: item.properties ?? [],
    };
}

function buildComponentsExportPayload(items) {
    return {
        version: 1,
        format: 'voodbuilder-components',
        exported_at: new Date().toISOString(),
        components: items.map(serializeComponentForExport),
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

function downloadComponentsExport(items, filename) {
    const payload = buildComponentsExportPayload(items);
    const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    URL.revokeObjectURL(url);
}

function setBlockDisplay(blockEl, visible) {
    if (visible) {
        blockEl.style.removeProperty('display');
    } else {
        blockEl.style.display = 'none';
    }
}

function markLibraryMounts(mounts, libraryId) {
    if (libraryId === 'blocks') {
        mounts.blocks?.setAttribute('data-voodbuilder-blocks-library', 'blocks');
        mounts.componentsBlocks?.removeAttribute('data-voodbuilder-blocks-library');
    } else {
        mounts.componentsBlocks?.setAttribute('data-voodbuilder-blocks-library', 'components');
        mounts.blocks?.removeAttribute('data-voodbuilder-blocks-library');
    }
}

function openComponentBlockCategories(editor) {
    editor.BlockManager.getCategories?.()?.each?.((category) => {
        if (isComponentCategoryId(String(category.get('id') ?? ''))) {
            category.set('open', true);
        }
    });
}

export function refreshComponentBlocksLibrary(editor, libraryId, mounts = {}) {
    const container = editor.BlockManager?.getContainer?.();
    const target = libraryId === 'components' ? mounts.componentsBlocks : mounts.blocks;

    if (! container || ! target) {
        return;
    }

    editor.__voodbuilderActiveLibrary = libraryId;
    markLibraryMounts(mounts, libraryId);

    if (container.parentElement !== target) {
        target.appendChild(container);
    }

    editor.BlockManager?.render?.();
    tagComponentBlockElements(editor);

    if (libraryId === 'components') {
        openComponentBlockCategories(editor);
    }

    syncBlocksLibraryVisibility(editor, libraryId);
    scheduleTagComponentBlockElements(editor);

    if (libraryId === 'blocks') {
        refreshBlockPinUi(editor, { sync: false });
    }
}

export function relocateComponentBlocksLibrary(editor, libraryId, mounts = {}) {
    refreshComponentBlocksLibrary(editor, libraryId, mounts);
}

function syncBlocksLibraryVisibility(editor, libraryId = 'blocks') {
    const container = editor.BlockManager?.getContainer?.();

    if (! container) {
        return;
    }

    tagComponentBlockElements(editor);

    const showComponents = libraryId === 'components';

    container.querySelectorAll('.gjs-block').forEach((blockEl) => {
        const isComponent = isComponentBlockElement(editor, blockEl);
        setBlockDisplay(blockEl, showComponents ? isComponent : ! isComponent);
    });

    editor.BlockManager.getCategories?.()?.each?.((category) => {
        const categoryId = String(category.get('id') ?? '');
        const isComponentCategory = isComponentCategoryId(categoryId);
        const categoryEl = category.view?.el;

        if (! categoryEl) {
            return;
        }

        categoryEl.classList.toggle('voodbuilder-gjs-component-category', isComponentCategory);

        if (showComponents) {
            setBlockDisplay(categoryEl, isComponentCategory);
        } else {
            setBlockDisplay(categoryEl, ! isComponentCategory);
        }
    });

    container.querySelectorAll('.gjs-block-category').forEach((categoryEl) => {
        const isComponentCategory = categoryEl.classList.contains('voodbuilder-gjs-component-category');

        if (showComponents && ! isComponentCategory) {
            categoryEl.style.display = 'none';

            return;
        }

        if (! showComponents && isComponentCategory) {
            categoryEl.style.display = 'none';

            return;
        }

        const hasVisible = [...categoryEl.querySelectorAll('.gjs-block')].some(
            (blockEl) => blockEl.style.display !== 'none',
        );

        setBlockDisplay(categoryEl, hasVisible);
    });
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
        hydrateComponentInstance(component, catalog);
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
            class: 'voodbuilder-gjs-component-instance',
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

function hydrateComponentInstance(component, catalog) {
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
        applyComponentInstancePresentation(component, item);

        return;
    }

    if (hasMeaningfulComponentBody(component) && component.find('.voodbuilder-pasted-component').length > 0) {
        component.set(COMPONENT_HYDRATED_KEY, true, { silent: true });
        applyComponentInstancePresentation(component, item);

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
        [PROPS_ATTR]: attrs[PROPS_ATTR] ?? JSON.stringify(defaultProps(item)),
        class: 'voodbuilder-gjs-component-instance',
    });
}

function formatComponentInstanceName(name) {
    const label = String(name ?? 'Component').trim() || 'Component';

    return `Voodbuilder: ${label}`;
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
            [PROPS_ATTR]: JSON.stringify(defaultProps(item)),
            class: 'voodbuilder-gjs-component-instance',
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

            return;
        }

        const component = getCatalog().find((item) => String(item.id) === String(componentId));
        const props = parseProps(selected.getAttributes()[PROPS_ATTR]);

        if (! component?.properties?.length) {
            mount.replaceChildren();

            return;
        }

        mount.innerHTML = `
            <section class="voodbuilder-gjs-component-props">
                <h3 class="voodbuilder-gjs-panel-subtitle">${labels.componentsProps ?? 'Instance properties'}</h3>
                <div class="voodbuilder-gjs-form-stack" data-voodbuilder-component-props-form></div>
            </section>
        `;

        const form = mount.querySelector('[data-voodbuilder-component-props-form]');

        for (const property of component.properties) {
            const field = document.createElement('label');
            field.className = 'voodbuilder-gjs-form-field';

            const fieldLabel = document.createElement('span');
            fieldLabel.className = 'voodbuilder-gjs-form-field__label';
            fieldLabel.textContent = property.label;

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'voodbuilder-gjs-input';
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

function inferProperties(component) {
    const properties = [];
    const seen = new Set();

    component.find('[data-voodbuilder-prop]').forEach((child) => {
        const id = child.getAttributes()['data-voodbuilder-prop'];

        if (! id || seen.has(id)) {
            return;
        }

        seen.add(id);
        properties.push({
            id,
            label: id,
            type: 'text',
            default: child.get('content') ?? '',
        });
    });

    return properties;
}

const COMPONENT_THEME_TOKEN_BRIDGE = `
.voodbuilder-gjs-component-instance .voodbuilder-pasted-component,
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
}
`.trim();

function injectComponentCatalogCss(editor, catalog) {
    const frame = editor.Canvas?.getFrameEl?.();

    if (! frame) {
        return;
    }

    const doc = frame.contentDocument ?? frame.contentWindow?.document;

    if (! doc) {
        return;
    }

    let styleEl = doc.getElementById('voodbuilder-component-catalog-css');

    if (! styleEl) {
        styleEl = doc.createElement('style');
        styleEl.id = 'voodbuilder-component-catalog-css';
        doc.head.appendChild(styleEl);
    }

    const catalogCss = catalog
        .map((item) => String(item.css ?? '').trim())
        .filter(Boolean)
        .join('\n\n');

    styleEl.textContent = [catalogCss, COMPONENT_THEME_TOKEN_BRIDGE].filter(Boolean).join('\n\n');
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
        const nextAttributes = {
            [COMPONENT_ATTR]: String(componentId),
            class: 'voodbuilder-gjs-component-instance',
        };

        if (props) {
            nextAttributes[PROPS_ATTR] = props;
        }

        component.addAttributes(nextAttributes);
    });
}
