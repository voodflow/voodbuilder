/**
 * Editor Tailwind CSS plugin wrapper, gated by `voodbuilder.editor.plugins.tailwind`.
 *
 * The canvas paints utilities from pre-built `section-utilities.css` plus the server-side
 * compile-css endpoint, so this plugin's compiler is a fallback rather than the main path:
 * `autobuild` stays off because a MutationObserver rebuild on every canvas mutation can
 * spin forever at boot. The heavy dependency is therefore loaded off the critical path.
 *
 * @see https://github.com/fasenderos/grapesjs-tailwindcss-plugin
 */
import { beginEditorBuild, endEditorBuild } from './editor-build-status.js';

const TAILWIND_BUILD_SCOPE = 'tailwind';

/** Marks our stand-in command so we can tell it apart from the upstream one. */
const PLACEHOLDER_MARKER = '__voodbuilderTailwindPlaceholder';

/**
 * Upstream ships a UMD bundle whose `module.exports` is the plugin function itself.
 * Static and dynamic imports wrap that differently (and interop can nest `default`
 * more than once), so unwrap defensively instead of assuming one shape.
 */
function resolveEditorTailwindPlugin(module) {
    const seen = new Set();
    let candidate = module;

    for (let depth = 0; depth < 5; depth += 1) {
        if (typeof candidate === 'function') {
            return candidate;
        }

        if (candidate == null || typeof candidate !== 'object' || seen.has(candidate)) {
            break;
        }

        seen.add(candidate);

        const named = candidate['grapesjs-tailwindcss-plugin'];

        if (typeof named === 'function') {
            return named;
        }

        candidate = candidate.default;
    }

    const shape = module && typeof module === 'object'
        ? Object.keys(module).join(', ') || '(no keys)'
        : typeof module;

    throw new TypeError(`grapesjs-tailwindcss-plugin: plugin export is not a function (got: ${shape})`);
}

/**
 * The upstream plugin bundles its own Tailwind compiler: 466 KiB raw, ~11% of the editor
 * boot chunk. A static import put that on the critical path for every author even though
 * `autobuild` and `buildButton` are both off by default, so at boot the plugin only
 * registers a `build-tailwind` command. Loading it on a separate chunk keeps the command
 * available without paying for the compiler before the canvas is interactive.
 */
function loadGrapesjsTailwind() {
    return import('grapesjs-tailwindcss-plugin').then(resolveEditorTailwindPlugin);
}

const VOODBUILDER_TAILWIND_CUSTOM_CSS = `
@theme {
    --color-vp-bg: #ffffff;
    --color-vp-bg-elv: #ffffff;
    --color-vp-bg-alt: #f6f6f7;
    --color-vp-gray-soft: #e2e8f0;
    --color-vp-text-1: #3c3c43;
    --color-vp-text-2: #67676c;
    --color-vp-text-3: #94949e;
    --color-vp-divider: #e2e8f0;
    --color-vp-brand-1: #6366f1;
    --color-vp-brand-2: #4f46e5;
    --color-vp-brand-3: #4338ca;
}

@layer components {
    .body-font {
        font-family: ui-sans-serif, system-ui, sans-serif;
        color: var(--color-vp-text-2);
        -webkit-font-smoothing: antialiased;
    }

    .title-font {
        font-family: ui-sans-serif, system-ui, sans-serif;
        letter-spacing: -0.025em;
    }
}
`;

export default function registerEditorTailwindPlugin(editor, options = {}) {
    let tailwindBuilding = false;

    const armBuild = () => {
        if (! tailwindBuilding) {
            tailwindBuilding = true;
            beginEditorBuild(editor, TAILWIND_BUILD_SCOPE);
        }
    };

    const disarmBuild = () => {
        if (tailwindBuilding) {
            tailwindBuilding = false;
            endEditorBuild(editor, TAILWIND_BUILD_SCOPE);
        }
    };

    // Listeners are wired synchronously so a build triggered before the chunk lands still
    // drives the status indicator.
    editor.on('run:build-tailwind', armBuild);
    editor.on('stop:build-tailwind', disarmBuild);
    editor.on('destroy', disarmBuild);

    let destroyed = false;

    editor.on('destroy', () => {
        destroyed = true;
    });

    const autobuild = options.autobuild ?? true;

    const apply = () => {
        if (editor.__voodbuilderTailwindPluginReady) {
            return editor.__voodbuilderTailwindPluginReady;
        }

        // `loadPlugin` is a seam for tests and for hosts that pre-bundle the compiler.
        editor.__voodbuilderTailwindPluginReady = (options.loadPlugin ?? loadGrapesjsTailwind)()
            .then((grapesjsTailwind) => {
                if (destroyed) {
                    return;
                }

                grapesjsTailwind(editor, {
                    autobuild,
                    autocomplete: options.autocomplete ?? false,
                    buildButton: options.buildButton ?? false,
                    customCss: options.customCss ?? VOODBUILDER_TAILWIND_CUSTOM_CSS,
                    notificationCallback: () => {
                        disarmBuild();
                        options.notificationCallback?.();
                    },
                });
            })
            .catch((error) => {
                disarmBuild();
                console.error('Voodbuilder Editor: Tailwind plugin failed to load.', error);
            });

        return editor.__voodbuilderTailwindPluginReady;
    };

    editor.__voodbuilderLoadTailwindPlugin = apply;

    // autobuild has to observe the canvas from the start, so it cannot be deferred.
    // Without it the plugin's only boot effect is registering `build-tailwind`, so the
    // compiler is fetched when a build is first requested instead of on every page load.
    if (autobuild || options.buildButton) {
        void apply();

        return;
    }

    editor.Commands.add('build-tailwind', {
        [PLACEHOLDER_MARKER]: true,
        run(_editorInstance, _sender, commandOptions) {
            armBuild();

            void apply().then(() => {
                if (destroyed) {
                    return;
                }

                // Applying the upstream plugin replaces this command. If it is still
                // ours, the compiler never registered one — delegating would re-enter
                // this same handler and recurse until the tab dies.
                if (editor.Commands.get('build-tailwind')?.[PLACEHOLDER_MARKER]) {
                    disarmBuild();
                    console.warn('Voodbuilder Editor: Tailwind build is unavailable.');

                    return;
                }

                editor.runCommand('build-tailwind', commandOptions);
            });
        },
    });
}

export { loadGrapesjsTailwind };
