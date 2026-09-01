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
    syncSelectsFromComponent,
    watchComponentClassList,
    resolveSpacingState,
    spacingGroupFor,
    applySpacingToken,
    setSpacingLinkMode,
} from '../../resources/js/editor/style-tailwind-panel.js';
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
