/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    isValidDomAttributeName,
    stripInvalidDomAttributesFromHtml,
} from '../../resources/js/editor/core/html-sanitize.js';
import { ATTR } from '../../resources/js/editor/core/attrs.js';
import {
    hasInvalidLayerChildren,
    sanitizeComponentTreeForLayers,
} from '../../resources/js/editor/core/component-model.js';
import { findPrimaryBlock, readBlockId } from '../../resources/js/editor/core/block-tree.js';
import {
    ensureRootInspectable,
    findInspectableRoot,
    findLayoutChromeZoneBlockRoot,
    shouldPromoteSelectionToRoot,
} from '../../resources/js/editor/blocks/settings/select.js';
import {
    registerBlockSettings,
    resolveSettings,
} from '../../resources/js/editor/blocks/settings/registry.js';
import {
    rebuildLayoutChromeBlockRegistry,
    resolveLayoutChromeBlock,
    getLayoutChromeBlock,
    setActiveLayoutSettingsRoot,
    resolveLayoutChromeZone,
} from '../../resources/js/editor/blocks/settings/layout-chrome-registry.js';

function mockComponent(attrs = {}, children = [], parent = null) {
    const state = { ...attrs };
    const childModels = children.map((child) => mockComponent(child.attrs ?? {}, child.children ?? [], null));

    const component = {
        getAttributes: () => state,
        getClasses: () => String(state.class ?? '')
            .split(/\s+/)
            .map((name) => name.trim())
            .filter(Boolean),
        get: (key) => {
            if (key === 'type') {
                return state.type ?? 'default';
            }

            if (Object.hasOwn(state, key)) {
                return state[key];
            }

            return undefined;
        },
        set: (values) => {
            Object.assign(state, values);
        },
        parent: () => parent,
        components: () => ({
            models: childModels,
            forEach: (fn) => childModels.forEach(fn),
        }),
    };

    for (const child of childModels) {
        child.parent = () => component;
    }

    return component;
}

describe('core/html-sanitize', () => {
    it('rejects uncompiled Blade attribute names', () => {
        expect(isValidDomAttributeName('@hidden(!$isActive)')).toBe(false);
        expect(isValidDomAttributeName('class')).toBe(true);
    });

    it('stripInvalidDomAttributesFromHtml removes leaked Blade fragments', () => {
        const html = '<div data-voodbuilder-nav-mobile-panel @hidden(!$isActive)><span>Menu</span></div>';
        const cleaned = stripInvalidDomAttributesFromHtml(html);

        expect(cleaned).not.toContain('@hidden');
        expect(cleaned).toContain('data-voodbuilder-nav-mobile-panel');
    });
});

describe('core/block-tree', () => {
    it('readBlockId returns trimmed block id', () => {
        const component = mockComponent({ [ATTR.block]: '  site_nav_simple  ' });

        expect(readBlockId(component)).toBe('site_nav_simple');
    });

    it('findPrimaryBlock resolves nested block without DOM', () => {
        const zone = mockComponent(
            { [ATTR.dropZone]: 'nav' },
            [{ attrs: { [ATTR.block]: 'site_nav_simple' } }],
        );

        expect(findPrimaryBlock(zone)?.getAttributes?.()[ATTR.block]).toBe('site_nav_simple');
    });
});

describe('blocks/settings/select', () => {
    it('findInspectableRoot resolves inner div inside nav block without getEl', () => {
        const navBlock = mockComponent({ [ATTR.block]: 'site_nav_simple' });
        const inner = mockComponent({ class: 'nav-link' }, [], navBlock);
        const zone = mockComponent(
            { [ATTR.dropZone]: 'nav', type: 'voodbuilder-chrome-drop-zone' },
            [{ attrs: navBlock.getAttributes() }],
        );
        const editor = { __voodbuilderChromeLayoutMode: true };

        expect(readBlockId(findInspectableRoot(inner, editor))).toBe('site_nav_simple');
        expect(readBlockId(findInspectableRoot(zone, editor))).toBe('site_nav_simple');
    });

    it('findInspectableRoot resolves block inside drop zone without getEl', () => {
        const navBlock = mockComponent({ [ATTR.block]: 'site_nav_simple' });
        const zone = mockComponent(
            { [ATTR.dropZone]: 'nav', type: 'voodbuilder-chrome-drop-zone' },
            [{ attrs: navBlock.getAttributes() }],
        );
        const editor = { __voodbuilderChromeLayoutMode: true };

        const root = findInspectableRoot(zone, editor);

        expect(readBlockId(root)).toBe('site_nav_simple');
    });

    it('findInspectableRoot returns null for layout content slot', () => {
        const slot = mockComponent({ [ATTR.contentSlot]: 'main', type: 'voodbuilder-chrome-content-slot' });
        const editor = { __voodbuilderChromeLayoutMode: true };

        expect(findInspectableRoot(slot, editor)).toBeNull();
    });

    it('shouldPromoteSelectionToRoot promotes from inner child even when root is not selectable', () => {
        const root = mockComponent({ [ATTR.block]: 'site_footer_social', selectable: false });
        const inner = mockComponent({ class: 'inner' }, [], root);
        const editor = { __voodbuilderChromeLayoutMode: true };

        expect(shouldPromoteSelectionToRoot(inner, root, editor)).toBe(true);
    });

    it('shouldPromoteSelectionToRoot keeps animated nodes selectable', () => {
        const root = mockComponent({ [ATTR.block]: 'site_footer_social' });
        const plasma = mockComponent({
            class: 'h-32 blur-[100px] animate-spin animate-duration-1000',
        }, [], root);

        expect(shouldPromoteSelectionToRoot(plasma, root, {})).toBe(false);
    });

    it('shouldPromoteSelectionToRoot skips while layers selection is pinned', () => {
        const root = mockComponent({ [ATTR.block]: 'site_footer_social' });
        const inner = mockComponent({ class: 'inner' }, [], root);
        const editor = {
            __voodbuilderLayersSelectionPin: inner,
            __voodbuilderLayersSelectionPinUntil: Date.now() + 1000,
        };

        expect(shouldPromoteSelectionToRoot(inner, root, editor)).toBe(false);
    });

    it('findLayoutChromeZoneBlockRoot resolves block inside default wrapper', () => {
        const zone = mockComponent(
            { [ATTR.dropZone]: 'footer', type: 'voodbuilder-chrome-drop-zone' },
            [{
                attrs: { class: 'wrapper' },
                children: [{ attrs: { [ATTR.block]: 'site_footer_centered' } }],
            }],
        );
        const footer = zone.components().models[0].components().models[0];
        const inner = mockComponent({ class: 'footer-link' }, [], footer);
        const editor = { __voodbuilderChromeLayoutMode: true };

        expect(readBlockId(findLayoutChromeZoneBlockRoot(inner, editor))).toBe('site_footer_centered');
        expect(readBlockId(findLayoutChromeZoneBlockRoot(zone, editor))).toBe('site_footer_centered');
    });

    it('ensureRootInspectable restores selection flags on block root', () => {
        const root = mockComponent({
            [ATTR.block]: 'site_nav_simple',
            selectable: false,
            hoverable: false,
            highlightable: false,
            layerable: false,
        });

        ensureRootInspectable(root);

        expect(root.get('selectable')).toBe(true);
        expect(root.get('hoverable')).toBe(true);
        expect(root.get('highlightable')).toBe(true);
        expect(root.get('layerable')).toBe(true);
    });

    it('findLayoutChromeZoneBlockRoot resolves block root even without drop-zone ancestor', () => {
        const navBlock = mockComponent({ [ATTR.block]: 'site_nav_simple' });
        const inner = mockComponent({ class: 'nav-link' }, [], navBlock);
        const editor = { __voodbuilderChromeLayoutMode: true };

        expect(readBlockId(findLayoutChromeZoneBlockRoot(inner, editor))).toBe('site_nav_simple');
        expect(readBlockId(findLayoutChromeZoneBlockRoot(navBlock, editor))).toBe('site_nav_simple');
    });

    it('resolveSettings still matches after selecting the same root twice (refresh identity)', () => {
        registerBlockSettings({
            id: 'test_nav_refresh_identity',
            layoutOnly: true,
            matchBlockId: (blockId) => blockId === 'site_nav_refresh_identity',
            findRoot: findLayoutChromeZoneBlockRoot,
            render: () => {},
        });

        const navBlock = mockComponent({ [ATTR.block]: 'site_nav_refresh_identity' });
        const zone = mockComponent(
            { [ATTR.dropZone]: 'nav', type: 'voodbuilder-chrome-drop-zone' },
            [],
        );
        const wrapper = mockComponent({ type: 'wrapper' }, []);
        zone.parent = () => wrapper;
        navBlock.parent = () => zone;
        zone.components = () => ({
            models: [navBlock],
            forEach: (fn) => [navBlock].forEach(fn),
        });
        wrapper.components = () => ({
            models: [zone],
            forEach: (fn) => [zone].forEach(fn),
        });

        const editor = {
            __voodbuilderChromeLayoutMode: true,
            getWrapper: () => wrapper,
        };

        rebuildLayoutChromeBlockRegistry(editor);
        setActiveLayoutSettingsRoot(editor, navBlock, 'nav');

        const first = resolveSettings(navBlock, editor);
        const second = resolveSettings(navBlock, editor);

        expect(first.descriptor?.id).toBe('test_nav_refresh_identity');
        expect(second.descriptor?.id).toBe('test_nav_refresh_identity');
        expect(first.root).toBe(navBlock);
        expect(second.root).toBe(navBlock);
    });
});

describe('blocks/settings/registry', () => {
    it('resolveSettings matches footer block ids via matchBlockId', () => {
        registerBlockSettings({
            id: 'test_footer',
            layoutOnly: true,
            matchBlockId: (blockId) => typeof blockId === 'string' && blockId.startsWith('site_footer_'),
            render: () => {},
        });

        const footer = mockComponent({ [ATTR.block]: 'site_footer_centered' });
        const editor = { __voodbuilderChromeLayoutMode: true };
        const { descriptor, root } = resolveSettings(footer, editor);

        expect(descriptor?.id).toBe('test_footer');
        expect(root).toBe(footer);
    });

    it('resolveSettings falls back to layout chrome registry when selection is drop zone', () => {
        registerBlockSettings({
            id: 'test_nav_registry',
            layoutOnly: true,
            matchBlockId: (blockId) => blockId === 'site_nav_simple',
            render: () => {},
        });

        const navBlock = mockComponent({ [ATTR.block]: 'site_nav_simple' });
        const zone = mockComponent(
            { [ATTR.dropZone]: 'nav', type: 'voodbuilder-chrome-drop-zone' },
            [{ attrs: navBlock.getAttributes() }],
        );
        const wrapper = mockComponent({ type: 'wrapper' }, [{ attrs: zone.getAttributes(), children: [{ attrs: navBlock.getAttributes() }] }]);
        zone.parent = () => wrapper;
        navBlock.parent = () => zone;

        const editor = {
            __voodbuilderChromeLayoutMode: true,
            getWrapper: () => wrapper,
        };

        rebuildLayoutChromeBlockRegistry(editor);

        const { descriptor, root } = resolveSettings(zone, editor);

        expect(descriptor?.id).toBe('test_nav_registry');
        expect(readBlockId(root)).toBe('site_nav_simple');
        expect(readBlockId(getLayoutChromeBlock(editor, 'nav'))).toBe('site_nav_simple');
    });

    it('resolveSettings uses active settings root when selection is ambiguous', () => {
        registerBlockSettings({
            id: 'test_active_footer',
            layoutOnly: true,
            matchBlockId: (blockId) => blockId === 'layout_footer_active',
            render: () => {},
        });

        const footer = mockComponent({ [ATTR.block]: 'layout_footer_active' });
        const editor = { __voodbuilderChromeLayoutMode: true };

        setActiveLayoutSettingsRoot(editor, footer, 'footer');

        const wrapperChild = mockComponent({ class: 'wrapper-div' });
        const { descriptor, root } = resolveSettings(wrapperChild, editor);

        expect(descriptor?.id).toBe('test_active_footer');
        expect(root).toBe(footer);
    });
});

describe('blocks/settings/layout-chrome-registry', () => {
    it('rebuildLayoutChromeBlockRegistry indexes nav and footer blocks from model tree', () => {
        const navBlock = mockComponent({ [ATTR.block]: 'site_nav_simple' });
        const footerBlock = mockComponent({ [ATTR.block]: 'site_footer_centered' });
        const navZone = mockComponent(
            { [ATTR.dropZone]: 'nav' },
            [{ attrs: navBlock.getAttributes() }],
        );
        const footerZone = mockComponent(
            { [ATTR.dropZone]: 'footer' },
            [{ attrs: footerBlock.getAttributes() }],
        );
        const wrapper = mockComponent(
            { type: 'wrapper' },
            [
                { attrs: navZone.getAttributes(), children: [{ attrs: navBlock.getAttributes() }] },
                { attrs: footerZone.getAttributes(), children: [{ attrs: footerBlock.getAttributes() }] },
            ],
        );

        navZone.parent = () => wrapper;
        footerZone.parent = () => wrapper;
        navBlock.parent = () => navZone;
        footerBlock.parent = () => footerZone;

        const editor = {
            __voodbuilderChromeLayoutMode: true,
            getWrapper: () => wrapper,
        };

        const registry = rebuildLayoutChromeBlockRegistry(editor);

        expect(registry.nav).toBe('site_nav_simple');
        expect(registry.footer).toBe('site_footer_centered');
        expect(readBlockId(resolveLayoutChromeBlock(editor, 'nav'))).toBe('site_nav_simple');
        expect(readBlockId(getLayoutChromeBlock(editor, 'footer'))).toBe('site_footer_centered');
        expect(resolveLayoutChromeZone(navZone)).toBe('nav');
        expect(resolveLayoutChromeZone(footerBlock)).toBe('footer');
    });

    it('resolveLayoutChromeBlock walks the live tree after block replacement', () => {
        const navBlock = mockComponent({ [ATTR.block]: 'site_nav_simple' });
        const navZone = mockComponent(
            { [ATTR.dropZone]: 'nav' },
            [{ attrs: navBlock.getAttributes() }],
        );
        const initialBlock = navZone.components().models[0];
        const wrapper = mockComponent({ type: 'wrapper' }, []);

        wrapper.components = () => ({
            models: [navZone],
            forEach: (fn) => [navZone].forEach(fn),
        });
        navZone.parent = () => wrapper;

        const editor = {
            __voodbuilderChromeLayoutMode: true,
            getWrapper: () => wrapper,
        };

        expect(readBlockId(getLayoutChromeBlock(editor, 'nav'))).toBe('site_nav_simple');
        expect(getLayoutChromeBlock(editor, 'nav')).toBe(initialBlock);

        const replacement = mockComponent({ [ATTR.block]: 'site_nav_simple' });
        navZone.components = () => ({
            models: [replacement],
            forEach: (fn) => [replacement].forEach(fn),
        });
        replacement.parent = () => navZone;

        expect(readBlockId(getLayoutChromeBlock(editor, 'nav'))).toBe('site_nav_simple');
        expect(getLayoutChromeBlock(editor, 'nav')).toBe(replacement);
        expect(getLayoutChromeBlock(editor, 'nav')).not.toBe(initialBlock);
    });
});

describe('core/component-model', () => {
    it('sanitizeComponentTreeForLayers removes null children from collections', () => {
        const validChild = mockComponent({ [ATTR.block]: 'hero' });
        const parent = mockComponent({}, [validChild.getAttributes()]);
        const collection = parent.components();

        collection.models.push(null);

        expect(hasInvalidLayerChildren(parent)).toBe(true);

        sanitizeComponentTreeForLayers(parent);

        expect(hasInvalidLayerChildren(parent)).toBe(false);
        expect(collection.models).toHaveLength(1);
    });
});

describe('theme-tokens background clear', () => {
    it('treats transparent and empty as cleared', async () => {
        const {
            isClearedBackground,
            isClearedBackgroundImage,
            isStyleManagerDefaultWhiteBackground,
            styleHasAuthorBackgroundPaint,
        } = await import(
            '../../resources/js/editor/theme-tokens.js'
        );

        expect(isClearedBackground('')).toBe(true);
        expect(isClearedBackground('transparent')).toBe(true);
        expect(isClearedBackground('none')).toBe(true);
        expect(isClearedBackground('#ff0000')).toBe(false);
        expect(isStyleManagerDefaultWhiteBackground('#ffffff')).toBe(true);
        expect(isStyleManagerDefaultWhiteBackground('#fff !important')).toBe(true);
        expect(isStyleManagerDefaultWhiteBackground('#ff0000')).toBe(false);
        expect(isClearedBackgroundImage('none')).toBe(true);
        expect(isClearedBackgroundImage('url(/bg.jpg)')).toBe(false);
        expect(styleHasAuthorBackgroundPaint({ 'background-image': 'url(/s.png)' })).toBe(true);
        expect(styleHasAuthorBackgroundPaint({ 'background-image': 'none' })).toBe(false);
        expect(styleHasAuthorBackgroundPaint({ 'background-color': '#ffffff' })).toBe(false);

        const { isClearedStyleValue } = await import(
            '../../resources/js/editor/theme-tokens.js'
        );

        expect(isClearedStyleValue('color', '')).toBe(true);
        expect(isClearedStyleValue('display', 'none')).toBe(false);
        expect(isClearedStyleValue('box-shadow', 'none')).toBe(true);
        expect(isClearedStyleValue('color', '#ff0000')).toBe(false);

        const { enforceStyleManagerColorOverUtilities } = await import(
            '../../resources/js/editor/theme-tokens.js'
        );

        const child = {
            classes: ['text-vp-text-2', 'text-lg'],
            getClasses() {
                return this.classes;
            },
            setClass(next) {
                this.classes = next;
            },
            getStyle() {
                return {};
            },
            components() {
                return { forEach() {} };
            },
        };

        const parent = {
            classes: ['text-white', 'p-10'],
            getClasses() {
                return this.classes;
            },
            setClass(next) {
                this.classes = next;
            },
            getStyle() {
                return { color: '#ffffff' };
            },
            components() {
                return {
                    forEach(fn) {
                        fn(child);
                    },
                };
            },
        };

        enforceStyleManagerColorOverUtilities(parent);
        expect(parent.classes).not.toContain('text-white');
        expect(parent.classes).toContain('p-10');
        expect(child.classes).not.toContain('text-vp-text-2');
        expect(child.classes).toContain('text-lg');
    });

    it('splitClassTokens splits pasted class blobs', async () => {
        const { splitClassTokens } = await import(
            '../../resources/js/editor/clipboard.js'
        );

        expect(splitClassTokens('bg-red-800 p-4 text-white')).toEqual([
            'bg-red-800',
            'p-4',
            'text-white',
        ]);
        expect(splitClassTokens('a, b\nc')).toEqual(['a', 'b', 'c']);
    });

    it('extractGrapesComposerCss keeps #id Style Manager rules but drops private classes', async () => {
        const { extractGrapesComposerCss } = await import(
            '../../resources/js/editor/editor/payload.js'
        );

        const css = `
.flex { display: flex }
#hero-title { color: #ff0000 !important; }
@media (min-width: 768px) { #hero-title { font-size: 2rem } }
.c123 { margin: 0; font-family: 'Archivo', sans-serif }
#section-1 { background-color: #0ea5e9 }
`;

        const extracted = extractGrapesComposerCss(css);

        expect(extracted).toContain('#hero-title');
        expect(extracted).toContain('color: #ff0000');
        expect(extracted).toContain('#section-1');
        expect(extracted).not.toContain('.c123');
        expect(extracted).not.toContain('Archivo');
        expect(extracted).not.toContain('.flex');
        expect(extracted).not.toContain('@media');
    });

    it('collectAuthorIdCssFromComponents emits #id rules from inline styles', async () => {
        const { collectAuthorIdCssFromComponents } = await import(
            '../../resources/js/editor/editor/payload.js'
        );

        const components = [
            {
                getId: () => 'title-1',
                getStyle: (opts) => (opts?.inline
                    ? { color: 'rgb(255, 0, 0)', 'font-family': "'Roboto', sans-serif" }
                    : {}),
            },
            {
                getId: () => 'box-2',
                getStyle: () => ({ 'background-color': '#0ea5e9' }),
            },
        ];

        const editor = {
            Css: {
                getIdRule: () => null,
            },
            getWrapper: () => ({
                onAll: (cb) => components.forEach(cb),
            }),
        };

        const css = collectAuthorIdCssFromComponents(editor);

        expect(css).toContain('#title-1');
        expect(css).toContain('color:rgb(255, 0, 0)');
        expect(css).toContain("font-family:'Roboto', sans-serif");
        expect(css).toContain('#box-2');
        expect(css).toContain('background-color:#0ea5e9');
    });

    it('mergeAuthorCssChunks keeps first #id rule and utilities', async () => {
        const { mergeAuthorCssChunks } = await import(
            '../../resources/js/editor/editor/payload.js'
        );

        const merged = mergeAuthorCssChunks([
            '#a {color:red}',
            '#a {color:blue}',
            '#b {font-size:20px}',
            '.flex {display:flex}',
        ]);

        expect(merged).toContain('#a {color:red}');
        expect(merged).not.toContain('#a {color:blue}');
        expect(merged).toContain('#b {font-size:20px}');
        expect(merged).toContain('.flex {display:flex}');
    });

    it('stripAuthorIdRules removes #id but keeps utilities', async () => {
        const { stripAuthorIdRules } = await import(
            '../../resources/js/editor/editor/payload.js'
        );

        const next = stripAuthorIdRules(`
#hero { color: red !important; }
.flex { display: flex }
#box { font-family: 'Roboto', sans-serif }
`);

        expect(next).not.toContain('#hero');
        expect(next).not.toContain('#box');
        expect(next).toContain('.flex');
    });

    it('style:property:update ignores Grapes __up read refreshes', async () => {
        const { registerVisualStyleInspector } = await import(
            '../../resources/js/editor/tailwind-visual-style.js'
        );

        const handlers = {};
        const styles = {};
        const idRules = {};
        const target = {
            getId: () => 'box-1',
            addStyle(next) {
                Object.assign(styles, next);
            },
            getStyle: () => ({ ...styles }),
        };

        const editor = {
            on(event, handler) {
                handlers[event] = handler;
            },
            getSelected: () => target,
            StyleManager: { select() {} },
            Css: {
                getIdRule: (id) => (idRules[id] ? { getStyle: () => ({ ...idRules[id] }) } : null),
                setIdRule(id, style) {
                    idRules[id] = { ...style };
                },
            },
        };

        registerVisualStyleInspector(editor);

        handlers['style:property:update']({
            property: { getName: () => 'background-color' },
            value: '#f3f4f6',
            opts: { __up: true },
        });

        expect(styles['background-color']).toBeUndefined();
        expect(idRules['box-1']).toBeUndefined();

        handlers['style:property:update']({
            property: { getName: () => 'background-color' },
            value: '#0ea5e9',
            opts: {},
        });

        expect(styles['background-color']).toBe('#0ea5e9');
        expect(idRules['box-1']['background-color']).toContain('#0ea5e9');
    });

    it('styleUpdatePropertyNames reads Grapes { style } payload', async () => {
        const { styleUpdatePropertyNames } = await import(
            '../../resources/js/editor/tailwind-visual-style.js'
        );

        expect(styleUpdatePropertyNames('background-color')).toEqual(['background-color']);
        expect(styleUpdatePropertyNames({ style: { 'background-color': '#0ea5e9', color: 'red' } }))
            .toEqual(['background-color', 'color']);
        expect(styleUpdatePropertyNames(null)).toEqual([]);
    });

    it('hydrateAuthorStylesFromIdRules restores background-color after reload', async () => {
        const { hydrateAuthorStylesFromIdRules } = await import(
            '../../resources/js/editor/tailwind-visual-style.js'
        );
        const { collectAuthorIdCssFromComponents } = await import(
            '../../resources/js/editor/editor/payload.js'
        );

        const inline = {};
        const component = {
            getId: () => 'section-bg',
            getStyle: (opts) => (opts?.inline ? { ...inline } : { ...inline }),
            addStyle(next, opts) {
                if (opts?.inline) {
                    Object.assign(inline, next);
                }
            },
        };

        const editor = {
            Css: {
                getIdRule: (id) => (id === 'section-bg'
                    ? { getStyle: () => ({ 'background-color': '#daa0a0 !important' }) }
                    : null),
                setIdRule() {},
            },
            getWrapper: () => ({
                onAll: (cb) => cb(component),
            }),
        };

        const updated = hydrateAuthorStylesFromIdRules(editor);

        expect(updated).toBe(1);
        expect(inline['background-color']).toBe('#daa0a0');

        const css = collectAuthorIdCssFromComponents(editor);

        expect(css).toContain('#section-bg');
        expect(css).toContain('background-color:#daa0a0');
    });

    it('mergeCompiledPageCssWithAuthorIdRules keeps #id background after JIT rebuild', async () => {
        const { mergeCompiledPageCssWithAuthorIdRules } = await import(
            '../../resources/js/editor/page-tailwind-autobuild.js'
        );

        const editor = {
            __voodbuilderPageLiveCss: `
.flex { display: flex }
#section-bg { background-color: #daa0a0 !important; }
`,
            getCss: () => '#section-bg { background-color: #daa0a0 !important; }',
        };

        const merged = mergeCompiledPageCssWithAuthorIdRules(editor, '.text-lg { font-size: 1.125rem }');

        expect(merged).toContain('.text-lg');
        expect(merged).toContain('#section-bg');
        expect(merged).toContain('background-color: #daa0a0');
    });
});

describe('editor/registries', () => {
    it('registers and lists editor commands', async () => {
        const {
            clearEditorCommands,
            listEditorCommands,
            registerEditorCommand,
            applyEditorCommands,
        } = await import('../../resources/js/editor/editor/registries/commands.js');

        clearEditorCommands();
        registerEditorCommand('voodbuilder:test-cmd', () => ({ run() {} }), { source: 'test' });

        expect(listEditorCommands()).toEqual(['voodbuilder:test-cmd']);

        const added = [];
        const editor = {
            Commands: {
                add(id, definition) {
                    added.push({ id, definition });
                },
            },
        };

        applyEditorCommands(editor);
        expect(added).toHaveLength(1);
        expect(added[0].id).toBe('voodbuilder:test-cmd');
        clearEditorCommands();
    });

    it('filters editor panels by when()', async () => {
        const {
            clearEditorPanels,
            registerEditorPanel,
            resolveEditorPanels,
        } = await import('../../resources/js/editor/editor/registries/panels.js');

        clearEditorPanels();
        registerEditorPanel({ id: 'always' });
        registerEditorPanel({
            id: 'popup-only',
            when: (ctx) => ctx.popupMode === true,
        });

        expect(resolveEditorPanels({ popupMode: false }).map((p) => p.id)).toEqual(['always']);
        expect(resolveEditorPanels({ popupMode: true }).map((p) => p.id)).toEqual(['always', 'popup-only']);
        clearEditorPanels();
    });
});

describe('editor entitlements filtering', () => {
    it('filters actions by entitlement flags', async () => {
        const {
            canEntitlement,
            filterActionsByEntitlement,
        } = await import('../../resources/js/editor/editor/entitlements.js');

        const entitlements = {
            templatesImport: false,
            templatesExport: true,
            componentsLibrary: true,
        };

        expect(canEntitlement(entitlements, 'templatesExport')).toBe(true);
        expect(canEntitlement(entitlements, 'templatesImport')).toBe(false);

        const actions = filterActionsByEntitlement([
            { id: 'local' },
            { id: 'import', entitlement: 'templatesImport' },
            { id: 'export', entitlement: 'templatesExport' },
            { id: 'components', entitlement: 'componentsLibrary' },
        ], entitlements);

        expect(actions.map((a) => a.id)).toEqual(['local', 'export', 'components']);
    });
});

describe('canvas nest hosts (basic drops into Heroes)', () => {
    function mockAncestor(attrs = {}, type = 'default') {
        return {
            get: (key) => (key === 'type' ? type : undefined),
            getAttributes: () => ({ ...attrs }),
        };
    }

    function mockNested(ancestors) {
        // ancestors[0] = immediate parent of the leaf … ancestors[n] = wrapper
        let chain = null;

        for (let i = ancestors.length - 1; i >= 0; i -= 1) {
            const parent = chain;
            chain = {
                ...ancestors[i],
                parent: () => parent,
            };
        }

        return {
            get: () => undefined,
            getAttributes: () => ({}),
            parent: () => chain,
        };
    }

    it('treats catalog dropzones and layout-containers as nest hosts', async () => {
        const {
            isIntentionalNestHost,
            isNestedInLayoutStructure,
        } = await import('../../resources/js/editor/canvas-block-drag.js');

        expect(isIntentionalNestHost(mockAncestor({ 'data-voodbuilder-dropzone': 'copy' }, 'voodbuilder-dropzone'))).toBe(true);
        expect(isIntentionalNestHost(mockAncestor({ 'data-voodbuilder-role': 'content' }, 'voodbuilder-layout-container'))).toBe(true);
        expect(isIntentionalNestHost(mockAncestor({ 'data-voodbuilder-section-block': 'vb-hero-2' }, 'voodbuilder-section-dropzones'))).toBe(true);
        expect(isIntentionalNestHost(mockAncestor({ class: 'voodbuilder-editor-container flex' }, 'voodbuilder-layout-container'))).toBe(true);
        expect(isIntentionalNestHost(mockAncestor({ class: 'max-w-3xl' }, 'default'))).toBe(false);

        const headingInsideHero = mockNested([
            mockAncestor({ 'data-voodbuilder-dropzone': 'copy' }, 'voodbuilder-dropzone'),
            mockAncestor({ class: 'max-w-3xl' }, 'default'),
            mockAncestor({ 'data-voodbuilder-role': 'content', class: 'voodbuilder-editor-container' }, 'voodbuilder-layout-container'),
            mockAncestor({ 'data-voodbuilder-section-block': 'vb-hero-2' }, 'voodbuilder-section-dropzones'),
            mockAncestor({}, 'wrapper'),
        ]);

        expect(isNestedInLayoutStructure(headingInsideHero)).toBe(true);
    });
});

describe('layout container column presets preserve content', () => {
    function mockCollection(initial = []) {
        const models = [...initial];

        return {
            models,
            [Symbol.iterator]: () => models[Symbol.iterator](),
            forEach: (fn) => models.forEach(fn),
            at: (index) => models[index],
            length: models.length,
            indexOf: (item) => models.indexOf(item),
            add(child, opts = {}) {
                if (opts.at != null) {
                    models.splice(opts.at, 0, child);
                } else {
                    models.push(child);
                }

                Object.defineProperty(this, 'length', { get: () => models.length, configurable: true });
            },
            remove(child) {
                const index = models.indexOf(child);

                if (index >= 0) {
                    models.splice(index, 1);
                }

                Object.defineProperty(this, 'length', { get: () => models.length, configurable: true });
            },
        };
    }

    function mockComponent({ type = 'default', layout = '', kids = [], name = '' } = {}) {
        const attrs = {};

        if (layout) {
            attrs['data-voodbuilder-layout'] = layout;
        }

        const state = {
            type,
            name,
            classes: layout === 'block' ? ['vb-layout-block', 'min-h-16', 'min-w-0'] : [],
            style: {},
            attrs,
        };

        const collection = mockCollection();

        const component = {
            get: (key) => state[key],
            set: (key, value) => {
                if (typeof key === 'object' && key !== null) {
                    Object.assign(state, key);

                    return;
                }

                state[key] = value;
            },
            getAttributes: () => ({ ...state.attrs }),
            addAttributes: (next) => {
                Object.assign(state.attrs, next);
            },
            getClasses: () => [...state.classes],
            setClass: (classes) => {
                state.classes = [...classes];
            },
            getStyle: () => ({ ...state.style }),
            setStyle: (style) => {
                state.style = { ...style };
            },
            removeStyle: () => {},
            components: (maybe) => {
                if (Array.isArray(maybe)) {
                    collection.models.splice(0, collection.models.length, ...maybe);
                    Object.defineProperty(collection, 'length', {
                        get: () => collection.models.length,
                        configurable: true,
                    });

                    return collection;
                }

                return collection;
            },
            append: (def, opts = {}) => {
                const child = typeof def === 'object' && def.get
                    ? def
                    : mockComponent({
                        type: def.type ?? 'default',
                        layout: def.attributes?.['data-voodbuilder-layout'] ?? '',
                        name: def.name ?? '',
                    });
                child.__parent = component;
                collection.add(child, opts);

                return child;
            },
            move: (target) => {
                const parent = component.__parent;

                if (parent) {
                    parent.components().remove(component);
                }

                component.__parent = target;
                target.components().add(component);
            },
            remove: () => {
                const parent = component.__parent;

                if (parent) {
                    parent.components().remove(component);
                }

                component.__removed = true;
            },
            isRemoved: () => component.__removed === true,
            parent: () => component.__parent ?? null,
        };

        for (const kid of kids) {
            kid.__parent = component;
            collection.add(kid);
        }

        Object.defineProperty(collection, 'length', {
            get: () => collection.models.length,
            configurable: true,
        });

        return component;
    }

    it('keeps content when growing from 1 to 2 columns', async () => {
        const {
            applyContainerLayoutPreset,
            isLayoutBlock,
        } = await import('../../resources/js/editor/layout-blocks.js');

        const heading = mockComponent({ type: 'text', name: 'Heading' });
        const block = mockComponent({ type: 'voodbuilder-layout-block', layout: 'block', kids: [heading] });
        const container = mockComponent({ type: 'voodbuilder-container', layout: 'container', kids: [block] });

        applyContainerLayoutPreset(container, '2');

        const columns = [...container.components()].filter((child) => isLayoutBlock(child));

        expect(columns).toHaveLength(2);
        expect([...columns[0].components()].some((child) => child.get('name') === 'Heading')).toBe(true);
        expect([...columns[1].components()]).toHaveLength(0);
        expect(container.getAttributes()['data-vb-layout-preset']).toBe('2');
    });

    it('merges leftover columns when shrinking from 2 to 1', async () => {
        const {
            applyContainerLayoutPreset,
            isLayoutBlock,
        } = await import('../../resources/js/editor/layout-blocks.js');

        const left = mockComponent({ type: 'text', name: 'Left' });
        const right = mockComponent({ type: 'text', name: 'Right' });
        const col1 = mockComponent({ type: 'voodbuilder-layout-block', layout: 'block', kids: [left] });
        const col2 = mockComponent({ type: 'voodbuilder-layout-block', layout: 'block', kids: [right] });
        const container = mockComponent({
            type: 'voodbuilder-container',
            layout: 'container',
            kids: [col1, col2],
        });

        applyContainerLayoutPreset(container, '1');

        const columns = [...container.components()].filter((child) => isLayoutBlock(child));

        expect(columns).toHaveLength(1);
        const names = [...columns[0].components()].map((child) => child.get('name'));
        expect(names).toEqual(['Left', 'Right']);
    });

    it('preserves author gap-* and does not pin inline gap', async () => {
        const { syncContainerLayoutStyles } = await import('../../resources/js/editor/layout-blocks.js');

        const style = { gap: '1rem', width: '100%' };
        const classes = ['w-full', 'vb-layout-row', 'grid', 'gap-8'];
        const attrs = { 'data-voodbuilder-layout': 'container', 'data-vb-layout-preset': '2' };
        const container = {
            getClasses: () => [...classes],
            setClass: (next) => {
                classes.splice(0, classes.length, ...next);
            },
            getAttributes: () => ({ ...attrs }),
            addAttributes: (next) => Object.assign(attrs, next),
            getStyle: () => ({ ...style }),
            setStyle: (next) => {
                Object.keys(style).forEach((key) => delete style[key]);
                Object.assign(style, next);
            },
            removeStyle: (prop) => {
                delete style[prop];
            },
        };

        syncContainerLayoutStyles(container, '2');

        expect(classes).toContain('gap-8');
        expect(classes).not.toContain('gap-4');
        expect(style.gap).toBeUndefined();
        expect(style.display).toBe('grid');
        expect(style['grid-template-columns']).toContain('1fr');
        expect(attrs['data-vb-layout-tracks']).toContain('1fr');
    });

    it('restores column tracks even when content-width mode is set', async () => {
        const { syncContainerContentWidth } = await import('../../resources/js/editor/layout-blocks.js');

        const style = {
            width: '100%',
            'max-width': '80rem',
            'margin-left': 'auto',
            'margin-right': 'auto',
        };
        const classes = ['w-full', 'vb-layout-row', 'grid', 'gap-4', 'voodbuilder-editor-container', 'mx-auto', 'max-w-[80rem]'];
        const attrs = {
            'data-voodbuilder-layout': 'container',
            'data-vb-layout-preset': '2',
            'data-voodbuilder-content-width': 'normal',
            style: 'width: 100%; max-width: 80rem; margin-left: auto; margin-right: auto',
        };
        const container = {
            getClasses: () => [...classes],
            setClass: (next) => {
                classes.splice(0, classes.length, ...next);
            },
            getAttributes: () => ({ ...attrs }),
            addAttributes: (next) => Object.assign(attrs, next),
            getStyle: () => ({ ...style }),
            setStyle: (next) => {
                Object.keys(style).forEach((key) => delete style[key]);
                Object.assign(style, next);
            },
            removeStyle: (prop) => {
                delete style[prop];
            },
        };

        syncContainerContentWidth(container);

        expect(style['grid-template-columns']).toBe('minmax(0,1fr) minmax(0,1fr)');
        expect(attrs['data-vb-layout-preset']).toBe('2');
        expect(style['max-width']).toBe('80rem');
        expect(classes).toContain('max-w-[80rem]');
        expect(classes).toContain('mx-auto');
        expect(String(attrs.style)).toContain('max-width: 80rem');
    });
});

describe('basic-elements-settings icon apply', () => {
    function mockIconHost({ attrs = {}, classes = [], svgAttrs = {} } = {}) {
        const attrState = { ...attrs };
        let classList = [...classes];
        let styleState = {};
        let children = [];

        if (Object.keys(svgAttrs).length > 0 || attrs['data-vb-icon']) {
            children = [{
                get: (key) => (key === 'tagName' ? 'svg' : undefined),
                getAttributes: () => ({ ...svgAttrs }),
                addAttributes: (next) => Object.assign(svgAttrs, next),
                set: () => {},
                components: () => [],
            }];
        }

        const collection = {
            reset() {
                children = [];
            },
            remove(child) {
                children = children.filter((item) => item !== child);
            },
            [Symbol.iterator]: () => children[Symbol.iterator](),
            forEach: (fn) => children.forEach(fn),
        };

        return {
            getAttributes: () => ({ ...attrState }),
            addAttributes(next) {
                Object.assign(attrState, next);
            },
            getClasses: () => [...classList],
            setClass(next) {
                classList = [...next];
            },
            getStyle: () => ({ ...styleState }),
            setStyle(next) {
                styleState = { ...next };
            },
            get: (key) => attrState[key],
            set(values) {
                Object.assign(attrState, values);
            },
            getEl: () => ({}),
            components(html) {
                if (html === undefined) {
                    return collection;
                }

                if (Array.isArray(html) && html.length === 0) {
                    children = [];

                    return collection;
                }

                const markup = String(html);
                const glyph = /data-vb-icon-glyph="([^"]+)"/.exec(markup)?.[1] ?? '';
                const style = /data-vb-icon-style="([^"]+)"/.exec(markup)?.[1] ?? 'outline';
                const childAttrs = {
                    'data-vb-icon-glyph': glyph,
                    'data-vb-icon-style': style,
                };

                children = [{
                    get: (key) => (key === 'tagName' ? 'svg' : undefined),
                    getAttributes: () => ({ ...childAttrs }),
                    addAttributes: (next) => Object.assign(childAttrs, next),
                    set: () => {},
                    components: () => [],
                }];

                return collection;
            },
            __styleState: () => styleState,
            __classes: () => classList,
            __attrs: () => attrState,
            __childCount: () => children.length,
            __paintedGlyph: () => children[0]?.getAttributes?.()?.['data-vb-icon-glyph'] ?? '',
        };
    }

    it('replaces stale circle glyph when attrs already claim another icon', async () => {
        const { applyIconToComponent } = await import('../../resources/js/editor/basic-elements-settings.js');

        const host = mockIconHost({
            attrs: {
                'data-vb-icon': 'star',
                'data-vb-icon-size': 'size-8',
                'data-vb-icon-style': 'outline',
                'data-vb-icon-stroke': '1.75',
                'data-vb-link-type': 'none',
            },
            classes: ['inline-flex', 'vb-icon-link', 'size-8', 'w-full', 'max-w-[80rem]'],
            svgAttrs: {
                'data-vb-icon-glyph': 'circle',
                'data-vb-icon-style': 'outline',
            },
        });

        applyIconToComponent(host, null, {
            name: 'star',
            sizeClass: 'size-8',
            style: 'outline',
            stroke: '1.75',
            color: '',
            linkType: 'none',
        });

        expect(host.__paintedGlyph()).toBe('star');
        expect(host.__childCount()).toBe(1);
        expect(host.__classes()).not.toContain('w-full');
        expect(host.__classes()).not.toContain('max-w-[80rem]');
        expect(host.__styleState().width).toBe('32px');
        expect(host.__styleState().height).toBe('32px');
        expect(host.__attrs()['data-vb-icon']).toBe('star');
    });

    it('pins box size when only color changes', async () => {
        const { applyIconToComponent } = await import('../../resources/js/editor/basic-elements-settings.js');

        const host = mockIconHost({
            attrs: {
                'data-vb-icon': 'star',
                'data-vb-icon-size': 'size-6',
                'data-vb-icon-style': 'outline',
                'data-vb-icon-stroke': '1.75',
                'data-vb-link-type': 'none',
            },
            classes: ['inline-flex', 'vb-icon-link', 'size-6'],
            svgAttrs: {
                'data-vb-icon-glyph': 'star',
                'data-vb-icon-style': 'outline',
            },
        });

        applyIconToComponent(host, null, {
            name: 'star',
            sizeClass: 'size-6',
            style: 'outline',
            stroke: '1.75',
            color: '#3f7fd9',
            linkType: 'none',
        });

        expect(host.__paintedGlyph()).toBe('star');
        expect(host.__styleState().width).toBe('24px');
        expect(host.__styleState().maxWidth).toBe('24px');
    });
});
