import { describe, expect, it, vi } from 'vitest';
import {
    PAGE_SURFACE_CLASS,
    isPageSurfaceMode,
    remapPageSurfaceCssForPublish,
    resolveStyleTarget,
    selectPageSurface,
} from '../../resources/js/editor/page-surface-styles.js';

describe('page-surface-styles', () => {
    it('resolveStyleTarget falls back to wrapper when nothing is selected', () => {
        const wrapper = { id: 'wrap' };
        const editor = {
            getSelected: () => null,
            getWrapper: () => wrapper,
        };

        expect(resolveStyleTarget(editor)).toBe(wrapper);
    });

    it('resolveStyleTarget prefers the selected component', () => {
        const selected = { id: 'sel' };
        const editor = {
            getSelected: () => selected,
            getWrapper: () => ({ id: 'wrap' }),
        };

        expect(resolveStyleTarget(editor)).toBe(selected);
    });

    it('selectPageSurface selects the wrapper', () => {
        const wrapper = {
            getClasses: () => [],
            addClass: vi.fn(),
            set: vi.fn(),
        };
        const select = vi.fn();
        const editor = {
            getSelected: () => null,
            getWrapper: () => wrapper,
            select,
        };

        expect(isPageSurfaceMode(editor)).toBe(true);
        expect(selectPageSurface(editor)).toBe(wrapper);
        expect(select).toHaveBeenCalledWith(wrapper);
        expect(wrapper.addClass).toHaveBeenCalledWith(PAGE_SURFACE_CLASS);
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
