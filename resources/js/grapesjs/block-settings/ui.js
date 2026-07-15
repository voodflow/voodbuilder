/**
 * Inspector UI for registered block settings descriptors.
 */

import {
    isChromeLayoutContentSlotComponent,
    isChromeLayoutModeEditor,
} from '../chrome-content-slot-utils.js';
import {
    registerBlockSettings,
    resolveBlockSettingsTarget,
} from './registry.js';
import {
    resolveInspectableBlockRoot,
    shouldPromoteSelectionToRoot,
} from './selection.js';

export { registerBlockSettings };

/**
 * @param {object} editor
 * @param {HTMLElement|null} mount
 */
export function registerBlockSettingsUi(editor, mount) {
    if (! mount || editor.__voodbuilderBlockSettingsUiRegistered) {
        return;
    }

    editor.__voodbuilderBlockSettingsUiRegistered = true;

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

        const { descriptor, root } = resolveBlockSettingsTarget(rawSelected, editor);

        if (
            root
            && rawSelected
            && shouldPromoteSelectionToRoot(rawSelected, root)
        ) {
            editor.select(root, { scroll: false });

            return;
        }

        if (! descriptor || ! root) {
            showTraitsFallback();

            return;
        }

        mount.hidden = false;
        traitsMount?.classList.add('hidden');
        traitsMount?.replaceChildren?.();
        mount.replaceChildren();

        descriptor.render({ mount, root, editor, traitsMount });
    };

    editor.on('component:selected', () => {
        window.requestAnimationFrame(render);
    });
    editor.on('component:deselected', render);
    editor.on('load', render);
    editor.on('component:update', render);

    editor.__voodbuilderBlockSettingsRender = render;
}

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
export function promoteInspectableBlockSelection(editor, component) {
    const root = resolveInspectableBlockRoot(component, editor);

    if (! root || ! shouldPromoteSelectionToRoot(component, root)) {
        return root;
    }

    editor.select(root, { scroll: false });
    refreshBlockSettingsUi(editor);

    return root;
}
