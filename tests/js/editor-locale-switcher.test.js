/**
 * @vitest-environment node
 *
 * The editor header lists every site language: the one being edited is marked
 * current, a sibling translation links to its own editor session, and a language
 * with no translation stays visible but disabled.
 */

import { describe, expect, it } from 'vitest';
import { localeSwitcherMarkup } from '@voodbuilder-editor/editor-layout.js';

const labels = {
    editingLocaleSwitcher: 'Editing language',
    editingLocaleSwitchTo: 'Switch to :language',
    editingLocaleMissing: ':language has no translation of this page yet',
};

const switcher = {
    current: 'IT',
    options: [
        { code: 'IT', label: 'Italiano', current: true, url: null },
        { code: 'EN', label: 'English', current: false, url: 'https://example.test/?locale=en&edit=1' },
        { code: 'FR', label: 'Français', current: false, url: null },
    ],
};

describe('locale switcher in the editor header', () => {
    it('marks the current language and links the translated one', () => {
        const markup = localeSwitcherMarkup(switcher, labels);

        expect(markup).toContain('voodbuilder-editor-topbar__locale--current');
        expect(markup).toContain('aria-current="true"');
        expect(markup).toContain('href="https://example.test/?locale=en&amp;edit=1"');
        expect(markup).toContain('data-voodbuilder-editor-locale="en"');
        expect(markup).toContain('Switch to English');
    });

    it('renders a missing translation as a disabled chip', () => {
        const markup = localeSwitcherMarkup(switcher, labels);

        expect(markup).toContain('voodbuilder-editor-topbar__locale--unavailable');
        expect(markup).toContain('aria-disabled="true"');
        expect(markup).toContain('has no translation of this page yet');
    });

    it('renders nothing for a single-language site', () => {
        expect(localeSwitcherMarkup({ current: 'IT', options: [{ code: 'IT', label: 'Italiano', current: true, url: null }] }, labels)).toBe('');
        expect(localeSwitcherMarkup(null, labels)).toBe('');
    });
});
