import { describe, expect, it } from 'vitest';
import {
    composeColorOverlayLayer,
    composeDecorationBackgroundImageCss,
    composeTailwindGradientLayer,
    cssColorFromBackgroundUtility,
    extractUrlFromBackgroundImage,
    normalizeBackgroundImageOpacity,
    overlayAlphaFromPhotoVisibility,
    toRgbaWithAlpha,
} from '../../resources/js/editor/style-background-image.js';

describe('style-background-image', () => {
    it('normalizes opacity from 0–1 and 0–100 scales', () => {
        expect(normalizeBackgroundImageOpacity(undefined)).toBe(1);
        expect(normalizeBackgroundImageOpacity('0.55')).toBe(0.55);
        expect(normalizeBackgroundImageOpacity('55')).toBe(0.55);
        expect(normalizeBackgroundImageOpacity('150')).toBe(1);
        expect(normalizeBackgroundImageOpacity('-1')).toBe(0);
    });

    it('maps photo visibility to overlay alpha', () => {
        expect(overlayAlphaFromPhotoVisibility(1)).toBe(0);
        expect(overlayAlphaFromPhotoVisibility(0.65)).toBe(0.35);
        expect(overlayAlphaFromPhotoVisibility(0)).toBe(1);
    });

    it('extracts url from layered opacity overlays', () => {
        expect(extractUrlFromBackgroundImage('none')).toBe('');
        expect(extractUrlFromBackgroundImage("url('/a.jpg')")).toBe('/a.jpg');
        expect(extractUrlFromBackgroundImage(
            "linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.45)), url('/a.jpg')",
        )).toBe('/a.jpg');
        expect(extractUrlFromBackgroundImage('linear-gradient(red, blue)')).toBe('');
    });

    it('composes color overlay above a full-opacity photo (no faded url)', () => {
        expect(composeDecorationBackgroundImageCss('/a.jpg', 1, '#0f172a'))
            .toBe("url('/a.jpg')");

        expect(composeDecorationBackgroundImageCss('/a.jpg', 0.55, '#0f172a'))
            .toBe('linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.45)), url(\'/a.jpg\')');

        expect(composeColorOverlayLayer('#0f172a', 0.45))
            .toBe('linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.45))');

        expect(toRgbaWithAlpha('rgb(10, 20, 30)', 0.4)).toBe('rgba(10, 20, 30, 0.4)');
        expect(toRgbaWithAlpha('var(--color-vp-bg)', 0.4)).toBeNull();
    });

    it('infers photo visibility from overlay CSS', async () => {
        const { inferBackgroundImageOpacityFromCss } = await import(
            '../../resources/js/editor/style-background-image.js'
        );

        expect(inferBackgroundImageOpacityFromCss(
            'linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.45)), url(/a.jpg)',
        )).toBe(0.55);
        expect(inferBackgroundImageOpacityFromCss("url('/a.jpg')")).toBeNull();
    });

    it('stacks gradient above image without fading the url', () => {
        const gradient = composeTailwindGradientLayer('bg-gradient-to-r');

        expect(gradient).toBe('linear-gradient(var(--tw-gradient-stops))');
        expect(composeDecorationBackgroundImageCss('/a.jpg', 0.25, '#ff0000', { gradientLayer: gradient }))
            .toBe(`${gradient}, url('/a.jpg')`);
        expect(composeDecorationBackgroundImageCss('', 1, '', { gradientLayer: gradient }))
            .toBe(gradient);
    });

    it('resolves bg-* utilities to hex for overlays', () => {
        expect(cssColorFromBackgroundUtility('bg-red-700')).toBe('#b91c1c');
        expect(cssColorFromBackgroundUtility('bg-black')).toBe('#000000');
        expect(cssColorFromBackgroundUtility('bg-transparent')).toBe('');
    });
});
