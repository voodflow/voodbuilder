import { describe, expect, it } from 'vitest';
import {
    composeColorOverlayLayer,
    composeDecorationBackgroundImageCss,
    composePhotoAwareGradientLayer,
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

    it('TW utility gradient layer matches v4 formula', () => {
        expect(composeTailwindGradientLayer('bg-gradient-to-r'))
            .toBe('linear-gradient(var(--tw-gradient-stops))');
    });

    it('stacks translucent gradient above image from photo visibility', () => {
        const gradient = composePhotoAwareGradientLayer({
            directionUtility: 'bg-gradient-to-tr',
            fromUtility: 'from-red-500',
            viaUtility: 'via-blue-700',
            toUtility: 'to-green-300',
            photoVisibility: 0.35,
        });

        // Stops stay opaque; photo visibility is a separate scrim under the fade.
        expect(gradient).toBe(
            'linear-gradient(to top right, rgba(239, 68, 68, 1), rgba(29, 78, 216, 1), rgba(134, 239, 172, 1))',
        );
        expect(composeDecorationBackgroundImageCss('/a.jpg', 0.35, '#0f172a', { gradientLayer: gradient }))
            .toBe(
                `${gradient}, linear-gradient(rgba(15, 23, 42, 0.65), rgba(15, 23, 42, 0.65)), url('/a.jpg')`,
            );

        // 100% photo visibility → directional gradient still paints (no uniform scrim).
        expect(composePhotoAwareGradientLayer({
            directionUtility: 'bg-gradient-to-t',
            fromUtility: 'from-red-500',
            toUtility: 'to-blue-700',
            photoVisibility: 1,
        })).toBe('linear-gradient(to top, rgba(239, 68, 68, 1), rgba(29, 78, 216, 1))');
    });

    it('supports transparent→black fade over a photo (section blend)', () => {
        const gradient = composePhotoAwareGradientLayer({
            directionUtility: 'bg-gradient-to-b',
            fromUtility: 'from-transparent',
            toUtility: 'to-black',
            photoVisibility: 1,
        });

        expect(gradient).toBe('linear-gradient(to bottom, transparent, rgba(0, 0, 0, 1))');
        expect(composeDecorationBackgroundImageCss('/hero.jpg', 1, '', { gradientLayer: gradient }))
            .toBe(`${gradient}, url('/hero.jpg')`);
    });

    it('applies stop positions for transparent→black fades', () => {
        const gradient = composePhotoAwareGradientLayer({
            directionUtility: 'bg-gradient-to-b',
            fromUtility: 'from-transparent',
            toUtility: 'to-black',
            fromPos: 40,
            toPos: 100,
            photoVisibility: 1,
        });

        expect(gradient).toBe('linear-gradient(to bottom, transparent 40%, rgba(0, 0, 0, 1) 100%)');
    });

    it('resolves white/black/transparent gradient stops', () => {
        expect(composePhotoAwareGradientLayer({
            directionUtility: 'bg-gradient-to-b',
            fromUtility: 'from-white',
            toUtility: 'to-black',
        })).toBe('linear-gradient(to bottom, rgba(255, 255, 255, 1), rgba(0, 0, 0, 1))');
    });

    it('resolves bg-* utilities to hex for overlays', () => {
        expect(cssColorFromBackgroundUtility('bg-red-700')).toBe('#b91c1c');
        expect(cssColorFromBackgroundUtility('bg-black')).toBe('#000000');
        expect(cssColorFromBackgroundUtility('bg-transparent')).toBe('');
    });
});
