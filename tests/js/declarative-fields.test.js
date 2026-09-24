import { describe, expect, it } from 'vitest';
import {
    findClosestItem,
    findDeclarativeFields,
    findItemDeclarativeFields,
    readFieldText,
    resolveFocusedItem,
    writeFieldText,
} from '../../resources/js/editor/declarative-fields.js';

function mockComponent({ attrs = {}, content = '', children = [], cid = null } = {}) {
    let storedContent = content;

    const self = {
        cid,
        getAttributes: () => ({ ...attrs }),
        get: (key) => (key === 'content' ? storedContent : undefined),
        components: (next) => {
            if (typeof next === 'string') {
                storedContent = next;

                return undefined;
            }

            return children;
        },
        parent: () => null,
    };

    return self;
}

describe('declarative-fields', () => {
    it('collects section-level fields and skips nested items', () => {
        const title = mockComponent({
            attrs: { 'data-vb-field': 'heading', 'data-vb-field-label': 'Heading' },
            content: 'Hello',
        });
        const itemTitle = mockComponent({
            attrs: { 'data-vb-field': 'title' },
            content: 'Card',
        });
        const item = mockComponent({
            attrs: { 'data-vb-item': '1' },
            children: [itemTitle],
            cid: 'c1',
        });
        itemTitle.parent = () => item;

        const section = mockComponent({
            attrs: { 'data-voodbuilder-section-block': 'demo' },
            children: [title, item],
        });
        title.parent = () => section;
        item.parent = () => section;

        const fields = findDeclarativeFields(section);

        expect(fields.map((field) => field.key)).toEqual(['heading']);
        expect(fields[0].label).toBe('Heading');
        expect(findItemDeclarativeFields(item).map((field) => field.key)).toEqual(['title']);
    });

    it('resolves the focused repeating item from a nested selection', () => {
        const titleA = mockComponent({ attrs: { 'data-vb-field': 'title' }, content: 'A' });
        const titleB = mockComponent({ attrs: { 'data-vb-field': 'title' }, content: 'B' });
        const itemA = mockComponent({ attrs: { 'data-vb-item': '' }, children: [titleA], cid: 'a' });
        const itemB = mockComponent({ attrs: { 'data-vb-item': '' }, children: [titleB], cid: 'b' });
        titleA.parent = () => itemA;
        titleB.parent = () => itemB;

        expect(findClosestItem(titleB)).toBe(itemB);
        expect(resolveFocusedItem([itemA, itemB], titleB)).toEqual({ item: itemB, index: 1 });
        expect(resolveFocusedItem([itemA, itemB], null)).toBeNull();
    });

    it('reads and writes text field content', () => {
        const node = mockComponent({
            attrs: { 'data-vb-field': 'title' },
            content: 'Before',
        });

        expect(readFieldText(node)).toBe('Before');
        writeFieldText(node, 'After');
        expect(readFieldText(node)).toBe('After');
    });

    it('updates a single textnode child in place without remounting via components()', () => {
        let remounts = 0;
        let leafContent = 'Before';

        const leaf = {
            get: (key) => (key === 'type' ? 'textnode' : (key === 'content' ? leafContent : undefined)),
            set: (key, value) => {
                if (key === 'content') {
                    leafContent = value;
                }
            },
            components: () => [],
            parent: () => host,
        };

        const host = {
            getAttributes: () => ({ 'data-vb-field': 'title' }),
            get: () => undefined,
            getEl: () => {
                const el = {
                    childElementCount: 0,
                    childNodes: { length: 1 },
                    firstChild: { nodeType: 3, textContent: leafContent },
                };
                Object.defineProperty(el.firstChild, 'textContent', {
                    get: () => leafContent,
                    set: (value) => { leafContent = value; },
                });

                return el;
            },
            components: (next) => {
                if (typeof next === 'string') {
                    remounts += 1;
                    leafContent = next;

                    return undefined;
                }

                return [leaf];
            },
            parent: () => null,
        };

        writeFieldText(host, 'After');

        expect(remounts).toBe(0);
        expect(leafContent).toBe('After');
        expect(readFieldText(host)).toBe('After');
    });
});