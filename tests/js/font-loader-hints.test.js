/**
 * @vitest-environment node
 */

import { describe, expect, it, vi } from 'vitest';
import { collectEditorFontHints } from '../../resources/js/editor/fonts/font-loader.js';

describe('collectEditorFontHints', () => {
    it('includes data-vb-font even when model style is empty', () => {
        const components = [
            {
                getAttributes: () => ({ 'data-vb-font': "'JetBrains Mono', monospace" }),
                getStyle: () => ({}),
                getId: () => 'heading',
            },
            {
                getAttributes: () => ({}),
                getStyle: () => ({ 'font-family': "'Lato', sans-serif" }),
                getId: () => 'body',
            },
        ];

        const editor = {
            getCss: () => '#other { color: red }',
            Css: {
                getIdRule: (id) => (id === 'heading'
                    ? { getStyle: () => ({ 'font-family': "'JetBrains Mono', monospace !important" }) }
                    : null),
            },
            getWrapper: () => ({
                onAll: (visit) => components.forEach(visit),
            }),
        };

        const hay = collectEditorFontHints(editor, '');

        expect(hay).toContain('JetBrains Mono');
        expect(hay).toContain('Lato');
    });
});
