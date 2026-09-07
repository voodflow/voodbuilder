/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    ANIMATION_CLASS_SET,
    DURATION_CLASS_SET,
    applyModifierWithInteraction,
    reprefixAnimationModifiers,
    replaceClassGroup,
} from '../../resources/js/editor/style-animation-sector.js';

function mockComponent(initial = []) {
    let classes = [...initial];
    const calls = [];

    return {
        calls,
        getClasses: () => [...classes],
        // Broken setClass — historical Animation sector bug: races SelectorManager
        // and can wipe utilities so Cycle duration never lands in Classes.
        setClass: (next) => {
            calls.push(['setClass', next]);
            classes = [];
        },
        addClass: (name) => {
            calls.push(['addClass', name]);

            if (! classes.includes(name)) {
                classes = [...classes, name];
            }
        },
        removeClass: (name) => {
            calls.push(['removeClass', name]);
            classes = classes.filter((item) => item !== name);
        },
    };
}

function mockRoot(fields) {
    return {
        querySelector: (selector) => {
            const match = String(selector).match(/\[([^\]]+)\]/);
            const attr = match?.[1];

            if (! attr || ! Object.prototype.hasOwnProperty.call(fields, attr)) {
                return null;
            }

            return { value: fields[attr] ?? '' };
        },
    };
}

describe('style-animation-sector class persistence', () => {
    it('does not rely on setClass when applying cycle duration', () => {
        const component = mockComponent(['animate-spin', 'p-4']);

        replaceClassGroup(component, DURATION_CLASS_SET, 'animate-duration-8000');

        expect(component.getClasses()).toEqual([
            'animate-spin',
            'p-4',
            'animate-duration-8000',
        ]);
        expect(component.calls.some((entry) => entry[0] === 'setClass')).toBe(false);
        expect(component.calls).toContainEqual(['addClass', 'animate-duration-8000']);
    });

    it('applies cycle duration with the interaction prefix via the modifier helper', () => {
        const component = mockComponent(['hover:animate-spin']);
        const root = mockRoot({
            'data-voodbuilder-anim-interaction': 'hover:',
            'data-voodbuilder-anim-duration': 'animate-duration-8000',
            'data-voodbuilder-anim-type': 'animate-spin',
        });

        applyModifierWithInteraction(
            component,
            root,
            DURATION_CLASS_SET,
            '[data-voodbuilder-anim-duration]',
        );

        expect(component.getClasses()).toContain('hover:animate-duration-8000');
        expect(component.getClasses()).toContain('hover:animate-spin');
        expect(component.getClasses()).not.toContain('animate-duration-8000');
        expect(component.calls.some((entry) => entry[0] === 'setClass')).toBe(false);
    });

    it('reprefixes duration without wiping animation via setClass', () => {
        const component = mockComponent([
            'hover:animate-bounce',
            'animate-duration-8000',
            'text-lg',
        ]);

        reprefixAnimationModifiers(component, 'hover:');

        const classes = component.getClasses();

        expect(classes).toContain('hover:animate-bounce');
        expect(classes).toContain('hover:animate-duration-8000');
        expect(classes).toContain('text-lg');
        expect(classes).not.toContain('animate-duration-8000');
        expect(component.calls.some((entry) => entry[0] === 'setClass')).toBe(false);
    });

    it('swaps animation presets without dropping unrelated classes', () => {
        const component = mockComponent([
            'animate-fade',
            'animate-duration-8000',
            'animate-infinite',
            'rounded-lg',
        ]);

        replaceClassGroup(component, ANIMATION_CLASS_SET, 'animate-bounce');

        expect(component.getClasses()).toEqual([
            'animate-duration-8000',
            'animate-infinite',
            'rounded-lg',
            'animate-bounce',
        ]);
    });
});
