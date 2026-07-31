/**
 * @vitest-environment node
 */

import { describe, expect, it, vi } from 'vitest';
import {
    BACKGROUND_OPTIONS,
    FONT_SIZE_OPTIONS,
    PADDING_Y_OPTIONS,
    SHADOW_OPTIONS,
    STYLE_UTILITY_GROUPS,
    WIDTH_OPTIONS,
    classSetFromOptions,
    hasAuthoredStyleUtilities,
    replaceClassGroup,
    resolveGroupValue,
} from '../../resources/js/editor/style-tailwind-class-groups.js';
import {
    syncSelectsFromComponent,
    watchComponentClassList,
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

    it('replaces exclusive utility groups atomically', () => {
        let classes = ['w-full', 'p-4', 'text-lg'];
        const component = {
            getClasses: () => [...classes],
            setClass: (next) => {
                classes = [...next];
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
        };

        const backgroundSet = classSetFromOptions(BACKGROUND_OPTIONS);
        replaceClassGroup(component, backgroundSet, 'bg-vp-brand-2');

        expect(classes).toContain('p-6');
        expect(classes).toContain('bg-vp-brand-2');
        expect(classes).not.toContain('bg-vp-brand-1');
        expect(classes).toContain('gap-4');
        expect(classes).toContain('mx-auto');
    });

    it('includes common decoration utilities without invented shadow defaults', () => {
        expect(BACKGROUND_OPTIONS.some((opt) => opt.value === 'bg-vp-brand-1')).toBe(true);
        expect(SHADOW_OPTIONS.some((opt) => opt.value === 'shadow-lg')).toBe(true);
        expect(SHADOW_OPTIONS.every((opt) => opt.value === '' || opt.value.startsWith('shadow'))).toBe(true);
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
                if (! this._els) {
                    this._els = {};
                }

                if (! this._els[selector]) {
                    this._els[selector] = { value: '' };
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
