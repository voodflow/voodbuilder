import { describe, expect, it, vi } from 'vitest';
import {
    PAGE_SURFACE_CLASS,
    buildPageSurfaceCanvasWallpaperCss,
    extractOrphanWallpaperStylesFromCss,
    extractPageSurfaceWallpaperStylesFromCss,
    hydratePageSurfaceWallpaperFromCss,
    isPageSurfaceMode,
    isTargetingPageSurface,
    readPageSurfaceDarkWallpaperStyles,
    readPageSurfaceWallpaperStyles,
    remapPageSurfaceCssForPublish,
    resolveStyleTarget,
    selectPageSurface,
} from '../../resources/js/editor/page-surface-styles.js';

describe('page-surface-styles', () => {
    it('resolveStyleTarget falls back to wrapper when nothing is selected', () => {
        const wrapper = { id: 'wrap', get: (key) => (key === 'type' ? 'wrapper' : null) };
        const editor = {
            getSelected: () => null,
            getWrapper: () => wrapper,
        };

        expect(isTargetingPageSurface(editor)).toBe(true);
        expect(resolveStyleTarget(editor)).toBe(wrapper);
    });

    it('resolveStyleTarget prefers the selected component', () => {
        const selected = { id: 'sel', get: () => 'section' };
        const editor = {
            getSelected: () => selected,
            getWrapper: () => ({ id: 'wrap', get: () => 'wrapper' }),
        };

        expect(isTargetingPageSurface(editor)).toBe(false);
        expect(resolveStyleTarget(editor)).toBe(selected);
    });

    it('selectPageSurface selects the wrapper and clears force flag', () => {
        const wrapper = {
            get: (key) => (key === 'type' ? 'wrapper' : null),
            getClasses: () => [],
            addClass: vi.fn(),
            set: vi.fn(),
        };
        const select = vi.fn();
        const trigger = vi.fn();
        let selected = null;
        const editor = {
            getSelected: () => selected,
            getWrapper: () => wrapper,
            select: (...args) => {
                select(...args);
                selected = args[0] ?? null;
            },
            trigger,
        };

        expect(isPageSurfaceMode(editor)).toBe(true);
        expect(selectPageSurface(editor)).toBe(wrapper);
        expect(select).toHaveBeenCalledWith(wrapper, { scroll: false });
        expect(wrapper.addClass).toHaveBeenCalledWith(PAGE_SURFACE_CLASS);
        expect(wrapper.set).toHaveBeenCalledWith(expect.objectContaining({
            selectable: true,
            locked: false,
        }));
        expect(editor.__voodbuilderForcePageSurfaceStyle).toBe(false);
        expect(trigger).toHaveBeenCalled();
    });

    it('isPageSurfaceMode stays on for chrome shell site pages', () => {
        const editor = { __voodbuilderChromeShellMode: true };

        expect(isPageSurfaceMode(editor)).toBe(true);
    });

    it('isPageSurfaceMode is off for chrome layout / popup editors', () => {
        expect(isPageSurfaceMode({ __voodbuilderChromeLayoutMode: true })).toBe(false);
        expect(isPageSurfaceMode({ __voodbuilderPopupMode: true })).toBe(false);
    });

    it('selectPageSurface forces page targeting in chrome shell without selecting wrapper', () => {
        const wrapper = {
            get: (key) => (key === 'type' ? 'wrapper' : null),
            getClasses: () => [],
            addClass: vi.fn(),
            set: vi.fn(),
        };
        const select = vi.fn();
        const trigger = vi.fn();
        const editor = {
            __voodbuilderChromeShellMode: true,
            getSelected: () => null,
            getWrapper: () => wrapper,
            select,
            trigger,
        };

        expect(selectPageSurface(editor)).toBe(wrapper);
        expect(select).toHaveBeenCalledWith();
        expect(wrapper.set).not.toHaveBeenCalled();
        expect(wrapper.addClass).not.toHaveBeenCalled();
        expect(editor.__voodbuilderForcePageSurfaceStyle).toBe(true);
        expect(isTargetingPageSurface(editor)).toBe(true);
        expect(trigger).toHaveBeenCalled();
    });

    it('remapPageSurfaceCssForPublish rewrites wrapper id rules to html/body wallpaper', () => {
        const editor = {
            getWrapper: () => ({ getId: () => 'iabc123' }),
        };
        const css = '#iabc123 { background-image: url(/x.jpg); } .keep { color: red; }';
        const remapped = remapPageSurfaceCssForPublish(editor, css);

        expect(remapped).toContain(`html, body, body.${PAGE_SURFACE_CLASS}, .${PAGE_SURFACE_CLASS}`);
        expect(remapped).toContain('background-image: url(/x.jpg)');
        expect(remapped).toContain('background-size: cover');
        expect(remapped).toContain('background-position: center');
        expect(remapped).toContain('background-repeat: no-repeat');
        expect(remapped).toContain('background-attachment: fixed');
        expect(remapped).not.toContain('#iabc123');
        expect(remapped).toContain('.keep { color: red; }');
    });

    it('remapPageSurfaceCssForPublish keeps author size/position when already set', () => {
        const editor = {
            getWrapper: () => ({ getId: () => 'iabc123' }),
        };
        const css = '#iabc123 { background-image: url(/x.jpg); background-size: contain; background-position: top; }';
        const remapped = remapPageSurfaceCssForPublish(editor, css);

        expect(remapped).toContain('background-size: contain');
        expect(remapped).toContain('background-position: top');
        expect(remapped).toContain('background-attachment: fixed');
        expect(remapped).not.toMatch(/background-size:\s*cover/);
    });

    it('extractPageSurfaceWallpaperStylesFromCss reads body wallpaper layout', () => {
        const css = `html, body, body.${PAGE_SURFACE_CLASS} { background-image: url(/w.jpg); background-size: cover; background-position: top; background-repeat: no-repeat; }`;
        const styles = extractPageSurfaceWallpaperStylesFromCss(css);

        expect(styles['background-image']).toContain('/w.jpg');
        expect(styles['background-size']).toBe('cover');
        expect(styles['background-position']).toBe('top');
        expect(styles['background-repeat']).toBe('no-repeat');
    });

    it('hydratePageSurfaceWallpaperFromCss restores wrapper #id from body rules', () => {
        const setIdRule = vi.fn();
        const editor = {
            getWrapper: () => ({ getId: () => 'iwrap1' }),
            Css: {
                getIdRule: () => ({ getStyle: () => ({}) }),
                setIdRule,
            },
            __voodbuilderPageLiveCss: `html, body { background-image: url(/w.jpg); background-size: cover; background-position: top; background-repeat: no-repeat; }`,
            Canvas: { getDocument: () => null },
        };

        expect(hydratePageSurfaceWallpaperFromCss(editor)).toBe(true);
        expect(setIdRule).toHaveBeenCalledWith('iwrap1', expect.objectContaining({
            'background-image': expect.stringContaining('/w.jpg'),
            'background-size': 'cover',
            'background-position': 'top',
            'background-repeat': 'no-repeat',
        }));
    });

    it('extractOrphanWallpaperStylesFromCss reclaims previous wrapper wallpaper ids', () => {
        const css = `
#i40b { background-size:cover; background-position:center; background-repeat:no-repeat; background-image:url(/page.jpg); background-attachment:fixed }
#hero { background-image:url(/card.jpg) }
`;
        const orphan = extractOrphanWallpaperStylesFromCss(css, new Set(['inew']));

        expect(orphan['background-image']).toContain('/page.jpg');
        expect(orphan['background-size']).toBe('cover');
        expect(orphan['background-attachment']).toBe('fixed');
    });

    it('extractOrphanWallpaperStylesFromCss ignores ids still present in the tree', () => {
        const css = `#i40b { background-image:url(/page.jpg); background-size:cover }`;
        const orphan = extractOrphanWallpaperStylesFromCss(css, new Set(['i40b']));

        expect(orphan['background-image']).toBeUndefined();
    });

    it('hydratePageSurfaceWallpaperFromCss restores orphan #id wallpaper onto the current wrapper', () => {
        const setIdRule = vi.fn();
        const wrapper = {
            getId: () => 'inew',
            onAll: (cb) => cb({ getId: () => 'inew' }),
        };
        const editor = {
            getWrapper: () => wrapper,
            Css: {
                getIdRule: () => ({ getStyle: () => ({}) }),
                setIdRule,
            },
            __voodbuilderPageLiveCss: `#i40b { background-image:url(/page.jpg); background-size:cover; background-position:center; background-repeat:no-repeat; background-attachment:fixed }`,
            Canvas: { getDocument: () => null },
        };

        expect(hydratePageSurfaceWallpaperFromCss(editor)).toBe(true);
        expect(setIdRule).toHaveBeenCalledWith('inew', expect.objectContaining({
            'background-image': expect.stringContaining('/page.jpg'),
            'background-size': 'cover',
        }));
    });

    it('buildPageSurfaceCanvasWallpaperCss mirrors public fixed layer + transparent shells', () => {
        const css = buildPageSurfaceCanvasWallpaperCss({
            'background-image': "url('/w.jpg')",
            'background-size': 'cover',
            'background-position': 'center',
            'background-repeat': 'no-repeat',
        }, null, { hostId: 'iwrap1' });

        expect(css).toContain('body::before');
        expect(css).toContain('position: fixed');
        expect(css).toContain("url('/w.jpg')");
        expect(css).toContain('.voodbuilder-site-shell');
        expect(css).toContain('background-color: transparent !important');
        expect(css).toContain('#iwrap1');
        expect(css).toContain('background-image: none !important');
        expect(buildPageSurfaceCanvasWallpaperCss({})).toBe('');
    });

    it('buildPageSurfaceCanvasWallpaperCss hides light under html.dark when dark missing', () => {
        const css = buildPageSurfaceCanvasWallpaperCss({
            'background-image': "url('/light.jpg')",
            'background-size': 'cover',
        });

        expect(css).toContain('body::before');
        expect(css).toContain("url('/light.jpg')");
        expect(css).toContain('html.dark body::before');
        expect(css).toMatch(/html\.dark body::before\s*\{[^}]*background-image:\s*none\s*!important/s);
    });

    it('buildPageSurfaceCanvasWallpaperCss keeps separate light and dark layers', () => {
        const css = buildPageSurfaceCanvasWallpaperCss(
            { 'background-image': "url('/light.jpg')" },
            { 'background-image': "url('/dark.jpg')" },
        );

        expect(css).toContain("url('/light.jpg')");
        expect(css).toContain("url('/dark.jpg')");
        expect(css).toContain('html.dark body::before');
        expect(css).not.toMatch(/html\.dark body::before\s*\{[^}]*background-image:\s*none\s*!important/s);
    });

    it('setPageSurfaceDarkWallpaperRule stores dark in memory without CssComposer', async () => {
        const { setPageSurfaceDarkWallpaperRule, getPageSurfaceDarkWallpaperRuleStyles, getPageSurfaceWallpaperUrlCache } = await import(
            '../../resources/js/editor/page-surface-styles.js'
        );

        const removed = [];
        const editor = {
            Css: {
                getAll: () => [{
                    get: (key) => (key === 'selectorsAdd' ? 'html.dark' : undefined),
                    selectorsToString: () => '#iwrap, html.dark',
                    getStyle: () => ({ 'background-image': "url('/bad.jpg')" }),
                }],
                remove: (rule) => removed.push(rule),
                add: vi.fn(),
                setIdRule: vi.fn(),
            },
        };

        const ok = setPageSurfaceDarkWallpaperRule(editor, 'iwrap', {
            'background-image': "url('/dark.jpg')",
            'background-size': 'cover',
        });

        expect(ok).toBe(true);
        expect(editor.Css.add).not.toHaveBeenCalled();
        expect(editor.Css.setIdRule).not.toHaveBeenCalled();
        expect(getPageSurfaceDarkWallpaperRuleStyles(editor, 'iwrap')['background-image']).toContain('/dark.jpg');
        expect(getPageSurfaceWallpaperUrlCache(editor).dark).toContain('/dark.jpg');
        expect(removed.length).toBeGreaterThan(0);
    });

    it('buildPageSurfaceCanvasWallpaperCss defaults cover/no-repeat when layout missing', () => {
        const css = buildPageSurfaceCanvasWallpaperCss({
            'background-image': "url('/w.jpg')",
        });

        expect(css).toContain('background-size: cover');
        expect(css).toContain('background-position: center');
        expect(css).toContain('background-repeat: no-repeat');
    });

    it('hydratePageSurfaceWallpaperFromCss fills missing layout defaults', () => {
        const setIdRule = vi.fn();
        const editor = {
            getWrapper: () => ({
                getId: () => 'iwrap2',
                getAttributes: () => ({}),
                addAttributes: vi.fn(),
            }),
            Css: {
                getIdRule: () => ({
                    getStyle: () => ({ 'background-image': 'url(/only.jpg)' }),
                }),
                setIdRule,
            },
            __voodbuilderPageLiveCss: '',
            Canvas: { getDocument: () => null },
        };

        expect(hydratePageSurfaceWallpaperFromCss(editor)).toBe(true);
        expect(setIdRule).toHaveBeenCalledWith('iwrap2', expect.objectContaining({
            'background-image': expect.stringContaining('/only.jpg'),
            'background-size': 'cover',
            'background-position': 'center',
            'background-repeat': 'no-repeat',
        }));
    });

    it('hydratePageSurfaceWallpaperFromCss reclaims known-id ghost wallpaper onto wrapper', () => {
        const setIdRule = vi.fn();
        const addAttributes = vi.fn();
        const editor = {
            getWrapper: () => ({
                getId: () => 'inew',
                getAttributes: () => ({}),
                addAttributes,
                onAll: (fn) => {
                    fn({ getId: () => 'ighost' });
                },
            }),
            Css: {
                getIdRule: () => ({ getStyle: () => ({}) }),
                setIdRule,
            },
            __voodbuilderPageLiveCss: `#ighost { background-image:url(/page.jpg); background-size:cover; background-repeat:no-repeat }`,
            Canvas: { getDocument: () => null },
        };

        expect(hydratePageSurfaceWallpaperFromCss(editor)).toBe(true);
        expect(setIdRule).toHaveBeenCalledWith('inew', expect.objectContaining({
            'background-image': expect.stringContaining('/page.jpg'),
            'background-size': 'cover',
        }));
        expect(addAttributes).toHaveBeenCalledWith(expect.objectContaining({
            'data-vb-style-bg-src': expect.stringContaining('/page.jpg'),
        }));
    });

    it('reads light and dark wallpaper from author CSS on the current wrapper id', () => {
        const editor = {
            getWrapper: () => ({
                getId: () => 'iwrap',
                getAttributes: () => ({}),
                onAll: (fn) => fn({ getId: () => 'iwrap' }),
            }),
            Css: { getIdRule: () => null },
            getCss: () => '',
            __voodbuilderAuthorPageCss: '#iwrap{background-image:url(/light.jpg);background-size:cover}'
                + 'html.dark #iwrap{background-image:url(/dark.jpg);background-size:cover}',
        };

        expect(readPageSurfaceWallpaperStyles(editor)['background-image']).toContain('/light.jpg');
        expect(readPageSurfaceDarkWallpaperStyles(editor)['background-image']).toContain('/dark.jpg');
    });
});
