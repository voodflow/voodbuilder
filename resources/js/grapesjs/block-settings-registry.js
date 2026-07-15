/**
 * Registry for GrapesJS blocks with ad-hoc inspector settings (navbar, footer, etc.).
 *
 * Each entry:
 * - id: unique key
 * - findRoot(component, editor): resolve block root from selection
 * - matchesRoot(root): whether root is this block type
 * - render({ mount, root, editor, traitsMount }): build settings UI; return true when handled
 */

import {
    isChromeLayoutContentSlotComponent,
    isChromeLayoutModeEditor,
    isChromeNavFooterSettingsRoot,
    resolveBlockSettingsSelection,
    resolveChromeNavFooterSettingsRoot,
} from './chrome-content-slot-utils.js';

/** @type {Map<string, object>} */
const registry = new Map();

/**
 * @param {object} descriptor
 * @param {string} descriptor.id
 * @param {(component: object, editor: object) => object|null} descriptor.findRoot
 * @param {(root: object) => boolean} descriptor.matchesRoot
 * @param {(ctx: { mount: HTMLElement, root: object, editor: object, traitsMount: HTMLElement|null }) => void} descriptor.render
 */
export function registerBlockSettings(descriptor) {
    if (! descriptor?.id) {
        throw new Error('Block settings descriptor requires an id.');
    }

    registry.set(descriptor.id, descriptor);
}

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

    const renderChromeLayoutSlotHint = (traitsPanel) => {
        if (! traitsPanel) {
            return;
        }

        traitsPanel.classList.remove('hidden');
        traitsPanel.replaceChildren();

        const hint = document.createElement('p');
        hint.className = 'voodbuilder-gjs-inspector-empty-hint';
        hint.textContent = 'Select the header or footer to configure layout settings.';
        traitsPanel.appendChild(hint);
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
            renderChromeLayoutSlotHint(traitsMount);

            return;
        }

        const chromeRoot = resolveChromeNavFooterSettingsRoot(rawSelected);

        if (
            chromeRoot
            && rawSelected
            && chromeRoot !== rawSelected
            && isChromeLayoutModeEditor(editor)
            && ! chromeRoot.isRemoved?.()
        ) {
            editor.select(chromeRoot, { scroll: false });

            return;
        }

        const selected = resolveBlockSettingsSelection(rawSelected, editor);

        let active = null;
        let activeRoot = chromeRoot ?? null;
        const candidates = [selected, rawSelected, chromeRoot].filter((component, index, list) => {
            return component && list.indexOf(component) === index;
        });

        if (activeRoot && isChromeNavFooterSettingsRoot(activeRoot)) {
            for (const descriptor of registry.values()) {
                if (descriptor.matchesRoot(activeRoot)) {
                    active = descriptor;
                    break;
                }
            }
        }

        if (! active) {
            for (const descriptor of registry.values()) {
                for (const candidate of candidates) {
                    const root = descriptor.findRoot(candidate, editor);

                    if (! root || ! descriptor.matchesRoot(root)) {
                        continue;
                    }

                    active = descriptor;
                    activeRoot = root;
                    break;
                }

                if (active) {
                    break;
                }
            }
        }

        if (! active || ! activeRoot) {
            mount.hidden = true;
            mount.replaceChildren();
            traitsMount?.classList.remove('hidden');

            return;
        }

        mount.hidden = false;
        traitsMount?.classList.add('hidden');
        traitsMount?.replaceChildren?.();
        mount.replaceChildren();

        active.render({ mount, root: activeRoot, editor, traitsMount });
    };

    editor.on('component:selected', render);
    editor.on('component:deselected', render);
    editor.on('load', render);
    editor.on('component:update', render);

    editor.__voodbuilderBlockSettingsRender = render;
}

export function refreshBlockSettingsUi(editor) {
    editor?.__voodbuilderBlockSettingsRender?.();
}
