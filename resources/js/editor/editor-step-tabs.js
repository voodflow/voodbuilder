/**
 * Upgrades step nav (STEP 1…N) to accessible tabs in the editor canvas.
 */

import { initTabsInDocument } from './editor-tabs-runtime.js';

function collectStepLinks(navContainer) {
    return [...navContainer.children].filter((child) => {
        if (child.tagName !== 'A') {
            return false;
        }

        return /\bSTEP\s*\d+\b/i.test(child.textContent ?? '');
    });
}

function collectContentSiblings(navContainer) {
    const nodes = [];
    let sibling = navContainer.nextElementSibling;

    while (sibling) {
        nodes.push(sibling);
        sibling = sibling.nextElementSibling;
    }

    return nodes;
}

function findStepWrapper(element) {
    let node = element;

    while (node) {
        if (node.tagName === 'SECTION') {
            return node;
        }

        node = node.parentElement;
    }

    return element.parentElement ?? element;
}

export function normalizeStepTabsInDocument(doc = document) {
    let changed = false;

    doc.querySelectorAll('div').forEach((navContainer) => {
        if (navContainer.getAttribute('role') === 'tablist'
            || navContainer.classList.contains('vb-step-tabs__bar')) {
            return;
        }

        const stepLinks = collectStepLinks(navContainer);

        if (stepLinks.length < 2) {
            return;
        }

        const contentParent = navContainer.parentElement;

        if (! contentParent) {
            return;
        }

        const contentNodes = collectContentSiblings(navContainer);

        if (contentNodes.length === 0) {
            return;
        }

        const groupId = `vb-step-${Math.random().toString(36).slice(2, 10)}`;
        const contentsWrapper = doc.createElement('div');
        contentsWrapper.className = 'vb-step-tabs__contents';

        stepLinks.forEach((link, index) => {
            const panelId = `${groupId}-panel-${index}`;

            link.setAttribute('role', 'tab');
            link.setAttribute('aria-controls', panelId);
            link.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
            link.tabIndex = index === 0 ? 0 : -1;
            link.setAttribute('href', `#${panelId}`);
            link.removeAttribute('target');

            link.classList.remove('bg-gray-100', 'border-indigo-500', 'text-indigo-500');
            link.classList.add('vb-step-tabs__tab');

            if (index === 0) {
                link.classList.add('vb-step-tabs__tab--active');
            } else {
                link.classList.remove('vb-step-tabs__tab--active');
            }

            const panel = doc.createElement('div');
            panel.setAttribute('role', 'tabpanel');
            panel.id = panelId;
            panel.className = 'vb-step-tabs__panel';

            if (index > 0) {
                panel.hidden = true;
            }

            const source = contentNodes[0];
            panel.appendChild(source.cloneNode(true));

            contentsWrapper.appendChild(panel);
        });

        navContainer.setAttribute('role', 'tablist');
        navContainer.classList.add('vb-step-tabs__bar');

        contentNodes.forEach((node) => node.remove());
        contentParent.appendChild(contentsWrapper);

        const wrapper = findStepWrapper(contentParent);
        wrapper.setAttribute('data-voodbuilder-step-tabs', '');
        wrapper.setAttribute('data-vb-tab-active-class', 'vb-step-tabs__tab--active');

        changed = true;
    });

    if (changed) {
        initTabsInDocument(doc);
    }

    return changed;
}

export function configureEditorStepTabsCanvas(editor) {
    const refresh = () => {
        const doc = editor.Canvas.getDocument();

        if (! doc) {
            return;
        }

        normalizeStepTabsInDocument(doc);
        initTabsInDocument(doc);
    };

    editor.on('canvas:frame:load', refresh);
    editor.on('load', () => window.requestAnimationFrame(refresh));
    editor.on('component:add', () => window.requestAnimationFrame(refresh));
}
