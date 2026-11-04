import { describe, expect, it } from 'vitest';
import {
    collectSectionOptionals,
    optionalBelongsToPanel,
} from '../../resources/js/editor/section-optionals.js';

/**
 * Minimal component model: attributes + children.
 */
function node(attrs = {}, children = []) {
    const state = { ...attrs };
    const kids = [...children];

    const component = {
        getAttributes: () => ({ ...state }),
        get: (key) => (key === 'attributes' ? { ...state } : state[key]),
        addAttributes: (next) => Object.assign(state, next),
        removeAttributes: (key) => { delete state[key]; },
        addClass: () => {},
        removeClass: () => {},
        components: () => kids,
        parent: () => null,
    };

    return component;
}

function optional(attrs) {
    return node({ 'data-vb-optional': 'cta', ...attrs });
}

describe('section optionals scoping', () => {
    it('keeps a section-scoped toggle out of column panels', () => {
        const cta = optional({ 'data-vb-optional-scope': 'section' });
        const column = node({ 'data-vb-panel': 'column-2' }, [cta]);
        const section = node({ 'data-voodbuilder-section-block': 'demo' }, [column]);

        expect(collectSectionOptionals(column).map((item) => item.key)).toEqual([]);
        expect(collectSectionOptionals(section).map((item) => item.key)).toEqual(['cta']);
    });

    it('shows an unscoped toggle on the panel that contains it', () => {
        const cta = optional({});
        const column = node({ 'data-vb-panel': 'column-2' }, [cta]);
        const section = node({ 'data-voodbuilder-section-block': 'demo' }, [column]);

        expect(collectSectionOptionals(column).map((item) => item.key)).toEqual(['cta']);
        expect(collectSectionOptionals(section).map((item) => item.key)).toEqual(['cta']);
    });

    it('resolves an explicitly targeted panel scope', () => {
        const attrs = { 'data-vb-optional-scope': 'column-3' };

        expect(optionalBelongsToPanel(node({ 'data-vb-panel': 'column-3' }), attrs)).toBe(true);
        expect(optionalBelongsToPanel(node({ 'data-vb-panel': 'column-1' }), attrs)).toBe(false);
    });

    it('deduplicates repeated keys', () => {
        const column = node({ 'data-vb-panel': 'column-2' }, [
            optional({}),
            optional({}),
        ]);

        expect(collectSectionOptionals(column).length).toBe(1);
    });
});
