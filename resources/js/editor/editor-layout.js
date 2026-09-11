/**
 * Voodbuilder Editor shell — 3-column builder layout (blocks | canvas | inspector).
 * Uses only public Editor APIs: appendTo, Panels, BlockManager container.
 */

import { STYLE_MANAGER_SECTORS } from './editor-chrome.js';
import { registerBlockPins } from './block-pins.js';
import { lucideIcon, tablerIcon } from './editor-icons.js';
import { animatedMarkMarkup } from './editor-build-status.js';
import { setupStyleInspectorSectors } from './inspector-collapsible-sector.js';
import { registerInspectorSelectUi } from './inspector-select-ui.js';
import { registerInspectorColorFix } from './inspector-color-fix.js';
import { applyBlocksLibraryUi, collapseAllBlockCategories, collapseLibraryCategories, readBlocksSearchQuery } from './blocks-library-sync.js';
import { resolveBlockSettingsTarget, promoteRoot, refreshBlockSettingsUi, findInspectableRoot, ensureRootInspectable, readBlockId } from './blocks/settings/index.js';
import {
    inspectorSelectionNotice,
} from './chrome-editor-guards.js';
import {
    createInspectorEmptyState,
} from './inspector-empty-state.js';
import { canEntitlement } from './editor/entitlements.js';

const INSPECTOR_TABS = ['content', 'style', 'dynamic', 'conditions', 'layers'];

const LIBRARY_TAB_ICONS = {
    blocks: 'wall',
    components: 'components',
    templates: 'template',
};

const INSPECTOR_TAB_ICONS = {
    content: 'pencil',
    style: 'color-swatch',
    dynamic: 'database',
    conditions: 'directions',
    layers: 'layers',
};

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Resolve topbar context under the brand mark: "Page · Home" / "Layout · Docs" / "Popup · …".
 *
 * @param {Record<string, unknown>} options
 * @param {Record<string, string>} labels
 * @returns {{ kind: string, name: string } | null}
 */
export function resolveEditingContext(options = {}, labels = {}) {
    if (options.popupMode) {
        const name = String(options.popupName ?? '').trim();

        return {
            kind: labels.editingContextPopup ?? 'Popup',
            name: name !== '' ? name : (labels.editingContextUntitled ?? 'Untitled'),
        };
    }

    if (options.chromeLayoutMode) {
        const name = String(options.chromeLayoutName ?? '').trim();

        return {
            kind: labels.editingContextLayout ?? 'Layout',
            name: name !== '' ? name : (labels.editingContextUntitled ?? 'Untitled'),
        };
    }

    const name = String(options.pageTitle ?? options.pageName ?? '').trim();

    if (name === '' && ! options.pageId) {
        return null;
    }

    return {
        kind: labels.editingContextPage ?? 'Page',
        name: name !== '' ? name : (labels.editingContextUntitled ?? 'Untitled'),
    };
}

function editingContextMarkup(context) {
    if (! context?.kind) {
        return '';
    }

    const name = String(context.name ?? '').trim();
    const label = name !== '' ? `${context.kind} · ${name}` : context.kind;

    return `
        <div class="voodbuilder-editor-topbar__editing-context" title="${escapeHtml(label)}">
            <span class="voodbuilder-editor-topbar__editing-kind">${escapeHtml(context.kind)}</span>
            ${name !== '' ? `
                <span class="voodbuilder-editor-topbar__editing-sep" aria-hidden="true">·</span>
                <span class="voodbuilder-editor-topbar__editing-name">${escapeHtml(name)}</span>
            ` : ''}
        </div>
    `;
}

/**
 * Place a companion control in the topbar actions group.
 *
 * Callers used to anchor on the save-state readout, which put them to the left of it.
 * The readout now leads the group, so controls belong between it and Exit: everything
 * that acts on the page stays adjacent to Save.
 *
 * @param {HTMLElement|null} actionsMount  `.voodbuilder-editor-topbar__actions`
 * @param {HTMLElement} control
 */
export function mountTopbarAction(actionsMount, control) {
    if (! actionsMount || ! control) {
        return;
    }

    const exitLink = actionsMount.querySelector('[data-voodbuilder-editor-exit]')
        // Older shells had no marker on the exit link.
        ?? actionsMount.querySelector('.voodbuilder-editor-topbar__btn--ghost[href]');

    if (exitLink) {
        actionsMount.insertBefore(control, exitLink);

        return;
    }

    actionsMount.appendChild(control);
}

export function buildEditorShell(container, labels = {}, meta = {}) {
    const hideTemplates = Boolean(meta.hideTemplates);
    const brand = String(meta.brand ?? 'VoodBuilder').trim() || 'VoodBuilder';
    const editingContext = meta.editingContext
        ?? (meta.editingBadgeTitle
            ? { kind: meta.editingBadgeTitle, name: '' }
            : null);

    container.classList.add('voodbuilder-editor-root');
    container.innerHTML = `
        <div class="voodbuilder-editor-shell" data-voodbuilder-device="desktop">
            <header class="voodbuilder-editor-topbar">
                <div class="voodbuilder-editor-topbar__brand-wrap">
                    <div class="voodbuilder-editor-topbar__brand" aria-label="${escapeHtml(brand)}">
                        <span class="voodbuilder-editor-topbar__brand-mark">${animatedMarkMarkup(28)}</span>
                        <span class="voodbuilder-editor-topbar__brand-sr">${escapeHtml(brand)}</span>
                    </div>
                    ${editingContextMarkup(editingContext)}
                </div>
                <div class="voodbuilder-editor-topbar__tools"></div>
                <div class="voodbuilder-editor-topbar__actions">
                    <span
                        class="voodbuilder-editor-topbar__status"
                        data-voodbuilder-editor-saved
                        aria-live="polite"
                        hidden
                    ></span>
                    <a
                        href="${escapeHtml(meta.exitUrl ?? '#')}"
                        class="voodbuilder-editor-topbar__btn voodbuilder-editor-topbar__btn--ghost"
                        data-voodbuilder-editor-exit
                    >${escapeHtml(labels.exitEditor ?? 'Exit editor')}</a>
                    <button
                        type="button"
                        class="voodbuilder-editor-topbar__btn voodbuilder-editor-topbar__btn--primary"
                        data-voodbuilder-editor-save
                    >
                        <span data-voodbuilder-editor-save-label>${escapeHtml(labels.save ?? 'Save')}</span>
                    </button>
                </div>
            </header>
            <div class="voodbuilder-editor-shell__workspace">
                <aside class="voodbuilder-editor-shell__left" aria-label="${escapeHtml(labels.panelLibrary ?? labels.panelBlocks ?? 'Library')}">
                    <div class="voodbuilder-editor-library-tabs" role="tablist">
                        <button type="button" class="voodbuilder-editor-library-tab voodbuilder-editor-library-tab--active" data-voodbuilder-library="blocks" role="tab" aria-selected="true" title="${escapeHtml(labels.tabElements ?? 'Elements')}" aria-label="${escapeHtml(labels.tabElements ?? 'Elements')}">
                            ${tablerIcon(LIBRARY_TAB_ICONS.blocks, 17)}
                        </button>
                        <button type="button" class="voodbuilder-editor-library-tab" data-voodbuilder-library="components" role="tab" aria-selected="false" title="${escapeHtml(labels.tabComponents ?? 'Components')}" aria-label="${escapeHtml(labels.tabComponents ?? 'Components')}">
                            ${tablerIcon(LIBRARY_TAB_ICONS.components, 17)}
                        </button>
                        ${hideTemplates ? '' : `
                        <button type="button" class="voodbuilder-editor-library-tab" data-voodbuilder-library="templates" role="tab" aria-selected="false" title="${escapeHtml(labels.tabTemplates ?? 'Templates')}" aria-label="${escapeHtml(labels.tabTemplates ?? 'Templates')}">
                            ${tablerIcon(LIBRARY_TAB_ICONS.templates, 17)}
                        </button>
                        `}
                    </div>
                    <label class="voodbuilder-editor-blocks-search-wrap">
                        <span class="voodbuilder-editor-blocks-search-icon">${lucideIcon('search', 16)}</span>
                        <input
                            type="search"
                            class="voodbuilder-editor-blocks-search"
                            placeholder="${escapeHtml(labels.blockSearch ?? 'Search elements…')}"
                            autocomplete="off"
                            aria-label="${escapeHtml(labels.blockSearch ?? 'Search elements')}"
                        />
                    </label>
                    <div class="voodbuilder-editor-library-panels">
                        <div class="voodbuilder-editor-library-panel voodbuilder-editor-library-panel--active" data-voodbuilder-library-panel="blocks">
                            <div class="voodbuilder-editor-blocks-mount"></div>
                        </div>
                        <div class="voodbuilder-editor-library-panel" data-voodbuilder-library-panel="components" hidden>
                            <div class="voodbuilder-editor-components-mount"></div>
                        </div>
                        ${hideTemplates ? '' : `
                        <div class="voodbuilder-editor-library-panel" data-voodbuilder-library-panel="templates" hidden>
                            <div class="voodbuilder-editor-templates-mount"></div>
                        </div>
                        `}
                    </div>
                </aside>
                <div class="voodbuilder-editor-shell__center">
                    <div class="voodbuilder-editor-canvas-mount"></div>
                </div>
                <aside class="voodbuilder-editor-shell__right" aria-label="${escapeHtml(labels.panelInspector ?? 'Inspector')}">
                    <div class="voodbuilder-editor-inspector-tabs" role="tablist"></div>
                    <div class="voodbuilder-editor-inspector-panels">
                        <div class="voodbuilder-editor-inspector-panel voodbuilder-editor-inspector-panel--active" data-voodbuilder-inspector="content">
                            <div class="voodbuilder-editor-traits-mount"></div>
                            <div class="voodbuilder-editor-site-chrome-settings-mount" hidden></div>
                            <div class="voodbuilder-editor-component-props-mount"></div>
                        </div>
                        <div class="voodbuilder-editor-inspector-panel" data-voodbuilder-inspector="style">
                            <div class="voodbuilder-editor-selectors-mount"></div>
                            <div class="voodbuilder-editor-styles-mount"></div>
                        </div>
                        <div class="voodbuilder-editor-inspector-panel" data-voodbuilder-inspector="dynamic">
                            <div class="voodbuilder-editor-dynamic-mount"></div>
                        </div>
                        <div class="voodbuilder-editor-inspector-panel" data-voodbuilder-inspector="conditions">
                            <div class="voodbuilder-editor-conditions-mount"></div>
                        </div>
                        <div class="voodbuilder-editor-inspector-panel" data-voodbuilder-inspector="layers">
                            <div class="voodbuilder-editor-layers-mount"></div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    `;

    const tabLabels = {
        content: labels.tabContent ?? 'Content',
        style: labels.tabStyle ?? 'Style',
        dynamic: labels.tabDynamic ?? 'Dynamic',
        conditions: labels.tabConditions ?? 'Conditions',
        layers: labels.tabLayers ?? 'Layers',
    };

    const tablist = container.querySelector('.voodbuilder-editor-inspector-tabs');

    for (const tabId of INSPECTOR_TABS) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'voodbuilder-editor-inspector-tab';
        button.dataset.voodbuilderTab = tabId;
        button.setAttribute('role', 'tab');
        button.setAttribute('aria-selected', tabId === 'content' ? 'true' : 'false');
        button.setAttribute('aria-label', tabLabels[tabId]);
        button.title = tabLabels[tabId];
        button.innerHTML = tabId === 'style' || tabId === 'conditions'
            ? tablerIcon(INSPECTOR_TAB_ICONS[tabId] ?? 'box-select', 17)
            : lucideIcon(INSPECTOR_TAB_ICONS[tabId] ?? 'box-select', 17);
        tablist.appendChild(button);
    }

    const shell = container.querySelector('.voodbuilder-editor-shell');

    return {
        shell,
        mounts: {
            canvas: container.querySelector('.voodbuilder-editor-canvas-mount'),
            canvasToolbar: container.querySelector('.voodbuilder-editor-topbar__tools'),
            blocks: container.querySelector('.voodbuilder-editor-blocks-mount'),
            components: container.querySelector('.voodbuilder-editor-components-mount'),
            templates: container.querySelector('.voodbuilder-editor-templates-mount'),
            componentProps: container.querySelector('.voodbuilder-editor-component-props-mount'),
            libraryTabs: container.querySelector('.voodbuilder-editor-library-tabs'),
            libraryPanels: container.querySelector('.voodbuilder-editor-library-panels'),
            layers: container.querySelector('.voodbuilder-editor-layers-mount'),
            traits: container.querySelector('.voodbuilder-editor-traits-mount'),
            siteChromeSettings: container.querySelector('.voodbuilder-editor-site-chrome-settings-mount'),
            selectors: container.querySelector('.voodbuilder-editor-selectors-mount'),
            styles: container.querySelector('.voodbuilder-editor-styles-mount'),
            dynamic: container.querySelector('.voodbuilder-editor-dynamic-mount'),
            conditions: container.querySelector('.voodbuilder-editor-conditions-mount'),
            search: container.querySelector('.voodbuilder-editor-blocks-search'),
            tablist,
            panels: container.querySelector('.voodbuilder-editor-inspector-panels'),
            saveButton: container.querySelector('[data-voodbuilder-editor-save]'),
            saveLabel: container.querySelector('[data-voodbuilder-editor-save-label]'),
            savedIndicator: container.querySelector('[data-voodbuilder-editor-saved]'),
        },
    };
}

export function editorLayoutInitOptions(mounts) {
    return {
        container: mounts.canvas,
        showDevices: false,
        blockManager: {
            appendTo: mounts.blocks,
        },
        layerManager: {
            appendTo: mounts.layers,
        },
        traitManager: {
            appendTo: mounts.traits,
        },
        selectorManager: {
            componentFirst: true,
            states: [
                { name: 'hover', label: 'Hover' },
                { name: 'active', label: 'Active' },
                { name: 'focus', label: 'Focus' },
            ],
        },
        styleManager: {
            appendTo: mounts.styles,
            sectors: STYLE_MANAGER_SECTORS,
        },
    };
}

function setupLibraryTabs(mounts, labels = {}, editor = null) {
    const { libraryTabs, libraryPanels, search } = mounts;

    if (! libraryTabs || ! libraryPanels) {
        return;
    }

    let activeLibrary = 'blocks';

    const placeholders = {
        blocks: labels.blockSearch ?? 'Search elements…',
        components: labels.componentSearch ?? 'Search components…',
        templates: labels.templateSearch ?? 'Search templates…',
    };

    const activateLibrary = (libraryId) => {
        activeLibrary = libraryId;

        if (editor) {
            editor.__voodbuilderActiveLibrary = libraryId;
            editor.__voodbuilderRelocateLibrary?.(libraryId);
            editor.trigger?.('voodbuilder:active-library-changed', { libraryId });
        }

        libraryTabs.closest('.voodbuilder-editor-shell')
            ?.setAttribute('data-voodbuilder-active-library', libraryId);

        libraryTabs.querySelectorAll('[data-voodbuilder-library]').forEach((button) => {
            const active = button.dataset.voodbuilderLibrary === libraryId;
            button.classList.toggle('voodbuilder-editor-library-tab--active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        libraryPanels.querySelectorAll('[data-voodbuilder-library-panel]').forEach((panel) => {
            const active = panel.dataset.voodbuilderLibraryPanel === libraryId;
            panel.classList.toggle('voodbuilder-editor-library-panel--active', active);
            panel.hidden = ! active;
        });

        if (search) {
            search.placeholder = placeholders[libraryId] ?? placeholders.blocks;
            search.value = '';
            search.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };

    libraryTabs.addEventListener('click', (event) => {
        const button = event.target.closest('[data-voodbuilder-library]');

        if (! button?.dataset.voodbuilderLibrary) {
            return;
        }

        activateLibrary(button.dataset.voodbuilderLibrary);
    });

    activateLibrary('blocks');
    mounts.blocks?.setAttribute('data-voodbuilder-blocks-library', 'blocks');
}

function setupBlockSearch(editor, searchInput) {
    if (! searchInput) {
        return;
    }

    const filter = () => {
        editor.__voodbuilderBlocksSearchQuery = searchInput.value.trim();
        applyBlocksLibraryUi(editor, editor.__voodbuilderBlocksSearchQuery);
    };

    searchInput.addEventListener('input', filter);
    editor.on('block:add', () => {
        window.requestAnimationFrame(filter);
    });
    editor.on('block:remove', filter);
    editor.on('load', () => {
        window.requestAnimationFrame(filter);
    });
}

export function collapseBlockCategories(editor) {
    collapseAllBlockCategories(editor);
}

export function refreshBlocksLibraryUi(editor) {
    const libraryId = editor.__voodbuilderActiveLibrary ?? 'blocks';

    editor.__voodbuilderRelocateLibrary?.(libraryId);
    collapseLibraryCategories(editor, libraryId);
    applyBlocksLibraryUi(editor, readBlocksSearchQuery());
}

function syncInspectorManagers(editor, tabId, { refreshInspectorPanels = false } = {}) {
    let component = editor.getSelected();

    if (tabId === 'content' && component) {
        promoteRoot(editor, component);
        component = editor.getSelected();
        refreshBlockSettingsUi(editor);

        let { descriptor } = resolveBlockSettingsTarget(component, editor);

        if (! descriptor && editor.__voodbuilderChromeLayoutMode) {
            const root = findInspectableRoot(component, editor);

            if (root) {
                ensureRootInspectable(root);
                ({ descriptor } = resolveBlockSettingsTarget(root, editor));
            }
        }

        if (! descriptor) {
            const layoutTarget = editor.__voodbuilderChromeLayoutMode
                ? resolveBlockSettingsTarget(component, editor)
                : { descriptor: null };

            if (
                ! layoutTarget.descriptor?.layoutOnly
                && ! editor.__voodbuilderChromeLayoutMode
            ) {
                editor.TraitManager.select(component);
            }
        }
    } else if (tabId !== 'content' && editor.__voodbuilderChromeLayoutMode) {
        editor.__voodbuilderBlockSettingsRender?.();
    }

    if (tabId === 'style') {
        syncChromeLayoutStylePanel(editor, editor.__voodbuilderShellMounts);
    }

    if (tabId === 'layers') {
        window.requestAnimationFrame(() => {
            editor.trigger('voodbuilder:layers-panel:show');
        });
    }

    if (refreshInspectorPanels && (tabId === 'dynamic' || tabId === 'conditions')) {
        window.requestAnimationFrame(() => {
            editor.trigger('voodbuilder:inspector-panel:refresh', {
                tabId,
                component: editor.getSelected(),
            });
        });
    }
}

function setInspectorSidebarWidth(inspectorAside, tabId) {
    if (! inspectorAside) {
        return;
    }

    inspectorAside.setAttribute('data-voodbuilder-inspector-tab', tabId);
}

function setupInspectorTabs(mounts, editor) {
    const { tablist, panels } = mounts;
    const inspectorAside = panels?.closest('.voodbuilder-editor-shell__right') ?? null;
    let activeTab = 'content';

    if (! tablist || ! panels) {
        return;
    }

    const activateTab = (tabId, { userInitiated = false } = {}) => {
        activeTab = tabId;
        editor.__voodbuilderInspectorActiveTab = tabId;

        if (userInitiated) {
            editor.__voodbuilderInspectorTabUserChoice = true;
        }

        tablist.querySelectorAll('.voodbuilder-editor-inspector-tab').forEach((button) => {
            const active = button.dataset.voodbuilderTab === tabId;
            button.classList.toggle('voodbuilder-editor-inspector-tab--active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.querySelectorAll('[data-voodbuilder-inspector]').forEach((panel) => {
            const active = panel.dataset.voodbuilderInspector === tabId;

            panel.classList.toggle('voodbuilder-editor-inspector-panel--active', active);
            panel.toggleAttribute('hidden', ! active);
            panel.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        if (inspectorAside) {
            setInspectorSidebarWidth(inspectorAside, tabId);
        }

        editor.trigger?.('voodbuilder:inspector-tab', tabId);

        window.requestAnimationFrame(() => syncInspectorManagers(editor, tabId, {
            refreshInspectorPanels: tabId === 'dynamic' || tabId === 'conditions',
        }));
    };

    editor.__voodbuilderActivateInspectorTab = activateTab;

    tablist.addEventListener('click', (event) => {
        const button = event.target.closest('.voodbuilder-editor-inspector-tab');

        if (! button?.dataset.voodbuilderTab) {
            return;
        }

        // Always go through the editor hook so listeners (e.g. Reading preview) can wrap it.
        editor.__voodbuilderActivateInspectorTab?.(button.dataset.voodbuilderTab, { userInitiated: true });
    });

    editor.on('component:selected', (component) => {
        if (component) {
            const { descriptor, root } = resolveBlockSettingsTarget(component, editor);

            if (descriptor && root) {
                // Prefer block id over object identity so a post-refresh re-select
                // still opens Content once, without locking the user on that tab.
                const rootKey = readBlockId(root) || `cid:${component.cid ?? ''}`;
                const lastKey = editor.__voodbuilderLayoutSettingsAutoTabKey ?? null;

                editor.__voodbuilderLayoutSettingsAutoTabRoot = root;

                if (lastKey !== rootKey) {
                    editor.__voodbuilderLayoutSettingsAutoTabKey = rootKey;
                    editor.__voodbuilderInspectorTabUserChoice = false;
                    activateTab('content');
                }
            } else {
                editor.__voodbuilderLayoutSettingsAutoTabRoot = null;
                editor.__voodbuilderLayoutSettingsAutoTabKey = null;
            }

            syncInspectorManagers(editor, activeTab);

            // Prefer block settings over bind/conditions tabs when a descriptor matches.
            if (! descriptor) {
                const hasBindingSources = (editor.__voodbuilderBindingsCatalog?.groups ?? []).length > 0;

                if (hasBindingSources && component.getAttributes?.()['data-voodbuilder-bind']) {
                    activateTab('dynamic');
                }

                if (component.getAttributes?.()['data-voodbuilder-conditions']) {
                    activateTab('conditions');
                }
            }

            return;
        }

        syncInspectorManagers(editor, activeTab);
    });

    editor.on('voodbuilder:dynamic-blocks-refreshed', () => {
        // Allow the next selection to re-open Content once after refresh.
        editor.__voodbuilderLayoutSettingsAutoTabKey = null;
        editor.__voodbuilderLayoutSettingsAutoTabRoot = null;

        if (activeTab === 'content') {
            refreshBlockSettingsUi(editor);
        }
    });

    editor.on('component:deselected', () => {
        syncInspectorManagers(editor, activeTab);
    });

    activateTab('content');
}

function mountSelectorManagerPanel(editor, mount) {
    if (! mount) {
        return;
    }

    mount.replaceChildren();

    const panel = editor.SelectorManager.render();

    if (panel) {
        mount.appendChild(panel);
    }
}

function getClassManagerPanels(root) {
    if (! root) {
        return [];
    }

    return [...root.querySelectorAll('.clm-tags, .gjs-clm')];
}

function dedupeSelectorManagerPanels(selectorsMount, stylesMount) {
    const stylePanel = selectorsMount?.closest('[data-voodbuilder-inspector="style"]')
        ?? stylesMount?.closest('[data-voodbuilder-inspector="style"]');

    if (stylesMount) {
        getClassManagerPanels(stylesMount).forEach((panel) => panel.remove());
    }

    const scope = stylePanel ?? selectorsMount;

    if (! scope) {
        return;
    }

    const classPanels = getClassManagerPanels(scope);

    for (let index = 1; index < classPanels.length; index += 1) {
        classPanels[index].remove();
    }
}

function observeSelectorManagerDedupe(mounts) {
    const targets = [mounts.selectors, mounts.styles].filter(Boolean);

    if (targets.length === 0) {
        return;
    }

    let dedupeTimer = null;
    let deduping = false;
    const observers = [];

    const dedupe = () => {
        if (deduping) {
            return;
        }

        deduping = true;

        for (const { observer } of observers) {
            observer.disconnect();
        }

        try {
            dedupeSelectorManagerPanels(mounts.selectors, mounts.styles);
        } finally {
            window.queueMicrotask(() => {
                deduping = false;

                for (const { observer, target } of observers) {
                    observer.observe(target, { childList: true, subtree: true });
                }
            });
        }
    };

    const scheduleDedupe = () => {
        if (deduping) {
            return;
        }

        window.clearTimeout(dedupeTimer);
        dedupeTimer = window.setTimeout(dedupe, 80);
    };

    for (const target of targets) {
        const observer = new MutationObserver(() => {
            scheduleDedupe();
        });

        observer.observe(target, { childList: true, subtree: true });
        observers.push({ observer, target });
    }

    dedupe();
}

function syncChromeLayoutStylePanel(editor, mounts) {
    if (! mounts?.styles) {
        return;
    }

    const panel = mounts.styles.closest('[data-voodbuilder-inspector="style"]');

    if (! panel) {
        return;
    }

    const labels = editor.__voodbuilderLabels ?? {};
    const selected = editor.getSelected?.();
    const noticeText = inspectorSelectionNotice(selected, editor, labels);
    const hideControls = noticeText !== null;
    const styleMounts = [mounts.selectors, mounts.styles].filter(Boolean);

    styleMounts.forEach((el) => {
        el.hidden = hideControls;
        const sector = el.closest?.('.voodbuilder-editor-inspector-sector');

        if (sector) {
            sector.hidden = hideControls;
        }
    });

    let notice = panel.querySelector('[data-voodbuilder-chrome-layout-notice]');

    if (! hideControls) {
        if (notice) {
            notice.hidden = true;
        }

        return;
    }

    if (! notice) {
        notice = createInspectorEmptyState({ labels });
        notice.dataset.voodbuilderChromeLayoutNotice = '1';
        panel.insertBefore(notice, panel.firstChild);
    }

    notice.hidden = false;
    notice.className = 'voodbuilder-editor-inspector-empty-state voodbuilder-editor-chrome-layout-notice';
    notice.textContent = noticeText;
}

function setupStyleInspector(editor, mounts) {
    const dedupe = () => dedupeSelectorManagerPanels(mounts.selectors, mounts.styles);

    editor.__voodbuilderShellMounts = mounts;

    editor.on('load', () => {
        mountSelectorManagerPanel(editor, mounts.selectors);
        observeSelectorManagerDedupe(mounts);
        dedupe();
        syncChromeLayoutStylePanel(editor, mounts);
    });
    editor.on('component:selected', () => {
        window.requestAnimationFrame(() => {
            dedupe();
            window.requestAnimationFrame(dedupe);
            syncChromeLayoutStylePanel(editor, mounts);
        });
    });
    editor.on('component:deselected', () => {
        syncChromeLayoutStylePanel(editor, mounts);
    });
    editor.on('component:remove', () => {
        window.requestAnimationFrame(() => {
            syncChromeLayoutStylePanel(editor, mounts);
        });
    });
    editor.on('selector:add', dedupe);
    editor.on('selector:remove', dedupe);
    editor.on('selector:update', dedupe);
}

function trimDefaultPanelButtons(editor) {
    const removeIds = [
        'export-template',
        'open-sm',
        'open-tm',
        'open-layers',
        'open-blocks',
        'fullscreen',
        'preview',
        'sw-visibility',
    ];

    for (const panelId of ['options', 'views', 'commands']) {
        for (const buttonId of removeIds) {
            editor.Panels.removeButton(panelId, buttonId);
        }
    }

    for (const panelId of ['views', 'commands', 'options']) {
        if (editor.Panels.getPanel(panelId)) {
            editor.Panels.removePanel(panelId);
        }
    }
}

/**
 * Soft commercial gate: keep Community Elements visible, show companion upsell above the catalog.
 *
 * @param {object|null|undefined} shell
 * @param {object} [labels]
 * @param {import('grapesjs').Editor|null|undefined} editor
 * @returns {void}
 */
function mountElementsLibraryUpsell(shell, labels = {}, editor = null) {
    const entitlements = editor?.__voodbuilderEntitlements ?? {};

    // Elements plugin active → SOURCE UI owns the library (no upsell).
    // Pro capability alone is not enough: without the companion, keep the soft upsell
    // above the limited local Elements accordion (same pattern as other companions).
    if (canEntitlement(entitlements, 'elementsLibrary')) {
        return;
    }

    const blocksMount = shell?.mounts?.blocks ?? null;
    const panel = blocksMount?.closest('[data-voodbuilder-library-panel="blocks"]')
        ?? blocksMount?.parentElement
        ?? null;

    if (! panel || panel.querySelector('[data-voodbuilder-elements-upsell]')) {
        return;
    }

    const upsell = createInspectorEmptyState({
        classNameExtra: 'voodbuilder-editor-elements-library__locked',
        title: labels.elementsPluginRequiredTitle ?? 'Voodbuilder Elements',
        message: labels.elementsPluginRequiredBody
            ?? 'Thousands of elements — copy and paste from toolkits like Tailwind Plus, create new elements with code, and expand your library. Requires the Voodbuilder Elements plugin.',
        labels,
        linkUrl: labels.marketingUrl ?? null,
        linkLabel: labels.learnMore ?? 'Learn more',
    });
    upsell.setAttribute('data-voodbuilder-elements-upsell', '1');

    if (blocksMount?.parentElement === panel) {
        panel.insertBefore(upsell, blocksMount);
    } else {
        panel.prepend(upsell);
    }
}

export function configureEditorLayout(editor, shell, labels = {}) {
    trimDefaultPanelButtons(editor);
    setupStyleInspectorSectors(shell.mounts, labels);
    setupLibraryTabs(shell.mounts, labels, editor);
    mountElementsLibraryUpsell(shell, labels, editor);
    setupBlockSearch(editor, shell.mounts.search);
    registerBlockPins(editor, {
        blocksMount: shell.mounts.blocks,
        labels,
    });
    setupStyleInspector(editor, shell.mounts);
    setupInspectorTabs(shell.mounts, editor);
    registerInspectorSelectUi(editor, shell.mounts);
    registerInspectorColorFix(editor, shell.mounts);

    editor.on('style:change', () => {
        editor.Canvas.getFrameEl()?.contentWindow?.dispatchEvent(new Event('resize'));
    });

    editor.on('component:styleUpdate', () => {
        editor.trigger('change:canvasOffset');
    });

    editor.on('load', () => {
        collapseBlockCategories(editor);
    });
}
