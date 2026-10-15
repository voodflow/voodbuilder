import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import {
    findClosestItem,
    findDeclarativeFields,
    findItemDeclarativeFields,
    readFieldText,
    readStatusValue,
    resolveFocusedItem,
    scopeHasDeclarativeFields,
    writeFieldText,
    writeStatusValue,
} from '../../resources/js/editor/declarative-fields.js';
import {
    dynamicBlockRenderFingerprint,
    isDynamicRefreshBusy,
    isStaleDynamicRender,
    parseBlockConfig,
} from '../../resources/js/editor/voodbuilder-dynamic-config.js';

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

/**
 * Editor stand-in that only records the save-gate "page is dirty" signal.
 */
function mockDirtyTrackingEditor() {
    return {
        unsavedMarks: 0,
        __voodbuilderMarkPageUnsaved() {
            this.unsavedMarks += 1;
        },
    };
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

    it('treats table cells as text fields even without data-vb-field', () => {        const cell = mockComponent({ content: '15 GB', cid: 'td1' });
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

    it('matches the descriptor scope of a freshly rendered block preview', () => {
        const heading = mockComponent({
            attrs: { 'data-vb-field': 'heading', 'data-vb-field-type': 'text', 'data-vb-field-label': 'Heading' },
            content: 'Explore the site',
        });
        const perPage = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '4',
        });
        const section = mockComponent({
            attrs: { 'data-voodbuilder-block': 'demo_pages_explorer', 'data-voodbuilder-config': '{}' },
            children: [heading, perPage],
        });
        heading.parent = () => section;
        perPage.parent = () => section;

        // The Content panel gate (`matchesRoot`): markers must live on the root tree.
        expect(scopeHasDeclarativeFields(section)).toBe(true);
        expect(findDeclarativeFields(section).map((field) => field.key)).toEqual(['heading', 'per_page']);
    });

    it('has no scope when only saved page HTML is on the canvas', () => {
        // Saved HTML never carries the editor-only markers, which is why the
        // canvas must refresh page-content blocks: without the round-trip the
        // Content inspector stays empty ("No settings for this element").
        const heading = mockComponent({
            attrs: { class: 'text-xl font-semibold text-vp-text-1' },
            content: 'Explore the site',
        });
        const section = mockComponent({
            attrs: { 'data-voodbuilder-block': 'demo_pages_explorer', 'data-voodbuilder-config': '{}' },
            children: [heading],
        });
        heading.parent = () => section;

        expect(scopeHasDeclarativeFields(section)).toBe(false);
    });
});

describe('declarative-fields → dynamic block config', () => {
    const originalDocument = globalThis.document;

    beforeAll(() => {
        // parseBlockConfig decodes entities through a throwaway <textarea>; this suite
        // runs in the node environment, so provide the narrowest faithful stand-in.
        globalThis.document = {
            createElement: () => {
                let html = '';

                return {
                    set innerHTML(value) {
                        html = String(value);
                    },
                    get value() {
                        return html
                            .replace(/&quot;/g, '"')
                            .replace(/&lt;/g, '<')
                            .replace(/&gt;/g, '>')
                            .replace(/&amp;/g, '&');
                    },
                };
            },
        };
    });

    afterAll(() => {
        if (originalDocument === undefined) {
            delete globalThis.document;

            return;
        }

        globalThis.document = originalDocument;
    });

    function mockBlockRoot({ blockId = 'demo_pages_explorer', config = {} } = {}) {
        const attrs = {
            'data-voodbuilder-block': blockId,
            'data-voodbuilder-config': JSON.stringify(config),
        };

        const root = {
            attrs,
            getAttributes: () => ({ ...attrs }),
            get: () => undefined,
            set: (key, value) => {
                root.lastSet = { key, value };
            },
            addAttributes: (next) => {
                Object.assign(attrs, next);
            },
            parent: () => null,
        };

        return root;
    }

    function configOf(root) {
        return parseBlockConfig(root.attrs['data-voodbuilder-config']);
    }

    it('routes a marker value into the block config and leaves the preview DOM alone', () => {
        const root = mockBlockRoot({ config: { heading: 'Old heading', per_page: 4 } });
        const heading = mockComponent({ attrs: { 'data-vb-field': 'heading' }, content: 'Old heading' });
        heading.parent = () => root;
        heading.components = (next) => {
            if (typeof next === 'string') {
                heading.rewritten = next;
            }

            return [];
        };

        writeFieldText(heading, 'New heading', null);

        expect(configOf(root)).toEqual({ heading: 'New heading', per_page: 4 });
        expect(root.lastSet?.key).toBe('voodbuilderConfig');
        // The block re-renders from the server: patching the canvas DOM would be lost.
        expect(heading.rewritten).toBeUndefined();
    });

    it('coerces number and status markers to config types', () => {
        const root = mockBlockRoot({ config: { per_page: 4, show_search: false } });

        const perPage = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '4',
        });
        perPage.parent = () => root;

        const toggle = mockComponent({
            attrs: { 'data-vb-field': 'show_search', 'data-vb-field-type': 'status' },
            content: '',
        });
        toggle.parent = () => root;

        writeFieldText(perPage, '9', null);
        writeStatusValue(toggle, 'yes', null);

        expect(configOf(root)).toEqual({ per_page: 9, show_search: true });
    });

    it('keeps static sections writing into the preview DOM', () => {
        const section = mockComponent({ attrs: { 'data-voodbuilder-section-block': 'demo_static' } });
        section.parent = () => null;
        const heading = mockComponent({ attrs: { 'data-vb-field': 'heading' }, content: 'Old' });
        heading.parent = () => section;

        writeFieldText(heading, 'New', null);

        expect(readFieldText(heading)).toBe('New');
    });

    it('marks the page unsaved when a dynamic field really changes the config', () => {
        const root = mockBlockRoot({ config: { heading: 'Explore the site', per_page: 5 } });
        const perPage = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '5',
        });
        perPage.parent = () => root;

        const editor = mockDirtyTrackingEditor();

        writeFieldText(perPage, '3', editor);

        expect(configOf(root)).toEqual({ heading: 'Explore the site', per_page: 3 });
        // The config write is silent: without this the page stayed "clean" and a Save
        // could take the read-only path and never post the value.
        expect(editor.unsavedMarks).toBe(1);
    });

    it('does not mark the page unsaved when the write repeats the stored value', () => {
        const root = mockBlockRoot({ config: { per_page: 5 } });
        const perPage = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '5',
        });
        perPage.parent = () => root;

        const editor = mockDirtyTrackingEditor();

        writeFieldText(perPage, '5', editor);

        expect(editor.unsavedMarks).toBe(0);
    });

    it('ignores an emptied number field instead of writing 0 into the config', () => {
        const root = mockBlockRoot({ config: { heading: 'Explore the site', per_page: 5 } });
        const perPage = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '5',
        });
        perPage.parent = () => root;
        perPage.components = (next) => {
            if (typeof next === 'string') {
                perPage.rewritten = next;
            }

            return [];
        };

        const editor = mockDirtyTrackingEditor();

        writeFieldText(perPage, '', editor);

        // Clearing the box is a moment of typing: coercing it wrote per_page: 0, rendered
        // the block with a count nobody asked for and persisted that 0 on the next Save.
        expect(configOf(root)).toEqual({ heading: 'Explore the site', per_page: 5 });
        expect(perPage.rewritten).toBeUndefined();
        expect(editor.unsavedMarks).toBe(0);
    });
});

describe('declarative-fields → dynamic block render freshness', () => {
    const originalDocument = globalThis.document;

    beforeAll(() => {
        globalThis.document = {
            createElement: () => {
                let html = '';

                return {
                    set innerHTML(value) {
                        html = String(value);
                    },
                    get value() {
                        return html
                            .replace(/&quot;/g, '"')
                            .replace(/&lt;/g, '<')
                            .replace(/&gt;/g, '>')
                            .replace(/&amp;/g, '&');
                    },
                };
            },
        };
    });

    afterAll(() => {
        if (originalDocument === undefined) {
            delete globalThis.document;

            return;
        }

        globalThis.document = originalDocument;
    });

    function mockBlockRoot({ blockId = 'demo_pages_explorer', config = {} } = {}) {
        const attrs = {
            'data-voodbuilder-block': blockId,
            'data-voodbuilder-config': JSON.stringify(config),
        };

        const root = {
            attrs,
            getAttributes: () => ({ ...attrs }),
            get: () => undefined,
            set: () => {},
            addAttributes: (next) => {
                Object.assign(attrs, next);
            },
            parent: () => null,
        };

        return root;
    }

    it('drops the response of a render the author already outran', () => {
        const requestFingerprint = dynamicBlockRenderFingerprint('demo_pages_explorer', {
            heading: 'Explore the site',
            per_page: 5,
        });

        const liveConfig = { heading: 'Explore the site', per_page: 3 };

        expect(isStaleDynamicRender({
            requestFingerprint,
            blockId: 'demo_pages_explorer',
            liveConfig,
        })).toBe(true);

        expect(isStaleDynamicRender({
            requestFingerprint,
            blockId: 'demo_pages_explorer',
            liveConfig: { heading: 'Explore the site', per_page: 5 },
        })).toBe(false);

        // A different block id never matches a fingerprint of this block.
        expect(isStaleDynamicRender({
            requestFingerprint,
            blockId: 'voodflow_other_block',
            liveConfig: { heading: 'Explore the site', per_page: 5 },
        })).toBe(true);
    });

    it('keeps the newest value through the clear → type → response cycle', () => {
        const root = mockBlockRoot({ config: { heading: 'Explore the site', per_page: 5 } });
        const perPage = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '5',
        });
        perPage.parent = () => root;

        const editor = {
            unsavedMarks: 0,
            __voodbuilderMarkPageUnsaved() {
                this.unsavedMarks += 1;
            },
        };

        const configOf = () => parseBlockConfig(root.attrs['data-voodbuilder-config']);
        const fingerprintOf = () => dynamicBlockRenderFingerprint('demo_pages_explorer', configOf());

        // The render for the saved config is already in flight …
        const inFlightFingerprint = fingerprintOf();

        // … the author clears the box (no value to write) …
        writeFieldText(perPage, '', editor);

        expect(configOf().per_page).toBe(5);

        // … and types the real value while that render is still running.
        writeFieldText(perPage, '3', editor);

        expect(configOf()).toEqual({ heading: 'Explore the site', per_page: 3 });
        expect(editor.unsavedMarks).toBe(1);

        // Merging the in-flight response (pre-1.11.80 behaviour) put the old value back:
        // 3 reverted to 5 in the canvas, in the payload and in the database.
        expect(isStaleDynamicRender({
            requestFingerprint: inFlightFingerprint,
            blockId: 'demo_pages_explorer',
            liveConfig: configOf(),
        })).toBe(true);

        // The pass queued by the edit renders the newest config, so it is applied.
        expect(isStaleDynamicRender({
            requestFingerprint: fingerprintOf(),
            blockId: 'demo_pages_explorer',
            liveConfig: configOf(),
        })).toBe(false);
    });

    it('treats a running render — this block or another — as busy', () => {
        const editor = { __voodbuilderDynamicBlockRefreshing: 0 };

        expect(isDynamicRefreshBusy(editor, {})).toBe(false);
        expect(isDynamicRefreshBusy(editor, { __voodbuilderRefreshing: true })).toBe(true);
        expect(isDynamicRefreshBusy({ __voodbuilderDynamicBlockRefreshing: 1 }, {})).toBe(true);
    });
});

describe('declarative-fields → dynamic block config element sync', () => {
    const originalDocument = globalThis.document;

    beforeAll(() => {
        globalThis.document = {
            createElement: () => {
                let html = '';

                return {
                    set innerHTML(value) {
                        html = String(value);
                    },
                    get value() {
                        return html
                            .replace(/&quot;/g, '"')
                            .replace(/&lt;/g, '<')
                            .replace(/&gt;/g, '>')
                            .replace(/&amp;/g, '&');
                    },
                };
            },
        };
    });

    afterAll(() => {
        if (originalDocument === undefined) {
            delete globalThis.document;

            return;
        }

        globalThis.document = originalDocument;
    });

    /**
     * Block root whose canvas element ("view") has its own attribute map, like the
     * real DOM: the model's `attributes` and the element's attributes are separate,
     * and only a view render / explicit write makes them agree.
     */
    function mockBlockRootWithView({ blockId = 'demo_pages_explorer', config = {} } = {}) {
        const attrs = {
            'data-voodbuilder-block': blockId,
            'data-voodbuilder-config': JSON.stringify(config),
        };
        // Starts equal (as after the initial render) and must be re-synced on edit.
        const elementAttrs = { ...attrs };

        return {
            attrs,
            elementAttrs,
            getAttributes: () => ({ ...attrs }),
            get: () => undefined,
            set: () => {},
            addAttributes: (next) => {
                Object.assign(attrs, next);
            },
            getView: () => ({
                el: {
                    setAttribute: (name, value) => {
                        elementAttrs[name] = String(value);
                    },
                },
            }),
            parent: () => null,
        };
    }

    function configOf(root) {
        return parseBlockConfig(root.attrs['data-voodbuilder-config']);
    }

    it('mirrors the new config onto the canvas element right after the silent write', () => {
        const root = mockBlockRootWithView({ config: { heading: 'Explore the site', per_page: 5 } });
        const perPage = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '5',
        });
        perPage.parent = () => root;

        writeFieldText(perPage, '3', null);

        // Model updated …
        expect(configOf(root)).toEqual({ heading: 'Explore the site', per_page: 3 });
        // … and the element agrees without waiting for an unrelated Grapes resync:
        // before the fix the canvas kept `per_page: 5` while the model had `3`.
        expect(root.elementAttrs['data-voodbuilder-config'])
            .toBe(root.attrs['data-voodbuilder-config']);
        expect(parseBlockConfig(root.elementAttrs['data-voodbuilder-config']))
            .toEqual({ heading: 'Explore the site', per_page: 3 });
    });

    it('mirrors status writes too', () => {
        const root = mockBlockRootWithView({ config: { show_search: false } });
        const toggle = mockComponent({
            attrs: { 'data-vb-field': 'show_search', 'data-vb-field-type': 'status' },
            content: '',
        });
        toggle.parent = () => root;

        writeStatusValue(toggle, 'yes', null);

        expect(configOf(root)).toEqual({ show_search: true });
        expect(parseBlockConfig(root.elementAttrs['data-voodbuilder-config']))
            .toEqual({ show_search: true });
    });

    it('leaves the element alone when the write is swallowed (emptied number box)', () => {
        const root = mockBlockRootWithView({ config: { per_page: 5 } });
        const elementBefore = root.elementAttrs['data-voodbuilder-config'];
        const perPage = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '5',
        });
        perPage.parent = () => root;

        writeFieldText(perPage, '', null);

        expect(configOf(root)).toEqual({ per_page: 5 });
        expect(root.elementAttrs['data-voodbuilder-config']).toBe(elementBefore);
    });

    it('writes the model even when the block has no view yet (nothing to sync)', () => {
        const attrs = { 'data-voodbuilder-block': 'demo_pages_explorer', 'data-voodbuilder-config': JSON.stringify({ per_page: 5 }) };
        const root = {
            attrs,
            getAttributes: () => ({ ...attrs }),
            get: () => undefined,
            set: () => {},
            addAttributes: (next) => {
                Object.assign(attrs, next);
            },
            // No getView: importing a detached block must not throw.
            parent: () => null,
        };
        const perPage = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '5',
        });
        perPage.parent = () => root;

        expect(() => writeFieldText(perPage, '3', null)).not.toThrow();
        expect(configOf(root)).toEqual({ per_page: 3 });
    });
});