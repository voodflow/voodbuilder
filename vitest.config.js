import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

const root = path.dirname(fileURLToPath(import.meta.url));

// These suites import the private voodbuilder-elements sibling (monorepo only).
const elementsSiblingSuites = [
    'tests/js/elements-companion-gate.test.js',
    'tests/js/library-bundle-preference.test.js',
];
const hasElementsSibling = fs.existsSync(path.resolve(root, '../voodbuilder-elements/resources/js/editor'));

export default defineConfig({
    resolve: {
        alias: {
            '@voodbuilder-editor': path.resolve(root, 'resources/js/editor'),
        },
    },
    test: {
        include: ['tests/js/**/*.test.js'],
        exclude: hasElementsSibling ? [] : elementsSiblingSuites,
        environment: 'node',
    },
});
