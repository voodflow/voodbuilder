import { afterEach, describe, expect, it, vi } from 'vitest';
import { forceTemplatePageCssRebuild } from '../../resources/js/editor/page-template-apply.js';

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
