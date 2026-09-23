import { describe, expect, it } from 'vitest';
import {
    applyBackgroundColorWithOpacity,
    BG_COLOR_OPACITY_ATTR,
    composeBackgroundColorClass,
    resolveBackgroundColorAndOpacity,
} from '../../resources/js/editor/style-tailwind-class-groups.js';

describe('background color opacity', () => {
    it('parses slash, legacy, and data-attr forms', () => {
        expect(resolveBackgroundColorAndOpacity(['bg-black/50'])).toEqual({
            color: 'bg-black',
            opacity: '50',
            legacyOpacity: true,
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

        const component = {
            getAttributes: () => ({ [BG_COLOR_OPACITY_ATTR]: '60' }),
        };
        expect(resolveBackgroundColorAndOpacity(['bg-black'], component)).toEqual({
            color: 'bg-black',
            opacity: '60',
            legacyOpacity: false,
        });
    });

    it('keeps plain Grapes-safe color class', () => {
        expect(composeBackgroundColorClass('bg-black')).toBe('bg-black');
        expect(composeBackgroundColorClass('bg-black/50')).toBe('');
    });

    it('applies color + opacity attr and clears legacy opacity', () => {
        const classes = new Set(['bg-black', 'bg-opacity-65', 'relative']);
        const attrs = {};
        const component = {
            getClasses: () => [...classes],
            addClass: (name) => { classes.add(name); },
            removeClass: (name) => { classes.delete(name); },
            getAttributes: () => ({ ...attrs }),
            addAttributes: (next) => { Object.assign(attrs, next); },
            removeAttributes: (key) => { delete attrs[key]; },
            setAttributes: (next) => {
                Object.keys(attrs).forEach((k) => delete attrs[k]);
                Object.assign(attrs, next);
            },
        };

        applyBackgroundColorWithOpacity(component, 'bg-black', '65');
        expect([...classes].sort()).toEqual(['bg-black', 'relative'].sort());
        expect(attrs[BG_COLOR_OPACITY_ATTR]).toBe('65');
    });
});
