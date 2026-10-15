import { describe, expect, it } from 'vitest';
import { stripOrphanIdRules } from '../../resources/js/editor/page-surface-styles.js';

describe('stripOrphanIdRules', () => {
    it('drops a bare id rule whose element is gone', () => {
        const css = '#live {color:red}\n#ghost {background-color:#ffffff !important}';
        const next = stripOrphanIdRules(css, { html: '<div id="live"></div>' });

        expect(next).toContain('#live {color:red}');
        expect(next).not.toContain('ghost');
    });

    it('drops the dark companion of a dead id', () => {
        const css = 'html.dark #ghost {background-color:#ffffff !important}';
        const next = stripOrphanIdRules(css, { html: '<div id="live"></div>' });

        expect(next).toBe('');
    });

    it('keeps live light and dark rules side by side', () => {
        const css = [
            'html.dark #live {background-color:#0f172a !important}',
            '#live {background-color:rgb(2, 6, 23)}',
        ].join('\n');
        const next = stripOrphanIdRules(css, { html: '<div id="live"></div>' });

        expect(next).toBe(css);
    });

    it('protects the page-surface wrapper id that chrome-shell Save omits from html', () => {
        const css = '#wrapper {background-image:url(/wall.jpg)}';
        const next = stripOrphanIdRules(css, {
            html: '<section id="content"></section>',
            keepIds: ['wrapper'],
        });

        expect(next).toBe(css);
    });

    it('accepts a Set for keepIds', () => {
        const css = '#wrapper {width:100%}';
        const next = stripOrphanIdRules(css, { html: '', keepIds: new Set(['wrapper']) });

        expect(next).toBe(css);
    });

    it('never touches pseudo or descendant selectors', () => {
        const css = '#ghost:hover {color:red}\n#ghost .child {color:blue}';
        const next = stripOrphanIdRules(css, { html: '<div id="live"></div>' });

        expect(next).toBe(css);
    });

    it('leaves plain class and element rules untouched', () => {
        const css = '.card {padding:1rem}\nsection {display:block}';
        const next = stripOrphanIdRules(css, { html: '<div id="live"></div>' });

        expect(next).toBe(css);
    });

    it('is a no-op when no id is known at all', () => {
        const css = '#ghost {color:red}';

        expect(stripOrphanIdRules(css, {})).toBe(css);
        expect(stripOrphanIdRules(css, { html: '' })).toBe(css);
    });

    it('handles an empty stylesheet', () => {
        expect(stripOrphanIdRules('', { html: '<div id="live"></div>' })).toBe('');
        expect(stripOrphanIdRules(null, { html: '<div id="live"></div>' })).toBe('');
    });

    it('recognises ids written with single quotes', () => {
        const css = "#live {color:red}\n#ghost {color:blue}";
        const next = stripOrphanIdRules(css, { html: "<div id='live'></div>" });

        expect(next).toContain('#live {color:red}');
        expect(next).not.toContain('ghost');
    });
});
