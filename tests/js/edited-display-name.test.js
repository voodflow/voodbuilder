/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import { resolveEditedDisplayName } from '../../resources/js/editor/jodit-image-editor.js';

describe('resolveEditedDisplayName', () => {
    it('prefers asset manager name over bare edited', () => {
        const editor = {
            Assets: {
                getAll: () => ([
                    { get: (key) => (key === 'src' ? '/storage/abc.jpg' : 'Vacanze al mare') },
                ]),
            },
        };

        expect(resolveEditedDisplayName(editor, null, '/storage/abc.jpg')).toBe('Vacanze-al-mare');
    });

    it('falls back to alt text', () => {
        const component = {
            getAttributes: () => ({ alt: 'Logo CosmoLab' }),
        };

        expect(resolveEditedDisplayName(null, component, '/storage/hashyhashyhashy.jpg')).toBe('Logo-CosmoLab');
    });

    it('avoids bare edited and shortens hash-like urls', () => {
        expect(resolveEditedDisplayName(null, null, '/storage/a1b2c3d4e5f67890abcd.jpg')).toBe('edited-a1b2c3d4');
        expect(resolveEditedDisplayName(null, null, '')).toBe('edited-image');
    });
});
