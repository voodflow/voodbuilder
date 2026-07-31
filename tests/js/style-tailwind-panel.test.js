/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    BACKGROUND_OPTIONS,
    FONT_SIZE_OPTIONS,
    SHADOW_OPTIONS,
    STYLE_UTILITY_GROUPS,
    WIDTH_OPTIONS,
    classSetFromOptions,
    hasAuthoredStyleUtilities,
    replaceClassGroup,
    resolveGroupValue,
} from '../../resources/js/editor/style-tailwind-class-groups.js';
import { STYLE_MANAGER_SECTORS } from '../../resources/js/editor/editor-chrome.js';

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

    it('includes common decoration utilities without invented shadow defaults', () => {
        expect(BACKGROUND_OPTIONS.some((opt) => opt.value === 'bg-vp-brand-1')).toBe(true);
        expect(SHADOW_OPTIONS.some((opt) => opt.value === 'shadow-lg')).toBe(true);
        expect(SHADOW_OPTIONS.every((opt) => opt.value === '' || opt.value.startsWith('shadow'))).toBe(true);
    });
});

describe('native Style Manager sectors', () => {
    it('are empty so Grapes does not invent inline style sectors', () => {
        expect(STYLE_MANAGER_SECTORS).toEqual([]);
    });
});
