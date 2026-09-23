import { describe, expect, it } from 'vitest';
import {
    applyBackgroundColorWithOpacity,
    composeBackgroundColorClass,
    resolveBackgroundColorAndOpacity,
} from '../../resources/js/editor/style-tailwind-class-groups.js';

describe('background color opacity', () => {
    it('parses slash and legacy bg-opacity forms', () => {
        expect(resolveBackgroundColorAndOpacity(['bg-black/50'])).toEqual({
            color: 'bg-black',
            opacity: '50',
            legacyOpacity: false,
        });
        expect(resolveBackgroundColorAndOpacity(['bg-black', 'bg-opacity-65'])).toEqual({
            color: 'bg-black',
            opacity: '65',
            legacyOpacity: true,
        });
        expect(resolveBackgroundColorAndOpacity(['bg-red-700'])).toEqual({
            color: 'bg-red-700',
            opacity: '',
            legacyOpacity: false,
        });
    });

    it('composes TW4 slash utilities', () => {
        expect(composeBackgroundColorClass('bg-black', '')).toBe('bg-black');
        expect(composeBackgroundColorClass('bg-black', '50')).toBe('bg-black/50');
        expect(composeBackgroundColorClass('bg-black', '100')).toBe('bg-black');
    });

    it('applies color+opacity and clears legacy opacity', () => {
        const classes = new Set(['bg-black', 'bg-opacity-65', 'relative']);
        const component = {
            getClasses: () => [...classes],
            addClass: (name) => { classes.add(name); },
            removeClass: (name) => { classes.delete(name); },
        };

        applyBackgroundColorWithOpacity(component, 'bg-black', '65');
        expect([...classes].sort()).toEqual(['bg-black/65', 'relative'].sort());
    });
});
