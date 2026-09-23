import { afterEach, describe, expect, it, vi } from 'vitest';
import {
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

describe('shouldForceCssRebuildAfterTemplate', () => {
    it('forces rebuild when the template has no stylesheet', () => {
        expect(shouldForceCssRebuildAfterTemplate({ html: '<section></section>', css: '' })).toBe(true);
        expect(shouldForceCssRebuildAfterTemplate({ html: '<section></section>', css: null })).toBe(true);
    });

    it('skips rebuild when a compiled sheet is already stored on the template', () => {
        expect(shouldForceCssRebuildAfterTemplate({
            html: '<section class="flex"></section>',
            css: '.flex { display: flex }',
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
