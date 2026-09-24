import { describe, expect, it } from 'vitest';
import {
    findDeclarativeFields,
    findItemDeclarativeFields,
    readFieldText,
    writeFieldText,
} from '../../resources/js/editor/declarative-fields.js';

function mockComponent({ attrs = {}, content = '', children = [] } = {}) {
    const self = {
        getAttributes: () => ({ ...attrs }),
        get: (key) => (key === 'content' ? content : undefined),
        components: (next) => {
            if (typeof next === 'string') {
                content = next;
                self.__content = next;

                return;
            }

            return children;
        },
        parent: () => null,
        __content: content,
    };

    self.components = (next) => {
        if (typeof next === 'string') {
            content = next;
            self.__content = next;

            return undefined;
        }

        return children;
    };

    self.get = (key) => (key === 'content' ? content : undefined);

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

    it('reads and writes text field content', () => {
        const node = mockComponent({
            attrs: { 'data-vb-field': 'title' },
            content: 'Before',
        });

        expect(readFieldText(node)).toBe('Before');
        writeFieldText(node, 'After');
        expect(readFieldText(node)).toBe('After');
    });
});
