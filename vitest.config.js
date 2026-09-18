import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

const root = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    resolve: {
        alias: {
            '@voodbuilder-editor': path.resolve(root, 'resources/js/editor'),
        },
    },
    test: {
        include: ['tests/js/**/*.test.js'],
        environment: 'node',
    },
});
