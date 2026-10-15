/**
 * @vitest-environment node
 *
 * Regression (1.11.77): a dynamic block's inner preview layout row could carry the
 * content-width shell stamps a pre-1.11.76 save wrote. Capturing that node made the
 * publish wrapper copy `flex … max-w-[80rem]` around the whole block, so the mount
 * shrank on the page. The shared class rules must tell a genuine author measure
 * from a layout node, and unstamp the latter.
 */

import { describe, expect, it } from 'vitest';
import {
    CONTENT_SHELL_SELECTOR,
    classListHasLayoutUtilities,
    isGenuineContentShellNode,
    isLayoutUtilityClass,
    isShellMeasureToken,
    stripClassVariants,
    unstampLayoutContentShellNode,
} from '../../resources/js/editor/core/content-shell-classes.js';

function makeComponent({ tagName = 'div', classes = [], attrs = {} } = {}) {
    const state = {
        tagName,
        classes: [...classes],
        attrs: { ...attrs },
    };

    return {
        get: (key) => (key === 'type' ? state.attrs.type : (key in state ? state[key] : undefined)),
        getAttributes: () => ({ ...state.attrs }),
        addAttributes: (next) => {
            Object.assign(state.attrs, next);
        },
        removeAttributes: (key) => {
            delete state.attrs[key];
        },
        getClasses: () => [...state.classes],
        setClass: (next) => {
            state.classes = [...next];
        },
    };
}

describe('content shell class rules', () => {
    it('strips Tailwind variant segments but leaves arbitrary values alone', () => {
        expect(stripClassVariants('sm:flex-row')).toBe('flex-row');
        expect(stripClassVariants('dark:lg:my-20')).toBe('my-20');
        expect(stripClassVariants('bg-[url(https://example.test/a.png)]')).toBe('bg-[url(https://example.test/a.png)]');
        expect(stripClassVariants('')).toBe('');
        expect(stripClassVariants(undefined)).toBe('');
    });

    it('recognizes layout utilities whatever the variant prefix is', () => {
        for (const token of ['flex', 'inline-flex', 'grid', 'flex-col', 'sm:flex-row', 'sm:items-center', 'sm:justify-between', 'gap-4', 'dark:gap-8', 'order-2', 'col-span-2', 'row-start-1', 'basis-1/2', 'content-center']) {
            expect(isLayoutUtilityClass(token), token).toBe(true);
        }

        for (const token of ['mx-auto', 'w-full', 'max-w-[80rem]', 'lg:my-20', 'rounded-2xl', 'bg-vp-bg-elv', 'p-6', 'dark:lg:my-20']) {
            expect(isLayoutUtilityClass(token), token).toBe(false);
        }
    });

    it('flags a class list that hosts a layout', () => {
        expect(classListHasLayoutUtilities(['flex', 'flex-col', 'gap-4', 'sm:flex-row'])).toBe(true);
        expect(classListHasLayoutUtilities('voodbuilder-editor-container mx-auto w-full max-w-[80rem]')).toBe(false);
        expect(classListHasLayoutUtilities(null)).toBe(false);
        expect(classListHasLayoutUtilities(undefined)).toBe(false);
    });

    it('only calls a node a genuine shell when it hosts no layout', () => {
        expect(isGenuineContentShellNode(makeComponent({
            classes: ['voodbuilder-editor-container', 'mx-auto', 'max-w-[48rem]'],
            attrs: { 'data-voodbuilder-role': 'content' },
        }))).toBe(true);

        expect(isGenuineContentShellNode(makeComponent({
            classes: ['voodbuilder-editor-container', 'flex', 'flex-col', 'gap-4', 'sm:flex-row', 'sm:items-center', 'sm:justify-between', 'mx-auto', 'w-full', 'max-w-[80rem]'],
            attrs: { 'data-voodbuilder-role': 'content', 'data-voodbuilder-content-width': 'normal' },
        }))).toBe(false);

        expect(isGenuineContentShellNode(null)).toBe(false);
    });

    it('knows the measure tokens the shell stamp adds', () => {
        for (const token of ['voodbuilder-editor-container', 'container', 'mx-auto', 'w-full', 'max-w-[80rem]', 'max-w-[48rem]']) {
            expect(isShellMeasureToken(token), token).toBe(true);
        }

        for (const token of ['flex', 'gap-4', 'lg:my-20', 'rounded-2xl', 'p-6']) {
            expect(isShellMeasureToken(token), token).toBe(false);
        }
    });

    it('unstamps a poisoned layout node, keeping the layout itself', () => {
        const layout = makeComponent({
            classes: ['voodbuilder-editor-container', 'flex', 'flex-col', 'gap-4', 'sm:flex-row', 'sm:items-center', 'sm:justify-between', 'mx-auto', 'w-full', 'max-w-[80rem]'],
            attrs: {
                'data-voodbuilder-role': 'content',
                'data-voodbuilder-content-width': 'normal',
            },
        });

        expect(unstampLayoutContentShellNode(layout)).toBe(4);

        expect(layout.getClasses()).toEqual(['flex', 'flex-col', 'gap-4', 'sm:flex-row', 'sm:items-center', 'sm:justify-between']);
        expect(layout.getAttributes()['data-voodbuilder-role']).toBeUndefined();
        expect(layout.getAttributes()['data-voodbuilder-content-width']).toBeUndefined();
    });

    it('never touches a genuine shell', () => {
        const shell = makeComponent({
            classes: ['voodbuilder-editor-container', 'mx-auto', 'max-w-[48rem]'],
            attrs: { 'data-voodbuilder-role': 'content', 'data-voodbuilder-content-width': 'custom' },
        });

        expect(unstampLayoutContentShellNode(shell)).toBe(0);
        expect(shell.getClasses()).toEqual(['voodbuilder-editor-container', 'mx-auto', 'max-w-[48rem]']);
        expect(shell.getAttributes()['data-voodbuilder-role']).toBe('content');
        expect(shell.getAttributes()['data-voodbuilder-content-width']).toBe('custom');
    });

    it('keeps the selector aligned with the PHP shell query', () => {
        expect(CONTENT_SHELL_SELECTOR).toContain('.voodbuilder-editor-container');
        expect(CONTENT_SHELL_SELECTOR).toContain('.container');
        expect(CONTENT_SHELL_SELECTOR).toContain('[data-voodbuilder-role="content"]');
    });
});
