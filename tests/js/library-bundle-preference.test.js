import { describe, expect, it } from 'vitest';
import { preferredBundleForLibraryTab } from '../../../voodbuilder-elements/resources/js/editor/library-modal.js';

const catalogs = [
    { id: 'wireframes', label: 'Elements' },
    { id: 'voodflow', label: 'Voodflow' },
    { id: 'templates', label: 'Templates' },
];

describe('preferredBundleForLibraryTab', () => {
    it('opens Elements (wireframes bundle) from the Elements tab', () => {
        expect(preferredBundleForLibraryTab('blocks', catalogs)).toBe('wireframes');
    });

    it('opens Templates from the Templates tab', () => {
        expect(preferredBundleForLibraryTab('templates', catalogs)).toBe('templates');
    });

    it('falls back to wireframes when templates catalog is missing', () => {
        expect(preferredBundleForLibraryTab('templates', [
            { id: 'wireframes', label: 'Elements' },
        ])).toBe('wireframes');
    });
});
