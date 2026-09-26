import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

describe('dynamic block cascade guard', () => {
    it('skips cascade-delete while a dynamic block is refreshing', () => {
        const root = join(dirname(fileURLToPath(import.meta.url)), '../..');
        const source = readFileSync(
            join(root, 'resources/js/editor/plugins/voodbuilder-editor.js'),
            'utf8',
        );

        expect(source).toContain('__voodbuilderDynamicBlockRefreshing');
        expect(source).toContain('Voodflow Core nodes grid appeared');
        expect(source).toContain('dynamic.components?.()?.length ?? 0) > 0');
    });

    it('marks intentional dynamic root deletes before child remove storms', () => {
        const root = join(dirname(fileURLToPath(import.meta.url)), '../..');
        const source = readFileSync(
            join(root, 'resources/js/editor/plugins/voodbuilder-editor.js'),
            'utf8',
        );

        expect(source).toContain("component:remove:before");
        expect(source).toContain('__voodbuilderRemoving');
    });
});
