import { describe, expect, it } from 'vitest';
import { hydrateLinkPropsFromAttributes } from '../../resources/js/editor/editor-button-link.js';
import { hydrateIconLinkPropsFromAttributes } from '../../resources/js/editor/editor-utility-blocks.js';

function fakeCta(props, attrs) {
    const state = { ...props };

    return {
        get: (key) => state[key],
        set: (updates) => Object.assign(state, updates),
        getAttributes: () => ({ ...attrs }),
        addAttributes: (next) => Object.assign(attrs, next),
        components: () => [],
        getView: () => null,
        state,
    };
}

describe('editor-button-link hydrate', () => {
    it('restores saved link attributes over model defaults after reload', () => {
        const cta = fakeCta(
            { href: '#', linkType: 'url', linkRef: '', target: '', ctaLabel: 'Button' },
            {
                href: '/pricing',
                target: '_blank',
                'data-vb-link-type': 'page',
                'data-vb-link': '12',
                'data-voodbuilder-cta': 'true',
                'data-voodbuilder-cta-label': 'Start building',
            },
        );

        hydrateLinkPropsFromAttributes(cta);

        expect(cta.state.href).toBe('/pricing');
        expect(cta.state.target).toBe('_blank');
        expect(cta.state.linkType).toBe('page');
        expect(cta.state.linkRef).toBe('12');
    });

    it('keeps the model href when the anchor has no href attribute', () => {
        const cta = fakeCta(
            { href: 'https://example.com', linkType: 'url', linkRef: '', target: '' },
            { 'data-voodbuilder-cta': 'true', 'data-voodbuilder-cta-label': 'Go' },
        );

        hydrateLinkPropsFromAttributes(cta);

        expect(cta.state.href).toBe('https://example.com');
        expect(cta.state.linkType).toBe('url');
    });

    it('restores icon links over the none/# type defaults after reload', () => {
        const icon = fakeCta(
            { href: '#', linkType: 'none' },
            { href: 'https://github.com/voodflow', target: '_blank', 'data-vb-link-type': 'url', 'data-voodbuilder-icon': '' },
        );

        hydrateIconLinkPropsFromAttributes(icon);

        expect(icon.state.linkType).toBe('url');
        expect(icon.state.href).toBe('https://github.com/voodflow');
        expect(icon.state.target).toBe('_blank');
    });
});
