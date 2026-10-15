/**
 * @vitest-environment node
 *
 * Regression: a dynamic block owns its markup. The content-width helpers must
 * never treat the block's own layout child as a section content wrapper, or the
 * whole block gets absorbed into that child and shrinks on the published page.
 */

import { describe, expect, it } from 'vitest';
import {
    applyComponentContentWidth,
    buildContentWidthToolbarButton,
    ensureSectionContentWrapper,
    findSectionContentWrapper,
    resolveContentWidthTarget,
    shouldShowContentWidthToolbar,
} from '../../resources/js/editor/content-width-toolbar.js';

function makeComponent({ tagName = 'div', classes = [], attrs = {}, children = [] } = {}) {
    const state = {
        tagName,
        classes: [...classes],
        attrs: { ...attrs },
    };
    const models = [];
    let parent = null;
    let removed = false;

    const component = {
        get: (key) => (key === 'type' ? state.attrs.type : (key in state ? state[key] : undefined)),
        getAttributes: () => ({ ...state.attrs }),
        setAttributes: (next) => {
            state.attrs = { ...next };
        },
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
        addClass: (name) => {
            if (! state.classes.includes(name)) {
                state.classes.push(name);
            }
        },
        removeClass: (name) => {
            state.classes = state.classes.filter((item) => item !== name);
        },
        getStyle: () => ({}),
        addStyle: () => {},
        removeStyle: () => {},
        components: () => ({
            models,
            add: (def = {}, opts = {}) => {
                const child = makeComponent({
                    tagName: def.tagName ?? 'div',
                    classes: def.classes ?? [],
                    attrs: def.attributes ?? {},
                });
                child.__setParent(component);

                if (opts.at != null) {
                    models.splice(opts.at, 0, child);
                } else {
                    models.push(child);
                }

                return child;
            },
        }),
        __models: () => models,
        __setParent: (next) => {
            parent = next;
        },
        parent: () => parent,
        isRemoved: () => removed,
        move: (target) => {
            const ownerModels = parent?.__models?.();

            if (ownerModels) {
                const index = ownerModels.indexOf(component);

                if (index >= 0) {
                    ownerModels.splice(index, 1);
                }
            }

            const targetModels = target.__models();

            if (! targetModels.includes(component)) {
                targetModels.push(component);
            }

            parent = target;
        },
        remove: () => {
            const ownerModels = parent?.__models?.();
            const index = ownerModels?.indexOf(component) ?? -1;

            if (index >= 0) {
                ownerModels.splice(index, 1);
            }

            parent = null;
            removed = true;
        },
    };

    for (const child of children) {
        child.__setParent(component);
        models.push(child);
    }

    return component;
}

function attach(parent, child) {
    child.__setParent(parent);
    parent.__models().push(child);

    return child;
}

function dynamicSection(blockId = 'demo_pages_explorer') {
    return makeComponent({
        tagName: 'section',
        classes: ['voodbuilder-editor-section', 'voodbuilder-editor-dynamic'],
        attrs: { 'data-voodbuilder-block': blockId },
    });
}

describe('content-width helpers and dynamic blocks', () => {
    it('never treats a dynamic block layout child as the section content wrapper', () => {
        const section = dynamicSection();
        const flexRow = attach(section, makeComponent({
            tagName: 'div',
            classes: ['flex', 'flex-col', 'gap-4', 'sm:flex-row', 'sm:items-center', 'sm:justify-between'],
        }));
        const list = attach(section, makeComponent({ tagName: 'ul', classes: ['mt-5', 'grid', 'gap-3', 'sm:grid-cols-2'] }));

        expect(findSectionContentWrapper(section)).toBeNull();
        // Sanity: the fallback would otherwise have grabbed the layout div.
        expect([flexRow, list]).toContain(section.__models()[0]);
    });

    it('does not synthesize a wrapper nor absorb children for a dynamic block', () => {
        const section = dynamicSection();
        const flexRow = attach(section, makeComponent({
            tagName: 'div',
            classes: ['flex', 'flex-col', 'gap-4', 'sm:flex-row'],
        }));
        const list = attach(section, makeComponent({ tagName: 'ul', classes: ['mt-5'] }));

        const wrapper = ensureSectionContentWrapper(section);

        expect(wrapper).toBeNull();
        // Children stay direct children of the block root — nothing moved.
        expect(section.__models()).toEqual([flexRow, list]);
        expect(flexRow.getClasses()).not.toContain('voodbuilder-editor-container');
        expect(flexRow.getClasses()).not.toContain('mx-auto');
        expect(flexRow.getAttributes()['data-voodbuilder-role']).toBeUndefined();
        expect(flexRow.getAttributes()['data-voodbuilder-content-width']).toBeUndefined();
    });

    it('keeps the "any div" fallback for ordinary sections', () => {
        const section = makeComponent({
            tagName: 'section',
            classes: ['voodbuilder-editor-section'],
        });
        const plainDiv = attach(section, makeComponent({
            tagName: 'div',
            classes: ['mx-auto', 'max-w-3xl'],
        }));

        expect(findSectionContentWrapper(section)).toBe(plainDiv);
    });

    it('still resolves an explicit content shell inside a dynamic block', () => {
        const section = dynamicSection('vb-bg-image');
        const shell = attach(section, makeComponent({
            tagName: 'div',
            classes: ['voodbuilder-editor-container', 'relative'],
            attrs: { 'data-voodbuilder-role': 'content' },
        }));

        expect(findSectionContentWrapper(section)).toBe(shell);
        expect(ensureSectionContentWrapper(section)).toBe(shell);
    });

    it('shows the content-width toolbar when a dynamic block has no shell', () => {
        const editor = { __voodbuilderFullWidthPage: true };
        const bare = dynamicSection();
        const flexRow = attach(bare, makeComponent({ tagName: 'div', classes: ['flex', 'gap-4'] }));
        attach(bare, makeComponent({ tagName: 'ul' }));

        const withShell = dynamicSection('vb-bg-image');
        attach(withShell, makeComponent({
            tagName: 'div',
            classes: ['voodbuilder-editor-container'],
            attrs: { 'data-voodbuilder-role': 'content' },
        }));

        // Regression (1.11.79): a Livewire/dynamic block normally ships no content
        // shell, yet the width control must be offered — the measure lands on the
        // block root. Hiding it whenever no shell existed made the button disappear.
        expect(shouldShowContentWidthToolbar(bare, editor)).toBe(true);
        expect(buildContentWidthToolbarButton(bare, editor)).not.toBeNull();
        expect(shouldShowContentWidthToolbar(withShell, editor)).toBe(true);
        expect(buildContentWidthToolbarButton(withShell, editor)).not.toBeNull();
        expect(flexRow.getClasses()).toEqual(['flex', 'gap-4']);
    });

    it('resolves a dynamic block without a shell to the block root, never an inner layout node', () => {
        const editor = { __voodbuilderFullWidthPage: true };
        const section = dynamicSection();
        const flexRow = attach(section, makeComponent({
            tagName: 'div',
            classes: ['flex', 'flex-col', 'gap-4', 'sm:flex-row', 'sm:justify-between'],
        }));
        const list = attach(section, makeComponent({ tagName: 'ul', classes: ['grid', 'gap-3'] }));

        // The root is a valid measure target; inner layout nodes never are.
        const target = resolveContentWidthTarget(section, editor);

        expect(target).toBe(section);
        expect(target).not.toBe(flexRow);
        expect(target).not.toBe(list);
    });

    it('resolves a dynamic block with a genuine shell to that shell', () => {
        const editor = { __voodbuilderFullWidthPage: true };
        const section = dynamicSection('vb-bg-image');
        const shell = attach(section, makeComponent({
            tagName: 'div',
            classes: ['voodbuilder-editor-container', 'mx-auto', 'max-w-[48rem]'],
            attrs: { 'data-voodbuilder-role': 'content' },
        }));

        expect(resolveContentWidthTarget(section, editor)).toBe(shell);
    });

    it('persists a custom measure as a durable max-w utility on the block root', () => {
        const editor = {
            __voodbuilderFullWidthPage: true,
            __voodbuilderPageContentWidth: { mode: 'custom', maxWidth: '48rem', customMaxWidth: '48rem' },
        };
        const section = dynamicSection();
        attach(section, makeComponent({ tagName: 'div', classes: ['flex', 'gap-4'] }));

        applyComponentContentWidth(section, 'custom', editor);

        const classes = section.getClasses();

        expect(section.getAttributes()['data-voodbuilder-content-width']).toBe('custom');
        expect(classes).toContain('mx-auto');
        expect(classes).toContain('max-w-[48rem]');
        expect(classes).toContain('w-full');
    });

    /*
     * Regression (1.11.77): a pre-1.11.76 save stamped the shell markers on the
     * block's own preview flex row. `findSectionContentWrapper()` handed that row to
     * `ensureSectionContentWrapper()`, which then absorbed the rest of the block into
     * it — the published block shrank and lost its identity.
     */
    it('never treats a stamped layout row inside a dynamic block as the content wrapper', () => {
        const section = dynamicSection();
        const poisoned = attach(section, makeComponent({
            tagName: 'div',
            classes: ['voodbuilder-editor-container', 'flex', 'flex-col', 'gap-4', 'sm:flex-row', 'sm:items-center', 'sm:justify-between', 'mx-auto', 'w-full', 'max-w-[80rem]'],
            attrs: {
                'data-voodbuilder-role': 'content',
                'data-voodbuilder-content-width': 'normal',
            },
        }));
        const list = attach(section, makeComponent({ tagName: 'ul', classes: ['mt-5', 'grid', 'gap-3'] }));

        expect(findSectionContentWrapper(section)).toBeNull();

        const wrapper = ensureSectionContentWrapper(section);

        expect(wrapper).toBeNull();
        // Nothing was absorbed: the list is still a direct child of the block root.
        expect(section.__models()).toEqual([poisoned, list]);
        expect(poisoned.getClasses()).toEqual(['flex', 'flex-col', 'gap-4', 'sm:flex-row', 'sm:items-center', 'sm:justify-between']);
        expect(poisoned.getAttributes()['data-voodbuilder-role']).toBeUndefined();
        expect(poisoned.getAttributes()['data-voodbuilder-content-width']).toBeUndefined();
    });

    it('keeps a genuine author content-width shell inside a dynamic block', () => {
        const section = dynamicSection('vb-bg-image');
        const shell = attach(section, makeComponent({
            tagName: 'div',
            classes: ['voodbuilder-editor-container', 'mx-auto', 'max-w-[48rem]'],
            attrs: { 'data-voodbuilder-role': 'content', 'data-voodbuilder-content-width': 'custom' },
        }));

        expect(findSectionContentWrapper(section)).toBe(shell);
        expect(ensureSectionContentWrapper(section)).toBe(shell);
        expect(shell.getClasses()).toEqual(['voodbuilder-editor-container', 'mx-auto', 'max-w-[48rem]']);
        expect(shell.getAttributes()['data-voodbuilder-content-width']).toBe('custom');
    });
});
