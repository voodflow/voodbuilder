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
