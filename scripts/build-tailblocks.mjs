/**
 * Export Tailblocks (MIT) React components to GrapesJS-ready JSON.
 * @see https://github.com/mertJF/tailblocks
 * @see https://tailblocks.cc/
 */
import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import * as esbuild from 'esbuild';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const packageRoot = path.resolve(__dirname, '..');
const cacheDir = path.join(packageRoot, 'storage', 'tailblocks-src');
const outJson = path.join(packageRoot, 'resources', 'grapesjs', 'tailblocks-blocks.json');
const outCatalog = path.join(packageRoot, 'resources', 'grapesjs', 'tailblocks-catalog.html');

const theme = process.env.TAILBLOCKS_THEME ?? 'indigo';

const TAILWIND_V4_REPLACEMENTS = {
    'flex-grow': 'grow',
    'flex-shrink-0': 'shrink-0',
    'flex-shrink': 'shrink',
    'overflow-ellipsis': 'text-ellipsis',
};

function migrateToTailwindV4(html) {
    let migrated = html;

    for (const [from, to] of Object.entries(TAILWIND_V4_REPLACEMENTS)) {
        migrated = migrated.replace(new RegExp(`\\b${from}\\b`, 'g'), to);
    }

    return migrated;
}

function formatBlockLabel(category, variant, mode) {
    const spaced = variant.replace(/([a-z])([A-Z])/g, '$1 $2');

    return `${spaced} · ${mode}`;
}

function buildPreview(html) {
    return `<div class="vpress-gjs-block-preview"><div class="vpress-gjs-block-preview__scale">${html}</div></div>`;
}

if (! fs.existsSync(cacheDir)) {
    fs.mkdirSync(path.dirname(cacheDir), { recursive: true });
    execSync(`git clone --depth 1 https://github.com/mertJF/tailblocks.git "${cacheDir}"`, {
        stdio: 'inherit',
    });
}

const entryFile = path.join(packageRoot, 'storage', 'tailblocks-extract-entry.mjs');

fs.writeFileSync(
    entryFile,
    `
import getBlock from ${JSON.stringify(path.join(cacheDir, 'src', 'blocks', 'index.js'))};

export default getBlock;
`,
);

const bundlePath = path.join(packageRoot, 'storage', 'tailblocks-extract.bundle.mjs');

await esbuild.build({
    entryPoints: [entryFile],
    bundle: true,
    platform: 'node',
    format: 'esm',
    outfile: bundlePath,
    loader: {
        '.js': 'jsx',
    },
    jsx: 'automatic',
    external: ['react', 'react-dom', 'prop-types'],
});

const { default: getBlock } = await import(bundlePath);

/** @type {Array<{id:string,label:string,category:string,content:string,preview:string,mode:string}>} */
const definitions = [];

for (const darkMode of [false, true]) {
    const blocks = getBlock({ theme, darkMode });

    for (const [category, variants] of Object.entries(blocks)) {
        for (const [variant, element] of Object.entries(variants)) {
            if (! React.isValidElement(element)) {
                console.warn(`Skipping ${category}.${variant}: invalid element`);

                continue;
            }

            const rawHtml = renderToStaticMarkup(element).replace(/<link rel="preload"[^>]*>/g, '');
            const html = migrateToTailwindV4(rawHtml);
            const mode = darkMode ? 'dark' : 'light';
            const id = `tailblocks-${category.toLowerCase()}-${variant.toLowerCase()}-${mode}`;

            definitions.push({
                id,
                label: formatBlockLabel(category, variant, mode),
                category: `Tailblocks / ${category}`,
                content: html,
                preview: buildPreview(html),
                mode,
            });
        }
    }
}

fs.mkdirSync(path.dirname(outJson), { recursive: true });
fs.writeFileSync(outJson, JSON.stringify(definitions, null, 2));

const catalog = definitions.map((block) => block.content).join('\n');
fs.writeFileSync(outCatalog, `<!doctype html><html><body>${catalog}</body></html>`);

console.log(`Exported ${definitions.length} Tailblocks (Tailwind v4 migrated) to ${outJson}`);
