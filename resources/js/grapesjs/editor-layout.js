/**
 * Voodbuilder GrapesJS shell — 3-column builder layout (blocks | canvas | inspector).
 * Uses only public GrapesJS APIs: appendTo, Panels, BlockManager container.
 */

import { STYLE_MANAGER_SECTORS } from './editor-chrome.js';
import { lucideIcon } from './editor-icons.js';

const INSPECTOR_TABS = ['content', 'style', 'dynamic', 'layers'];

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
                <div class="voodbuilder-gjs-topbar__brand">${escapeHtml(meta.brand ?? 'VoodBuilder')}</div>
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
                <aside class="voodbuilder-gjs-shell__left" aria-label="${escapeHtml(labels.panelBlocks ?? 'Blocks')}">
                    <div class="voodbuilder-gjs-shell__panel-head">
                        <span class="voodbuilder-gjs-shell__panel-title">${escapeHtml(labels.panelBlocks ?? 'Blocks')}</span>
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
                    <div class="voodbuilder-gjs-blocks-mount"></div>
                </aside>
                <div class="voodbuilder-gjs-shell__center">
                    <div class="voodbuilder-gjs-canvas-mount"></div>
                </div>
                <aside class="voodbuilder-gjs-shell__right" aria-label="${escapeHtml(labels.panelInspector ?? 'Inspector')}">
                    <div class="voodbuilder-gjs-inspector-tabs" role="tablist"></div>
                    <div class="voodbuilder-gjs-inspector-panels">
                        <div class="voodbuilder-gjs-inspector-panel voodbuilder-gjs-inspector-panel--active" data-voodbuilder-inspector="content">
                            <div class="voodbuilder-gjs-traits-mount"></div>
                        </div>
                        <div class="voodbuilder-gjs-inspector-panel" data-voodbuilder-inspector="style">
                            <div class="voodbuilder-gjs-selectors-mount"></div>
                            <div class="voodbuilder-gjs-styles-mount"></div>
                        </div>
                        <div class="voodbuilder-gjs-inspector-panel" data-voodbuilder-inspector="dynamic">
                            <div class="voodbuilder-gjs-dynamic-mount"></div>
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
        layers: labels.tabLayers ?? 'Layers',
    };

    const tablist = container.querySelector('.voodbuilder-gjs-inspector-tabs');

    for (const tabId of INSPECTOR_TABS) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'voodbuilder-gjs-inspector-tab';
        button.dataset.vpressTab = tabId;
        button.setAttribute('role', 'tab');
        button.setAttribute('aria-selected', tabId === 'content' ? 'true' : 'false');
        button.textContent = tabLabels[tabId];
        tablist.appendChild(button);
    }

    const shell = container.querySelector('.voodbuilder-gjs-shell');

    return {
        shell,
        mounts: {
            canvas: container.querySelector('.voodbuilder-gjs-canvas-mount'),
            canvasToolbar: container.querySelector('.voodbuilder-gjs-topbar__tools'),
            blocks: container.querySelector('.voodbuilder-gjs-blocks-mount'),
            layers: container.querySelector('.voodbuilder-gjs-layers-mount'),
            traits: container.querySelector('.voodbuilder-gjs-traits-mount'),
            selectors: container.querySelector('.voodbuilder-gjs-selectors-mount'),
            styles: container.querySelector('.voodbuilder-gjs-styles-mount'),
            dynamic: container.querySelector('.voodbuilder-gjs-dynamic-mount'),
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
            appendTo: mounts.selectors,
            componentFirst: true,
        },
        styleManager: {
            appendTo: mounts.styles,
            sectors: STYLE_MANAGER_SECTORS,
        },
        panels: {
            defaults: [
                {
                    id: 'commands',
                    el: mounts.canvasToolbar,
                },
                {
                    id: 'options',
                    el: mounts.canvasToolbar,
                },
            ],
        },
    };
}

function setupBlockSearch(editor, searchInput) {
    if (! searchInput) {
        return;
    }

    const filter = () => {
        const query = searchInput.value.trim().toLowerCase();
        const blockContainer = editor.BlockManager.getContainer();

        if (! blockContainer) {
            return;
        }

        const openCategories = new Set();

        editor.BlockManager.getAll().forEach((block) => {
            const label = String(block.get('label') ?? '').toLowerCase();

            if (query && ! label.includes(query)) {
                return;
            }

            const category = block.get('category');

            if (category?.get?.('id')) {
                openCategories.add(category.get('id'));
            } else if (typeof category === 'string') {
                openCategories.add(category);
            }
        });

        blockContainer.querySelectorAll('.gjs-block').forEach((blockEl) => {
            const label = blockEl.textContent?.toLowerCase() ?? '';
            blockEl.style.display = ! query || label.includes(query) ? '' : 'none';
        });

        blockContainer.querySelectorAll('.gjs-block-category').forEach((categoryEl) => {
            const hasVisible = [...categoryEl.querySelectorAll('.gjs-block')].some(
                (blockEl) => blockEl.style.display !== 'none',
            );

            categoryEl.style.display = hasVisible ? '' : 'none';
        });

        editor.BlockManager.getCategories?.()?.each?.((category) => {
            category.set('open', query ? openCategories.has(category.get('id')) : false);
        });
    };

    searchInput.addEventListener('input', filter);
    editor.on('block:add', filter);
    editor.on('block:remove', filter);
}

export function collapseBlockCategories(editor) {
    const categories = editor.BlockManager.getCategories?.();

    categories?.each?.((category) => {
        category.set('open', false);
    });
}

function syncInspectorManagers(editor, tabId) {
    const component = editor.getSelected();

    if (! component) {
        return;
    }

    // Managers are mounted in the custom right sidebar — do not run GrapesJS
    // open-tm / open-sm / open-layers commands; they show the native views panel
    // inside the canvas and create an empty white column beside the iframe.
    if (tabId === 'content') {
        editor.TraitManager.select(component);
    }
}

function setupInspectorTabs(mounts, editor) {
    const { tablist, panels } = mounts;
    let activeTab = 'content';

    if (! tablist || ! panels) {
        return;
    }

    const activateTab = (tabId) => {
        activeTab = tabId;

        tablist.querySelectorAll('.voodbuilder-gjs-inspector-tab').forEach((button) => {
            const active = button.dataset.vpressTab === tabId;
            button.classList.toggle('voodbuilder-gjs-inspector-tab--active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.querySelectorAll('[data-voodbuilder-inspector]').forEach((panel) => {
            panel.classList.toggle('voodbuilder-gjs-inspector-panel--active', panel.dataset.vpressInspector === tabId);
        });

        window.requestAnimationFrame(() => syncInspectorManagers(editor, tabId));
    };

    tablist.addEventListener('click', (event) => {
        const button = event.target.closest('.voodbuilder-gjs-inspector-tab');

        if (! button?.dataset.vpressTab) {
            return;
        }

        activateTab(button.dataset.vpressTab);
    });

    editor.on('component:selected', (component) => {
        syncInspectorManagers(editor, activeTab);

        if (component?.getAttributes?.()['data-voodbuilder-bind']) {
            activateTab('dynamic');
        }
    });

    editor.on('component:deselected', () => {
        syncInspectorManagers(editor, activeTab);
    });

    activateTab('content');
}

function dedupeSelectorManagerPanels(selectorsMount, stylesMount) {
    if (stylesMount) {
        stylesMount.querySelectorAll('.gjs-clm').forEach((panel) => panel.remove());
    }

    if (! selectorsMount) {
        return;
    }

    const classPanels = selectorsMount.querySelectorAll('.gjs-clm');

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
        observeSelectorManagerDedupe(mounts);
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
    ];

    for (const panelId of ['options', 'views', 'commands']) {
        for (const buttonId of removeIds) {
            editor.Panels.removeButton(panelId, buttonId);
        }
    }

    const viewsPanel = editor.Panels.getPanel('views');

    if (viewsPanel) {
        editor.Panels.removePanel('views');
    }
}

export function configureEditorLayout(editor, shell, labels = {}) {
    trimDefaultPanelButtons(editor);
    setupBlockSearch(editor, shell.mounts.search);
    setupStyleInspector(editor, shell.mounts);
    setupInspectorTabs(shell.mounts, editor);

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
