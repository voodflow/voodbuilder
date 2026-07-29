/**
 * Export upstream section React components to GrapesJS-ready JSON for voodbuilder:build-sections.
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
const cacheDir = path.join(packageRoot, 'storage', 'section-source-src');
const outJson = path.join(packageRoot, 'resources', 'editor', 'section-source-blocks.json');
const outCatalog = path.join(packageRoot, 'resources', 'editor', 'section-catalog.html');

const theme = process.env.VOODBUILDER_SECTION_THEME ?? 'indigo';

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

function fixGrapesJsSrcUri(value) {
    return value.replace(/%(?![0-9A-Fa-f]{2})/g, '%25');
}

const NEUTRAL_PLACEHOLDER_SRC = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">'
    + '<rect width="800" height="500" fill="#e2e8f0"/>'
    + '<text x="400" y="250" text-anchor="middle" dominant-baseline="middle" fill="#94a3b8" font-family="system-ui,sans-serif" font-size="18">Image placeholder</text>'
    + '</svg>',
);

function normalizePlaceholderUrls(html) {
    if (typeof html !== 'string' || html === '' || ! html.includes('://')) {
        return html;
    }

    const hostPattern = 'https?:\\/\\/(?:dummyimage|placehold|placekitten|placeimg|picsum|unsplash)\\.[^"\'\\\\]+';

    return html
        .replace(new RegExp(`\\bsrc=(["'])(${hostPattern})\\1`, 'gi'), `src=$1${NEUTRAL_PLACEHOLDER_SRC}$1`)
        .replace(new RegExp(`\\bbackground-image\\s*:\\s*url\\((["']?)(${hostPattern})\\1\\)\\s*;?`, 'gi'), `background-image: url(${NEUTRAL_PLACEHOLDER_SRC});`);
}

function sanitizeGrapesJsHtml(html) {
    const normalized = normalizePlaceholderUrls(html);

    return normalized.replace(/\bsrc=(["'])(.*?)\1/gi, (match, quote, src) => `src=${quote}${fixGrapesJsSrcUri(src)}${quote}`);
}

function formatBlockLabel(category, variant) {
    return variant.replace(/([a-z])([A-Z])/g, '$1 $2');
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

const entryFile = path.join(packageRoot, 'storage', 'section-source-extract-entry.mjs');

fs.writeFileSync(
    entryFile,
    `
import getBlock from ${JSON.stringify(path.join(cacheDir, 'src', 'blocks', 'index.js'))};

export default getBlock;
`,
);

const bundlePath = path.join(packageRoot, 'storage', 'section-source-extract.bundle.mjs');

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

for (const darkMode of [false]) {
    const blocks = getBlock({ theme, darkMode });

    for (const [category, variants] of Object.entries(blocks)) {
        for (const [variant, element] of Object.entries(variants)) {
            if (! React.isValidElement(element)) {
                console.warn(`Skipping ${category}.${variant}: invalid element`);

                continue;
            }

            const rawHtml = renderToStaticMarkup(element).replace(/<link rel="preload"[^>]*>/g, '');
            const html = sanitizeGrapesJsHtml(migrateToTailwindV4(rawHtml));
            const id = `section-source-${category.toLowerCase()}-${variant.toLowerCase()}`;

            definitions.push({
                id,
                label: formatBlockLabel(category, variant),
                category: `Sections / ${category}`,
                content: html,
                preview: buildPreview(html),
                mode: 'adaptive',
            });
        }
    }
}

fs.mkdirSync(path.dirname(outJson), { recursive: true });
fs.writeFileSync(outJson, JSON.stringify(definitions, null, 2));

const catalog = definitions.map((block) => block.content).join('\n');
fs.writeFileSync(outCatalog, `<!doctype html><html><body>${catalog}</body></html>`);

console.log(`Exported ${definitions.length} section source blocks to ${outJson}`);
