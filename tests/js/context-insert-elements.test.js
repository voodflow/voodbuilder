/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    CONTEXT_INSERT_ELEMENT_IDS,
    CONTEXT_INSERT_GROUPS,
    buildContextInsertSubmenu,
    resolveContextInsertGroups,
} from '../../resources/js/editor/context-insert-elements.js';

function mockEditor(blockMap = {}) {
    return {
        BlockManager: {
            get: (id) => blockMap[id] ?? null,
        },
    };
}

function mockBlock(content, label) {
    return {
        get: (key) => {
            if (key === 'content') {
                return content;
            }

            if (key === 'label') {
                return label;
            }

            return undefined;
        },
    };
}

describe('context-insert-elements', () => {
    it('lists every Basic and Media foundation tile', () => {
        expect(CONTEXT_INSERT_GROUPS.map((group) => group.id)).toEqual(['basic', 'media']);

        const ids = CONTEXT_INSERT_ELEMENT_IDS.map((entry) => entry.id);

        expect(ids).toEqual([
            'voodbuilder-heading',
            'voodbuilder-text',
            'voodbuilder-rich-text',
            'voodbuilder-text-link',
            'voodbuilder-button',
            'voodbuilder-icon',
            'voodbuilder-divider',
            'voodbuilder-code-block',
            'image',
            'video',
            'voodbuilder-image-gallery',
            'voodbuilder-audio',
            'voodbuilder-carousel',
            'voodbuilder-slider',
        ]);
    });

    it('builds Insert → Basic / Media nested submenu from BlockManager content', () => {
        const blocks = Object.fromEntries(
            CONTEXT_INSERT_ELEMENT_IDS.map((entry) => [
                entry.id,
                mockBlock({ type: entry.id }, entry.fallback),
            ]),
        );
        const editor = mockEditor(blocks);
        const target = {
            get: () => true,
            append: () => [{ id: 'created' }],
        };

        const groups = resolveContextInsertGroups(editor, {
            contextInsertBasic: 'Basic',
            contextInsertMedia: 'Media',
        });

        expect(groups).toHaveLength(2);
        expect(groups[0].elements).toHaveLength(8);
        expect(groups[1].elements).toHaveLength(6);
        expect(groups[0].elements.map((item) => item.id)).toContain('voodbuilder-rich-text');
        expect(groups[0].elements.map((item) => item.id)).toContain('voodbuilder-code-block');

        const submenu = buildContextInsertSubmenu(editor, target, {
            contextInsert: 'Insert',
            contextInsertBasic: 'Basic',
            contextInsertMedia: 'Media',
        });

        expect(submenu?.id).toBe('insert-element');
        expect(submenu?.children).toHaveLength(2);
        expect(submenu?.children[0].label).toBe('Basic');
        expect(submenu?.children[1].label).toBe('Media');
        expect(submenu?.children[0].children).toHaveLength(8);
        expect(submenu?.children[1].children).toHaveLength(6);
    });

    it('skips groups when BlockManager has no content for their tiles', () => {
        const editor = mockEditor({
            image: mockBlock({ type: 'image' }, 'Image'),
        });

        const groups = resolveContextInsertGroups(editor, {});

        expect(groups).toHaveLength(1);
        expect(groups[0].id).toBe('media');
        expect(groups[0].elements).toHaveLength(1);
    });
});
