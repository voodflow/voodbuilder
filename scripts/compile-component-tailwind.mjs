#!/usr/bin/env node
/**
 * Compile Tailwind CSS utilities for pasted component HTML (JIT via class candidates).
 * Reads HTML from stdin; writes JSON { success, css, candidates } to stdout.
 */
import { compile } from '@tailwindcss/node';
import postcss from 'postcss';

const COMPONENT_SCOPE = '.voodbuilder-pasted-component';
const SCOPE_MODE = process.env.VOODBUILDER_TAILWIND_SCOPE === 'page' ? 'page' : 'component';

const INHERITED_THEME_PROPS = [
    '--color-vp-brand-1',
    '--color-vp-brand-2',
    '--color-vp-brand-3',
    '--color-vp-text-1',
    '--color-vp-text-2',
    '--color-vp-text-3',
    '--color-vp-bg',
    '--color-vp-bg-alt',
    '--color-vp-bg-elv',
    '--color-vp-divider',
    '--color-vp-gray-soft',
];

const LEGACY_PALETTE_NAMES = [
    'indigo',
    'yellow',
    'red',
    'purple',
    'violet',
    'pink',
    'blue',
    'green',
];

function isLegacyPaletteColorVariable(prop) {
    if (! prop.startsWith('--color-')) {
        return false;
    }

    return LEGACY_PALETTE_NAMES.some((name) => prop.startsWith(`--color-${name}-`));
}

function extractClassNames(html) {
    const classes = new Set();
    const pattern = /\bclass=(["'])(.*?)\1/gis;

    for (const match of html.matchAll(pattern)) {
        for (const token of String(match[2] ?? '').split(/\s+/)) {
            const trimmed = token.trim();

            if (trimmed !== '') {
                classes.add(trimmed);
            }
        }
    }

    return [...classes];
}

function htmlUsesClassDarkVariant(html) {
    return /\bdark:[a-z0-9_\-!/\[\]#%.]+/i.test(html);
}

const PAGE_THEME_FALLBACKS = {
    '--spacing': '0.25rem',
    '--radius-lg': '0.5rem',
    '--radius-md': '0.375rem',
    '--radius-sm': '0.25rem',
    '--color-white': '#fff',
    '--color-black': '#000',
    '--font-sans': 'ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
    '--default-font-family': 'ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
    '--text-xs': '0.75rem',
    '--text-xs--line-height': 'calc(1 / 0.75)',
    '--text-sm': '0.875rem',
    '--text-sm--line-height': 'calc(1.25 / 0.875)',
    '--text-base': '1rem',
    '--text-base--line-height': 'calc(1.5 / 1)',
    '--text-lg': '1.125rem',
    '--text-lg--line-height': 'calc(1.75 / 1.125)',
    '--text-xl': '1.25rem',
    '--text-xl--line-height': 'calc(1.75 / 1.25)',
    '--text-2xl': '1.5rem',
    '--text-2xl--line-height': 'calc(2 / 1.5)',
    '--text-3xl': '1.875rem',
    '--text-3xl--line-height': 'calc(2.25 / 1.875)',
    '--text-4xl': '2.25rem',
    '--text-4xl--line-height': 'calc(2.5 / 2.25)',
    '--text-5xl': '3rem',
    '--text-5xl--line-height': '1',
    '--text-6xl': '3.75rem',
    '--text-6xl--line-height': '1',
    '--width-vp-layout': '80rem',
    '--container-3xs': '16rem',
    '--container-2xs': '18rem',
    '--container-xs': '20rem',
    '--container-sm': '24rem',
    '--container-md': '28rem',
    '--container-lg': '32rem',
    '--container-xl': '36rem',
    '--container-2xl': '42rem',
    '--container-3xl': '48rem',
    '--container-4xl': '56rem',
    '--container-5xl': '64rem',
    '--container-6xl': '72rem',
    '--container-7xl': '80rem',
};

function rememberThemeVariable(variables, prop, value) {
    if (! prop.startsWith('--')) {
        return;
    }

    // Site brand tokens (--color-vp-*) come from the canvas/admin theme — do not
    // bake them into the JIT bundle (would override the live palette).
    // Default Tailwind palette tokens (--color-red-800, --color-blue-500, …) MUST
    // be kept: stripScopedVpThemeOverrides removes @theme, and utilities like
    // bg-red-800 resolve to var(--color-red-800) — without the token, nothing paints.
    if (prop.startsWith('--color-vp-')) {
        return;
    }

    variables.set(prop, value);
}

function collectThemeVariables(root) {
    const variables = new Map();

    root.walkAtRules('theme', (atRule) => {
        atRule.walkDecls((decl) => {
            rememberThemeVariable(variables, decl.prop, decl.value);
        });
    });

    root.walkAtRules('layer', (atRule) => {
        if (atRule.params.trim() !== 'theme') {
            return;
        }

        atRule.walkRules((rule) => {
            if (! rule.selectors.some((selector) => /:root|:host/.test(selector))) {
                return;
            }

            rule.walkDecls((decl) => {
                rememberThemeVariable(variables, decl.prop, decl.value);
            });
        });
    });

    root.walkRules((rule) => {
        if (! rule.selectors.some((selector) => /:root|:host/.test(selector))) {
            return;
        }

        rule.walkDecls((decl) => {
            rememberThemeVariable(variables, decl.prop, decl.value);
        });
    });

    return variables;
}

function prependPageThemeVariables(root, themeVariables) {
    for (const [prop, value] of Object.entries(PAGE_THEME_FALLBACKS)) {
        if (! themeVariables.has(prop)) {
            themeVariables.set(prop, value);
        }
    }

    if (themeVariables.size === 0) {
        return;
    }

    const declarations = [];

    for (const [prop, value] of themeVariables.entries()) {
        declarations.push(postcss.decl({ prop, value }));
    }

    root.prepend(postcss.rule({ selector: ':root', nodes: declarations }));
}

function rewriteThemeVarFallbacks(root) {
    const textFallbacks = {
        '--text-xs': '0.75rem',
        '--text-sm': '0.875rem',
        '--text-base': '1rem',
        '--text-lg': '1.125rem',
        '--text-xl': '1.25rem',
        '--text-2xl': '1.5rem',
        '--text-3xl': '1.875rem',
        '--text-4xl': '2.25rem',
        '--text-5xl': '3rem',
        '--text-6xl': '3.75rem',
        '--text-xs--line-height': 'calc(1 / 0.75)',
        '--text-sm--line-height': 'calc(1.25 / 0.875)',
        '--text-base--line-height': 'calc(1.5 / 1)',
        '--text-lg--line-height': 'calc(1.75 / 1.125)',
        '--text-xl--line-height': 'calc(1.75 / 1.25)',
        '--text-2xl--line-height': 'calc(2 / 1.5)',
        '--text-3xl--line-height': 'calc(2.25 / 1.875)',
        '--text-4xl--line-height': 'calc(2.5 / 2.25)',
        '--text-5xl--line-height': '1',
        '--text-6xl--line-height': '1',
    };

    root.walkDecls((decl) => {
        const value = String(decl.value ?? '');

        if (! value.includes('var(--')) {
            return;
        }

        let next = value.replace(/var\(--spacing\)/g, 'var(--spacing, 0.25rem)');

        next = next.replace(/var\(--radius-lg\)/g, 'var(--radius-lg, 0.5rem)');
        next = next.replace(/var\(--radius-md\)/g, 'var(--radius-md, 0.375rem)');
        next = next.replace(/var\(--radius-sm\)/g, 'var(--radius-sm, 0.25rem)');
        next = next.replace(/var\(--width-vp-layout\)/g, 'var(--width-vp-layout, 80rem)');
        next = next.replace(/var\(--color-white\)/g, 'var(--color-white, #fff)');
        next = next.replace(/var\(--color-black\)/g, 'var(--color-black, #000)');
        next = next.replace(/var\(--font-sans\)/g, 'var(--font-sans, ui-sans-serif, system-ui, sans-serif)');
        next = next.replace(
            /var\(--default-font-family\)/g,
            'var(--default-font-family, ui-sans-serif, system-ui, sans-serif)',
        );

        const containerFallbacks = {
            '--container-3xs': '16rem',
            '--container-2xs': '18rem',
            '--container-xs': '20rem',
            '--container-sm': '24rem',
            '--container-md': '28rem',
            '--container-lg': '32rem',
            '--container-xl': '36rem',
            '--container-2xl': '42rem',
            '--container-3xl': '48rem',
            '--container-4xl': '56rem',
            '--container-5xl': '64rem',
            '--container-6xl': '72rem',
            '--container-7xl': '80rem',
        };

        for (const [token, fallback] of Object.entries(containerFallbacks)) {
            const needle = `var(${token})`;

            if (next.includes(needle)) {
                next = next.split(needle).join(`var(${token}, ${fallback})`);
            }
        }

        for (const [token, fallback] of Object.entries(textFallbacks)) {
            const needle = `var(${token})`;

            if (next.includes(needle)) {
                next = next.split(needle).join(`var(${token}, ${fallback})`);
            }
        }

        if (next !== value) {
            decl.value = next;
        }
    });
}

function stripScopedVpThemeOverrides(root) {
    root.walkDecls((decl) => {
        if (decl.prop.startsWith('--color-vp-') || isLegacyPaletteColorVariable(decl.prop)) {
            decl.remove();
        }
    });
}

function rewriteLegacyPaletteUtilityColors(root) {
    root.walkDecls((decl) => {
        if (! ['background-color', 'outline-color', 'border-color', 'color'].includes(decl.prop)) {
            return;
        }

        const value = String(decl.value ?? '');

        if (/var\(--color-(?:indigo|purple|violet)-\d+\)/i.test(value)) {
            decl.value = decl.prop === 'background-color'
                ? 'var(--color-vp-brand-3, var(--color-vp-brand-1, #0d9488))'
                : 'var(--color-vp-brand-2, var(--color-vp-brand-1, #0d9488))';
        }
    });
}

function optimizeComponentCss(css, scope) {
    const root = postcss.parse(css);
    const themeVariables = collectThemeVariables(root);

    root.walkAtRules('layer', (atRule) => {
        const layer = atRule.params.trim();

        if (layer === 'base' || layer === 'theme') {
            atRule.remove();

            return;
        }

        if (layer === 'utilities') {
            const parent = atRule.parent;

            if (! parent) {
                return;
            }

            const nodes = atRule.nodes ?? [];

            if (nodes.length === 0) {
                atRule.remove();

                return;
            }

            for (const node of [...nodes]) {
                parent.insertBefore(atRule, node.clone());
            }

            atRule.remove();
        }
    });

    stripScopedVpThemeOverrides(root);
    rewriteLegacyPaletteUtilityColors(root);

    const scopedDeclarations = INHERITED_THEME_PROPS.map((prop) => postcss.decl({ prop, value: 'inherit' }));

    if (themeVariables.size > 0) {
        for (const [prop, value] of themeVariables.entries()) {
            scopedDeclarations.push(postcss.decl({ prop, value }));
        }
    }

    root.prepend(postcss.rule({ selector: scope, nodes: scopedDeclarations }));

    return root.toString();
}

function scopeCss(css, scope) {
    const root = postcss.parse(css);

    root.walkRules((rule) => {
        if (rule.parent?.type === 'atrule' && ['keyframes', 'font-face'].includes(rule.parent.name)) {
            return;
        }

        rule.selectors = rule.selectors.map((selector) => {
            if (selector.includes(scope)) {
                return selector;
            }

            if (selector === ':root' || selector === ':host' || selector === 'html' || selector === 'body' || selector === '*') {
                return selector;
            }

            return `${scope} ${selector}`;
        });
    });

    return root.toString();
}

function stripPagePreflight(root) {
    root.walkRules((rule) => {
        const selectors = rule.selectors ?? [];

        if (selectors.some((selector) => ['*', ':root', ':host', 'html', 'body'].includes(selector.trim()))) {
            rule.remove();

            return;
        }

        const isFormControlReset = selectors.some((selector) => (
            /^(?:button|input|select|optgroup|textarea)(?:,|$)/.test(selector.trim())
            || selector.includes('::file-selector-button')
            || selector.includes('::-webkit-outer-spin-button')
        ));

        if (isFormControlReset) {
            rule.remove();
        }
    });

    root.walkAtRules('layer', (atRule) => {
        const params = atRule.params.trim();

        if (params === 'properties' || params === 'theme, base, components, utilities') {
            atRule.remove();
        }
    });
}

const STRIP_PAGE_DISPLAY_UTILITIES = new Set([
    'hidden',
    'block',
    'flex',
    'inline-flex',
    'grid',
    'inline',
    'contents',
    'table',
    'flow-root',
]);

function stripChromeConflictingDisplayUtilities(root) {
    root.walkRules((rule) => {
        if (rule.parent?.type === 'atrule') {
            return;
        }

        if (rule.selectors.length !== 1) {
            return;
        }

        const selector = rule.selectors[0].trim();
        const match = selector.match(/^\.((?:\\.|[^\s:#\[,>+~])+)$/);

        if (! match) {
            return;
        }

        const className = match[1].replace(/\\/g, '');

        if (STRIP_PAGE_DISPLAY_UTILITIES.has(className)) {
            rule.remove();
        }
    });
}

function normalizeMediaRangeSyntax(root) {
    root.walkAtRules('media', (atRule) => {
        atRule.params = atRule.params
            .replace(/\(width\s*>=\s*([^)]+)\)/g, '(min-width: $1)')
            .replace(/\(width\s*<=\s*([^)]+)\)/g, '(max-width: $1)')
            .replace(/\(width\s*<\s*([^)]+)\)/g, '(max-width: calc($1 - 0.02px))');
    });
}

function stripPropertyAtRules(root) {
    root.walkAtRules('property', (atRule) => {
        atRule.remove();
    });
}

/**
 * Page JIT CSS can emit .container @media blocks in descending breakpoint order, so the
 * smallest max-width always wins. Global theme/section-utilities already define .container.
 */
function stripContainerUtilityFromPageCss(root) {
    const isContainerSelector = (selector) => /^\.container(?:\\!)?$/.test(String(selector ?? '').trim());

    root.walkAtRules('media', (atRule) => {
        atRule.walkRules((rule) => {
            if (isContainerSelector(rule.selector)) {
                rule.remove();
            }
        });

        let hasRules = false;

        atRule.walkRules(() => {
            hasRules = true;
        });

        if (! hasRules) {
            atRule.remove();
        }
    });

    root.walkRules((rule) => {
        if (rule.parent?.type === 'atrule' && rule.parent.name === 'media') {
            return;
        }

        if (isContainerSelector(rule.selector)) {
            rule.remove();
        }
    });
}

function removeEmptyRules(root) {
    root.walkRules((rule) => {
        let hasDeclarations = false;

        rule.walkDecls(() => {
            hasDeclarations = true;
        });

        if (! hasDeclarations) {
            rule.remove();
        }
    });
}

function flattenNestedMediaQueries(root) {
    const rulesToProcess = [];

    root.walkRules((rule) => {
        if (rule.parent?.type === 'atrule' && rule.parent.name === 'media') {
            return;
        }

        const nestedMedia = [];

        rule.walkAtRules('media', (atRule) => {
            nestedMedia.push(atRule);
        });

        if (nestedMedia.length > 0) {
            rulesToProcess.push({ rule, nestedMedia });
        }
    });

    for (const { rule, nestedMedia } of rulesToProcess) {
        for (const atRule of nestedMedia) {
            const declarations = [];

            atRule.walkDecls((decl) => {
                declarations.push(decl.clone());
            });

            if (declarations.length === 0) {
                atRule.remove();

                continue;
            }

            const flattenedRule = postcss.rule({
                selector: rule.selector,
                nodes: declarations,
            });
            const outerMedia = postcss.atRule({
                name: 'media',
                params: atRule.params,
                nodes: [flattenedRule],
            });

            rule.parent?.insertAfter(rule, outerMedia);
            atRule.remove();
        }

        let hasDeclarations = false;

        rule.walkDecls(() => {
            hasDeclarations = true;
        });

        if (! hasDeclarations) {
            rule.remove();
        }
    }
}

function optimizePageCss(css) {
    const root = postcss.parse(css);
    const themeVariables = collectThemeVariables(root);

    root.walkAtRules('layer', (atRule) => {
        const layer = atRule.params.trim();

        if (layer === 'base' || layer === 'theme' || layer.startsWith('theme,') || layer.includes(', base')) {
            atRule.remove();

            return;
        }

        if (layer === 'utilities') {
            const parent = atRule.parent;

            if (! parent) {
                return;
            }

            const nodes = atRule.nodes ?? [];

            if (nodes.length === 0) {
                atRule.remove();

                return;
            }

            for (const node of [...nodes]) {
                parent.insertBefore(atRule, node.clone());
            }

            atRule.remove();
        }
    });

    stripPagePreflight(root);
    stripScopedVpThemeOverrides(root);
    rewriteLegacyPaletteUtilityColors(root);
    rewriteThemeVarFallbacks(root);
    flattenNestedMediaQueries(root);
    normalizeMediaRangeSyntax(root);
    // Keep .flex / .inline-flex / .grid — page blocks need them. Stripping caused
    // "styles lost on save" when live CSS was replaced with the published bundle.
    stripPropertyAtRules(root);
    stripContainerUtilityFromPageCss(root);
    removeEmptyRules(root);
    prependPageThemeVariables(root, themeVariables);

    return root.toString();
}

async function readStdin() {
    const chunks = [];

    for await (const chunk of process.stdin) {
        chunks.push(chunk);
    }

    return Buffer.concat(chunks).toString('utf8');
}

async function main() {
    const appRoot = process.env.VOODBUILDER_APP_ROOT || process.cwd();
    const html = (await readStdin()).trim();

    if (html === '') {
        process.stdout.write(JSON.stringify({ success: true, css: '', candidates: 0 }));

        return;
    }

    const candidates = extractClassNames(html);

    if (candidates.length === 0) {
        process.stdout.write(JSON.stringify({ success: true, css: '', candidates: 0 }));

        return;
    }

    const entryCss = `@import 'tailwindcss';
@plugin '@tailwindcss/typography';
@custom-variant dark (&:where(.dark, .dark *));
@theme {
    --breakpoint-vp: 60rem;

    --color-vp-brand-1: #3451b2;
    --color-vp-brand-2: #3a5ccc;
    --color-vp-brand-3: #5672cd;
    --color-vp-text-1: #3c3c43;
    --color-vp-text-2: #67676c;
    --color-vp-text-3: #929295;
    --color-vp-bg: #ffffff;
    --color-vp-bg-alt: #f6f6f7;
    --color-vp-bg-elv: #ffffff;
    --color-vp-divider: #e2e2e3;
    --color-vp-gray-soft: rgba(142, 150, 170, 0.14);
    --color-primary: var(--color-vp-brand-2);
    --color-primary-hover: var(--color-vp-brand-1);
    --color-primary-focus: var(--color-vp-brand-1);
    --color-primary-line: var(--color-vp-brand-2);
    --color-primary-foreground: #ffffff;
    --color-foreground: var(--color-vp-text-1);
    --color-layer: var(--color-vp-bg-elv);
    --color-layer-hover: var(--color-vp-bg-alt);
    --color-layer-focus: var(--color-vp-bg-alt);
    --color-layer-line: var(--color-vp-divider);
    --color-layer-foreground: var(--color-vp-text-1);
    --color-surface-1: var(--color-vp-bg-alt);
    --color-surface: var(--color-vp-bg-alt);
    --color-plain: var(--color-vp-bg-elv);
    --color-inverse: #ffffff;
    --color-foreground-inverse: #ffffff;
    --color-muted-hover: var(--color-vp-bg-alt);
    --color-muted-focus: var(--color-vp-bg-alt);
    --color-travia-transparent: transparent;
    --spacing-120: 30rem;
    --spacing-vp-nav: 4rem;
    --spacing-vp-sidebar: 17rem;
    --spacing-vp-aside: 14rem;
    --width-vp-content: 43rem;
    --width-vp-layout: 90rem;
}
`;

    const compiled = await compile(entryCss, {
        base: appRoot,
        onDependency: () => {},
    });

    const rawCss = compiled.build(candidates);
    const css = SCOPE_MODE === 'page'
        ? optimizePageCss(rawCss)
        : optimizeComponentCss(scopeCss(rawCss, COMPONENT_SCOPE), COMPONENT_SCOPE);

    process.stdout.write(JSON.stringify({
        success: true,
        css,
        candidates: candidates.length,
    }));
}

main().catch((error) => {
    process.stdout.write(JSON.stringify({
        success: false,
        error: error instanceof Error ? error.message : String(error),
    }));
    process.exit(1);
});
