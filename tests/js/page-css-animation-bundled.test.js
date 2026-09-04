/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import { pageCssCoversClass } from '../../resources/js/editor/page-tailwind-autobuild.js';
import { STYLE_ANIMATION_BUNDLED_UTILITIES } from '../../resources/js/editor/style-animation-safelist.js';

describe('animation catalog skips page JIT', () => {
    it('treats Animation-sector utilities as already covered without live CSS', () => {
        const editor = { __voodbuilderPageLiveCss: '' };

        expect(pageCssCoversClass(editor, 'animate-bounce')).toBe(true);
        expect(pageCssCoversClass(editor, 'animate-duration-8000')).toBe(true);
        expect(pageCssCoversClass(editor, 'hover:animate-spin')).toBe(true);
        expect(pageCssCoversClass(editor, 'hover:animate-duration-8000')).toBe(true);
        expect(pageCssCoversClass(editor, 'transition-colors')).toBe(true);
        expect(pageCssCoversClass(editor, 'vb-animate-on-visible')).toBe(true);
        expect(pageCssCoversClass(editor, 'c1234')).toBe(true);
    });

    it('still requires JIT for unknown utilities', () => {
        const editor = { __voodbuilderPageLiveCss: '' };

        expect(pageCssCoversClass(editor, 'animate-gradient')).toBe(false);
        expect(pageCssCoversClass(editor, 'bg-[#abcdef]')).toBe(false);
    });

    it('keeps hover/active duration tokens in the shared safelist', () => {
        expect(STYLE_ANIMATION_BUNDLED_UTILITIES.has('animate-duration-8000')).toBe(true);
        expect(STYLE_ANIMATION_BUNDLED_UTILITIES.has('hover:animate-duration-8000')).toBe(true);
        expect(STYLE_ANIMATION_BUNDLED_UTILITIES.has('active:animate-duration-8000')).toBe(true);
    });
});
