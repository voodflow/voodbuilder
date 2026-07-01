#!/usr/bin/env node
/**
 * Compile Tailwind CSS utilities for pasted component HTML (JIT via class candidates).
 * Reads HTML from stdin; writes JSON { success, css, candidates } to stdout.
 */
import { compile } from '@tailwindcss/node';
import postcss from 'postcss';

const SCOPE = '.voodbuilder-pasted-component';

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

function collectThemeVariables(root) {
    const variables = new Map();

    root.walkAtRules('layer', (atRule) => {
        if (atRule.params.trim() !== 'theme') {
            return;
        }

        atRule.walkRules((rule) => {
            if (! rule.selectors.some((selector) => /:root|:host/.test(selector))) {
                return;
            }

            rule.walkDecls((decl) => {
                if (decl.prop.startsWith('--') && ! decl.prop.startsWith('--color-vp-')) {
                    variables.set(decl.prop, decl.value);
                }
            });
        });
    });

    return variables;
}

function stripScopedVpThemeOverrides(root) {
    root.walkDecls((decl) => {
        if (decl.prop.startsWith('--color-vp-')) {
            decl.remove();
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

            for (const node of [...atRule.nodes]) {
                parent.insertBefore(atRule, node.clone());
            }

            atRule.remove();
        }
    });

    stripScopedVpThemeOverrides(root);

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
}
`;

    const compiled = await compile(entryCss, {
        base: appRoot,
        onDependency: () => {},
    });

    const rawCss = compiled.build(candidates);
    const scopedCss = scopeCss(rawCss, SCOPE);
    const css = optimizeComponentCss(scopedCss, SCOPE);

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
