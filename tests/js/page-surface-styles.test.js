import { describe, expect, it } from 'vitest';
import {
    PAGE_SURFACE_CLASS,
    remapPageSurfaceCssForPublish,
    resolveStyleTarget,
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
