import {
    bootCoreFontCatalog,
    getFontCatalog,
    registerFontProvider,
    registerFonts,
} from './fonts/catalog.js';

/**
 * JS plugin bridge for companion packages (Popups, Components, Dynamic Data, Templates…).
 *
 * Contract:
 * - Companion packages own their JS under `resources/js/editor/` in their repo.
 * - They register with `registerEditorPlugin({ id, mount })` — never patch the canvas engine core.
 * - Core boots plugins after editor init, passing entitlements/URLs from EditorGate.
 * - Path-repo installs can also ship `plugin.js` discovered via import.meta.glob below.
 * - Font providers call `registerFonts` / `registerFontProvider` (Fontsource core is built-in).
 *
 * @see docs/EDITOR_JS_PLUGINS.md
 * @see docs/FONTS.md
 */

/**
 * @typedef {{
 *   id: string,
 *   mount: (editor: object, context: Record<string, unknown>) => void | Promise<void>,
 *   unmount?: (editor: object) => void,
 * }} EditorPluginDefinition
 */

/** @type {Map<string, EditorPluginDefinition>} */
const plugins = new Map();

let bridgeExposed = false;

/**
 * The booted editor, for callers that arrive after `bootEditorPlugins`.
 *
 * A companion loaded from a separate script tag has no `mount(editor)` argument to
 * work from, and neither do the audit scripts under `bin/`. Both used to have no way
 * in short of reading private view state off the DOM.
 *
 * @type {object|null}
 */
let bootedEditor = null;

/**
 * The editor instance driving this page, or null before boot.
 *
 * @returns {object|null}
 */
export function getBootedEditor() {
    return bootedEditor;
}

/**
 * Register (or replace) an editor companion plugin.
 *
 * @param {EditorPluginDefinition} definition
 */
export function registerEditorPlugin(definition) {
    const id = String(definition?.id ?? '').trim();

    if (! id) {
        throw new Error('registerEditorPlugin requires a non-empty id.');
    }

    if (typeof definition.mount !== 'function') {
        throw new Error(`registerEditorPlugin("${id}") requires a mount(editor, context) function.`);
    }

    plugins.set(id, {
        id,
        mount: definition.mount,
        unmount: typeof definition.unmount === 'function' ? definition.unmount : undefined,
    });
}

/**
 * @returns {string[]}
 */
export function listEditorPlugins() {
    return [...plugins.keys()];
}

/**
 * Discover official companion entrypoints when packages are path-installed as siblings.
 * Missing packages resolve to an empty glob — build stays green.
 */
function discoverCompanionPlugins() {
    const modules = import.meta.glob(
        [
            '../../../../vpopups/resources/js/editor/plugin.js',
            '../../../../voodbuilder-components/resources/js/editor/plugin.js',
            '../../../../voodbuilder-dynamic-data/resources/js/editor/plugin.js',
            '../../../../voodbuilder-templates/resources/js/editor/plugin.js',
            '../../../../voodbuilder-fonts/resources/js/editor/plugin.js',
            '../../../../voodbuilder-elements/resources/js/editor/plugin.js',
            '../../../../vevents/resources/js/editor/plugin.js',
            '../../../../vexhibitors/resources/js/editor/plugin.js',
        ],
        { eager: true },
    );

    for (const mod of Object.values(modules)) {
        if (! mod || typeof mod !== 'object') {
            continue;
        }

        if (typeof mod.register === 'function') {
            mod.register(registerEditorPlugin);

            continue;
        }

        const definition = mod.default ?? mod.plugin;

        if (definition?.id && typeof definition.mount === 'function') {
            registerEditorPlugin(definition);
        }
    }
}

discoverCompanionPlugins();

/**
 * Expose a stable global for companion packages that ship a separate Vite entry / deferred script.
 * Safe to call multiple times.
 */
export function exposeEditorBridge() {
    if (bridgeExposed) {
        return;
    }

    bridgeExposed = true;

    const api = {
        registerPlugin: registerEditorPlugin,
        listPlugins: listEditorPlugins,
        getEditor: getBootedEditor,
        registerFonts,
        registerFontProvider,
        getFontCatalog,
        bootCoreFontCatalog,
    };

    window.VoodbuilderEditor = {
        ...(window.VoodbuilderEditor ?? {}),
        ...api,
    };
}

/**
 * Mount all registered plugins after GrapesJS init.
 *
 * @param {object} editor
 * @param {Record<string, unknown>} [context]
 */
export async function bootEditorPlugins(editor, context = {}) {
    if (! editor) {
        return;
    }

    bootedEditor = editor;

    exposeEditorBridge();

    for (const plugin of plugins.values()) {
        try {
            await plugin.mount(editor, context);
        } catch (error) {
            console.error(`Voodbuilder editor plugin "${plugin.id}" failed to mount.`, error);
        }
    }

    editor.trigger?.('voodbuilder:plugins:booted', {
        plugins: listEditorPlugins(),
        context,
    });
}
