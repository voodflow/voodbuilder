import { describe, expect, it } from 'vitest';
import {
    applyBackgroundColorWithOpacity,
    BG_COLOR_OPACITY_ATTR,
    BG_COLOR_OPACITY_DARK_ATTR,
    clearBackgroundColorUtilities,
    composeBackgroundColorClass,
    resolveBackgroundColorAndOpacity,
} from '../../resources/js/editor/style-tailwind-class-groups.js';

function mockComponent(initialClasses = [], initialAttrs = {}) {
    const classes = new Set(initialClasses);
    const attrs = { ...initialAttrs };

    return {
        classes,
        attrs,
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
}

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
        const component = mockComponent(['bg-black', 'bg-opacity-65', 'relative']);

        applyBackgroundColorWithOpacity(component, 'bg-black', '65');
        expect([...component.classes].sort()).toEqual(['bg-black', 'relative'].sort());
        expect(component.attrs[BG_COLOR_OPACITY_ATTR]).toBe('65');
    });

    it('authors dark color + opacity without clearing light', () => {
        const component = mockComponent(
            ['bg-slate-200', 'relative'],
            { [BG_COLOR_OPACITY_ATTR]: '40' },
        );

        applyBackgroundColorWithOpacity(component, 'bg-slate-900', '80', 'dark:');

        expect(component.classes.has('bg-slate-200')).toBe(true);
        expect(component.classes.has('dark:bg-slate-900')).toBe(true);
        expect(component.attrs[BG_COLOR_OPACITY_ATTR]).toBe('40');
        expect(component.attrs[BG_COLOR_OPACITY_DARK_ATTR]).toBe('80');

        expect(resolveBackgroundColorAndOpacity(
            [...component.classes],
            component,
            'dark:',
        )).toEqual({
            color: 'bg-slate-900',
            opacity: '80',
            legacyOpacity: false,
        });

        expect(resolveBackgroundColorAndOpacity(
            [...component.classes],
            component,
            '',
        )).toEqual({
            color: 'bg-slate-200',
            opacity: '40',
            legacyOpacity: false,
        });
    });

    it('clears only the dark variant utilities and opacity attr', () => {
        const component = mockComponent(
            ['bg-white', 'dark:bg-black'],
            {
                [BG_COLOR_OPACITY_ATTR]: '50',
                [BG_COLOR_OPACITY_DARK_ATTR]: '70',
            },
        );

        clearBackgroundColorUtilities(component, 'dark:');

        expect(component.classes.has('bg-white')).toBe(true);
        expect(component.classes.has('dark:bg-black')).toBe(false);
        expect(component.attrs[BG_COLOR_OPACITY_ATTR]).toBe('50');
        expect(component.attrs[BG_COLOR_OPACITY_DARK_ATTR]).toBeUndefined();
    });

    it('cascades dark opacity to light when dark attr is unset', () => {
        const component = {
            getAttributes: () => ({ [BG_COLOR_OPACITY_ATTR]: '55' }),
        };

        expect(resolveBackgroundColorAndOpacity(
            ['bg-red-500', 'dark:bg-red-900'],
            component,
            'dark:',
        )).toEqual({
            color: 'bg-red-900',
            opacity: '55',
            legacyOpacity: false,
        });
    });
});
