import { describe, expect, it } from 'vitest';

import { shouldOfferBindingSource } from '../../resources/js/editor/bindings-ui.js';

/**
 * Which dynamic sources a page can actually host.
 *
 * A `.current` source reads the record the URL resolved to, so it only means something on a
 * dynamic page for its own channel. Offering it anywhere else lets an author bind a field
 * that can never resolve: the element renders empty on the published page, which reads as a
 * broken binding rather than a page that cannot host one.
 */
const editorOnDynamicPage = (currentSourceIds) => ({
    __voodbuilderDynamicPage: { channel: 'events', currentSourceIds },
});

// An ordinary page sends no dynamic page block at all.
const editorOnOrdinaryPage = () => ({ __voodbuilderDynamicPage: null });

const looseComponent = () => ({
    getAttributes: () => ({}),
    parent: () => null,
});

describe('shouldOfferBindingSource', () => {
    it('offers a current source on the dynamic page that owns it', () => {
        expect(shouldOfferBindingSource(
            'vevents.current',
            looseComponent(),
            editorOnDynamicPage(['vevents.current']),
        )).toBe(true);
    });

    it('withholds a current source on an ordinary page', () => {
        // The regression: an empty allow-list used to skip the check entirely, so every
        // `.current` source was offered on pages that have no current record.
        expect(shouldOfferBindingSource(
            'vevents.current',
            looseComponent(),
            editorOnOrdinaryPage(),
        )).toBe(false);
    });

    it('withholds a current source belonging to another channel', () => {
        expect(shouldOfferBindingSource(
            'vexhibitors.current',
            looseComponent(),
            editorOnDynamicPage(['vevents.current']),
        )).toBe(false);
    });

    it('still offers ordinary sources on a page with no dynamic record', () => {
        // Hiding the impossible ones must not take the usable ones with them.
        expect(shouldOfferBindingSource(
            'vtuts.latest',
            looseComponent(),
            editorOnOrdinaryPage(),
        )).toBe(true);
    });

    it('withholds a current source when no editor is supplied', () => {
        // Callers without an editor cannot prove the page hosts the record, and guessing
        // yes is the failure this test exists to prevent.
        expect(shouldOfferBindingSource('vevents.current', looseComponent())).toBe(false);
    });

    it('never offers a repeat list source directly', () => {
        expect(shouldOfferBindingSource(
            'vevents.list',
            looseComponent(),
            editorOnOrdinaryPage(),
        )).toBe(false);
    });

    it('withholds repeat item sources outside a repeat template', () => {
        expect(shouldOfferBindingSource(
            'vtuts.item',
            looseComponent(),
            editorOnOrdinaryPage(),
        )).toBe(false);
    });
});
