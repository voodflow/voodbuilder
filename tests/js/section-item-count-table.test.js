import { describe, expect, it } from 'vitest';
import {
    applySectionItemCount,
    applySectionOptional,
    applyTableColumnCount,
    isTableSection,
} from '../../resources/js/editor/section-item-count.js';

function mockNode({ tag = 'div', attrs = {}, children = [] } = {}) {
    const attrStore = { ...attrs };
    const classSet = new Set();
    const childList = [...children];
    const props = { tagName: tag };

    const node = {
        _children: childList,
        getAttributes: () => ({ ...attrStore }),
        addAttributes: (next) => {
            Object.assign(attrStore, next);
        },
        removeAttributes: (key) => {
            delete attrStore[key];
        },
        get: (key) => props[key],
        set: (next) => {
            Object.assign(props, next);
        },
        addClass: (name) => classSet.add(name),
        removeClass: (name) => classSet.delete(name),
        getClasses: () => [...classSet],
        parent: () => null,
        components: (next) => {
            if (typeof next === 'string') {
                return undefined;
            }

            return Object.assign(childList, {
                at: (index) => childList[index],
            });
        },
        append: (child, options = {}) => {
            child.parent = () => node;
            const at = options.at;

            if (typeof at === 'number' && at >= 0) {
                childList.splice(at, 0, child);
            } else {
                childList.push(child);
            }
        },
        clone: () => mockNode({
            tag,
            attrs: { ...attrStore },
            children: childList.map((child) => child.clone()),
        }),
        remove: () => {
            const parent = node.parent();

            if (! parent?._children) {
                return;
            }

            const index = parent._children.indexOf(node);

            if (index >= 0) {
                parent._children.splice(index, 1);
            }
        },
    };

    for (const child of childList) {
        child.parent = () => node;
    }

    return node;
}

function cell(role, text, extra = {}) {
    const field = mockNode({
        tag: 'span',
        attrs: { 'data-vb-field': extra.field ?? 'cell', 'data-vb-field-type': 'text' },
    });
    field.components = (next) => {
        if (typeof next === 'string') {
            field._text = next;

            return undefined;
        }

        return [];
    };
    field._text = text;

    return mockNode({
        tag: extra.tag ?? 'td',
        attrs: { 'data-vb-table-col': role },
        children: role === 'action' ? [] : [field],
    });
}

function makeTableSection({ rows = 2, dataCols = 3 } = {}) {
    const headCells = [
        cell('label', 'Plan', { tag: 'th', field: 'col_label' }),
        ...Array.from({ length: dataCols }, (_, index) => cell('data', `Col ${index + 1}`, { tag: 'th', field: `col_${index + 1}` })),
        cell('action', '', { tag: 'th' }),
    ];
    const head = mockNode({ tag: 'tr', children: headCells });

    const items = Array.from({ length: rows }, (_, rowIndex) => mockNode({
        tag: 'tr',
        attrs: { 'data-vb-item': '' },
        children: [
            cell('label', `Row ${rowIndex + 1}`, { field: 'label' }),
            ...Array.from({ length: dataCols }, (_, index) => cell('data', `${rowIndex}-${index}`, { field: `cell_${index + 1}` })),
            cell('action', ''),
        ],
    }));

    const tbody = mockNode({
        tag: 'tbody',
        attrs: { 'data-vb-items-root': '' },
        children: items,
    });
    const thead = mockNode({ tag: 'thead', children: [head] });
    const table = mockNode({ tag: 'table', children: [thead, tbody] });
    const link = mockNode({ tag: 'a', attrs: { 'data-vb-optional': 'link' } });
    const cta = mockNode({ tag: 'a', attrs: { 'data-vb-optional': 'cta' } });

    return mockNode({
        tag: 'section',
        attrs: {
            'data-vb-table': '',
            'data-vb-table-columns': String(dataCols + 1),
            'data-vb-item-count': String(rows),
            'data-vb-item-min': '1',
            'data-vb-item-max': '8',
            'data-vb-items-layout': 'preserve',
            'data-vb-show-link': '1',
            'data-vb-show-cta': '1',
            'data-vb-show-select': '1',
        },
        children: [table, link, cta],
    });
}

describe('table section inspector', () => {
    it('treats HTML tables as repeating items without a data-vb-table flag', () => {
        const section = makeTableSection({ rows: 3, dataCols: 2 });
        section.removeAttributes('data-vb-table');

        expect(isTableSection(section)).toBe(true);
        applySectionItemCount(section, 5);
        expect(section._children[0]._children[1]._children).toHaveLength(5);
    });

    it('adds and removes body rows without rewriting layout', () => {
        const section = makeTableSection({ rows: 2 });

        applySectionItemCount(section, 4);
        expect(section.getAttributes()['data-vb-item-count']).toBe('4');
        expect(section._children[0]._children[1]._children).toHaveLength(4);

        applySectionItemCount(section, 1);
        expect(section._children[0]._children[1]._children).toHaveLength(1);
    });

    it('clones and drops data columns on every row, keeping the label and action', () => {
        const section = makeTableSection({ rows: 2, dataCols: 3 });

        applyTableColumnCount(section, 5);
        expect(section.getAttributes()['data-vb-table-columns']).toBe('5');

        const head = section._children[0]._children[0]._children[0];
        const row = section._children[0]._children[1]._children[0];
        const roles = (line) => line._children.map((child) => child.getAttributes()['data-vb-table-col']);

        expect(roles(head)).toEqual(['label', 'data', 'data', 'data', 'data', 'action']);
        expect(roles(row)).toEqual(['label', 'data', 'data', 'data', 'data', 'action']);

        applyTableColumnCount(section, 12);
        expect(roles(head).filter((role) => role === 'data')).toHaveLength(11);
    });

    it('hides optional link, cta and selector columns', () => {
        const section = makeTableSection();
        const link = section._children[1];
        const cta = section._children[2];
        const action = section._children[0]._children[1]._children[0]._children.at(-1);

        applySectionOptional(section, 'link', false);
        applySectionOptional(section, 'cta', false);
        applySectionOptional(section, 'select', false);

        expect(section.getAttributes()['data-vb-show-link']).toBe('0');
        expect(Object.prototype.hasOwnProperty.call(link.getAttributes(), 'hidden')).toBe(true);
        expect(Object.prototype.hasOwnProperty.call(cta.getAttributes(), 'hidden')).toBe(true);
        expect(Object.prototype.hasOwnProperty.call(action.getAttributes(), 'hidden')).toBe(true);

        applySectionOptional(section, 'link', true);
        expect(Object.prototype.hasOwnProperty.call(link.getAttributes(), 'hidden')).toBe(false);
    });
});
