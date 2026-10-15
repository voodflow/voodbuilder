import { describe, expect, it } from 'vitest';
import {
    BLOCK_ATTR,
    isChromeShellBlockId,
    selectContentRefreshBlocks,
} from '../../resources/js/editor/editor/dynamic-block-scope.js';

function block(blockId) {
    return { getAttributes: () => ({ [BLOCK_ATTR]: blockId }) };
}

describe('dynamic block refresh scope', () => {
    it('treats nav, header and footer as chrome shell parts', () => {
        expect(isChromeShellBlockId('site_nav_simple')).toBe(true);
        expect(isChromeShellBlockId('site_nav_mega')).toBe(true);
        expect(isChromeShellBlockId('site_header')).toBe(true);
        expect(isChromeShellBlockId('site_footer')).toBe(true);
        expect(isChromeShellBlockId('site_footer_centered')).toBe(true);
    });

    it('treats page content blocks as refreshable', () => {
        expect(isChromeShellBlockId('demo_pages_explorer')).toBe(false);
        expect(isChromeShellBlockId('voodflow_core_nodes_grid')).toBe(false);
    });

    it('keeps page content blocks and drops chrome parts', () => {
        const selected = selectContentRefreshBlocks([
            block('site_nav_simple'),
            block('demo_pages_explorer'),
            block('site_footer_centered'),
            block('custom_faq'),
        ]);

        expect(selected.map((component) => component.getAttributes()[BLOCK_ATTR])).toEqual([
            'demo_pages_explorer',
            'custom_faq',
        ]);
    });

    it('ignores components without a usable block id', () => {
        expect(selectContentRefreshBlocks([
            { getAttributes: () => ({}) },
            { getAttributes: () => ({ [BLOCK_ATTR]: '' }) },
            {},
        ])).toEqual([]);
    });

    it('tolerates a missing component list', () => {
        expect(selectContentRefreshBlocks()).toEqual([]);
        expect(selectContentRefreshBlocks(null)).toEqual([]);
    });
});
