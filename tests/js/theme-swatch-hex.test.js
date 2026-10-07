import { describe, expect, it } from 'vitest';
import {
    resolveThemeSwatchHex,
    swatchHexForUtility,
} from '../../resources/js/editor/tailwind-color-palette.js';

describe('theme swatch hex', () => {
    it('resolves vp tokens from theme palette CSS', () => {
        const editor = {
            __voodbuilderThemePaletteCss: `
                :root {
                    --color-vp-brand-1: #e11d48;
                    --color-vp-bg: #fafafa;
                }
            `,
        };

        expect(resolveThemeSwatchHex(editor, 'vp-brand-1')).toBe('#e11d48');
        expect(resolveThemeSwatchHex(editor, 'bg-vp-brand-1')).toBe('#e11d48');
        expect(swatchHexForUtility(editor, 'bg-vp-bg')).toBe('#fafafa');
    });

    it('resolves color-mix(...)!important theme tokens (vp-bg-alt)', () => {
        const editor = {
            __voodbuilderThemePaletteCss: `
                :root {
                    --color-vp-bg-alt: color-mix(in srgb, #ffffff 94%, #000000)!important;
                }
            `,
        };

        const hex = resolveThemeSwatchHex(editor, 'bg-vp-bg-alt');
        expect(hex).toMatch(/^#[0-9a-f]{6}$/);
        // Near-white mix — not pure black / missing.
        expect(hex).not.toBe('#000000');
    });

    it('keeps concrete hex for black/white utilities', () => {
        expect(swatchHexForUtility(null, 'bg-black')).toBe('#000000');
        expect(swatchHexForUtility(null, 'text-white')).toBe('#ffffff');
    });
});
