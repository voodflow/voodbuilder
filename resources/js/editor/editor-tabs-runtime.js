/**
 * Runtime tab switching — used in the Editor canvas and as a public-page fallback.
 */

function findTabsRoot(tablist) {
    let node = tablist.parentElement;

    while (node) {
        if (node.querySelector('[role="tabpanel"]')) {
            return node;
        }

        node = node.parentElement;
    }

    return tablist.parentElement;
}

export function initTabsRoot(root) {
    if (! root || root.dataset.voodbuilderTabsReady === '1') {
        return;
    }

    root.dataset.voodbuilderTabsReady = '1';

    const classTabActive = root.dataset.vbTabActiveClass
        || root.closest('[data-vb-tab-active-class]')?.dataset.vbTabActiveClass
        || 'tab-active';
    const selectorTab = 'aria-controls';
    const roleTab = '[role="tab"]';
    const roleTabContent = '[role="tabpanel"]';
    const body = document.body;
    const matches = body.matchesSelector
        || body.webkitMatchesSelector
        || body.mozMatchesSelector
        || body.msMatchesSelector;

    const each = (items, callback) => {
        const list = items || [];

        for (let index = 0; index < list.length; index += 1) {
            callback(list[index], index);
        }
    };

    const hideContents = () => {
        each(root.querySelectorAll(roleTabContent), (panel) => {
            panel.hidden = true;
        });
    };

    const getTabId = (item) => item.getAttribute(selectorTab);
    const query = (element, selector) => element.querySelector(selector);
    const getAllTabs = () => root.querySelectorAll(roleTab);

    const activeTab = (tabEl) => {
        each(getAllTabs(), (item) => {
            item.className = item.className.replace(classTabActive, '').trim();
            item.setAttribute('aria-selected', 'false');
            item.tabIndex = -1;
        });
        hideContents();
        tabEl.className += ` ${classTabActive}`;
        tabEl.setAttribute('aria-selected', 'true');
        tabEl.tabIndex = 0;
        const tabContentId = getTabId(tabEl);
        const tabContent = tabContentId && query(root, `#${CSS.escape(tabContentId)}`);

        if (tabContent) {
            tabContent.hidden = false;
        }
    };

    const getTabByHash = () => {
        const hashId = (window.location.hash || '').replace('#', '');
        const selector = `${roleTab}[${selectorTab}="${hashId}"]`;

        return hashId ? query(root, selector) : null;
    };

    const getSelectedTab = (target) => {
        let found = null;

        each(getAllTabs(), (item) => {
            if (found) {
                return;
            }

            if (item.contains(target)) {
                found = item;
            }
        });

        return found;
    };

    let tabToActive = null;

    each(getAllTabs(), (tab) => {
        if (! tabToActive && tab.classList.contains(classTabActive)) {
            tabToActive = tab;
        }
    });

    tabToActive = tabToActive || getTabByHash() || query(root, roleTab);

    if (tabToActive) {
        activeTab(tabToActive);
    }

    root.addEventListener('click', (event) => {
        let { target } = event;
        let found = matches.call(target, roleTab);

        if (! found) {
            target = getSelectedTab(target);
            found = target ? 1 : 0;
        }

        if (found && ! event.__voodbuilderTabTrigger && ! target.className.includes(classTabActive)) {
            event.preventDefault();
            event.__voodbuilderTabTrigger = 1;
            activeTab(target);
            const id = getTabId(target);

            try {
                if (window.history && ! window._isEditor) {
                    window.history.pushState(null, '', `#${id}`);
                }
            } catch {
                // Ignore history API failures.
            }
        }
    });
}

export function initTabsInDocument(doc = document) {
    doc.querySelectorAll('[role="tablist"]').forEach((tablist) => {
        initTabsRoot(findTabsRoot(tablist));
    });
}

export function configureEditorTabsCanvas(editor) {
    const refresh = () => {
        const doc = editor.Canvas.getDocument();

        if (! doc) {
            return;
        }

        initTabsInDocument(doc);
    };

    editor.on('canvas:frame:load', refresh);
    editor.on('load', () => window.requestAnimationFrame(refresh));
    editor.on('component:add', () => window.requestAnimationFrame(refresh));
}
