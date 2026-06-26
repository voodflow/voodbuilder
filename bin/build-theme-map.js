import esbuild from 'esbuild';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = dirname(__dirname);
const isDev = process.argv.includes('--dev');

const options = {
    entryPoints: [join(root, 'resources/js/theme-map/index.jsx')],
    bundle: true,
    outfile: join(root, 'resources/dist/theme-map.js'),
    format: 'iife',
    platform: 'browser',
    target: ['es2020'],
    minify: !isDev,
    sourcemap: isDev ? 'inline' : false,
    jsx: 'automatic',
    loader: {
        '.css': 'css',
    },
    define: {
        'process.env.NODE_ENV': isDev ? '"development"' : '"production"',
    },
    logLevel: 'info',
};

if (isDev) {
    const context = await esbuild.context(options);
    await context.watch();
    console.log('Watching vpress theme-map…');
} else {
    await esbuild.build(options);
}
