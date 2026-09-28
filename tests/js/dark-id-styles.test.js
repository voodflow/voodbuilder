import { describe, expect, it } from 'vitest';
import {
    clearDarkIdStyles,
    collectDarkIdStylesCssForPersist,
    darkIdRuleSelector,
    getDarkIdStyles,
    hydrateDarkIdStylesFromCss,
    setDarkIdStyles,
} from '../../resources/js/editor/dark-id-styles.js';

describe('dark-id-styles', () => {
    it('builds html.dark #id selectors', () => {
        expect(darkIdRuleSelector('cta1')).toBe('html.dark #cta1');
        expect(darkIdRuleSelector('')).toBe('');
    });

    it('stores and emits companion background-color without touching light', () => {
        const editor = {};

        setDarkIdStyles(editor, 'btn1', { 'background-color': 'rgba(15,23,42,0.8)' });
        expect(getDarkIdStyles(editor, 'btn1')).toEqual({
            'background-color': 'rgba(15,23,42,0.8)',
        });

        const css = collectDarkIdStylesCssForPersist(editor);
        expect(css).toContain('html.dark #btn1');
        expect(css).toContain('background-color:rgba(15,23,42,0.8)');
        expect(css).not.toMatch(/^#btn1\s*\{/m);
    });

    it('clears a single property and drops empty rules', () => {
        const editor = {};

        setDarkIdStyles(editor, 'x', {
            'background-color': 'red',
            'background-image': 'url(/a.jpg)',
        });
        clearDarkIdStyles(editor, 'x', 'background-color');
        expect(getDarkIdStyles(editor, 'x')).toEqual({
            'background-image': 'url(/a.jpg)',
        });

        clearDarkIdStyles(editor, 'x', 'background-image');
        expect(getDarkIdStyles(editor, 'x')).toEqual({});
        expect(collectDarkIdStylesCssForPersist(editor)).toBe('');
    });

    it('hydrates from saved author css and skips page wallpaper fixed images', () => {
        const editor = {};
        const imported = hydrateDarkIdStylesFromCss(
            editor,
            [
                'html.dark #el1 {background-color:rgba(0,0,0,0.5);background-image:url(/el.jpg)}',
                'html.dark #page {background-image:url(/wall.jpg);background-attachment:fixed;background-size:cover}',
            ].join('\n'),
        );

        expect(imported).toBe(1);
        expect(getDarkIdStyles(editor, 'el1')['background-color']).toBe('rgba(0,0,0,0.5)');
        expect(getDarkIdStyles(editor, 'el1')['background-image']).toBe('url(/el.jpg)');
        expect(getDarkIdStyles(editor, 'page')).toEqual({});
    });

    it('keeps an explicit dark background-image none through set, Save and reload', () => {
        const editor = {};

        setDarkIdStyles(editor, 'cta', { 'background-image': 'none' });
        const css = collectDarkIdStylesCssForPersist(editor);

        expect(css).toBe('html.dark #cta {background-image:none !important}');

        const reloaded = {};
        hydrateDarkIdStylesFromCss(reloaded, css);

        expect(getDarkIdStyles(reloaded, 'cta')['background-image']).toBe('none');
        expect(collectDarkIdStylesCssForPersist(reloaded)).toBe(css);
    });
});
