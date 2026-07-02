/**
 * Optional GrapesJS Tailwind CSS plugin wrapper (not loaded by default).
 *
 * The editor canvas already uses pre-built `tailblocks-utilities.css` via Vite.
 * Enable this only if you explicitly add `registerGrapesJsTailwindPlugin` to the
 * editor `plugins` array — never patch files under node_modules.
 *
 * @see https://github.com/fasenderos/grapesjs-tailwindcss-plugin
 */
import tailwindPluginModule from 'grapesjs-tailwindcss-plugin';
import { beginEditorBuild, endEditorBuild } from './editor-build-status.js';

const TAILWIND_BUILD_SCOPE = 'tailwind';

function resolveGrapesJsTailwindPlugin(module) {
    if (typeof module === 'function') {
        return module;
    }

    if (typeof module?.default === 'function') {
        return module.default;
    }

    const named = module?.['grapesjs-tailwindcss-plugin'];

    if (typeof named === 'function') {
        return named;
    }

    throw new TypeError('grapesjs-tailwindcss-plugin: plugin export is not a function');
}

const grapesjsTailwind = resolveGrapesJsTailwindPlugin(tailwindPluginModule);

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

export default function registerGrapesJsTailwindPlugin(editor, options = {}) {
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

    grapesjsTailwind(editor, {
        autobuild: options.autobuild ?? true,
        autocomplete: options.autocomplete ?? false,
        buildButton: options.buildButton ?? false,
        customCss: options.customCss ?? VOODBUILDER_TAILWIND_CUSTOM_CSS,
        notificationCallback: () => {
            disarmBuild();
            options.notificationCallback?.();
        },
    });

    editor.on('run:build-tailwind', armBuild);
    editor.on('stop:build-tailwind', disarmBuild);
    editor.on('destroy', disarmBuild);
}

export { grapesjsTailwind };
