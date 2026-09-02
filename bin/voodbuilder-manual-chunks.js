/**
 * Rollup `manualChunks` strategy for the editor bundle.
 *
 * Without this the whole editor entry is one chunk: GrapesJS accounts for roughly half of
 * it and changes only on a deliberate upgrade, while our editor modules change on every
 * deploy. Sharing a chunk meant each of our edits invalidated the vendor half too, so
 * returning authors re-downloaded ~2.5 MiB for a one-line fix.
 *
 * Lives in the package (not the host `vite.config.js`) so every installation gets the
 * same chunking without copying build config.
 *
 * @example
 * import { voodbuilderManualChunks } from './packages/voodflow/voodbuilder/bin/voodbuilder-manual-chunks.js';
 *
 * export default defineConfig({
 *     build: {
 *         rollupOptions: { output: { manualChunks: voodbuilderManualChunks } },
 *     },
 * });
 */

/** Core runtime: the component model, canvas and CSS composer the editor is built on. */
const VENDOR_CORE = /node_modules\/(grapesjs|grapick)\//;

/** Community GrapesJS plugins — same caching rationale, independent release cadence. */
const VENDOR_PLUGINS = /node_modules\/grapesjs-(blocks-basic|plugin-forms|style-bg|tabs)\//;

/**
 * Dynamically imported by `editor-tailwind-plugin.js`; named only so it is recognisable
 * in build output and devtools instead of appearing as "dist".
 */
const VENDOR_TAILWIND_COMPILER = 'node_modules/grapesjs-tailwindcss-plugin/';

/**
 * @param  {string}  id  module id being assigned to a chunk
 * @returns {string|undefined} chunk name, or undefined to let Rollup decide
 */
export function voodbuilderManualChunks(id) {
    if (! id.includes('node_modules')) {
        return undefined;
    }

    if (VENDOR_CORE.test(id)) {
        return 'vendor-grapesjs';
    }

    if (VENDOR_PLUGINS.test(id)) {
        return 'vendor-grapesjs-plugins';
    }

    if (id.includes(VENDOR_TAILWIND_COMPILER)) {
        return 'vendor-tailwind-compiler';
    }

    return undefined;
}

export default voodbuilderManualChunks;
