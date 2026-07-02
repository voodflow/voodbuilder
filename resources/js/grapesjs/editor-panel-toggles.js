/**
 * Collapsible left/right editor panels with persisted visibility.
 */

import { lucideIcon } from './editor-icons.js';

const STORAGE_LEFT = 'voodbuilder:gjs:panel-left-visible';
const STORAGE_RIGHT = 'voodbuilder:gjs:panel-right-visible';

function readPanelVisibility(key, fallback = true) {
    try {
        const value = window.localStorage.getItem(key);

        if (value === '0' || value === 'false') {
            return false;
        }

        if (value === '1' || value === 'true') {
            return true;
        }
    } catch {
        // Ignore storage errors (private mode, etc.).
    }

    return fallback;
}

function writePanelVisibility(key, visible) {
    try {
        window.localStorage.setItem(key, visible ? '1' : '0');
    } catch {
        // Ignore storage errors.
    }
}

function createPanelToggle({ id, icon, title, active }) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'voodbuilder-gjs-topbar-tool-btn';
    button.dataset.voodbuilderPanelToggle = id;

    if (active) {
        button.classList.add('is-active');
    }

    button.title = title;
    button.setAttribute('aria-pressed', active ? 'true' : 'false');
    button.innerHTML = lucideIcon(icon, 18);

    return button;
}

export function registerEditorPanelToggles(shell, labels = {}, toolsMount = null) {
    const rootShell = shell?.shell;

    if (! rootShell) {
        return;
    }

    const leftAside = rootShell.querySelector('.voodbuilder-gjs-shell__left');
    const rightAside = rootShell.querySelector('.voodbuilder-gjs-shell__right');

    if (! leftAside || ! rightAside) {
        return;
    }

    let leftVisible = readPanelVisibility(STORAGE_LEFT, true);
    let rightVisible = readPanelVisibility(STORAGE_RIGHT, true);

    const applyVisibility = () => {
        rootShell.classList.toggle('voodbuilder-gjs-shell--left-hidden', ! leftVisible);
        rootShell.classList.toggle('voodbuilder-gjs-shell--right-hidden', ! rightVisible);
        leftAside.setAttribute('aria-hidden', leftVisible ? 'false' : 'true');
        rightAside.setAttribute('aria-hidden', rightVisible ? 'false' : 'true');
    };

    const mountToggles = (mount) => {
        if (! mount) {
            return;
        }

        const divider = document.createElement('div');
        divider.className = 'voodbuilder-gjs-topbar-tool-divider';
        divider.setAttribute('aria-hidden', 'true');

        const group = document.createElement('div');
        group.className = 'voodbuilder-gjs-topbar-tool-group';
        group.setAttribute('role', 'group');
        group.setAttribute('aria-label', labels.panelToggles ?? 'Panels');

        const leftButton = createPanelToggle({
            id: 'left',
            icon: 'panel-left',
            title: labels.toggleLibraryPanel ?? 'Show/hide library',
            active: leftVisible,
        });

        const rightButton = createPanelToggle({
            id: 'right',
            icon: 'panel-right',
            title: labels.toggleInspectorPanel ?? 'Show/hide inspector',
            active: rightVisible,
        });

        const syncButtons = () => {
            leftButton.classList.toggle('is-active', leftVisible);
            leftButton.setAttribute('aria-pressed', leftVisible ? 'true' : 'false');
            rightButton.classList.toggle('is-active', rightVisible);
            rightButton.setAttribute('aria-pressed', rightVisible ? 'true' : 'false');
        };

        leftButton.addEventListener('click', () => {
            leftVisible = ! leftVisible;
            writePanelVisibility(STORAGE_LEFT, leftVisible);
            applyVisibility();
            syncButtons();
        });

        rightButton.addEventListener('click', () => {
            rightVisible = ! rightVisible;
            writePanelVisibility(STORAGE_RIGHT, rightVisible);
            applyVisibility();
            syncButtons();
        });

        group.append(leftButton, rightButton);
        mount.append(divider, group);
    };

    applyVisibility();
    mountToggles(toolsMount);
}
