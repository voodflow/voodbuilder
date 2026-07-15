/**
 * Inspector UI for registered block settings descriptors.
 */

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
    ensureRootInspectable,
    findInspectableRoot,
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
    let renderedDescriptorId = null;

    const traitsMount = mount.closest('[data-voodbuilder-inspector="content"]')
        ?.querySelector('.voodbuilder-gjs-traits-mount');

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

    const render = () => {
        if (editor.__voodbuilderBlockSettingsRendering) {
            return;
        }

        editor.__voodbuilderBlockSettingsRendering = true;

        try {
            const rawSelected = editor.getSelected();

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

            if (
                root
                && rawSelected
                && shouldPromoteSelectionToRoot(rawSelected, root, editor)
            ) {
                if (editor.getSelected?.() !== root) {
                    ensureRootInspectable(root);
                    editor.select(root, { scroll: false });
                    window.requestAnimationFrame(render);
                }

                return;
            }

            if (! descriptor || ! root) {
                showTraitsFallback();
                renderedRoot = null;
                renderedDescriptorId = null;

                return;
            }

            ensureRootInspectable(root);

            if (
                renderedRoot === root
                && renderedDescriptorId === descriptor.id
                && mount.querySelector('.voodbuilder-gjs-form')
            ) {
                mount.hidden = false;
                traitsMount?.classList.add('hidden');
                syncSettingsFormValues(mount, root);

                if (isChromeLayoutModeEditor(editor)) {
                    editor.__voodbuilderActivateInspectorTab?.('content');
                }

                return;
            }

            mount.hidden = false;
            traitsMount?.classList.add('hidden');
            traitsMount?.replaceChildren?.();
            mount.replaceChildren();

            descriptor.render({ mount, root, editor, traitsMount });

            renderedRoot = root;
            renderedDescriptorId = descriptor.id;

            if (isChromeLayoutModeEditor(editor)) {
                editor.__voodbuilderActivateInspectorTab?.('content');
            }
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
