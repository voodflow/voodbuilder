/**
 * Inspector UI for registered block settings descriptors.
 */

import { ATTR } from '../../core/attrs.js';
import {
    isChromeLayoutContentSlotComponent,
    isChromeLayoutModeEditor,
    isChromeShellModeEditor,
} from '../../chrome-content-slot-utils.js';
import {
    registerBlockSettings,
    resolveSettings,
} from './registry.js';
import {
    getLayoutInspectorForceRenderMs,
    isLayoutInspectorReady,
} from './layout-chrome-registry.js';
import {
    ensureRootInspectable,
    findInspectableRoot,
    readBlockId,
    shouldPromoteSelectionToRoot,
} from './select.js';

/**
 * @param {HTMLElement} mount
 * @param {object} root
 */
function syncSettingsFormValues(mount, root) {
    if (! mount || ! root?.get) {
        return;
    }

    mount.querySelectorAll('[name]').forEach((input) => {
        const name = input.getAttribute('name');

        if (! name) {
            return;
        }

        if (input instanceof HTMLInputElement && input.type === 'checkbox') {
            input.checked = root.get(name) === true;

            return;
        }

        if (input instanceof HTMLSelectElement || input instanceof HTMLInputElement) {
            const value = root.get(name);

            input.value = value == null ? '' : String(value);
        }
    });
}

/**
 * @param {object|null|undefined} component
 * @param {object|null|undefined} root
 * @returns {boolean}
 */
function isWithinSettingsRoot(component, root) {
    if (! component || ! root || component === root) {
        return Boolean(component && root && component === root);
    }

    let current = component;

    while (current && current !== root) {
        current = current.parent?.();
    }

    return current === root;
}

/**
 * Suppresses inspector/settings re-renders and compile-css autobuild while applying block settings.
 *
 * @param {object} editor
 * @param {() => void} callback
 */
export function runWithSettingsChangeGuard(editor, callback) {
    if (! editor || typeof callback !== 'function') {
        return;
    }

    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;

    try {
        callback();
    } finally {
        const nextDepth = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;
        editor.__voodbuilderSettingsChangeDepth = nextDepth;

        if (nextDepth <= 0) {
            editor.__voodbuilderSettingsChange = false;
            delete editor.__voodbuilderSettingsChangeDepth;
        }
    }
}

/**
 * @param {object|null|undefined} descriptor
 * @returns {boolean}
 */
function isLayoutOnlyDescriptor(descriptor) {
    return Boolean(descriptor?.layoutOnly);
}

/**
 * @param {object} editor
 * @returns {boolean}
 */
function isContentInspectorTabActive(editor) {
    return (editor.__voodbuilderInspectorActiveTab ?? 'content') === 'content';
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
function shouldAllowTraitsInLayoutMode(component) {
    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        const attrs = current.getAttributes?.() ?? {};
        const zone = attrs[ATTR.dropZone];

        if (zone === 'nav' || zone === 'footer') {
            return false;
        }

        if (attrs[ATTR.contentSlot]) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

/**
 * @param {object} editor
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
function shouldRenderCustomSettings(editor, component) {
    const { descriptor } = resolveSettings(component, editor);

    if (! descriptor) {
        return false;
    }

    if (
        isChromeShellModeEditor(editor)
        && ! isChromeLayoutModeEditor(editor)
        && isLayoutOnlyDescriptor(descriptor)
    ) {
        return false;
    }

    return true;
}

/**
 * @param {object} editor
 */
function guardTraitManagerForBlockSettings(editor) {
    if (editor.__voodbuilderTraitManagerBlockSettingsGuarded || ! editor.TraitManager?.select) {
        return;
    }

    editor.__voodbuilderTraitManagerBlockSettingsGuarded = true;

    const originalSelect = editor.TraitManager.select.bind(editor.TraitManager);

    editor.TraitManager.select = (component, ...args) => {
        if (isChromeLayoutModeEditor(editor)) {
            if (component && shouldAllowTraitsInLayoutMode(component)) {
                return originalSelect(component, ...args);
            }

            editor.__voodbuilderBlockSettingsRender?.();

            return;
        }

        if (shouldRenderCustomSettings(editor, component)) {
            editor.__voodbuilderBlockSettingsRender?.();

            return;
        }

        return originalSelect(component, ...args);
    };
}

/**
 * @param {object} editor
 * @param {HTMLElement|null} mount
 */
export function registerSettingsUi(editor, mount) {
    if (! mount || editor.__voodbuilderBlockSettingsUiRegistered) {
        return;
    }

    editor.__voodbuilderBlockSettingsUiRegistered = true;

    guardTraitManagerForBlockSettings(editor);

    let renderedRoot = null;
    let renderedRootBlockId = '';
    let renderedDescriptorId = null;
    let layoutInspectorLoadingSince = 0;

    const traitsMount = mount.closest('[data-voodbuilder-inspector="content"]')
        ?.querySelector('.voodbuilder-gjs-traits-mount');

    const renderLayoutInspectorLoading = () => {
        mount.hidden = false;
        traitsMount?.classList.add('hidden');
        mount.replaceChildren();

        const hint = document.createElement('p');
        hint.className = 'voodbuilder-gjs-inspector-empty-hint';
        hint.textContent = 'Loading header and footer settings…';
        mount.appendChild(hint);
    };

    const renderLayoutSlotHint = (traitsPanel) => {
        if (! traitsPanel) {
            return;
        }

        traitsPanel.classList.remove('hidden');
        traitsPanel.replaceChildren();

        const hint = document.createElement('p');
        hint.className = 'voodbuilder-gjs-inspector-empty-hint';
        hint.textContent = 'Select a block in the header or footer zone to configure its settings.';
        traitsPanel.appendChild(hint);
    };

    const showTraitsFallback = () => {
        mount.hidden = true;
        mount.replaceChildren();
        traitsMount?.classList.remove('hidden');
    };

    const maybePromoteSelectionForHighlight = (rawSelected, root) => {
        if (
            ! root
            || ! rawSelected
            || ! shouldPromoteSelectionToRoot(rawSelected, root, editor)
            || editor.getSelected?.() === root
        ) {
            return;
        }

        window.requestAnimationFrame(() => {
            ensureRootInspectable(root);
            editor.select(root, { scroll: false });
        });
    };

    const shouldDeferLayoutInspectorRender = (rawSelected) => {
        if (! isChromeLayoutModeEditor(editor) || isLayoutInspectorReady(editor)) {
            layoutInspectorLoadingSince = 0;

            return false;
        }

        const { descriptor, root } = resolveSettings(rawSelected, editor);

        if (descriptor && root) {
            layoutInspectorLoadingSince = 0;

            return false;
        }

        if (! layoutInspectorLoadingSince) {
            layoutInspectorLoadingSince = Date.now();
        }

        return Date.now() - layoutInspectorLoadingSince < getLayoutInspectorForceRenderMs();
    };

    const render = () => {
        if (editor.__voodbuilderBlockSettingsRendering) {
            return;
        }

        editor.__voodbuilderBlockSettingsRendering = true;

        try {
            const rawSelected = editor.getSelected();

            if (shouldDeferLayoutInspectorRender(rawSelected)) {
                renderLayoutInspectorLoading();

                return;
            }

            if (
                isChromeLayoutModeEditor(editor)
                && rawSelected
                && isChromeLayoutContentSlotComponent(rawSelected)
            ) {
                mount.hidden = true;
                mount.replaceChildren();
                renderLayoutSlotHint(traitsMount);

                return;
            }

            const { descriptor, root } = resolveSettings(rawSelected, editor);

            if (
                isChromeShellModeEditor(editor)
                && ! isChromeLayoutModeEditor(editor)
                && isLayoutOnlyDescriptor(descriptor)
            ) {
                showTraitsFallback();

                return;
            }

            if (! descriptor || ! root) {
                showTraitsFallback();
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            const rootBlockId = readBlockId(root);

            if (
                isChromeLayoutModeEditor(editor)
                && isLayoutOnlyDescriptor(descriptor)
                && ! isContentInspectorTabActive(editor)
            ) {
                mount.hidden = true;
                traitsMount?.classList.add('hidden');

                return;
            }

            ensureRootInspectable(root);
            maybePromoteSelectionForHighlight(rawSelected, root);

            if (
                renderedRootBlockId !== ''
                && renderedRootBlockId === rootBlockId
                && renderedDescriptorId === descriptor.id
                && mount.querySelector('.voodbuilder-gjs-form')
            ) {
                mount.hidden = false;
                traitsMount?.classList.add('hidden');
                renderedRoot = root;
                syncSettingsFormValues(mount, root);

                return;
            }

            mount.hidden = false;
            traitsMount?.classList.add('hidden');
            traitsMount?.replaceChildren?.();
            mount.replaceChildren();

            descriptor.render({ mount, root, editor, traitsMount });

            renderedRoot = root;
            renderedRootBlockId = rootBlockId;
            renderedDescriptorId = descriptor.id;
        } finally {
            editor.__voodbuilderBlockSettingsRendering = false;
        }
    };

    const scheduleRender = () => {
        if (editor.__voodbuilderSettingsChange) {
            return;
        }

        if (editor.__voodbuilderBlockSettingsRenderScheduled) {
            return;
        }

        editor.__voodbuilderBlockSettingsRenderScheduled = true;

        window.requestAnimationFrame(() => {
            editor.__voodbuilderBlockSettingsRenderScheduled = false;
            render();
        });
    };

    editor.on('component:selected', scheduleRender);
    editor.on('component:deselected', render);
    editor.on('load', scheduleRender);
    editor.on('voodbuilder:chrome-layout-ready', scheduleRender);
    editor.on('voodbuilder:layout-inspector-ready', scheduleRender);
    editor.on('voodbuilder:dynamic-blocks-refreshed', scheduleRender);
    editor.on('component:update', (component) => {
        if (editor.__voodbuilderSettingsChange) {
            return;
        }

        const selected = editor.getSelected();
        const { root } = resolveSettings(selected, editor);

        if (
            root
            && renderedRoot === root
            && isWithinSettingsRoot(component, root)
            && mount.querySelector('.voodbuilder-gjs-form')
        ) {
            return;
        }

        scheduleRender();
    });

    editor.__voodbuilderBlockSettingsRender = render;
}

/** @deprecated */
export const registerBlockSettingsUi = registerSettingsUi;

/**
 * @param {object} editor
 */
export function refreshBlockSettingsUi(editor) {
    editor?.__voodbuilderBlockSettingsRender?.();
}

/**
 * @param {object} editor
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function promoteRoot(editor, component) {
    const root = findInspectableRoot(component, editor);

    if (! root || ! shouldPromoteSelectionToRoot(component, root, editor)) {
        refreshBlockSettingsUi(editor);

        return root;
    }

    if (editor.getSelected?.() === root) {
        refreshBlockSettingsUi(editor);

        return root;
    }

    ensureRootInspectable(root);
    editor.select(root, { scroll: false });
    refreshBlockSettingsUi(editor);

    return root;
}

/** @deprecated */
export const promoteInspectableBlockSelection = promoteRoot;
