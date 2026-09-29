import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    applyTemplatePayload,
    composerCssFromTemplateSheet,
    cssLooksLikeCompiledUtilitySheet,
    forceTemplatePageCssRebuild,
    notifyPageCssReadyFromTemplate,
    shouldForceCssRebuildAfterTemplate,
} from '../../resources/js/editor/page-template-apply.js';

describe('forceTemplatePageCssRebuild', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('forces JIT after a short delay so template apply cannot rely on Save alone', () => {
        vi.stubGlobal('requestAnimationFrame', (cb) => {
            cb(0);

            return 1;
        });
        vi.stubGlobal('setTimeout', (cb) => {
            cb();

            return 1;
        });

        const editor = {
            __voodbuilderForcePageCssRebuild: vi.fn(),
            trigger: vi.fn(),
        };

        forceTemplatePageCssRebuild(editor, 250);

        expect(editor.__voodbuilderForcePageCssRebuild).toHaveBeenCalledWith(0);
        expect(editor.trigger).toHaveBeenCalledWith('voodbuilder:page-css-invalidate');
    });
});

describe('cssLooksLikeCompiledUtilitySheet', () => {
    it('rejects empty and author-only sheets', () => {
        expect(cssLooksLikeCompiledUtilitySheet('')).toBe(false);
        expect(cssLooksLikeCompiledUtilitySheet('.hero { color: red; }')).toBe(false);
        expect(cssLooksLikeCompiledUtilitySheet('#page-1 { background: white; }')).toBe(false);
    });

    it('accepts a Tailwind-sized utility sheet', () => {
        const utilities = Array.from({ length: 20 }, (_, i) => `.util-${i}{display:flex}`).join('');

        expect(cssLooksLikeCompiledUtilitySheet(utilities)).toBe(true);
    });
});

describe('composerCssFromTemplateSheet', () => {
    it('passes author-only sheets through unchanged', () => {
        const author = '#wrap{background:red} .hero{color:blue}';

        expect(composerCssFromTemplateSheet(author)).toBe(author);
    });

    it('strips utility selectors before CssComposer setStyle', () => {
        const utilities = Array.from({ length: 20 }, (_, i) => `.flex-${i}{display:flex}`).join('');
        const sheet = `#hero{background:red}${utilities}`;

        const composer = composerCssFromTemplateSheet(sheet);

        expect(composer).toContain('#hero');
        expect(composer).not.toContain('.flex-0');
        expect(composer.length).toBeLessThan(sheet.length);
    });
});

describe('shouldForceCssRebuildAfterTemplate', () => {
    it('forces rebuild when the template has no stylesheet', () => {
        expect(shouldForceCssRebuildAfterTemplate({ html: '<section></section>', css: '' })).toBe(true);
        expect(shouldForceCssRebuildAfterTemplate({ html: '<section></section>', css: null })).toBe(true);
    });

    it('forces rebuild when CSS is only author rules', () => {
        expect(shouldForceCssRebuildAfterTemplate({
            html: '<section class="flex"></section>',
            css: '.hero { color: red; } #x { padding: 1rem; }',
        })).toBe(true);
    });

    it('skips rebuild when a compiled utility sheet is stored on the template', () => {
        const utilities = Array.from({ length: 20 }, (_, i) => `.flex-${i}\\:gap-2{display:flex;gap:.5rem}`).join('');

        expect(shouldForceCssRebuildAfterTemplate({
            html: '<section class="flex"></section>',
            css: utilities,
        })).toBe(false);
    });
});

describe('notifyPageCssReadyFromTemplate', () => {
    it('syncs boot tracking and emits compiled so the apply overlay can clear', () => {
        const editor = {
            __voodbuilderSyncPageCssBootTracking: vi.fn(),
            trigger: vi.fn(),
        };

        notifyPageCssReadyFromTemplate(editor, '.flex{display:flex}');

        expect(editor.__voodbuilderSyncPageCssBootTracking).toHaveBeenCalled();
        expect(editor.trigger).toHaveBeenCalledWith('voodbuilder:page-css-compiled', {
            css: '.flex{display:flex}',
            html: '',
        });
    });
});

describe('applyTemplatePayload wallpaper hydrate', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('hydrates page-surface wallpaper from template CSS onto the current page', () => {
        vi.stubGlobal('requestAnimationFrame', (cb) => {
            cb(0);

            return 1;
        });
        vi.stubGlobal('window', {
            requestAnimationFrame: (cb) => {
                cb(0);

                return 1;
            },
        });

        const hydrate = vi.fn();
        const editor = {
            __voodbuilderChromeShellMode: false,
            setComponents: vi.fn(),
            setStyle: vi.fn(),
            __voodbuilderApplyPageLiveCss: vi.fn(),
            __voodbuilderHydratePageSurfaceWallpaper: hydrate,
            trigger: vi.fn(),
            getWrapper: () => ({ components: () => [] }),
        };

        applyTemplatePayload(editor, {
            html: '<section>Hero</section>',
            css: "#oldwrap{background-image:url('/storage/bg.webp');background-size:cover}",
        });

        expect(editor.setStyle).toHaveBeenCalled();
        expect(hydrate).toHaveBeenCalledWith(
            expect.stringContaining('background-image:url'),
        );
    });

    it('feeds CssComposer author rules only when the template ships a utility sheet', () => {
        vi.stubGlobal('requestAnimationFrame', (cb) => {
            cb(0);

            return 1;
        });
        vi.stubGlobal('window', {
            requestAnimationFrame: (cb) => {
                cb(0);

                return 1;
            },
        });

        const utilities = Array.from({ length: 20 }, (_, i) => `.p-${i}{padding:${i}px}`).join('');
        const sheet = `#hero{color:red}${utilities}`;
        const applyLive = vi.fn();
        const editor = {
            __voodbuilderChromeShellMode: false,
            setComponents: vi.fn(),
            setStyle: vi.fn(),
            __voodbuilderApplyPageLiveCss: applyLive,
            trigger: vi.fn(),
            getWrapper: () => ({ components: () => [] }),
        };

        applyTemplatePayload(editor, {
            html: '<section class="p-0">Hero</section>',
            css: sheet,
        });

        expect(applyLive).toHaveBeenCalledWith(sheet);
        expect(editor.setStyle).toHaveBeenCalledWith(expect.stringContaining('#hero'));
        expect(editor.setStyle.mock.calls[0][0]).not.toContain('.p-0');
        expect(editor.__voodbuilderAuthorPageCss).toContain('#hero');
    });
});
