import { describe, expect, it, vi } from 'vitest';

vi.mock('../../resources/js/editor/jodit-image-editor.js', () => ({
    CMD_EDIT_IMAGE: 'voodbuilder:edit-image',
    isDynamicallyBoundImage: (component) => Boolean(component?.__bound),
    isRasterEditableSrc: () => false,
}));

vi.mock('../../resources/js/editor/tailwind-visual-style.js', () => ({
    safeFindComponents: (root, selector) => {
        if (String(selector).includes('img') && root.__heroImg) {
            return [root.__heroImg];
        }

        return [];
    },
}));

import { resolveImageSettingsContext } from '../../resources/js/editor/image-content-settings.js';

function mockComponent({
    tag = 'div',
    attrs = {},
    type = '',
    classes = [],
    parent = null,
    bound = false,
} = {}) {
    return {
        __bound: bound,
        get: (key) => {
            if (key === 'tagName') {
                return tag;
            }

            if (key === 'type') {
                return type;
            }

            if (key === 'src') {
                return attrs.src ?? '';
            }

            return undefined;
        },
        getAttributes: () => ({ ...attrs }),
        getClasses: () => [...classes],
        parent: () => parent,
        getStyle: () => ({}),
    };
}

describe('resolveImageSettingsContext for hero backgrounds', () => {
    it('still offers Background settings when the hero img has a dynamic bind', () => {
        const section = mockComponent({
            tag: 'section',
            attrs: { 'data-voodbuilder-block': 'vb-bg-image' },
            type: 'vb-bg-image',
        });
        const img = mockComponent({
            tag: 'img',
            classes: ['voodbuilder-hero-media__img'],
            attrs: { 'data-voodbuilder-bind': 'vtutSeries.latest.featured_image', src: '/x.jpg' },
            parent: section,
            bound: true,
        });
        section.__heroImg = img;

        const context = resolveImageSettingsContext(section);

        expect(context).not.toBeNull();
        expect(context?.mode).toBe('hero');
        expect(context?.image).toBe(img);
    });

    it('hides settings for a plain bound img that is not a hero media image', () => {
        const img = mockComponent({
            tag: 'img',
            attrs: { 'data-voodbuilder-bind': 'demo.latest.image', src: '/x.jpg' },
            bound: true,
        });

        expect(resolveImageSettingsContext(img)).toBeNull();
    });
});
