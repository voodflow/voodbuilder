/**
 * Build a compact Tabler Icons catalog for the VoodBuilder icon picker.
 * Source: @tabler/icons (MIT) — categories match https://tabler.io/icons
 *
 * Output is lazy-imported by the editor; it must not be in the main Editor chunk.
 */

import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const packageRoot = path.resolve(__dirname, '..');
const appRoot = path.resolve(packageRoot, '../../..');

function resolveTablerRoot() {
    const candidates = [
        path.join(appRoot, 'node_modules/@tabler/icons'),
        path.join(packageRoot, 'node_modules/@tabler/icons'),
    ];

    try {
        candidates.unshift(path.dirname(require.resolve('@tabler/icons/package.json')));
    } catch {
        // fall through
    }

    for (const candidate of candidates) {
        if (fs.existsSync(path.join(candidate, 'icons.json'))) {
            return candidate;
        }
    }

    throw new Error(
        'Missing @tabler/icons. Install it in the app root: npm install -D @tabler/icons',
    );
}

function nodesToInner(nodes) {
    if (! Array.isArray(nodes)) {
        return '';
    }

    return nodes.map(([tag, attrs = {}]) => {
        const pairs = Object.entries(attrs)
            .map(([key, value]) => `${key}="${String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;')}"`)
            .join(' ');

        return `<${tag}${pairs ? ` ${pairs}` : ''}/>`;
    }).join('');
}

function buildCatalog(tablerRoot) {
    const icons = JSON.parse(fs.readFileSync(path.join(tablerRoot, 'icons.json'), 'utf8'));
    const outline = JSON.parse(fs.readFileSync(path.join(tablerRoot, 'tabler-nodes-outline.json'), 'utf8'));
    const filled = JSON.parse(fs.readFileSync(path.join(tablerRoot, 'tabler-nodes-filled.json'), 'utf8'));
    const pkg = JSON.parse(fs.readFileSync(path.join(tablerRoot, 'package.json'), 'utf8'));

    const categories = [...new Set(
        Object.values(icons).map((icon) => String(icon.category || 'System')),
    )].sort((a, b) => a.localeCompare(b));

    /** @type {Record<string, string[]>} */
    const byCategory = {};
    /** @type {Record<string, string>} */
    const outlineMap = {};
    /** @type {Record<string, string>} */
    const filledMap = {};
    /** @type {Record<string, string>} */
    const tags = {};

    for (const [name, meta] of Object.entries(icons)) {
        const category = String(meta.category || 'System');
        (byCategory[category] ??= []).push(name);

        if (Array.isArray(meta.tags) && meta.tags.length > 0) {
            tags[name] = meta.tags.map(String).join(' ');
        }

        if (outline[name]) {
            outlineMap[name] = nodesToInner(outline[name]);
        }

        if (filled[name]) {
            filledMap[name] = nodesToInner(filled[name]);
        }
    }

    for (const list of Object.values(byCategory)) {
        list.sort((a, b) => a.localeCompare(b));
    }

    return {
        version: String(pkg.version || ''),
        categories,
        byCategory,
        tags,
        outline: outlineMap,
        filled: filledMap,
    };
}

export function buildTablerIconsCatalog({ silent = false } = {}) {
    const tablerRoot = resolveTablerRoot();
    const catalog = buildCatalog(tablerRoot);
    const outDir = path.join(packageRoot, 'resources/js/editor/generated');
    const outFile = path.join(outDir, 'tabler-icons-full.json');

    fs.mkdirSync(outDir, { recursive: true });
    fs.writeFileSync(outFile, JSON.stringify(catalog));

    if (! silent) {
        const bytes = fs.statSync(outFile).size;
        console.log(
            `[voodbuilder] Tabler catalog v${catalog.version}: `
            + `${Object.keys(catalog.outline).length} outline, `
            + `${Object.keys(catalog.filled).length} filled, `
            + `${catalog.categories.length} categories → ${path.relative(appRoot, outFile)} `
            + `(${(bytes / 1024).toFixed(0)} KB)`,
        );
    }

    return outFile;
}

const isDirectRun = process.argv[1]
    && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url);

if (isDirectRun) {
    buildTablerIconsCatalog();
}
