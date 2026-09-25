import { describe, expect, it } from 'vitest';
import { hydratePropsFromAttributes } from '../../resources/js/editor/component-attr-hydrate.js';
import {
    ANIMATED_CTA_PROPS,
    COUNTER_PROPS,
    LOGO_SCROLL_PROPS,
    readCounterConfig,
} from '../../resources/js/editor/editor-animated-blocks.js';

function fakeComponent(props, attrs) {
    const state = { ...props };

    return {
        get: (key) => state[key],
        set: (updates) => Object.assign(state, updates),
        getAttributes: () => ({ ...attrs }),
        addAttributes: (next) => Object.assign(attrs, next),
        state,
        attrs,
    };
}

describe('hydratePropsFromAttributes', () => {
    it('lets saved attributes win over numeric and string model defaults', () => {
        const component = fakeComponent(
            { 'data-vb-anim': 'fade-up', 'data-vb-anim-duration': 700, 'data-vb-anim-delay': 0 },
            { 'data-vb-anim': 'scale-in', 'data-vb-anim-duration': '1200', 'data-vb-anim-delay': '150' },
        );

        expect(hydratePropsFromAttributes(component, ANIMATED_CTA_PROPS)).toBe(true);
        expect(component.state['data-vb-anim']).toBe('scale-in');
        expect(component.state['data-vb-anim-duration']).toBe(1200);
        expect(component.state['data-vb-anim-delay']).toBe(150);
    });

    it('keeps defaults when the attribute is missing or not numeric', () => {
        const component = fakeComponent(
            { 'data-vb-logo-count': 6, 'data-vb-logo-speed': 'normal' },
            { 'data-vb-logo-count': 'abc' },
        );

        expect(hydratePropsFromAttributes(component, LOGO_SCROLL_PROPS)).toBe(false);
        expect(component.state['data-vb-logo-count']).toBe(6);
        expect(component.state['data-vb-logo-speed']).toBe('normal');
    });

    it('restores a saved counter config instead of the 0 → 100 defaults', () => {
        const component = fakeComponent(
            {
                'data-vb-count-from': 0,
                'data-vb-count-to': 100,
                'data-vb-count-duration': 1600,
                'data-vb-count-suffix': '',
                'data-vb-count-trigger': 'visible',
            },
            {
                'data-vb-count-from': '10',
                'data-vb-count-to': '2500',
                'data-vb-count-duration': '900',
                'data-vb-count-suffix': '+',
                'data-vb-count-trigger': 'always',
            },
        );

        hydratePropsFromAttributes(component, COUNTER_PROPS);
        const config = readCounterConfig(component);

        expect(config.from).toBe(10);
        expect(config.to).toBe(2500);
        expect(config.duration).toBe(900);
        expect(config.suffix).toBe('+');
        expect(config.trigger).toBe('always');
    });
});
