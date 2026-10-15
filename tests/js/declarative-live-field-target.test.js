import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import {
    findDeclarativeFields,
    registerDeclarativeFieldSettings,
} from '../../resources/js/editor/declarative-fields.js';
import { resolveDescriptorForRoot } from '../../resources/js/editor/blocks/settings/registry.js';
import { parseBlockConfig } from '../../resources/js/editor/voodbuilder-dynamic-config.js';

/**
 * The Content panel keeps its controls mounted across a dynamic preview refresh
 * (`component.components(fresh.innerHTML)` replaces only the block children), so the field
 * component a control was built from is detached while the panel keeps typing into it.
 * These go through the registered descriptor render — the same entry point the inspector
 * uses — so the `liveFields` / `liveItems` wiring is covered, not just the helpers.
 */

const originalDocument = globalThis.document;
const originalWindow = globalThis.window;

function createElementStub(tag) {
    const listeners = new Map();
    const attributes = new Map();

    const el = {
        tagName: String(tag).toUpperCase(),
        children: [],
        className: '',
        textContent: '',
        dataset: {},
        style: {},
        classList: {
            add: (name) => {
                if (! el.classList.contains(name)) {
                    el.className = `${el.className} ${name}`.trim();
                }
            },
            remove: (name) => {
                el.className = el.className.split(/\s+/).filter((token) => token && token !== name).join(' ');
            },
            toggle: (name, force) => {
                const on = force === undefined ? ! el.classList.contains(name) : Boolean(force);

                if (on) {
                    el.classList.add(name);
                } else {
                    el.classList.remove(name);
                }

                return on;
            },
            contains: (name) => el.className.split(/\s+/).includes(name),
        },
        setAttribute: (name, value) => {
            attributes.set(name, String(value));
        },
        getAttribute: (name) => (attributes.has(name) ? attributes.get(name) : null),
        removeAttribute: (name) => {
            attributes.delete(name);
        },
        appendChild: (child) => {
            el.children.push(child);

            return child;
        },
        append: (...nodes) => {
            nodes.forEach((node) => el.children.push(node));
        },
        addEventListener: (type, handler) => {
            listeners.set(type, [...(listeners.get(type) ?? []), handler]);
        },
        removeEventListener: (type, handler) => {
            listeners.set(type, (listeners.get(type) ?? []).filter((entry) => entry !== handler));
        },
        dispatchEvent: (event) => {
            (listeners.get(event?.type) ?? []).forEach((handler) => handler(event));

            return true;
        },
        querySelector: () => null,
        querySelectorAll: () => [],
    };

    return el;
}

beforeAll(() => {
    globalThis.document = {
        createElement: (tag) => {
            // parseBlockConfig decodes entities through a throwaway <textarea>.
            if (String(tag).toLowerCase() === 'textarea') {
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
            }

            return createElementStub(tag);
        },
        createTextNode: (text) => ({ nodeType: 3, textContent: String(text) }),
    };

    // The static-field path (the one the bug fell into) defers its caret guard to rAF.
    globalThis.window = {
        requestAnimationFrame: (callback) => callback(),
        setTimeout: (callback) => callback(),
        clearTimeout: () => {},
    };
});

afterAll(() => {
    if (originalDocument === undefined) {
        delete globalThis.document;
    } else {
        globalThis.document = originalDocument;
    }

    if (originalWindow === undefined) {
        delete globalThis.window;
    } else {
        globalThis.window = originalWindow;
    }
});

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

function mockBlockRoot({ blockId = 'demo_pages_explorer', config = {} } = {}) {
    const attrs = {
        'data-voodbuilder-block': blockId,
        'data-voodbuilder-config': JSON.stringify(config),
    };

    const wrapper = { get: (key) => (key === 'type' ? 'wrapper' : undefined), parent: () => null };

    const root = {
        attrs,
        getAttributes: () => ({ ...attrs }),
        get: () => undefined,
        set: (key, value) => {
            root.lastSet = { key, value };
        },
        addAttributes: (next, options = {}) => {
            Object.assign(attrs, next);

            if (! options.silent) {
                root.attributesChanged = true;
            }
        },
        parent: () => wrapper,
        find: () => [],
    };

    return root;
}

function configOf(root) {
    return parseBlockConfig(root.attrs['data-voodbuilder-config']);
}

function collectInputs(node, found = []) {
    if (node?.tagName === 'INPUT' && node.type !== 'checkbox') {
        found.push(node);
    }

    (node?.children ?? []).forEach((child) => collectInputs(child, found));

    return found;
}

function mockDirtyTrackingEditor() {
    return {
        unsavedMarks: 0,
        __voodbuilderDeclarativeFieldSettingsRegistered: true,
        __voodbuilderMarkPageUnsaved() {
            this.unsavedMarks += 1;
        },
    };
}

function openPanel(root, editor, selected = null) {
    const descriptor = resolveDescriptorForRoot(root);

    expect(descriptor?.id).toBe('declarative_fields');

    const mount = document.createElement('div');

    descriptor.render({ mount, root, editor, selected });

    return mount;
}

describe('declarative fields → live field target after a preview refresh', () => {
    beforeAll(() => {
        registerDeclarativeFieldSettings({ __voodbuilderDeclarativeFieldSettingsRegistered: false });
    });

    it('writes the first keystroke after a refresh into the field the server just rendered', () => {
        const root = mockBlockRoot({ config: { per_page: 4 } });
        const editor = mockDirtyTrackingEditor();

        // Panel opened on this tree: `per_page` is bound to the component of that render.
        const boundField = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '4',
        });
        boundField.parent = () => root;
        root.components = () => [boundField];

        const mount = openPanel(root, editor);
        const [input] = collectInputs(mount);

        expect(input?.name).toBe('vbField_per_page');

        // The author types `6`, the server render lands and replaces the block children:
        // `component.components(fresh.innerHTML)` detaches everything below the root.
        boundField.parent = () => null;
        const liveField = mockComponent({
            attrs: { 'data-vb-field': 'per_page', 'data-vb-field-type': 'number' },
            content: '1',
        });
        liveField.parent = () => root;
        root.components = () => [liveField];

        expect(findDeclarativeFields(root).map((field) => field.key)).toEqual(['per_page']);
        expect(findDeclarativeFields(root)[0].component).toBe(liveField);

        input.value = '6';
        input.dispatchEvent({ type: 'input' });

        // Before the fix the keystroke landed on the detached component: the block config
        // kept 4, no preview refresh was scheduled and the page was never marked dirty.
        expect(configOf(root)).toEqual({ per_page: 6 });
        expect(editor.unsavedMarks).toBe(1);
    });

    it('keeps writing through the bound component while it is still on the canvas', () => {
        const root = mockBlockRoot({ config: { heading: 'Explore the site' } });
        const editor = mockDirtyTrackingEditor();

        const heading = mockComponent({
            attrs: { 'data-vb-field': 'heading' },
            content: 'Explore the site',
        });
        heading.parent = () => root;
        root.components = () => [heading];

        const mount = openPanel(root, editor);
        const [input] = collectInputs(mount);

        input.value = 'Docs';
        input.dispatchEvent({ type: 'input' });

        expect(configOf(root)).toEqual({ heading: 'Docs' });
    });

    it('routes an item panel keystroke to the live row after a refresh', () => {
        const root = mockBlockRoot({ config: { title: 'Old' } });
        const editor = mockDirtyTrackingEditor();

        const boundTitle = mockComponent({ attrs: { 'data-vb-field': 'title' }, content: 'Old' });
        const boundItem = mockComponent({ attrs: { 'data-vb-item': '' }, children: [boundTitle], cid: 'i1' });
        boundTitle.parent = () => boundItem;
        boundItem.parent = () => root;

        const itemsRoot = { attrs: { 'data-vb-items-root': '' }, components: () => [boundItem] };

        root.find = (selector) => (selector === '[data-vb-items-root]' ? [itemsRoot] : []);
        root.components = () => [itemsRoot];

        const mount = openPanel(root, editor, boundItem);
        const [input] = collectInputs(mount);

        expect(input?.name).toBe('vbField_title');

        // Refresh: the row (and the field inside it) is replaced under the same root.
        boundTitle.parent = () => null;
        boundItem.parent = () => null;
        const liveTitle = mockComponent({ attrs: { 'data-vb-field': 'title' }, content: 'Old' });
        const liveItem = mockComponent({ attrs: { 'data-vb-item': '' }, children: [liveTitle], cid: 'i1' });
        liveTitle.parent = () => liveItem;
        liveItem.parent = () => root;
        itemsRoot.components = () => [liveItem];
        root.components = () => [itemsRoot];

        input.value = 'New';
        input.dispatchEvent({ type: 'input' });

        expect(configOf(root)).toEqual({ title: 'New' });
        expect(editor.unsavedMarks).toBe(1);
    });
});
