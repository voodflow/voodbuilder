/**
 * Vpress GrapesJS shell — 3-column builder layout (blocks | canvas | inspector).
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
    container.classList.add('vpress-gjs-root');
    container.innerHTML = `
        <div class="vpress-gjs-shell" data-vpress-device="desktop">
            <header class="vpress-gjs-topbar">
                <div class="vpress-gjs-topbar__brand">${escapeHtml(meta.brand ?? 'VoodBuilder')}</div>
                <div class="vpress-gjs-topbar__tools"></div>
                <div class="vpress-gjs-topbar__actions">
                    <span class="vpress-gjs-topbar__saved" data-vpress-grapesjs-saved hidden>${escapeHtml(labels.saved ?? 'Saved')}</span>
                    <a
                        href="${escapeHtml(meta.exitUrl ?? '#')}"
                        class="vpress-gjs-topbar__btn vpress-gjs-topbar__btn--ghost"
                    >${escapeHtml(labels.exitEditor ?? 'Exit editor')}</a>
                    <button
                        type="button"
                        class="vpress-gjs-topbar__btn vpress-gjs-topbar__btn--primary"
                        data-vpress-grapesjs-save
                    >
                        <span data-vpress-grapesjs-save-label>${escapeHtml(labels.save ?? 'Save')}</span>
                    </button>
                </div>
            </header>
            <div class="vpress-gjs-shell__workspace">
                <aside class="vpress-gjs-shell__left" aria-label="${escapeHtml(labels.panelBlocks ?? 'Blocks')}">
                    <div class="vpress-gjs-shell__panel-head">
                        <span class="vpress-gjs-shell__panel-title">${escapeHtml(labels.panelBlocks ?? 'Blocks')}</span>
                    </div>
                    <label class="vpress-gjs-blocks-search-wrap">
                        <span class="vpress-gjs-blocks-search-icon">${lucideIcon('search', 16)}</span>
                        <input
                            type="search"
                            class="vpress-gjs-blocks-search"
                            placeholder="${escapeHtml(labels.blockSearch ?? 'Search blocks…')}"
                            autocomplete="off"
                            aria-label="${escapeHtml(labels.blockSearch ?? 'Search blocks')}"
                        />
                    </label>
                    <div class="vpress-gjs-blocks-mount"></div>
                </aside>
                <div class="vpress-gjs-shell__center">
                    <div class="vpress-gjs-canvas-mount"></div>
                </div>
                <aside class="vpress-gjs-shell__right" aria-label="${escapeHtml(labels.panelInspector ?? 'Inspector')}">
                    <div class="vpress-gjs-inspector-tabs" role="tablist"></div>
                    <div class="vpress-gjs-inspector-panels">
                        <div class="vpress-gjs-inspector-panel vpress-gjs-inspector-panel--active" data-vpress-inspector="content">
                            <div class="vpress-gjs-traits-mount"></div>
                        </div>
                        <div class="vpress-gjs-inspector-panel" data-vpress-inspector="style">
                            <div class="vpress-gjs-selectors-mount"></div>
                            <div class="vpress-gjs-styles-mount"></div>
                        </div>
                        <div class="vpress-gjs-inspector-panel" data-vpress-inspector="dynamic">
                            <div class="vpress-gjs-dynamic-mount"></div>
                        </div>
                        <div class="vpress-gjs-inspector-panel" data-vpress-inspector="layers">
                            <div class="vpress-gjs-layers-mount"></div>
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

    const tablist = container.querySelector('.vpress-gjs-inspector-tabs');

    for (const tabId of INSPECTOR_TABS) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'vpress-gjs-inspector-tab';
        button.dataset.vpressTab = tabId;
        button.setAttribute('role', 'tab');
        button.setAttribute('aria-selected', tabId === 'content' ? 'true' : 'false');
        button.textContent = tabLabels[tabId];
        tablist.appendChild(button);
    }

    const shell = container.querySelector('.vpress-gjs-shell');

    return {
        shell,
        mounts: {
            canvas: container.querySelector('.vpress-gjs-canvas-mount'),
            canvasToolbar: container.querySelector('.vpress-gjs-topbar__tools'),
            blocks: container.querySelector('.vpress-gjs-blocks-mount'),
            layers: container.querySelector('.vpress-gjs-layers-mount'),
            traits: container.querySelector('.vpress-gjs-traits-mount'),
            selectors: container.querySelector('.vpress-gjs-selectors-mount'),
            styles: container.querySelector('.vpress-gjs-styles-mount'),
            dynamic: container.querySelector('.vpress-gjs-dynamic-mount'),
            search: container.querySelector('.vpress-gjs-blocks-search'),
            tablist,
            panels: container.querySelector('.vpress-gjs-inspector-panels'),
            saveButton: container.querySelector('[data-vpress-grapesjs-save]'),
            saveLabel: container.querySelector('[data-vpress-grapesjs-save-label]'),
            savedIndicator: container.querySelector('[data-vpress-grapesjs-saved]'),
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

        tablist.querySelectorAll('.vpress-gjs-inspector-tab').forEach((button) => {
            const active = button.dataset.vpressTab === tabId;
            button.classList.toggle('vpress-gjs-inspector-tab--active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.querySelectorAll('[data-vpress-inspector]').forEach((panel) => {
            panel.classList.toggle('vpress-gjs-inspector-panel--active', panel.dataset.vpressInspector === tabId);
        });

        window.requestAnimationFrame(() => syncInspectorManagers(editor, tabId));
    };

    tablist.addEventListener('click', (event) => {
        const button = event.target.closest('.vpress-gjs-inspector-tab');

        if (! button?.dataset.vpressTab) {
            return;
        }

        activateTab(button.dataset.vpressTab);
    });

    editor.on('component:selected', (component) => {
        syncInspectorManagers(editor, activeTab);

        if (component?.getAttributes?.()['data-vpress-bind']) {
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
