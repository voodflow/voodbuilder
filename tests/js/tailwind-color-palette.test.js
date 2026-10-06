import { describe, expect, it } from 'vitest';
import { hexForUtility, swatchCssForClassName } from '../../resources/js/editor/tailwind-color-palette.js';

describe('swatchCssForClassName', () => {
    it('resolves palette utilities and theme tokens with opacity', () => {
        expect(hexForUtility('bg-red-500')).toBe('#ef4444');
        expect(swatchCssForClassName('bg-red-500')).toBe('#ef4444');
        expect(swatchCssForClassName('text-emerald-500')).toBe('#10b981');
        expect(swatchCssForClassName('bg-vp-brand-1')).toBe('var(--color-vp-brand-1)');
        expect(swatchCssForClassName('bg-vp-brand-1/10')).toBe(
            'color-mix(in srgb, var(--color-vp-brand-1) 10%, transparent)',
        );
        expect(swatchCssForClassName('hover:bg-vp-brand-1/15')).toBe(
            'color-mix(in srgb, var(--color-vp-brand-1) 15%, transparent)',
        );
        expect(swatchCssForClassName('w-full')).toBeNull();
    });
});
