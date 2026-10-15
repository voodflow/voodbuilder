/**
 * @vitest-environment node
 */

import { afterEach, describe, expect, it } from 'vitest';
import {
    STYLE_UTILITY_GROUPS,
    classSetFromOptions,
    MARGIN_Y_OPTIONS,
} from '../../resources/js/editor/style-tailwind-class-groups.js';
import {
    currentStyleBreakpointPrefix,
    replaceClassGroupAtBreakpoint,
} from '../../resources/js/editor/style-tailwind-breakpoints.js';
import {
    THEME_SCOPED_STYLE_GROUP_IDS,
    healNonThemeScopedThemeClasses,
    healNonThemeScopedThemeClassesForExport,
    isThemeScopedStyleGroupId,
    stripNonThemeScopedThemePrefixes,
    styleGroupVariantPrefix,
} from '../../resources/js/editor/style-theme-scope.js';
import { applyGroup } from '../../resources/js/editor/style-tailwind-panel.js';

function makeDarkDesktopEditor() {
    return {
        __voodbuilderTwStyleApplying: false,
        __voodbuilderStyleThemeDark: true,
        trigger: () => {},
        getDevice: () => 'desktop',
        Devices: { getSelected: () => ({ get: () => 'desktop' }) },
    };
}

function makeComponent(initial = []) {
    let classes = [...initial];
    const attrs = {};

    return {
        getClasses: () => [...classes],
        getClass: () => [...classes],
        setClass: (next) => {
            classes = [...next];
        },
        removeClass: (name) => {
            classes = classes.filter((item) => item !== name);
        },
        addClass: (name) => {
            if (! classes.includes(name)) {
                classes = [...classes, name];
            }
        },
        getId: () => 'iscope',
        get: (key) => (key === 'type' ? 'default' : undefined),
        getAttributes: () => ({ ...attrs }),
        addAttributes: (next) => Object.assign(attrs, next),
        removeAttributes: (key) => {
            if (Array.isArray(key)) {
                key.forEach((item) => delete attrs[item]);

                return;
            }

            delete attrs[key];
        },
        setAttributes: (next) => {
            Object.keys(attrs).forEach((key) => delete attrs[key]);
            Object.assign(attrs, next);
        },
        getStyle: () => ({}),
        addStyle: () => {},
        removeStyle: () => {},
        isRemoved: () => false,
        view: { updateClasses: () => {}, updateStyle: () => {}, updateStyles: () => {} },
    };
}

describe('style group theme scope', () => {
    it('only background paints are theme-scoped', () => {
        for (const id of [
            'background',
            'background-opacity',
            'gradient-direction',
            'gradient-opacity',
            'gradient-from',
            'gradient-via',
            'gradient-to',
            'gradient-from-pos',
            'gradient-via-pos',
            'gradient-to-pos',
            'bg-size',
            'bg-position',
            'bg-repeat',
        ]) {
            expect(isThemeScopedStyleGroupId(id)).toBe(true);
        }

        for (const id of [
            'width',
            'height',
            'max-width',
            'margin',
            'margin-y',
            'padding',
            'padding-x',
            'font-size',
            'font-weight',
            'leading',
            'tracking',
            'text-align',
            'text-color',
            'text-transform',
            'text-decoration',
            'text-gradient-from',
            'border-width',
            'border-t-width',
            'border-style',
            'border-color',
            'rounded',
            'rounded-tl',
            'shadow',
            'shadow-color',
            'drop-shadow',
        ]) {
            expect(isThemeScopedStyleGroupId(id)).toBe(false);
        }

        expect(isThemeScopedStyleGroupId(undefined)).toBe(false);
    });

    it('covers every Style utility group with an explicit scope', () => {
        for (const group of STYLE_UTILITY_GROUPS) {
            expect(THEME_SCOPED_STYLE_GROUP_IDS.includes(group.id)).toBe(
                group.id.startsWith('background')
                || group.id.startsWith('gradient-')
                || group.id.startsWith('bg-'),
            );
        }
    });

    it('resolves the write prefix: breakpoint only unless the group is a background paint', () => {
        expect(currentStyleBreakpointPrefix(makeDarkDesktopEditor())).toBe('lg:');

        // Dark + desktop.
        expect(styleGroupVariantPrefix(makeDarkDesktopEditor(), 'margin-y')).toBe('lg:');
        expect(styleGroupVariantPrefix(makeDarkDesktopEditor(), 'font-size')).toBe('lg:');
        expect(styleGroupVariantPrefix(makeDarkDesktopEditor(), 'rounded')).toBe('lg:');
        expect(styleGroupVariantPrefix(makeDarkDesktopEditor(), 'background')).toBe('dark:lg:');
        expect(styleGroupVariantPrefix(makeDarkDesktopEditor(), 'bg-size')).toBe('dark:lg:');
        expect(styleGroupVariantPrefix(makeDarkDesktopEditor(), 'gradient-from')).toBe('dark:lg:');

        const lightMobileEditor = {
            __voodbuilderStyleThemeDark: false,
            getDevice: () => 'mobilePortrait',
            Devices: { getSelected: () => ({ get: () => 'mobilePortrait' }) },
        };

        expect(styleGroupVariantPrefix(lightMobileEditor, 'margin-y')).toBe('');
        expect(styleGroupVariantPrefix(lightMobileEditor, 'background')).toBe('');
    });
});

describe('non-background dark: healing', () => {
    it('moves a theme prefix back to the breakpoint prefix', () => {
        const { classes, removed } = stripNonThemeScopedThemePrefixes([
            'dark:lg:my-20',
            'dark:text-lg',
            'dark:p-4',
            'dark:rounded-lg',
            'dark:border-2',
            'dark:shadow-lg',
            'dark:border-red-500',
            'w-full',
        ]);

        expect(removed).toEqual([
            'dark:lg:my-20',
            'dark:text-lg',
            'dark:p-4',
            'dark:rounded-lg',
            'dark:border-2',
            'dark:shadow-lg',
            'dark:border-red-500',
        ]);
        expect(classes).toEqual([
            'lg:my-20',
            'text-lg',
            'p-4',
            'rounded-lg',
            'border-2',
            'shadow-lg',
            'border-red-500',
            'w-full',
        ]);
    });

    it('leaves background paints and shared gradient stops untouched', () => {
        const input = [
            'dark:bg-red-500',
            'dark:lg:bg-vp-brand-1',
            'dark:bg-cover',
            'dark:bg-gradient-to-r',
            'dark:from-purple-500',
            'dark:via-white',
            'dark:to-0%',
            'flex',
        ];
        const { classes, removed } = stripNonThemeScopedThemePrefixes(input);

        expect(removed).toEqual([]);
        expect(classes).toEqual(input);
    });

    it('never duplicates a class that is already neutral', () => {
        const { classes, removed } = stripNonThemeScopedThemePrefixes([
            'lg:my-20',
            'dark:lg:my-20',
        ]);

        expect(removed).toEqual(['dark:lg:my-20']);
        expect(classes).toEqual(['lg:my-20']);
    });

    it('heals a component through the CLASSES remove/add path', () => {
        const component = makeComponent(['w-full', 'dark:lg:my-20', 'dark:text-lg', 'dark:bg-red-500']);

        expect(healNonThemeScopedThemeClasses(component)).toBe(2);
        expect(component.getClasses()).toEqual(['w-full', 'dark:bg-red-500', 'lg:my-20', 'text-lg']);
    });

    it('heals every component under the export root', () => {
        const first = makeComponent(['dark:my-4']);
        const second = makeComponent(['dark:bg-red-500']);
        const editor = {
            getWrapper: () => ({
                onAll: (callback) => {
                    callback(first);
                    callback(second);
                },
            }),
        };

        expect(healNonThemeScopedThemeClassesForExport(editor)).toBe(1);
        expect(first.getClasses()).toEqual(['my-4']);
        expect(second.getClasses()).toEqual(['dark:bg-red-500']);
        expect(editor.__voodbuilderTwStyleApplying).toBe(false);
    });
});

describe('authoring policy end to end', () => {
    it('writes spacing without a dark: prefix while the Dark toggle is active', () => {
        const component = makeComponent(['w-full']);

        applyGroup(makeDarkDesktopEditor(), component, 'margin-y', 'my-20');

        expect(component.getClasses()).toContain('lg:my-20');
        expect(component.getClasses()).not.toContain('dark:lg:my-20');
    });

    it('removes a legacy dark: spacing utility when the group is rewritten', () => {
        const component = makeComponent(['w-full', 'dark:lg:my-20']);

        applyGroup(makeDarkDesktopEditor(), component, 'margin-y', 'my-24');

        expect(component.getClasses()).toContain('lg:my-24');
        expect(component.getClasses()).not.toContain('dark:lg:my-20');
    });

    it('mirrors the panel write: background colors keep the dark: prefix', () => {
        let classes = ['w-full'];
        const component = {
            getClasses: () => [...classes],
            removeClass: (name) => {
                classes = classes.filter((item) => item !== name);
            },
            addClass: (name) => {
                if (! classes.includes(name)) {
                    classes = [...classes, name];
                }
            },
        };

        replaceClassGroupAtBreakpoint(
            component,
            classSetFromOptions(MARGIN_Y_OPTIONS),
            'my-20',
            styleGroupVariantPrefix(makeDarkDesktopEditor(), 'margin-y'),
        );
        replaceClassGroupAtBreakpoint(
            component,
            classSetFromOptions(STYLE_UTILITY_GROUPS.find((group) => group.id === 'background').options),
            'bg-red-500',
            styleGroupVariantPrefix(makeDarkDesktopEditor(), 'background'),
        );

        expect(classes).toContain('lg:my-20');
        expect(classes).toContain('dark:lg:bg-red-500');
        expect(classes).not.toContain('dark:lg:my-20');
    });
});

afterEach(() => {
    delete globalThis.window;
});
