import { describe, expect, it } from 'vitest';
import { publishedDarkWarningKind } from '../../resources/js/editor/style-tailwind-breakpoints.js';

describe('publishedDarkWarningKind', () => {
    it('reports dead dark: variants when the site can never render dark', () => {
        expect(publishedDarkWarningKind({
            __voodbuilderStyleThemeDark: true,
            __voodbuilderPublishedDarkScope: false,
            __voodbuilderPublishedDefaultDark: false,
        })).toBe('never');
    });

    it('reports dark-only overrides when the page can be dark but starts light', () => {
        expect(publishedDarkWarningKind({
            __voodbuilderStyleThemeDark: true,
            __voodbuilderPublishedDarkScope: true,
            __voodbuilderPublishedDefaultDark: false,
        })).toBe('only-dark');
    });

    it('stays quiet when the page publishes dark', () => {
        expect(publishedDarkWarningKind({
            __voodbuilderStyleThemeDark: true,
            __voodbuilderPublishedDarkScope: true,
            __voodbuilderPublishedDefaultDark: true,
        })).toBe('');
    });

    it('stays quiet while editing the light theme', () => {
        expect(publishedDarkWarningKind({
            __voodbuilderStyleThemeDark: false,
            __voodbuilderPublishedDarkScope: false,
            __voodbuilderPublishedDefaultDark: false,
        })).toBe('');
    });

    it('assumes scope when an older payload omits the flags', () => {
        expect(publishedDarkWarningKind({ __voodbuilderStyleThemeDark: true })).toBe('');
    });

    it('tolerates a missing editor', () => {
        expect(publishedDarkWarningKind()).toBe('');
        expect(publishedDarkWarningKind(null)).toBe('');
    });
});
