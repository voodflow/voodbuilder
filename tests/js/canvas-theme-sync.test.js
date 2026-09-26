import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

describe('canvas theme sync', () => {
    const root = join(dirname(fileURLToPath(import.meta.url)), '../..');
    const source = readFileSync(
        join(root, 'resources/js/editor/editor/init.js'),
        'utf8',
    );

    it('prefers live html.dark over localStorage when resolving chrome theme', () => {
        const fnStart = source.indexOf('function resolveEditorChromePrefersDark');
        const fnEnd = source.indexOf('\nfunction ', fnStart + 1);
        const fn = source.slice(fnStart, fnEnd);

        expect(fn.indexOf("classList.contains('dark')")).toBeLessThan(fn.indexOf("getItem('theme')"));
    });

    it('does not block Grapes body render on companion canvas.scripts', () => {
        expect(source).toContain('injectCanvasCompanionScripts');
        expect(source).toContain('broke dark-mode sync');
        expect(source).toMatch(/scripts:\s*\[\],/);
    });

    it('re-applies theme after frame body load and host html.dark changes', () => {
        expect(source).toContain("canvas:frame:load:body");
        expect(source).toContain('__voodbuilderHostThemeObserver');
    });
});
