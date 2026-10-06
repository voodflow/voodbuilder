/**
 * @vitest-environment node
 */

import { describe, expect, it, vi } from 'vitest';
import {
    BACKGROUND_OPTIONS,
    BORDER_B_WIDTH_OPTIONS,
    BORDER_L_WIDTH_OPTIONS,
    BORDER_R_WIDTH_OPTIONS,
    BORDER_T_WIDTH_OPTIONS,
    BORDER_WIDTH_OPTIONS,
    FONT_SIZE_OPTIONS,
    PADDING_Y_OPTIONS,
    ROUNDED_L_OPTIONS,
    ROUNDED_OPTIONS,
    ROUNDED_T_OPTIONS,
    ROUNDED_TL_OPTIONS,
    ROUNDED_BR_OPTIONS,
    SHADOW_OPTIONS,
    STYLE_UTILITY_GROUPS,
    WIDTH_OPTIONS,
    classSetFromOptions,
    hasAuthoredStyleUtilities,
    hasTextGradientClasses,
    replaceClassGroup,
    resolveGroupValue,
    resolveSolidTextColor,
} from '../../resources/js/editor/style-tailwind-class-groups.js';
import {
    cascadePrefixesFor,
    currentStyleBreakpointPrefix,
    deviceIdToBreakpointPrefix,
    replaceClassGroupAllBreakpoints,
    replaceClassGroupAtBreakpoint,
    resolveGroupValueAtBreakpoint,
    stripResponsivePrefix,
} from '../../resources/js/editor/style-tailwind-breakpoints.js';
import {
    syncSelectsFromComponent,
    watchComponentClassList,
    resolveSpacingState,
    spacingGroupFor,
    applySpacingToken,
    setSpacingLinkMode,
    rememberStyleSubject,
    styleWriteTarget,
    resolveBackgroundFadeColor,
    readBackgroundImageUrl,
    readDisplayedBackgroundImageUrl,
    clearDecorationBackgroundImage,
} from '../../resources/js/editor/style-tailwind-panel.js';
import {
    composeDecorationBackgroundImageCss,
    STYLE_BG_SRC_ATTR,
    STYLE_BG_SRC_DARK_ATTR,
} from '../../resources/js/editor/style-background-image.js';
import { getDarkIdStyles, setDarkIdStyles } from '../../resources/js/editor/dark-id-styles.js';
import { STYLE_MANAGER_SECTORS } from '../../resources/js/editor/editor-chrome.js';
import {
    filterOutConflictingBoxSpacingClasses,
} from '../../resources/js/editor/spacing-utility-sync.js';

describe('style tailwind class groups', () => {
    it('exposes dimension / decoration / typography utility groups', () => {
        const ids = STYLE_UTILITY_GROUPS.map((group) => group.id);

        expect(ids).toContain('width');
        expect(ids).toContain('padding');
        expect(ids).toContain('background');
        expect(ids).toContain('shadow');
        expect(ids).toContain('font-size');
        expect(ids).toContain('text-color');
        expect(ids).toContain('text-gradient-direction');
        expect(ids).toContain('tracking');
        expect(ids).toContain('text-transform');
        expect(ids).toContain('text-decoration');
        expect(ids).not.toContain('text-shadow');
    });

    it('resolves authored utilities and leaves empty when none match', () => {
        expect(resolveGroupValue(['p-4', 'text-lg'], FONT_SIZE_OPTIONS)).toBe('text-lg');
        expect(resolveGroupValue(['flex', 'gap-4'], WIDTH_OPTIONS)).toBe('');
        expect(hasAuthoredStyleUtilities(['rounded-lg', 'shadow-md'])).toBe(true);
        expect(hasAuthoredStyleUtilities(['flex', 'items-center'])).toBe(false);
    });

    it('resolves padding-y after manual class swap (py-12 → py-24)', () => {
        expect(resolveGroupValue(['py-12', 'w-full'], PADDING_Y_OPTIONS)).toBe('py-12');
        expect(resolveGroupValue(['py-24', 'w-full'], PADDING_Y_OPTIONS)).toBe('py-24');
        expect(resolveGroupValue(['py-4', 'w-full'], PADDING_Y_OPTIONS)).toBe('py-4');
        expect(resolveGroupValue(['w-full'], PADDING_Y_OPTIONS)).toBe('');
    });

    it('lists full corner rounded literals for Tailwind @source scanning', () => {
        expect(ROUNDED_TL_OPTIONS.some((opt) => opt.value === 'rounded-tl-3xl')).toBe(true);
        expect(ROUNDED_BR_OPTIONS.some((opt) => opt.value === 'rounded-br-full')).toBe(true);
        expect(BORDER_T_WIDTH_OPTIONS.some((opt) => opt.value === 'border-t-2')).toBe(true);
    });

    it('replaces exclusive utility groups atomically', () => {
        let classes = ['w-full', 'p-4', 'text-lg'];
        const component = {
            getClasses: () => [...classes],
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
        };

        const widthSet = classSetFromOptions(WIDTH_OPTIONS);
        replaceClassGroup(component, widthSet, 'w-1/2');

        expect(classes).toContain('w-1/2');
        expect(classes).not.toContain('w-full');
        expect(classes).toContain('p-4');
        expect(classes).toContain('text-lg');

        replaceClassGroup(component, widthSet, null);
        expect(classes).not.toContain('w-1/2');
        expect(classes).toContain('p-4');
    });

    it('uses removeClass + addClass (CLASSES + path) and never dual-writes attributes.class', () => {
        let classes = ['w-full', 'p-4', 'text-lg'];
        const calls = [];
        const component = {
            getClasses: () => [...classes],
            setClass: (next) => {
                calls.push(['setClass', [...next]]);
                classes = [...next];
            },
            removeClass: (name) => {
                calls.push(['removeClass', name]);
                classes = classes.filter((item) => item !== name);
            },
            addClass: (name) => {
                calls.push(['addClass', name]);
                if (! classes.includes(name)) {
                    classes = [...classes, name];
                }
            },
            addAttributes: (attrs) => {
                calls.push(['addAttributes', attrs]);
            },
        };

        const widthSet = classSetFromOptions(WIDTH_OPTIONS);
        replaceClassGroup(component, widthSet, 'w-1/2');

        expect(classes).toEqual(['p-4', 'text-lg', 'w-1/2']);
        expect(calls.some((entry) => entry[0] === 'addAttributes')).toBe(false);
        expect(calls.some((entry) => entry[0] === 'setClass')).toBe(false);
        expect(calls).toContainEqual(['removeClass', 'w-full']);
        expect(calls).toContainEqual(['addClass', 'w-1/2']);
    });

    it('recovers tokens when SelectorManager needs an explicit addClass', () => {
        let classes = ['w-full', 'p-4', 'text-lg'];
        let addPasses = 0;
        const component = {
            getClasses: () => [...classes],
            setClass: () => {
                // Intentionally unused — replaceClassGroup must not rely on setClass.
            },
            addClass: (name) => {
                addPasses += 1;
                // First addClass is ignored (Grapes race); second sticks.
                if (addPasses >= 2 && ! classes.includes(name)) {
                    classes = [...classes, name];
                }
            },
            removeClass: (name) => {
                classes = classes.filter((item) => item !== name);
            },
        };

        replaceClassGroup(component, classSetFromOptions(WIDTH_OPTIONS), 'w-1/2');

        expect(classes).toContain('w-1/2');
        expect(classes).toContain('p-4');
        expect(classes).not.toContain('w-full');
    });

    it('keeps padding when swapping background utilities', () => {
        let classes = [
            'voodbuilder-editor-container',
            'vb-layout-row',
            'gap-4',
            'w-full',
            'bg-vp-brand-1',
            'p-6',
            'grid',
            'mx-auto',
            'max-w-[80rem]',
        ];
        const component = {
            getClasses: () => [...classes],
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
        };

        const backgroundSet = classSetFromOptions(BACKGROUND_OPTIONS);
        replaceClassGroup(component, backgroundSet, 'bg-vp-brand-2');

        expect(classes).toContain('p-6');
        expect(classes).toContain('bg-vp-brand-2');
        expect(classes).not.toContain('bg-vp-brand-1');
        expect(classes).toContain('gap-4');
        expect(classes).toContain('mx-auto');
    });

    it('detects gradient text mode from clip utilities or gradient stops with transparent text', () => {
        expect(hasTextGradientClasses([
            'bg-clip-text',
            'text-transparent',
            'bg-gradient-to-r',
            'from-purple-500',
            'to-green-500',
        ])).toBe(true);

        expect(hasTextGradientClasses([
            'text-transparent',
            'bg-gradient-to-r',
            'from-purple-500',
            'to-green-800',
        ])).toBe(true);

        expect(hasTextGradientClasses(['text-purple-500'])).toBe(false);
        expect(resolveSolidTextColor(['text-purple-500'])).toBe('text-purple-500');
        expect(resolveSolidTextColor([
            'bg-clip-text',
            'text-transparent',
            'from-purple-500',
            'bg-gradient-to-r',
            'to-green-500',
        ])).toBe('');
    });

    it('includes common decoration utilities without invented shadow defaults', () => {
        expect(BACKGROUND_OPTIONS.some((opt) => opt.value === 'bg-vp-brand-1')).toBe(true);
        expect(SHADOW_OPTIONS.some((opt) => opt.value === 'shadow-lg')).toBe(true);
        expect(SHADOW_OPTIONS.some((opt) => opt.value === 'shadow-xs')).toBe(true);
        expect(SHADOW_OPTIONS.every((opt) => opt.value === '' || opt.value.startsWith('shadow'))).toBe(true);
        expect(STYLE_UTILITY_GROUPS.some((group) => group.id === 'shadow-color')).toBe(true);
        expect(STYLE_UTILITY_GROUPS.some((group) => group.id === 'drop-shadow')).toBe(true);
        expect(STYLE_UTILITY_GROUPS.some((group) => group.id === 'bg-size')).toBe(true);
        expect(STYLE_UTILITY_GROUPS.some((group) => group.id === 'bg-position')).toBe(true);
        expect(STYLE_UTILITY_GROUPS.some((group) => group.id === 'bg-repeat')).toBe(true);

        const bgSize = STYLE_UTILITY_GROUPS.find((group) => group.id === 'bg-size');
        const bgPosition = STYLE_UTILITY_GROUPS.find((group) => group.id === 'bg-position');
        const bgRepeat = STYLE_UTILITY_GROUPS.find((group) => group.id === 'bg-repeat');
        const shadowColor = STYLE_UTILITY_GROUPS.find((group) => group.id === 'shadow-color');

        // Size/position/repeat must not clear inline background-* paint (image wipe bug).
        expect(bgSize?.inlineProps ?? []).toEqual([]);
        expect(bgPosition?.inlineProps ?? []).toEqual([]);
        expect(bgRepeat?.inlineProps ?? []).toEqual([]);
        expect(shadowColor?.options?.some((opt) => opt.value === 'shadow-red-500')).toBe(true);
    });
});

describe('live class → style panel sync', () => {
    function makeClassCollection(initial = []) {
        const listeners = new Map();
        let models = [...initial];

        return {
            get models() {
                return models;
            },
            on(events, handler) {
                for (const event of String(events).split(/\s+/).filter(Boolean)) {
                    if (! listeners.has(event)) {
                        listeners.set(event, new Set());
                    }

                    listeners.get(event).add(handler);
                }
            },
            off(events, handler) {
                for (const event of String(events).split(/\s+/).filter(Boolean)) {
                    listeners.get(event)?.delete(handler);
                }
            },
            trigger(event) {
                for (const handler of listeners.get(event) ?? []) {
                    handler();
                }
            },
            add(name) {
                models.push(name);
                this.trigger('add');
            },
            remove(name) {
                models = models.filter((item) => item !== name);
                this.trigger('remove');
            },
            reset(next = []) {
                models = [...next];
                this.trigger('reset');
            },
        };
    }

    it('watchComponentClassList notifies on chip-like collection mutations', () => {
        const classes = makeClassCollection(['py-12']);
        const component = {
            get: (key) => (key === 'classes' ? classes : undefined),
            getClasses: () => [...classes.models],
        };
        const onChange = vi.fn();
        const stop = watchComponentClassList(component, onChange);

        classes.remove('py-12');
        classes.add('py-24');
        classes.trigger('change');

        expect(onChange).toHaveBeenCalledTimes(3);

        stop();
        classes.add('py-4');
        expect(onChange).toHaveBeenCalledTimes(3);
    });

    it('syncSelectsFromComponent hydrates padding-y from live classes without refresh', () => {
        const root = {
            querySelector(selector) {
                if (
                    String(selector).includes('data-voodbuilder-spacing-box')
                    || String(selector).includes('data-voodbuilder-decorations')
                    || String(selector).includes('data-voodbuilder-tw-font-family')
                    || String(selector).includes('data-voodbuilder-typo-seg')
                ) {
                    return null;
                }

                if (! this._els) {
                    this._els = {};
                }

                if (! this._els[selector]) {
                    this._els[selector] = {
                        value: '',
                        dispatchEvent: () => {},
                    };
                }

                return this._els[selector];
            },
        };

        let classes = ['w-full', 'py-12'];
        const component = {
            getClasses: () => [...classes],
            getStyle: () => ({}),
        };

        syncSelectsFromComponent(root, component, null);
        expect(root.querySelector('[data-voodbuilder-tw-group="padding-y"]').value).toBe('py-12');

        classes = ['w-full', 'py-24'];
        syncSelectsFromComponent(root, component, null);
        expect(root.querySelector('[data-voodbuilder-tw-group="padding-y"]').value).toBe('py-24');

        classes = ['w-full', 'py-4'];
        syncSelectsFromComponent(root, component, null);
        expect(root.querySelector('[data-voodbuilder-tw-group="padding-y"]').value).toBe('py-4');
    });
});

describe('native Style Manager sectors', () => {
    it('are empty so Grapes does not invent inline style sectors', () => {
        expect(STYLE_MANAGER_SECTORS).toEqual([]);
    });
});

describe('content-width margin vs author padding', () => {
    it('keeps p-6 when only horizontal margins change', () => {
        const kept = filterOutConflictingBoxSpacingClasses(
            [
                'voodbuilder-editor-container',
                'vb-layout-row',
                'gap-4',
                'w-full',
                'bg-vp-brand-1',
                'p-6',
                'grid',
                'mx-auto',
                'max-w-[80rem]',
            ],
            ['margin-left', 'margin-right'],
        );

        expect(kept).toContain('p-6');
        expect(kept).toContain('bg-vp-brand-1');
        expect(kept).not.toContain('mx-auto');
    });
});

describe('decoration conflict groups', () => {
    it('clears side border widths when applying shorthand border', () => {
        let classes = ['w-full', 'border-t-4', 'border-solid'];
        const component = {
            getClasses: () => [...classes],
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
        };

        const borderSet = classSetFromOptions(BORDER_WIDTH_OPTIONS);
        const alsoClear = ['border-t-width', 'border-r-width', 'border-b-width', 'border-l-width']
            .map((id) => classSetFromOptions(
                id === 'border-t-width' ? BORDER_T_WIDTH_OPTIONS
                    : id === 'border-r-width' ? BORDER_R_WIDTH_OPTIONS
                        : id === 'border-b-width' ? BORDER_B_WIDTH_OPTIONS
                            : BORDER_L_WIDTH_OPTIONS,
            ));

        replaceClassGroup(component, borderSet, 'border-2', { alsoClear });

        expect(classes).toContain('border-2');
        expect(classes).toContain('border-solid');
        expect(classes).not.toContain('border-t-4');
    });

    it('clears rounded shorthand when applying a corner', () => {
        let classes = ['w-full', 'rounded-lg'];
        const component = {
            getClasses: () => [...classes],
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
        };

        const cornerSet = classSetFromOptions(ROUNDED_TL_OPTIONS);
        const alsoClear = [
            classSetFromOptions(ROUNDED_OPTIONS),
            classSetFromOptions(ROUNDED_T_OPTIONS),
            classSetFromOptions(ROUNDED_L_OPTIONS),
        ];

        replaceClassGroup(component, cornerSet, 'rounded-tl-full', { alsoClear });

        expect(classes).toContain('rounded-tl-full');
        expect(classes).not.toContain('rounded-lg');
    });
});

describe('spacing link modes (opposites vs all)', () => {
    function makeComponent(initial = []) {
        let classes = [...initial];

        return {
            getClasses: () => [...classes],
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
            addAttributes: () => {},
            removeAttributes: () => {},
            getStyle: () => ({}),
            view: { render: () => {} },
        };
    }

    function makeEditor() {
        return {
            __voodbuilderTwStyleApplying: false,
            trigger: () => {},
            getDevice: () => 'mobilePortrait',
            Devices: {
                getSelected: () => ({ get: (key) => (key === 'id' ? 'mobilePortrait' : null) }),
            },
        };
    }

    it('maps opposites top/bottom to py and left/right to px', () => {
        expect(spacingGroupFor('padding', 't', 'opposites').prefix).toBe('py');
        expect(spacingGroupFor('padding', 'b', 'opposites').prefix).toBe('py');
        expect(spacingGroupFor('padding', 'l', 'opposites').prefix).toBe('px');
        expect(spacingGroupFor('padding', 'r', 'opposites').prefix).toBe('px');
        expect(spacingGroupFor('padding', 't', 'all').prefix).toBe('p');
    });

    it('resolves py+px as opposites, not all', () => {
        const state = resolveSpacingState('padding', ['w-full', 'py-8', 'px-8']);
        expect(state.link).toBe('opposites');
        expect(state.sides).toEqual({ t: '8', r: '8', b: '8', l: '8' });
    });

    it('editing top in opposites updates only the Y axis', () => {
        const editor = makeEditor();
        const component = makeComponent(['w-full', 'py-8', 'px-8']);

        applySpacingToken(editor, component, 'padding', 't', '4', 'opposites');

        const classes = component.getClasses();
        expect(classes).toContain('py-4');
        expect(classes).toContain('px-8');
        expect(classes).not.toContain('p-4');
        expect(classes).not.toContain('py-8');

        const state = resolveSpacingState('padding', classes);
        expect(state.link).toBe('opposites');
        expect(state.sides).toEqual({ t: '4', r: '8', b: '4', l: '8' });
    });

    it('editing top in all updates the shorthand on every side', () => {
        const editor = makeEditor();
        const component = makeComponent(['w-full', 'p-8']);

        applySpacingToken(editor, component, 'padding', 't', '4', 'all');

        const classes = component.getClasses();
        expect(classes).toContain('p-4');
        expect(classes).not.toContain('py-4');
        expect(classes).not.toContain('px-4');

        const state = resolveSpacingState('padding', classes);
        expect(state.link).toBe('all');
        expect(state.sides).toEqual({ t: '4', r: '4', b: '4', l: '4' });
    });

    it('switching to opposites from a single side does not copy the token to the other axis', () => {
        const editor = makeEditor();
        const component = makeComponent(['w-full', 'pt-4']);

        setSpacingLinkMode(editor, component, 'padding', 'opposites');

        const classes = component.getClasses();
        expect(classes).toContain('py-4');
        expect(classes).not.toContain('px-4');
        expect(classes).not.toContain('p-4');
        expect(classes).not.toContain('pt-4');
    });

    it('switching to opposites from shorthand expands to both axes', () => {
        const editor = makeEditor();
        const component = makeComponent(['w-full', 'p-8']);

        setSpacingLinkMode(editor, component, 'padding', 'opposites');

        const classes = component.getClasses();
        expect(classes).toContain('py-8');
        expect(classes).toContain('px-8');
        expect(classes).not.toContain('p-8');
    });

    it('opposites edit expands leftover shorthand so the other axis is kept', () => {
        const editor = makeEditor();
        const component = makeComponent(['w-full', 'p-8']);

        applySpacingToken(editor, component, 'padding', 't', '4', 'opposites');

        const classes = component.getClasses();
        expect(classes).toContain('py-4');
        expect(classes).toContain('px-8');
        expect(classes).not.toContain('p-8');
    });
});

describe('style viewport breakpoints', () => {
    it('maps grapes devices to Tailwind prefixes', () => {
        expect(deviceIdToBreakpointPrefix('mobilePortrait')).toBe('');
        expect(deviceIdToBreakpointPrefix('tablet')).toBe('md:');
        expect(deviceIdToBreakpointPrefix('desktop')).toBe('lg:');
        expect(cascadePrefixesFor('lg:')).toEqual(['lg:', 'md:', '']);
        expect(cascadePrefixesFor('md:')).toEqual(['md:', '']);
        expect(cascadePrefixesFor('')).toEqual(['']);
        expect(stripResponsivePrefix('md:text-6xl')).toBe('text-6xl');
    });

    it('reads current device from editor', () => {
        const editor = {
            getDevice: () => 'tablet',
            Devices: { getSelected: () => ({ get: () => 'tablet' }) },
        };

        expect(currentStyleBreakpointPrefix(editor)).toBe('md:');
    });

    it('resolves cascade: exact breakpoint then smaller then base', () => {
        const classes = ['text-4xl', 'md:text-6xl', 'lg:text-7xl'];

        expect(resolveGroupValueAtBreakpoint(classes, FONT_SIZE_OPTIONS, '')).toBe('text-4xl');
        expect(resolveGroupValueAtBreakpoint(classes, FONT_SIZE_OPTIONS, 'md:')).toBe('text-6xl');
        expect(resolveGroupValueAtBreakpoint(classes, FONT_SIZE_OPTIONS, 'lg:')).toBe('text-7xl');
        expect(resolveGroupValueAtBreakpoint(['text-4xl'], FONT_SIZE_OPTIONS, 'md:')).toBe('text-4xl');
        expect(resolveGroupValueAtBreakpoint(['text-4xl', 'md:text-6xl'], FONT_SIZE_OPTIONS, 'lg:')).toBe('text-6xl');
    });

    it('writes prefixed utilities without removing other breakpoints', () => {
        let classes = ['text-4xl', 'md:text-5xl'];
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

        const fontSet = classSetFromOptions(FONT_SIZE_OPTIONS);
        replaceClassGroupAtBreakpoint(component, fontSet, 'text-6xl', 'md:');

        expect(classes).toContain('text-4xl');
        expect(classes).toContain('md:text-6xl');
        expect(classes).not.toContain('md:text-5xl');
    });

    it('clear at breakpoint only removes that prefix', () => {
        let classes = ['text-4xl', 'md:text-6xl', 'lg:text-7xl'];
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

        const fontSet = classSetFromOptions(FONT_SIZE_OPTIONS);
        replaceClassGroupAtBreakpoint(component, fontSet, null, 'md:');

        expect(classes).toContain('text-4xl');
        expect(classes).toContain('lg:text-7xl');
        expect(classes).not.toContain('md:text-6xl');
    });

    it('gradient utilities write at base and clear leftover md:/lg: stops', () => {
        let classes = ['lg:from-red-500', 'md:to-green-500', 'bg-gradient-to-r'];
        const fromSet = classSetFromOptions(
            STYLE_UTILITY_GROUPS.find((g) => g.id === 'gradient-from').options,
        );
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

        replaceClassGroupAllBreakpoints(component, fromSet, 'from-purple-500');

        expect(classes).toContain('from-purple-500');
        expect(classes).toContain('bg-gradient-to-r');
        expect(classes).toContain('md:to-green-500');
        expect(classes).not.toContain('lg:from-red-500');
    });

    it('spacing state resolves md: padding at tablet prefix', () => {
        const state = resolveSpacingState('padding', ['p-4', 'md:py-12'], 'md:');

        expect(state.link).toBe('opposites');
        expect(state.sides.t).toBe('12');
        expect(state.sides.b).toBe('12');
    });

    it('authors dark: variants and cascades dark → light', () => {
        expect(cascadePrefixesFor('dark:lg:')).toEqual(['dark:lg:', 'dark:md:', 'dark:', 'lg:', 'md:', '']);
        expect(cascadePrefixesFor('dark:')).toEqual(['dark:', '']);

        const classes = ['text-4xl', 'dark:text-6xl', 'dark:lg:text-7xl'];

        expect(resolveGroupValueAtBreakpoint(classes, FONT_SIZE_OPTIONS, 'dark:')).toBe('text-6xl');
        expect(resolveGroupValueAtBreakpoint(classes, FONT_SIZE_OPTIONS, 'dark:lg:')).toBe('text-7xl');
        expect(resolveGroupValueAtBreakpoint(['text-4xl'], FONT_SIZE_OPTIONS, 'dark:md:')).toBe('text-4xl');

        let next = ['text-4xl'];
        const component = {
            getClasses: () => [...next],
            removeClass: (name) => {
                next = next.filter((item) => item !== name);
            },
            addClass: (name) => {
                if (! next.includes(name)) {
                    next = [...next, name];
                }
            },
        };

        const fontSet = classSetFromOptions(FONT_SIZE_OPTIONS);
        replaceClassGroupAtBreakpoint(component, fontSet, 'text-5xl', 'dark:md:');

        expect(next).toContain('text-4xl');
        expect(next).toContain('dark:md:text-5xl');
    });
});

describe('style write target', () => {
    function makeEditor() {
        const wrapper = { get: (key) => (key === 'type' ? 'wrapper' : undefined), getId: () => 'wrap', find: vi.fn(() => []) };
        const editor = { selected: null, getWrapper: () => wrapper, getSelected: () => editor.selected };

        return { editor, wrapper };
    }

    function makeSection(id) {
        let removed = false;

        return {
            get: () => 'default',
            getId: () => id,
            isRemoved: () => removed,
            remove: () => {
                removed = true;
            },
        };
    }

    it('keeps writing to the section when selection drops, never to the page', () => {
        const { editor, wrapper } = makeEditor();
        const section = makeSection('cta1');

        editor.selected = section;
        rememberStyleSubject(editor, section);
        editor.selected = null;

        expect(styleWriteTarget(editor)).toBe(section);
        expect(styleWriteTarget(editor)).not.toBe(wrapper);
    });

    it('re-resolves a remounted section by id and returns null when it is gone', () => {
        const { editor, wrapper } = makeEditor();
        const section = makeSection('cta1');
        const remounted = makeSection('cta1');

        rememberStyleSubject(editor, section);
        section.remove();
        wrapper.find.mockReturnValueOnce([remounted]).mockReturnValue([]);

        expect(styleWriteTarget(editor)).toBe(remounted);
        expect(wrapper.find).toHaveBeenCalledWith('#cta1');

        remounted.remove();

        expect(styleWriteTarget(editor)).toBeNull();
    });

    it('targets the page wrapper only when the page is the explicit subject', () => {
        const { editor, wrapper } = makeEditor();

        rememberStyleSubject(editor, null);

        expect(styleWriteTarget(editor)).toBe(wrapper);
    });
});

describe('photo color scrim uses Style theme Color', () => {
    it('resolves dark:bg-orange-500 for the fade overlay instead of near-black vp-bg', () => {
        const editor = { __voodbuilderStyleThemeDark: true };
        const component = {
            getClasses: () => ['dark:bg-orange-500', 'relative'],
            getAttributes: () => ({}),
            getEl: () => null,
        };

        const fade = resolveBackgroundFadeColor(editor, component);

        expect(fade.toLowerCase()).toBe('#f97316');

        // Photo visibility 35% → 65% orange scrim over the url (what the canvas should show).
        expect(composeDecorationBackgroundImageCss('/hero.jpg', 0.35, fade)).toBe(
            "linear-gradient(rgba(249, 115, 22, 0.65), rgba(249, 115, 22, 0.65)), url('/hero.jpg')",
        );
    });

    it('cascades light bg color when dark companion is unset', () => {
        const editor = { __voodbuilderStyleThemeDark: true };
        const component = {
            getClasses: () => ['bg-orange-100'],
            getAttributes: () => ({}),
            getEl: () => null,
        };

        expect(resolveBackgroundFadeColor(editor, component).toLowerCase()).toBe('#ffedd5');
    });
});

describe('dark element background image panel sync', () => {
    function makeElementEditor(idRuleStyle = {}) {
        const idRule = {
            getStyle: () => ({ ...idRuleStyle }),
            setStyle: vi.fn(function setStyle(next) {
                idRuleStyle = { ...next };
                this.getStyle = () => ({ ...idRuleStyle });
            }),
        };
        const editor = {
            __voodbuilderStyleThemeDark: true,
            getWrapper: () => ({ getId: () => 'iwrap', get: () => 'wrapper' }),
            Css: {
                getIdRule: (id) => (id === 'ixu2pn' ? idRule : null),
                setIdRule: vi.fn(),
                remove: vi.fn(() => {
                    idRuleStyle = {};
                    idRule.getStyle = () => ({});
                }),
            },
        };

        return { editor, idRule };
    }

    it('dark paint read never adopts the light photo, but the UI shows it as inherited', () => {
        const { editor } = makeElementEditor({
            'background-image': "url('/try-today.jpg')",
        });
        const component = {
            getId: () => 'ixu2pn',
            get: () => 'div',
            getAttributes: () => ({}),
            getStyle: () => ({}),
            getEl: () => null,
            isRemoved: () => false,
        };

        expect(readBackgroundImageUrl(component, editor)).toBe('');
        expect(readDisplayedBackgroundImageUrl(component, editor)).toContain('/try-today.jpg');
        expect(editor.__voodbuilderStyleThemeDark).toBe(true);
    });

    it('dark explicit none hides the inherited light photo in the UI', () => {
        const { editor } = makeElementEditor({
            'background-image': "url('/try-today.jpg')",
        });
        setDarkIdStyles(editor, 'ixu2pn', { 'background-image': 'none' });
        const component = {
            getId: () => 'ixu2pn',
            get: () => 'div',
            getAttributes: () => ({}),
            getStyle: () => ({}),
            getEl: () => null,
            isRemoved: () => false,
        };

        expect(readDisplayedBackgroundImageUrl(component, editor)).toBe('');
    });

    it('readBackgroundImageUrl prefers dark companion over light #id', () => {
        const { editor } = makeElementEditor({
            'background-image': "url('/light.jpg')",
        });
        setDarkIdStyles(editor, 'ixu2pn', {
            'background-image': "url('/dark.jpg')",
        });
        const component = {
            getId: () => 'ixu2pn',
            get: () => 'div',
            getAttributes: () => ({ [STYLE_BG_SRC_DARK_ATTR]: '/dark-attr.jpg' }),
            getStyle: () => ({}),
            getEl: () => null,
            isRemoved: () => false,
        };

        expect(readBackgroundImageUrl(component, editor)).toBe('/dark-attr.jpg');
    });

    it('clearDecorationBackgroundImage in dark writes a dark-only none and keeps light', () => {
        const style = { 'background-image': "url('/try-today.jpg')", 'background-size': 'cover' };
        const { editor, idRule } = makeElementEditor(style);
        const attrs = { [STYLE_BG_SRC_ATTR]: '/try-today.jpg' };
        const component = {
            getId: () => 'ixu2pn',
            get: () => 'div',
            getAttributes: () => ({ ...attrs }),
            getClasses: () => ['bg-cover', 'bg-top'],
            set: vi.fn(),
            setClass: vi.fn(),
            addAttributes: vi.fn((next) => Object.assign(attrs, next)),
            removeAttributes: vi.fn((key) => {
                delete attrs[key];
            }),
            setAttributes: vi.fn((next) => {
                Object.keys(attrs).forEach((k) => delete attrs[k]);
                Object.assign(attrs, next);
            }),
            getStyle: () => ({}),
            getEl: () => ({ style: { removeProperty: vi.fn(), backgroundImage: "url('/try-today.jpg')" } }),
            isRemoved: () => false,
            view: {
                updateStyle: vi.fn(),
                updateAttributes: vi.fn(),
                updateClasses: vi.fn(),
                updateStyles: vi.fn(),
                el: null,
            },
        };

        clearDecorationBackgroundImage(editor, component);

        expect(attrs[STYLE_BG_SRC_ATTR]).toBe('/try-today.jpg');
        expect(attrs[STYLE_BG_SRC_DARK_ATTR]).toBeUndefined();
        expect(idRule.getStyle()['background-image']).toContain('/try-today.jpg');
        expect(getDarkIdStyles(editor, 'ixu2pn')['background-image']).toBe('none');
    });

    it('clearDecorationBackgroundImage with dark-own photo keeps light #id', () => {
        const style = { 'background-image': "url('/light.jpg')" };
        const { editor, idRule } = makeElementEditor(style);
        setDarkIdStyles(editor, 'ixu2pn', {
            'background-image': "url('/dark.jpg')",
        });
        const attrs = {
            [STYLE_BG_SRC_ATTR]: '/light.jpg',
            [STYLE_BG_SRC_DARK_ATTR]: '/dark.jpg',
        };
        const component = {
            getId: () => 'ixu2pn',
            get: () => 'div',
            getAttributes: () => ({ ...attrs }),
            getClasses: () => [],
            addAttributes: vi.fn((next) => Object.assign(attrs, next)),
            removeAttributes: vi.fn((key) => {
                delete attrs[key];
            }),
            setAttributes: vi.fn((next) => {
                Object.keys(attrs).forEach((k) => delete attrs[k]);
                Object.assign(attrs, next);
            }),
            getStyle: () => ({}),
            getEl: () => null,
            isRemoved: () => false,
            view: {
                updateStyle: vi.fn(),
                updateAttributes: vi.fn(),
            },
        };

        clearDecorationBackgroundImage(editor, component);

        expect(attrs[STYLE_BG_SRC_DARK_ATTR]).toBeUndefined();
        expect(attrs[STYLE_BG_SRC_ATTR]).toBe('/light.jpg');
        expect(idRule.getStyle()['background-image']).toContain('/light.jpg');
        expect(getDarkIdStyles(editor, 'ixu2pn')['background-image']).toBe('none');
    });

    it('light Clear with a dark-own photo leaves the dark photo intact', () => {
        const style = { 'background-image': "url('/light.jpg')" };
        const { editor, idRule } = makeElementEditor(style);
        editor.__voodbuilderStyleThemeDark = false;
        setDarkIdStyles(editor, 'ixu2pn', { 'background-image': "url('/dark.jpg')" });
        const attrs = {
            [STYLE_BG_SRC_ATTR]: '/light.jpg',
            [STYLE_BG_SRC_DARK_ATTR]: '/dark.jpg',
        };
        const component = {
            getId: () => 'ixu2pn',
            get: () => 'div',
            getAttributes: () => ({ ...attrs }),
            getClasses: () => [],
            addAttributes: vi.fn((next) => Object.assign(attrs, next)),
            removeAttributes: vi.fn((key) => {
                delete attrs[key];
            }),
            setAttributes: vi.fn(),
            getStyle: () => ({}),
            getEl: () => null,
            isRemoved: () => false,
            view: { updateStyle: vi.fn(), updateAttributes: vi.fn(), updateClasses: vi.fn() },
        };

        clearDecorationBackgroundImage(editor, component);

        expect(attrs[STYLE_BG_SRC_ATTR]).toBeUndefined();
        expect(attrs[STYLE_BG_SRC_DARK_ATTR]).toBe('/dark.jpg');
        expect(idRule.getStyle()['background-image']).toBeUndefined();
        expect(getDarkIdStyles(editor, 'ixu2pn')['background-image']).toContain('/dark.jpg');
    });
});

describe('section does not inherit a child block photo', () => {
    const PHOTO = '/storage/4/conversions/BS0jkz3CWCRzeRDfEZ3MuHBiCidueqzdCxwCx7QO-lg.webp';

    function makeTree() {
        const childAttrs = { [STYLE_BG_SRC_ATTR]: PHOTO };
        const parentAttrs = {};
        const childStyle = {
            'background-image': `url('${PHOTO}')`,
            'background-size': 'cover',
        };
        const parentStyle = {};
        const child = {
            getId: () => 'icard',
            get: () => 'div',
            getAttributes: () => ({ ...childAttrs }),
            getClasses: () => ['bg-cover', 'bg-center'],
            getStyle: (opts) => (opts?.inline ? { ...childStyle } : { ...childStyle }),
            addStyle: vi.fn((next) => Object.assign(childStyle, next)),
            addAttributes: vi.fn((next) => Object.assign(childAttrs, next)),
            removeAttributes: vi.fn((key) => {
                delete childAttrs[key];
            }),
            setAttributes: vi.fn((next) => {
                Object.keys(childAttrs).forEach((k) => delete childAttrs[k]);
                Object.assign(childAttrs, next);
            }),
            components: () => ({ models: [] }),
            find: () => [],
            getEl: () => null,
            isRemoved: () => false,
            view: { updateStyle: vi.fn(), updateAttributes: vi.fn(), updateClasses: vi.fn(), updateStyles: vi.fn() },
        };
        const parent = {
            getId: () => 'isection',
            get: () => 'section',
            getAttributes: () => ({ ...parentAttrs }),
            getClasses: () => ['py-16'],
            getStyle: (opts) => (opts?.inline ? { ...parentStyle } : { ...parentStyle }),
            addStyle: vi.fn((next) => Object.assign(parentStyle, next)),
            addAttributes: vi.fn((next) => Object.assign(parentAttrs, next)),
            removeAttributes: vi.fn((key) => {
                delete parentAttrs[key];
            }),
            setAttributes: vi.fn((next) => {
                Object.keys(parentAttrs).forEach((k) => delete parentAttrs[k]);
                Object.assign(parentAttrs, next);
            }),
            components: () => ({ models: [child], length: 1 }),
            find: () => [child],
            getEl: () => null,
            isRemoved: () => false,
            view: { updateStyle: vi.fn(), updateAttributes: vi.fn(), updateClasses: vi.fn(), updateStyles: vi.fn() },
        };
        child.parent = () => parent;
        const idRules = {
            icard: {
                getStyle: () => ({ 'background-image': `url('${PHOTO}')` }),
                setStyle: vi.fn(),
            },
        };
        const editor = {
            getWrapper: () => ({
                getId: () => 'iwrap',
                get: () => 'wrapper',
                getAttributes: () => ({}),
                onAll: (cb) => {
                    cb(parent);
                    cb(child);
                },
            }),
            Css: {
                getIdRule: (id) => idRules[id] ?? null,
                setIdRule: vi.fn(),
                remove: vi.fn(),
            },
            trigger: vi.fn(),
            __voodbuilderMarkPageUnsaved: vi.fn(),
        };

        return { editor, parent, child, parentAttrs, parentStyle, childAttrs };
    }

    it('readBackgroundImageUrl on a section does not return the inner block photo', () => {
        const { editor, parent } = makeTree();

        expect(readBackgroundImageUrl(parent, editor)).toBe('');
    });

    it('hydrateDecorationBackgroundImages does not stamp the child photo onto the section', async () => {
        const { hydrateDecorationBackgroundImages } = await import(
            '../../resources/js/editor/style-tailwind-panel.js'
        );
        const { editor, parentAttrs, parentStyle, childAttrs } = makeTree();

        hydrateDecorationBackgroundImages(editor);

        expect(parentAttrs[STYLE_BG_SRC_ATTR]).toBeUndefined();
        expect(String(parentStyle['background-image'] ?? '')).not.toContain(PHOTO);
        expect(childAttrs[STYLE_BG_SRC_ATTR]).toBe(PHOTO);
    });

    it('stripLeakedAncestorDecorationBackgrounds clears a section copy of the child photo', async () => {
        const { stripLeakedAncestorDecorationBackgrounds } = await import(
            '../../resources/js/editor/style-tailwind-panel.js'
        );
        const { editor, parentAttrs, parentStyle, childAttrs } = makeTree();
        parentAttrs[STYLE_BG_SRC_ATTR] = PHOTO;
        parentStyle['background-image'] = `url('${PHOTO}')`;
        parentStyle['background-size'] = 'cover';
        parentStyle['background-position'] = 'center';
        parentStyle['background-repeat'] = 'no-repeat';
        parentStyle['background-color'] = 'transparent';

        const stripped = stripLeakedAncestorDecorationBackgrounds(editor);

        expect(stripped).toBe(1);
        expect(parentAttrs[STYLE_BG_SRC_ATTR]).toBeUndefined();
        expect(childAttrs[STYLE_BG_SRC_ATTR]).toBe(PHOTO);
    });
});
