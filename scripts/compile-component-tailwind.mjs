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

function extractClassListsPerElement(html) {
    const lists = [];
    const pattern = /\bclass=(["'])(.*?)\1/gis;

    for (const match of html.matchAll(pattern)) {
        const tokens = String(match[2] ?? '')
            .split(/\s+/)
            .map((token) => token.trim())
            .filter((token) => token !== '');

        if (tokens.length > 0) {
            lists.push(tokens);
        }
    }

    return lists;
}

function escapeTailwindClassSelector(className) {
    return `.${String(className).replace(/([^a-zA-Z0-9_-])/g, '\\$1')}`;
}

/**
 * hover:/active:animate-* re-applies the animation shorthand (incl. infinite / normal).
 * Emit compound overrides so bare (or same-variant) modifiers still win on interaction.
 */
function appendInteractionAnimationModifierOverrides(css, html) {
    const ANIMATION_BASES = new Set([
        'animate-spin', 'animate-ping', 'animate-pulse', 'animate-bounce',
        'animate-wiggle', 'animate-wiggle-more', 'animate-rotate-y', 'animate-rotate-x',
        'animate-jump', 'animate-jump-in', 'animate-jump-out', 'animate-shake',
        'animate-fade', 'animate-fade-down', 'animate-fade-up', 'animate-fade-left', 'animate-fade-right',
        'animate-flip-up', 'animate-flip-down',
    ]);

    const MODIFIER_DECLS = {
        'animate-infinite': 'animation-iteration-count: infinite',
        'animate-once': 'animation-iteration-count: 1',
        'animate-twice': 'animation-iteration-count: 2',
        'animate-thrice': 'animation-iteration-count: 3',
        'animate-normal': 'animation-direction: normal',
        'animate-reverse': 'animation-direction: reverse',
        'animate-alternate': 'animation-direction: alternate',
        'animate-alternate-reverse': 'animation-direction: alternate-reverse',
        'animate-duration-75': 'animation-duration: 75ms',
        'animate-duration-100': 'animation-duration: 0.1s',
        'animate-duration-150': 'animation-duration: 150ms',
        'animate-duration-200': 'animation-duration: 0.2s',
        'animate-duration-300': 'animation-duration: 0.3s',
        'animate-duration-500': 'animation-duration: 0.5s',
        'animate-duration-700': 'animation-duration: 0.7s',
        'animate-duration-1000': 'animation-duration: 1s',
        'animate-delay-none': 'animation-delay: 0s',
        'animate-delay-75': 'animation-delay: 75ms',
        'animate-delay-100': 'animation-delay: 0.1s',
        'animate-delay-150': 'animation-delay: 150ms',
        'animate-delay-200': 'animation-delay: 0.2s',
        'animate-delay-300': 'animation-delay: 0.3s',
        'animate-delay-500': 'animation-delay: 0.5s',
        'animate-delay-700': 'animation-delay: 0.7s',
        'animate-delay-1000': 'animation-delay: 1s',
        'animate-ease': 'animation-timing-function: ease',
        'animate-ease-linear': 'animation-timing-function: linear',
        'animate-ease-in': 'animation-timing-function: cubic-bezier(0.4, 0, 1, 1)',
        'animate-ease-out': 'animation-timing-function: cubic-bezier(0, 0, 0.2, 1)',
        'animate-ease-in-out': 'animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1)',
        'animate-fill-none': 'animation-fill-mode: none',
        'animate-fill-forwards': 'animation-fill-mode: forwards',
        'animate-fill-backwards': 'animation-fill-mode: backwards',
        'animate-fill-both': 'animation-fill-mode: both',
    };

    const hoverRules = new Map();
    const activeRules = new Map();

    for (const tokens of extractClassListsPerElement(html)) {
        const set = new Set(tokens);
        let interactionAnim = null;
        let variant = null;

        for (const token of set) {
            if (token.startsWith('hover:') && ANIMATION_BASES.has(token.slice(6))) {
                interactionAnim = token;
                variant = 'hover';
                break;
            }

            if (token.startsWith('active:') && ANIMATION_BASES.has(token.slice(7))) {
                interactionAnim = token;
                variant = 'active';
                break;
            }
        }

        if (! interactionAnim || ! variant) {
            continue;
        }

        const animSelector = escapeTailwindClassSelector(interactionAnim);
        const bucket = variant === 'hover' ? hoverRules : activeRules;
        const pseudo = variant === 'hover' ? ':hover' : ':active';

        for (const token of set) {
            let base = token;

            if (token.startsWith('hover:')) {
                base = token.slice(6);
            } else if (token.startsWith('active:')) {
                base = token.slice(7);
            }

            const decl = MODIFIER_DECLS[base];

            if (! decl) {
                continue;
            }

            const compound = token.startsWith(`${variant}:`)
                ? `${animSelector}${pseudo}${escapeTailwindClassSelector(token)}${pseudo}`
                : `${animSelector}${pseudo}${escapeTailwindClassSelector(token)}`;

            bucket.set(compound, decl);
        }
    }

    const chunks = [];

    if (hoverRules.size > 0) {
        const body = [...hoverRules.entries()]
            .map(([selector, decl]) => `${selector}{${decl};}`)
            .join('\n');
        chunks.push(`@media (hover: hover){\n${body}\n}`);
    }

    if (activeRules.size > 0) {
        const body = [...activeRules.entries()]
            .map(([selector, decl]) => `${selector}{${decl};}`)
            .join('\n');
        chunks.push(body);
    }

    if (chunks.length === 0) {
        return css;
    }

    return `${css}\n/* voodbuilder: keep animation modifiers after hover/active shorthand */\n${chunks.join('\n')}\n`;
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

/**
 * Ensure `animation: var(--animate-*)` keeps a literal fallback from @theme so
 * published page CSS still animates if the custom property is later stripped.
 *
 * @param {import('postcss').Root} root
 * @param {Map<string, string>} themeVariables
 */
function rewriteAnimateVarFallbacks(root, themeVariables) {
    root.walkDecls((decl) => {
        if (decl.prop !== 'animation' && decl.prop !== 'animation-name') {
            return;
        }

        const value = String(decl.value ?? '');

        if (! value.includes('var(--animate-')) {
            return;
        }

        const next = value.replace(
            /var\((--animate-[a-z0-9-]+)\)/gi,
            (match, token) => {
                const fallback = themeVariables.get(token);

                if (! fallback) {
                    return match;
                }

                return `var(${token}, ${fallback})`;
            },
        );

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
    rewriteAnimateVarFallbacks(root, themeVariables);

    const scopedDeclarations = INHERITED_THEME_PROPS.map((prop) => postcss.decl({ prop, value: 'inherit' }));

    if (themeVariables.size > 0) {
        for (const [prop, value] of themeVariables.entries()) {
            scopedDeclarations.push(postcss.decl({ prop, value }));
        }
    }

    flattenNestedMediaQueries(root);
    flattenNestedAmpersandRules(root);
    normalizeMediaRangeSyntax(root);
    removeEmptyRules(root);

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

/**
 * Split a selector list on top-level commas only (ignore commas inside :where/:is/:not/…).
 *
 * @param {string} selectorList
 * @returns {string[]}
 */
function splitSelectorList(selectorList) {
    const parts = [];
    let current = '';
    let depth = 0;

    for (const char of String(selectorList)) {
        if (char === '(') {
            depth += 1;
            current += char;

            continue;
        }

        if (char === ')') {
            depth = Math.max(0, depth - 1);
            current += char;

            continue;
        }

        if (char === ',' && depth === 0) {
            const trimmed = current.trim();

            if (trimmed !== '') {
                parts.push(trimmed);
            }

            current = '';

            continue;
        }

        current += char;
    }

    const trimmed = current.trim();

    if (trimmed !== '') {
        parts.push(trimmed);
    }

    return parts;
}

/**
 * Resolve nested selector lists (`&:hover` → `.foo:hover`).
 * Must not split commas inside functional pseudo-classes (:where, :is, :not).
 */
function resolveNestedSelector(parentSelector, nestedSelector) {
    const parents = splitSelectorList(parentSelector);
    const nesteds = splitSelectorList(nestedSelector);
    const resolved = [];

    for (const parent of parents) {
        for (const nested of nesteds) {
            if (nested.includes('&')) {
                resolved.push(nested.replaceAll('&', parent));
            } else {
                resolved.push(`${parent} ${nested}`);
            }
        }
    }

    return resolved.join(', ');
}

/**
 * Tailwind v4 emits nested CSS like:
 *   .hover\:spin { &:hover { @media (hover:hover) { ... } } }
 * Older flatten copied decls onto the parent selector and dropped `&:hover`,
 * so hover utilities applied permanently on hover-capable devices.
 */
function flattenNestedMediaQueries(root) {
    const jobs = [];

    root.walkAtRules('media', (atRule) => {
        const ruleChain = [];
        let node = atRule.parent;

        while (node && node.type === 'rule') {
            ruleChain.unshift(node);
            node = node.parent;
        }

        if (ruleChain.length === 0) {
            return;
        }

        jobs.push({ atRule, ruleChain });
    });

    for (const { atRule, ruleChain } of jobs) {
        if (! atRule.parent) {
            continue;
        }

        let selector = ruleChain[0].selector;

        for (let index = 1; index < ruleChain.length; index += 1) {
            selector = resolveNestedSelector(selector, ruleChain[index].selector);
        }

        const declarations = [];

        atRule.each((child) => {
            if (child.type === 'decl') {
                declarations.push(child.clone());
            }
        });

        // Declarations may sit only inside deeper nested rules inside the media.
        if (declarations.length === 0) {
            atRule.walkDecls((decl) => {
                declarations.push(decl.clone());
            });
        }

        if (declarations.length === 0) {
            atRule.remove();

            continue;
        }

        const flattenedRule = postcss.rule({
            selector,
            nodes: declarations,
        });
        const outerMedia = postcss.atRule({
            name: 'media',
            params: atRule.params,
            nodes: [flattenedRule],
        });

        ruleChain[0].parent?.insertAfter(ruleChain[0], outerMedia);
        atRule.remove();
    }

    // Drop emptied nesting shells left behind (e.g. `&:hover { }` then `.hover\:x { }`).
    removeEmptyNestingShells(root);
}

/**
 * Flatten Tailwind nested rules (`.focus\:x { &:focus { … } }`) to flat selectors.
 * Also drops orphan `&:…` rules that would be invalid at the document root.
 */
function flattenNestedAmpersandRules(root) {
    let changed = true;
    let guard = 0;

    while (changed && guard < 50) {
        changed = false;
        guard += 1;
        const jobs = [];

        root.walkRules((rule) => {
            if (! String(rule.selector ?? '').includes('&')) {
                return;
            }

            const parent = rule.parent;

            if (! parent || parent.type !== 'rule') {
                // Invalid at root / inside @media without a parent rule — drop.
                jobs.push({ type: 'drop', rule });

                return;
            }

            jobs.push({ type: 'flatten', rule, parent });
        });

        for (const job of jobs) {
            if (job.type === 'drop') {
                job.rule.remove();
                changed = true;

                continue;
            }

            const { rule, parent } = job;

            if (! rule.parent) {
                continue;
            }

            const selector = resolveNestedSelector(parent.selector, rule.selector);
            const declarations = [];
            const nestedAtRules = [];

            for (const child of [...(rule.nodes ?? [])]) {
                if (child.type === 'decl') {
                    declarations.push(child.clone());
                } else if (child.type === 'atrule') {
                    nestedAtRules.push(child.clone());
                } else if (child.type === 'rule') {
                    // Deeper nesting handled on a later pass after hoist.
                    const deeper = child.clone();
                    deeper.selector = resolveNestedSelector(selector, deeper.selector);
                    parent.parent?.insertAfter(parent, deeper);
                    changed = true;
                }
            }

            if (declarations.length > 0) {
                parent.parent?.insertAfter(parent, postcss.rule({
                    selector,
                    nodes: declarations,
                }));
                changed = true;
            }

            for (const atRule of nestedAtRules) {
                const wrapped = postcss.atRule({
                    name: atRule.name,
                    params: atRule.params,
                    nodes: [postcss.rule({
                        selector,
                        nodes: (atRule.nodes ?? []).map((node) => node.clone()),
                    })],
                });
                parent.parent?.insertAfter(parent, wrapped);
                changed = true;
            }

            rule.remove();
            changed = true;
        }

        removeEmptyNestingShells(root);
    }
}

function removeEmptyNestingShells(root) {
    let removed = true;

    while (removed) {
        removed = false;

        root.walkRules((rule) => {
            let hasOwnDeclarations = false;
            let hasAtRules = false;

            rule.each((child) => {
                if (child.type === 'decl') {
                    hasOwnDeclarations = true;
                }

                if (child.type === 'atrule') {
                    hasAtRules = true;
                }
            });

            const nestedRules = (rule.nodes ?? []).filter((child) => child.type === 'rule');
            const hasLiveNestedRules = nestedRules.some((nested) => (nested.nodes ?? []).length > 0);

            if (hasOwnDeclarations || hasAtRules || hasLiveNestedRules) {
                return;
            }

            rule.remove();
            removed = true;
        });
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
    rewriteAnimateVarFallbacks(root, themeVariables);
    flattenNestedMediaQueries(root);
    flattenNestedAmpersandRules(root);
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

    let typographyPlugin = '';

    try {
        const { createRequire } = await import('node:module');
        const { join } = await import('node:path');
        // Resolve against the host app root (compile base), not this script's
        // package folder — otherwise @plugin points at a package that Vite/Tailwind
        // cannot load from /var/www/html when typography is only in the package.
        createRequire(join(appRoot, 'package.json')).resolve('@tailwindcss/typography');
        typographyPlugin = `@plugin '@tailwindcss/typography';\n`;
    } catch {
        // Optional — host apps may omit the plugin.
    }

    const entryCss = `@import 'tailwindcss';
@import 'tailwindcss-animated';
${typographyPlugin}@custom-variant dark (&:is(.dark, .dark *));
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
    let css = SCOPE_MODE === 'page'
        ? optimizePageCss(rawCss)
        : optimizeComponentCss(scopeCss(rawCss, COMPONENT_SCOPE), COMPONENT_SCOPE);

    css = appendInteractionAnimationModifierOverrides(css, html);

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
