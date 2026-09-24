import { describe, expect, it, vi } from 'vitest';
import {
    PAGE_SURFACE_CLASS,
    isPageSurfaceMode,
    isTargetingPageSurface,
    remapPageSurfaceCssForPublish,
    resolveStyleTarget,
    selectPageSurface,
} from '../../resources/js/editor/page-surface-styles.js';

describe('page-surface-styles', () => {
    it('resolveStyleTarget falls back to wrapper when nothing is selected', () => {
        const wrapper = { id: 'wrap', get: (key) => (key === 'type' ? 'wrapper' : null) };
        const editor = {
            getSelected: () => null,
            getWrapper: () => wrapper,
        };

        expect(isTargetingPageSurface(editor)).toBe(true);
        expect(resolveStyleTarget(editor)).toBe(wrapper);
    });

    it('resolveStyleTarget prefers the selected component', () => {
        const selected = { id: 'sel', get: () => 'section' };
        const editor = {
            getSelected: () => selected,
            getWrapper: () => ({ id: 'wrap', get: () => 'wrapper' }),
        };

        expect(isTargetingPageSurface(editor)).toBe(false);
        expect(resolveStyleTarget(editor)).toBe(selected);
    });

    it('selectPageSurface selects the wrapper and clears force flag', () => {
        const wrapper = {
            get: (key) => (key === 'type' ? 'wrapper' : null),
            getClasses: () => [],
            addClass: vi.fn(),
            set: vi.fn(),
        };
        const select = vi.fn();
        const trigger = vi.fn();
        let selected = null;
        const editor = {
            getSelected: () => selected,
            getWrapper: () => wrapper,
            select: (...args) => {
                select(...args);
                selected = args[0] ?? null;
            },
            trigger,
        };

        expect(isPageSurfaceMode(editor)).toBe(true);
        expect(selectPageSurface(editor)).toBe(wrapper);
        expect(select).toHaveBeenCalledWith(wrapper, { scroll: false });
        expect(wrapper.addClass).toHaveBeenCalledWith(PAGE_SURFACE_CLASS);
        expect(wrapper.set).toHaveBeenCalledWith(expect.objectContaining({
            selectable: true,
            locked: false,
        }));
        expect(editor.__voodbuilderForcePageSurfaceStyle).toBe(false);
        expect(trigger).toHaveBeenCalled();
    });

    it('selectPageSurface forces page targeting when Grapes rejects wrapper select', () => {
        const wrapper = {
            get: (key) => (key === 'type' ? 'wrapper' : null),
            getClasses: () => [],
            addClass: vi.fn(),
            set: vi.fn(),
        };
        const select = vi.fn();
        const editor = {
            getSelected: () => null,
            getWrapper: () => wrapper,
            select,
            trigger: vi.fn(),
        };

        expect(selectPageSurface(editor)).toBe(wrapper);
        expect(editor.__voodbuilderForcePageSurfaceStyle).toBe(true);
        expect(isTargetingPageSurface(editor)).toBe(true);
        expect(resolveStyleTarget(editor)).toBe(wrapper);
    });

    it('remapPageSurfaceCssForPublish rewrites wrapper id rules to body', () => {
        const editor = {
            getWrapper: () => ({ getId: () => 'iabc123' }),
        };
        const css = '#iabc123 { background-image: url(/x.jpg); } .keep { color: red; }';
        const remapped = remapPageSurfaceCssForPublish(editor, css);

        expect(remapped).toContain(`body, body.${PAGE_SURFACE_CLASS}, .${PAGE_SURFACE_CLASS}`);
        expect(remapped).toContain('background-image: url(/x.jpg)');
        expect(remapped).not.toContain('#iabc123');
        expect(remapped).toContain('.keep { color: red; }');
    });
});
