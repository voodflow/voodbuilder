/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    CONTEXT_CONVERT_TARGETS,
    buildContextConvertSubmenu,
    convertTextComponent,
    describeConvertibleTargetId,
    isConvertibleTextComponent,
    readConvertiblePlainText,
    resolveConvertibleTextComponent,
} from '../../resources/js/editor/context-convert-element.js';

function mockComponent(state = {}) {
    const attrs = { ...(state.attributes ?? {}) };
    let classes = [...(state.classes ?? [])];
    let removed = false;
    const children = [...(state.children ?? [])];

    const component = {
        cid: state.cid ?? 'c1',
        get: (key) => {
            if (key === 'type') {
                return state.type ?? 'default';
            }

            if (key === 'tagName') {
                return state.tagName ?? 'div';
            }

            if (key === 'content') {
                return state.content ?? '';
            }

            if (key === 'name') {
                return state.name;
            }

            return state[key];
        },
        set: (values) => {
            Object.assign(state, values);
        },
        getAttributes: () => ({ ...attrs }),
        setAttributes: (next) => {
            Object.keys(attrs).forEach((key) => delete attrs[key]);
            Object.assign(attrs, next ?? {});
        },
        getClasses: () => [...classes],
        setClass: (next) => {
            classes = [...next];
        },
        getEl: () => state.el ?? null,
        parent: () => state.parent ?? null,
        components: () => ({
            forEach: (fn) => children.forEach(fn),
            [Symbol.iterator]: function* iterator() {
                yield* children;
            },
        }),
        toHTML: () => state.html ?? `<${state.tagName ?? 'div'}>${state.content ?? ''}</${state.tagName ?? 'div'}>`,
        remove: () => {
            removed = true;
            const parent = component.parent?.();
            parent?._removeChild?.(component);
        },
        _isRemoved: () => removed,
    };

    return component;
}

function mockParent(initialChildren = []) {
    const children = [...initialChildren];

    const parent = {
        components: () => ({
            indexOf: (child) => children.indexOf(child),
            forEach: (fn) => children.forEach(fn),
        }),
        append: (definition, options = {}) => {
            const created = mockComponent({
                ...definition,
                attributes: definition.attributes ?? {},
                classes: definition.classes ?? [],
                parent,
                el: {
                    textContent: typeof definition.content === 'string'
                        ? definition.content
                        : 'Rich',
                    innerHTML: typeof definition.components === 'string'
                        ? definition.components
                        : (definition.content ?? ''),
                },
            });
            const at = Number.isInteger(options.at) ? options.at : children.length;
            children.splice(at, 0, created);
            created.parent = () => parent;

            return [created];
        },
        _removeChild: (child) => {
            const index = children.indexOf(child);

            if (index >= 0) {
                children.splice(index, 1);
            }
        },
        _children: () => children,
    };

    for (const child of children) {
        child.parent = () => parent;
    }

    return parent;
}

describe('context-convert-element', () => {
    it('exposes heading / paragraph / basic / rich targets', () => {
        expect(CONTEXT_CONVERT_TARGETS.map((entry) => entry.id)).toEqual([
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'basic-text', 'rich-text',
        ]);
    });

    it('detects pasted paragraph and heading tags as convertible', () => {
        const paragraph = mockComponent({ tagName: 'p', type: 'text', content: 'Hello', classes: ['text-3xl'] });
        const heading = mockComponent({ tagName: 'h2', type: 'text', content: 'Title' });
        const button = mockComponent({ tagName: 'a', type: 'voodbuilder-cta-button' });

        expect(isConvertibleTextComponent(paragraph)).toBe(true);
        expect(isConvertibleTextComponent(heading)).toBe(true);
        expect(isConvertibleTextComponent(button)).toBe(false);
        expect(describeConvertibleTargetId(paragraph)).toBe('p');
        expect(describeConvertibleTargetId(heading)).toBe('h2');
    });

    it('tag-swaps p → h2 in place and keeps classes/content', () => {
        const paragraph = mockComponent({
            tagName: 'p',
            type: 'text',
            content: 'Hero title',
            classes: ['text-3xl', 'font-bold'],
        });
        const editor = { select: () => {} };

        const result = convertTextComponent(editor, paragraph, 'h2');

        expect(result).toBe(paragraph);
        expect(paragraph.get('tagName')).toBe('h2');
        expect(paragraph.getClasses()).toEqual(['text-3xl', 'font-bold']);
        expect(readConvertiblePlainText(paragraph)).toBe('Hero title');
    });

    it('converts paragraph → Rich Text without losing copy', () => {
        const paragraph = mockComponent({
            tagName: 'p',
            type: 'text',
            content: 'Keep me',
            classes: ['text-lg'],
            el: { textContent: 'Keep me', innerHTML: 'Keep me' },
        });
        const parent = mockParent([paragraph]);
        paragraph.parent = () => parent;
        const selected = [];
        const editor = { select: (component) => selected.push(component) };

        const created = convertTextComponent(editor, paragraph, 'rich-text');

        expect(created?.get('type')).toBe('voodbuilder-rich-text');
        expect(created?.get('tagName')).toBe('div');
        expect(created?.getClasses()).toContain('vb-rich-text');
        expect(created?.getClasses()).toContain('text-lg');
        expect(created?.getAttributes?.()['data-voodbuilder-rich-text']).toBe('');
        expect(String(created?.get('components') ?? '')).toContain('Keep me');
        expect(parent._children()).toHaveLength(1);
        expect(selected[0]).toBe(created);
    });

    it('converts Rich Text → Basic Text preserving plain text', () => {
        const rich = mockComponent({
            type: 'voodbuilder-rich-text',
            tagName: 'div',
            classes: ['vb-rich-text', 'text-base'],
            attributes: { 'data-voodbuilder-rich-text': '' },
            el: { textContent: 'Body copy', innerHTML: '<p>Body copy</p>' },
        });
        const parent = mockParent([rich]);
        rich.parent = () => parent;
        const editor = { select: () => {} };

        const created = convertTextComponent(editor, rich, 'basic-text');

        expect(created?.get('type')).toBe('voodbuilder-text');
        expect(created?.getAttributes?.()['data-voodbuilder-text']).toBe('');
        expect(created?.get('content')).toBe('Body copy');
        expect(created?.getClasses()).not.toContain('vb-rich-text');
    });

    it('builds Convert submenu with current option disabled', () => {
        const heading = mockComponent({ tagName: 'h2', type: 'text', content: 'Title' });
        const submenu = buildContextConvertSubmenu({}, heading, {
            contextConvert: 'Convert to',
            contextConvertH2: 'Heading 2',
        });

        expect(submenu?.id).toBe('convert-element');
        expect(submenu?.children).toHaveLength(CONTEXT_CONVERT_TARGETS.length);
        expect(submenu?.children.find((item) => item.id === 'convert-h2')?.disabled).toBe(true);
        expect(submenu?.children.find((item) => item.id === 'convert-p')?.disabled).toBe(false);
    });

    it('resolves Rich Text host from an inner child', () => {
        const host = mockComponent({
            type: 'voodbuilder-rich-text',
            tagName: 'div',
            attributes: { 'data-voodbuilder-rich-text': '' },
            classes: ['vb-rich-text'],
        });
        const child = mockComponent({ tagName: 'p', type: 'text', parent: host });
        child.parent = () => host;

        expect(resolveConvertibleTextComponent(child)).toBe(host);
        expect(describeConvertibleTargetId(child)).toBe('rich-text');
    });
});
