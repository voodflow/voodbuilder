import { describe, expect, it, vi } from 'vitest';

vi.mock('../../resources/js/editor/media-section-types.js', () => ({
    findHeroMediaImage: (section) => section.__heroImg ?? null,
}));

import {
    fieldTypeMatchesComponent,
    resolveBackgroundImageBindTarget,
    resolveFieldBindingTarget,
} from '../../resources/js/editor/bindings-ui.js';

function mockComponent({
    tag = 'div',
    attrs = {},
    type = '',
    classes = [],
    children = [],
    parent = null,
} = {}) {
    const component = {
        get: (key) => {
            if (key === 'tagName') {
                return tag;
            }

            if (key === 'type') {
                return type;
            }

            return undefined;
        },
        getAttributes: () => ({ ...attrs }),
        getClasses: () => [...classes],
        components: () => ({ models: children }),
        parent: () => parent,
    };

    for (const child of children) {
        child.parent = () => component;
    }

    return component;
}

describe('background image hero bindings', () => {
    it('offers image fields when the vb-bg-image section is selected', () => {
        const img = mockComponent({ tag: 'img', classes: ['voodbuilder-hero-media__img'] });
        const section = mockComponent({
            tag: 'section',
            attrs: { 'data-voodbuilder-block': 'vb-bg-image' },
            children: [img],
        });
        section.__heroImg = img;

        expect(fieldTypeMatchesComponent('image', section)).toBe(true);
        expect(resolveBackgroundImageBindTarget(section)).toBe(img);
        expect(resolveFieldBindingTarget(section, 'image')).toBe(img);
    });

    it('offers image fields when the hero media layer is selected', () => {
        const img = mockComponent({ tag: 'img' });
        const media = mockComponent({
            tag: 'div',
            attrs: { 'data-voodbuilder-role': 'media' },
            children: [img],
        });
        media.__heroImg = img;

        expect(fieldTypeMatchesComponent('image', media)).toBe(true);
        expect(resolveFieldBindingTarget(media, 'image')).toBe(img);
    });

    it('still matches a plain img selection', () => {
        const img = mockComponent({ tag: 'img' });

        expect(fieldTypeMatchesComponent('image', img)).toBe(true);
        expect(resolveFieldBindingTarget(img, 'image')).toBe(img);
    });

    it('does not treat an ordinary section as an image bind host', () => {
        const section = mockComponent({
            tag: 'section',
            attrs: { 'data-voodbuilder-block': 'vb-features' },
            children: [mockComponent({ tag: 'h2' })],
        });

        expect(fieldTypeMatchesComponent('image', section)).toBe(false);
        expect(resolveFieldBindingTarget(section, 'image')).toBeNull();
    });
});
