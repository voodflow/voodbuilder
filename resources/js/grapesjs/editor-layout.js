/**
 * Voodbuilder GrapesJS shell — 3-column builder layout (blocks | canvas | inspector).
 * Uses only public GrapesJS APIs: appendTo, Panels, BlockManager container.
 */

import { STYLE_MANAGER_SECTORS } from './editor-chrome.js';
import { registerBlockPins } from './block-pins.js';
import { lucideIcon, tablerIcon } from './editor-icons.js';
import { setupStyleInspectorSectors } from './inspector-collapsible-sector.js';
import { registerInspectorSelectUi } from './inspector-select-ui.js';
import { registerInspectorColorFix } from './inspector-color-fix.js';
import { applyBlocksLibraryUi, collapseAllBlockCategories, collapseLibraryCategories, readBlocksSearchQuery } from './blocks-library-sync.js';
import { resolveBlockSettingsTarget, promoteRoot, refreshBlockSettingsUi, findInspectableRoot, ensureRootInspectable } from './blocks/settings/index.js';

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

export function buildEditorShell(container, labels = {}, meta = {}) {
    container.classList.add('voodbuilder-gjs-root');
    container.innerHTML = `
        <div class="voodbuilder-gjs-shell" data-voodbuilder-device="desktop">
            <header class="voodbuilder-gjs-topbar">
                <div class="voodbuilder-gjs-topbar__brand-wrap">
                    <div class="voodbuilder-gjs-topbar__brand">${escapeHtml(meta.brand ?? 'VoodBuilder')}</div>
                    ${meta.editingBadgeTitle ? `
                        <div class="voodbuilder-gjs-topbar__editing-context">
                            <span class="voodbuilder-gjs-topbar__editing-badge">${escapeHtml(meta.editingBadgeTitle)}</span>
                            ${meta.editingBadgeHint ? `<span class="voodbuilder-gjs-topbar__editing-hint">${escapeHtml(meta.editingBadgeHint)}</span>` : ''}
                        </div>
                    ` : ''}
                </div>
                <div class="voodbuilder-gjs-topbar__tools"></div>
                <div class="voodbuilder-gjs-topbar__actions">
                    <span class="voodbuilder-gjs-topbar__saved" data-voodbuilder-grapesjs-saved hidden>${escapeHtml(labels.saved ?? 'Saved')}</span>
                    <a
                        href="${escapeHtml(meta.exitUrl ?? '#')}"
                        class="voodbuilder-gjs-topbar__btn voodbuilder-gjs-topbar__btn--ghost"
                    >${escapeHtml(labels.exitEditor ?? 'Exit editor')}</a>
                    <button
                        type="button"
                        class="voodbuilder-gjs-topbar__btn voodbuilder-gjs-topbar__btn--primary"
                        data-voodbuilder-grapesjs-save
                    >
                        <span data-voodbuilder-grapesjs-save-label>${escapeHtml(labels.save ?? 'Save')}</span>
                    </button>
                </div>
            </header>
            <div class="voodbuilder-gjs-shell__workspace">
                <aside class="voodbuilder-gjs-shell__left" aria-label="${escapeHtml(labels.panelLibrary ?? labels.panelBlocks ?? 'Library')}">
                    <div class="voodbuilder-gjs-library-tabs" role="tablist">
                        <button type="button" class="voodbuilder-gjs-library-tab voodbuilder-gjs-library-tab--active" data-voodbuilder-library="blocks" role="tab" aria-selected="true" title="${escapeHtml(labels.tabElements ?? 'Elements')}" aria-label="${escapeHtml(labels.tabElements ?? 'Elements')}">
                            ${tablerIcon(LIBRARY_TAB_ICONS.blocks, 17)}
                        </button>
                        <button type="button" class="voodbuilder-gjs-library-tab" data-voodbuilder-library="components" role="tab" aria-selected="false" title="${escapeHtml(labels.tabComponents ?? 'Components')}" aria-label="${escapeHtml(labels.tabComponents ?? 'Components')}">
                            ${tablerIcon(LIBRARY_TAB_ICONS.components, 17)}
                        </button>
                        <button type="button" class="voodbuilder-gjs-library-tab" data-voodbuilder-library="templates" role="tab" aria-selected="false" title="${escapeHtml(labels.tabTemplates ?? 'Templates')}" aria-label="${escapeHtml(labels.tabTemplates ?? 'Templates')}">
                            ${tablerIcon(LIBRARY_TAB_ICONS.templates, 17)}
                        </button>
                    </div>
                    <label class="voodbuilder-gjs-blocks-search-wrap">
                        <span class="voodbuilder-gjs-blocks-search-icon">${lucideIcon('search', 16)}</span>
                        <input
                            type="search"
                            class="voodbuilder-gjs-blocks-search"
                            placeholder="${escapeHtml(labels.blockSearch ?? 'Search blocks…')}"
                            autocomplete="off"
                            aria-label="${escapeHtml(labels.blockSearch ?? 'Search blocks')}"
                        />
                    </label>
                    <div class="voodbuilder-gjs-library-panels">
                        <div class="voodbuilder-gjs-library-panel voodbuilder-gjs-library-panel--active" data-voodbuilder-library-panel="blocks">
                            <div class="voodbuilder-gjs-blocks-mount"></div>
                        </div>
                        <div class="voodbuilder-gjs-library-panel" data-voodbuilder-library-panel="components" hidden>
                            <div class="voodbuilder-gjs-components-mount"></div>
                        </div>
                        <div class="voodbuilder-gjs-library-panel" data-voodbuilder-library-panel="templates" hidden>
                            <div class="voodbuilder-gjs-templates-mount"></div>
                        </div>
                    </div>
                </aside>
                <div class="voodbuilder-gjs-shell__center">
                    <div class="voodbuilder-gjs-canvas-mount"></div>
                </div>
                <aside class="voodbuilder-gjs-shell__right" aria-label="${escapeHtml(labels.panelInspector ?? 'Inspector')}">
                    <div class="voodbuilder-gjs-inspector-tabs" role="tablist"></div>
                    <div class="voodbuilder-gjs-inspector-panels">
                        <div class="voodbuilder-gjs-inspector-panel voodbuilder-gjs-inspector-panel--active" data-voodbuilder-inspector="content">
                            <div class="voodbuilder-gjs-traits-mount"></div>
                            <div class="voodbuilder-gjs-site-chrome-settings-mount" hidden></div>
                            <div class="voodbuilder-gjs-component-props-mount"></div>
                        </div>
                        <div class="voodbuilder-gjs-inspector-panel" data-voodbuilder-inspector="style">
                            <div class="voodbuilder-gjs-selectors-mount"></div>
                            <div class="voodbuilder-gjs-global-classes-mount"></div>
                            <div class="voodbuilder-gjs-styles-mount"></div>
                        </div>
                        <div class="voodbuilder-gjs-inspector-panel" data-voodbuilder-inspector="dynamic">
                            <div class="voodbuilder-gjs-dynamic-mount"></div>
                        </div>
                        <div class="voodbuilder-gjs-inspector-panel" data-voodbuilder-inspector="conditions">
                            <div class="voodbuilder-gjs-conditions-mount"></div>
                        </div>
                        <div class="voodbuilder-gjs-inspector-panel" data-voodbuilder-inspector="layers">
                            <div class="voodbuilder-gjs-layers-mount"></div>
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

    const tablist = container.querySelector('.voodbuilder-gjs-inspector-tabs');

    for (const tabId of INSPECTOR_TABS) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'voodbuilder-gjs-inspector-tab';
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

    const shell = container.querySelector('.voodbuilder-gjs-shell');

    return {
        shell,
        mounts: {
            canvas: container.querySelector('.voodbuilder-gjs-canvas-mount'),
            canvasToolbar: container.querySelector('.voodbuilder-gjs-topbar__tools'),
            blocks: container.querySelector('.voodbuilder-gjs-blocks-mount'),
            components: container.querySelector('.voodbuilder-gjs-components-mount'),
            templates: container.querySelector('.voodbuilder-gjs-templates-mount'),
            componentProps: container.querySelector('.voodbuilder-gjs-component-props-mount'),
            libraryTabs: container.querySelector('.voodbuilder-gjs-library-tabs'),
            libraryPanels: container.querySelector('.voodbuilder-gjs-library-panels'),
            layers: container.querySelector('.voodbuilder-gjs-layers-mount'),
            traits: container.querySelector('.voodbuilder-gjs-traits-mount'),
            siteChromeSettings: container.querySelector('.voodbuilder-gjs-site-chrome-settings-mount'),
            selectors: container.querySelector('.voodbuilder-gjs-selectors-mount'),
            styles: container.querySelector('.voodbuilder-gjs-styles-mount'),
            dynamic: container.querySelector('.voodbuilder-gjs-dynamic-mount'),
            conditions: container.querySelector('.voodbuilder-gjs-conditions-mount'),
            globalClasses: container.querySelector('.voodbuilder-gjs-global-classes-mount'),
            search: container.querySelector('.voodbuilder-gjs-blocks-search'),
            tablist,
            panels: container.querySelector('.voodbuilder-gjs-inspector-panels'),
            saveButton: container.querySelector('[data-voodbuilder-grapesjs-save]'),
            saveLabel: container.querySelector('[data-voodbuilder-grapesjs-save-label]'),
            savedIndicator: container.querySelector('[data-voodbuilder-grapesjs-saved]'),
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
        blocks: labels.blockSearch ?? 'Search blocks…',
        components: labels.componentSearch ?? 'Search components…',
        templates: labels.templateSearch ?? 'Search templates…',
    };

    const activateLibrary = (libraryId) => {
        activeLibrary = libraryId;

        if (editor) {
            editor.__voodbuilderActiveLibrary = libraryId;
            editor.__voodbuilderRelocateLibrary?.(libraryId);
        }

        libraryTabs.closest('.voodbuilder-gjs-shell')
            ?.setAttribute('data-voodbuilder-active-library', libraryId);

        libraryTabs.querySelectorAll('[data-voodbuilder-library]').forEach((button) => {
            const active = button.dataset.voodbuilderLibrary === libraryId;
            button.classList.toggle('voodbuilder-gjs-library-tab--active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        libraryPanels.querySelectorAll('[data-voodbuilder-library-panel]').forEach((panel) => {
            const active = panel.dataset.voodbuilderLibraryPanel === libraryId;
            panel.classList.toggle('voodbuilder-gjs-library-panel--active', active);
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
    const inspectorAside = panels?.closest('.voodbuilder-gjs-shell__right') ?? null;
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

        tablist.querySelectorAll('.voodbuilder-gjs-inspector-tab').forEach((button) => {
            const active = button.dataset.voodbuilderTab === tabId;
            button.classList.toggle('voodbuilder-gjs-inspector-tab--active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.querySelectorAll('[data-voodbuilder-inspector]').forEach((panel) => {
            const active = panel.dataset.voodbuilderInspector === tabId;

            panel.classList.toggle('voodbuilder-gjs-inspector-panel--active', active);
            panel.toggleAttribute('hidden', ! active);
            panel.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        if (inspectorAside) {
            setInspectorSidebarWidth(inspectorAside, tabId);
        }

        window.requestAnimationFrame(() => syncInspectorManagers(editor, tabId, {
            refreshInspectorPanels: tabId === 'dynamic' || tabId === 'conditions',
        }));
    };

    editor.__voodbuilderActivateInspectorTab = activateTab;

    tablist.addEventListener('click', (event) => {
        const button = event.target.closest('.voodbuilder-gjs-inspector-tab');

        if (! button?.dataset.voodbuilderTab) {
            return;
        }

        activateTab(button.dataset.voodbuilderTab, { userInitiated: true });
    });

    editor.on('component:selected', (component) => {
        if (component && editor.__voodbuilderChromeLayoutMode) {
            const { descriptor, root } = resolveBlockSettingsTarget(component, editor);

            if (descriptor?.layoutOnly && root) {
                const lastRoot = editor.__voodbuilderLayoutSettingsAutoTabRoot ?? null;

                if (lastRoot !== root) {
                    editor.__voodbuilderLayoutSettingsAutoTabRoot = root;
                    activateTab('content');
                }
            } else if (! descriptor?.layoutOnly) {
                editor.__voodbuilderLayoutSettingsAutoTabRoot = null;
            }
        }

        syncInspectorManagers(editor, activeTab);

        if (component?.getAttributes?.()['data-voodbuilder-bind']) {
            activateTab('dynamic');
        }

        if (component?.getAttributes?.()['data-voodbuilder-conditions']) {
            activateTab('conditions');
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

    const dedupe = () => dedupeSelectorManagerPanels(mounts.selectors, mounts.styles);
    const observer = new MutationObserver(() => {
        dedupe();
    });

    for (const target of targets) {
        observer.observe(target, { childList: true, subtree: true });
    }

    dedupe();
}

function setupStyleInspector(editor, mounts) {
    const dedupe = () => dedupeSelectorManagerPanels(mounts.selectors, mounts.styles);

    editor.on('load', () => {
        mountSelectorManagerPanel(editor, mounts.selectors);
        observeSelectorManagerDedupe(mounts);
        dedupe();
    });
    editor.on('component:selected', () => {
        window.requestAnimationFrame(() => {
            dedupe();
            window.requestAnimationFrame(dedupe);
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

export function configureEditorLayout(editor, shell, labels = {}) {
    trimDefaultPanelButtons(editor);
    setupStyleInspectorSectors(shell.mounts, labels);
    setupLibraryTabs(shell.mounts, labels, editor);
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
