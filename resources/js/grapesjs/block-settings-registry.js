/**
 * Registry for GrapesJS blocks with ad-hoc inspector settings (navbar, footer, etc.).
 *
 * Each entry:
 * - id: unique key
 * - findRoot(component, editor): resolve block root from selection
 * - matchesRoot(root): whether root is this block type
 * - render({ mount, root, editor, traitsMount }): build settings UI; return true when handled
 */

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

    const render = () => {
        const selected = editor.getSelected();
        let active = null;
        let activeRoot = null;

        for (const descriptor of registry.values()) {
            const root = descriptor.findRoot(selected, editor);

            if (! root || ! descriptor.matchesRoot(root)) {
                continue;
            }

            active = descriptor;
            activeRoot = root;
            break;
        }

        if (! active || ! activeRoot) {
            mount.hidden = true;
            mount.replaceChildren();
            traitsMount?.classList.remove('hidden');

            return;
        }

        mount.hidden = false;
        traitsMount?.classList.add('hidden');
        mount.replaceChildren();

        active.render({ mount, root: activeRoot, editor, traitsMount });
    };

    editor.on('component:selected', render);
    editor.on('component:deselected', render);
    editor.on('load', render);
}
