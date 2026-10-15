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
        expect(source).toContain('isHeavyRemovableRoot');
        expect(source).toContain('data-vb-table');
        expect(source).toContain('is-structure-busy');
    });

    it('queues a refresh asked for while a render is running, instead of dropping it', () => {
        const root = join(dirname(fileURLToPath(import.meta.url)), '../..');
        const source = readFileSync(
            join(root, 'resources/js/editor/editor/init.js'),
            'utf8',
        );

        // Dropping the request left the newest config unrendered, and the running
        // response then re-applied its pre-edit snapshot over the author's value.
        expect(source).toContain('dynamicBlockRefreshQueue');
        expect(source).toContain('flushQueuedDynamicBlockRefreshes');
        expect(source).toContain('isDynamicRefreshBusy(');
    });

    it('never applies a dynamic render whose config the author has outrun', () => {
        const root = join(dirname(fileURLToPath(import.meta.url)), '../..');
        const source = readFileSync(
            join(root, 'resources/js/editor/editor/init.js'),
            'utf8',
        );

        expect(source).toContain('isStaleDynamicRender(');
        // The merge must take the live config, never the request-time snapshot.
        expect(source).toContain('...liveConfig,');
    });
});
