import { describe, expect, it } from 'vitest';
import {
    findClosestItem,
    findDeclarativeFields,
    findItemDeclarativeFields,
    readFieldText,
    readStatusValue,
    resolveFocusedItem,
    writeFieldText,
    writeStatusValue,
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

    it('resolves an outer item when selection is inside a nested item (FAQ question → category)', () => {
        const questionField = mockComponent({
            attrs: { 'data-vb-field': 'question' },
            content: 'How does pricing work?',
        });
        const question = mockComponent({
            attrs: { 'data-vb-item': '1' },
            children: [questionField],
            cid: 'q1',
        });
        questionField.parent = () => question;

        const category = mockComponent({
            attrs: { 'data-vb-item': '', 'data-vb-item-count': '2' },
            children: [question],
            cid: 'cat1',
        });
        question.parent = () => category;

        const otherCategory = mockComponent({
            attrs: { 'data-vb-item': '', 'data-vb-item-count': '1' },
            cid: 'cat2',
        });

        expect(findClosestItem(questionField)).toBe(question);
        expect(resolveFocusedItem([category, otherCategory], questionField)).toEqual({
            item: category,
            index: 0,
        });
        expect(resolveFocusedItem([question], questionField)).toEqual({
            item: question,
            index: 0,
        });
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

    it('treats table cells as text fields even without data-vb-field', () => {
        const cell = mockComponent({ content: '15 GB', cid: 'td1' });
        const originalGet = cell.get;
        cell.get = (key) => (key === 'tagName' ? 'td' : originalGet(key));
        const row = mockComponent({ children: [cell], cid: 'tr1' });
        cell.parent = () => row;

        expect(findItemDeclarativeFields(row).map((field) => field.label)).toEqual(['Cell']);
        expect(readFieldText(cell)).toBe('15 GB');
    });

    it('keeps body row cells off the section panel until that row is focused', () => {
        const heading = mockComponent({
            attrs: { 'data-vb-field': 'heading' },
            content: 'Pricing',
        });
        const th = mockComponent({ content: 'Plan', cid: 'th1' });
        th.get = (key) => (key === 'tagName' ? 'th' : (key === 'content' ? 'Plan' : undefined));
        const headRow = mockComponent({ children: [th], cid: 'head' });
        th.parent = () => headRow;
        const thead = mockComponent({ children: [headRow], cid: 'thead' });
        thead.get = (key) => (key === 'tagName' ? 'thead' : undefined);
        headRow.parent = () => thead;
        headRow.get = (key) => (key === 'tagName' ? 'tr' : undefined);

        const td = mockComponent({ content: 'Start', cid: 'td1' });
        td.get = (key) => (key === 'tagName' ? 'td' : (key === 'content' ? 'Start' : undefined));
        const bodyRow = mockComponent({ attrs: { 'data-vb-item': '' }, children: [td], cid: 'row1' });
        bodyRow.get = (key) => (key === 'tagName' ? 'tr' : undefined);
        td.parent = () => bodyRow;
        const tbody = mockComponent({ children: [bodyRow], cid: 'tbody' });
        tbody.get = (key) => (key === 'tagName' ? 'tbody' : undefined);
        bodyRow.parent = () => tbody;

        const table = mockComponent({ children: [thead, tbody], cid: 'table' });
        table.get = (key) => (key === 'tagName' ? 'table' : undefined);
        thead.parent = () => table;
        tbody.parent = () => table;

        const section = mockComponent({
            attrs: { 'data-voodbuilder-section-block': 'vb-pricing-2' },
            children: [heading, table],
        });
        heading.parent = () => section;
        table.parent = () => section;

        const fields = findDeclarativeFields(section);

        expect(fields.map((field) => field.key)).toEqual(['heading', 'cell-th1']);
        expect(fields.some((field) => field.component.cid === 'td1')).toBe(false);
        expect(findItemDeclarativeFields(bodyRow).map((field) => field.component.cid)).toEqual(['td1']);
        expect(resolveFocusedItem([bodyRow], td)).toEqual({ item: bodyRow, index: 0 });
    });

    it('reads and writes status fields as yes/no without rich html', () => {
        let attrs = { 'data-vb-field': 'you', 'data-vb-field-type': 'status', 'data-vb-status': 'yes' };
        let childrenHtml = '';

        const cell = {
            getAttributes: () => ({ ...attrs }),
            setAttributes: (next) => { attrs = { ...next }; },
            addAttributes: (next) => { attrs = { ...attrs, ...next }; },
            get: (key) => (key === 'tagName' ? 'td' : undefined),
            components: (next) => {
                if (typeof next === 'string') {
                    childrenHtml = next;
                }

                return [];
            },
            parent: () => null,
            getEl: () => ({
                querySelector: (sel) => (sel === '[aria-label]'
                    ? { getAttribute: () => (attrs['data-vb-status'] === 'yes' ? 'Yes' : 'No') }
                    : null),
                getAttribute: () => null,
                innerHTML: childrenHtml,
            }),
        };

        expect(readStatusValue(cell)).toBe('yes');
        writeStatusValue(cell, 'no');
        expect(attrs['data-vb-status']).toBe('no');
        expect(childrenHtml).toContain('aria-label="No"');
        expect(childrenHtml).toContain('text-red-500');
        writeStatusValue(cell, 'yes');
        expect(childrenHtml).toContain('text-emerald-500');
        expect(readStatusValue(cell)).toBe('yes');

        const typed = mockComponent({
            attrs: { 'data-vb-field': 'alt1', 'data-vb-field-type': 'status', 'data-vb-status': 'no' },
            content: '',
        });
        const row = mockComponent({ attrs: { 'data-vb-item': '' }, children: [typed], cid: 'r1' });
        typed.parent = () => row;
        expect(findItemDeclarativeFields(row).map((field) => field.type)).toEqual(['status']);
    });

    it('resolves a focused row via DOM containment after remount', () => {
        const rowEl = { nodeType: 1, contains: (node) => node === cellEl };
        const cellEl = { nodeType: 1, parentElement: rowEl };
        rowEl.contains = (node) => node === cellEl;

        const orphanCell = mockComponent({ content: 'Pro', cid: 'orphan' });
        orphanCell.get = (key) => (key === 'tagName' ? 'td' : (key === 'content' ? 'Pro' : undefined));
        orphanCell.parent = () => null;
        orphanCell.getEl = () => cellEl;

        const bodyRow = mockComponent({ attrs: { 'data-vb-item': '' }, cid: 'row1' });
        bodyRow.get = (key) => (key === 'tagName' ? 'tr' : undefined);
        bodyRow.getEl = () => rowEl;

        expect(resolveFocusedItem([bodyRow], orphanCell)).toEqual({ item: bodyRow, index: 0 });
    });
});