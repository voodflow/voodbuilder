/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    pageCssCoversClass,
    stripCanvasBundledUtilitiesFromCss,
} from '../../resources/js/editor/page-tailwind-autobuild.js';

describe('stripCanvasBundledUtilitiesFromCss', () => {
    it('removes canvas-bundled base utilities that would override responsive widths', () => {
        const css = [
            '.w-full{width:100%}',
            '.flex{display:flex}',
            '.flex-wrap{flex-wrap:wrap}',
            '@media (width>=48rem){.md\\:w-1\\/2{width:50%}}',
            '@media (width>=80rem){.xl\\:w-1\\/4{width:25%}}',
            '.bg-\\[\\#070b16\\]{background-color:#070b16}',
        ].join('');

        const stripped = stripCanvasBundledUtilitiesFromCss(css);

        expect(stripped).not.toMatch(/\.w-full\s*\{/);
        expect(stripped).not.toMatch(/\.flex\s*\{/);
        // Responsive / arbitrary utilities must survive for canvas layout.
        expect(stripped).toContain('md\\:w-1\\/2');
        expect(stripped).toContain('xl\\:w-1\\/4');
        expect(stripped).toContain('bg-\\[\\#070b16\\]');
    });

    it('treats library card width tokens as already covered without live CSS', () => {
        const editor = { __voodbuilderPageLiveCss: '' };

        expect(pageCssCoversClass(editor, 'w-full')).toBe(true);
        expect(pageCssCoversClass(editor, 'flex-wrap')).toBe(true);
        expect(pageCssCoversClass(editor, 'md:w-1/2')).toBe(true);
        expect(pageCssCoversClass(editor, 'xl:w-1/4')).toBe(true);
        expect(pageCssCoversClass(editor, 'max-w-[80rem]')).toBe(true);
    });
});
