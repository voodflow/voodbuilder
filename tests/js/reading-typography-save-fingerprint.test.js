import { describe, expect, it } from 'vitest';
import { readingTypographyFingerprint } from '../../resources/js/editor/editor/payload.js';

describe('readingTypographyFingerprint', () => {
    it('is empty for missing typography', () => {
        expect(readingTypographyFingerprint(null)).toBe('');
        expect(readingTypographyFingerprint(undefined)).toBe('');
    });

    it('changes when only a type-scale size changes', () => {
        const base = {
            font: '',
            headingFont: '',
            typeScale: { h1: { size: '5xl', sizeMd: null, sizeLg: null, weight: null, leading: null } },
        };
        const smaller = {
            ...base,
            typeScale: { h1: { size: 'sm', sizeMd: null, sizeLg: null, weight: null, leading: null } },
        };

        expect(readingTypographyFingerprint(base)).not.toBe(readingTypographyFingerprint(smaller));
        expect(readingTypographyFingerprint(base)).toBe(readingTypographyFingerprint({ ...base }));
    });
});
