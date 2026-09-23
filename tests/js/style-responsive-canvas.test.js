/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    STYLE_RESPONSIVE_FONT_SIZE_SAFELIST,
    buildEditorBreakpointFontSizeCss,
} from '../../resources/js/editor/style-responsive-canvas.js';

describe('editor breakpoint font-size css', () => {
    it('safelists md/lg font-size utilities', () => {
        expect(STYLE_RESPONSIVE_FONT_SIZE_SAFELIST).toContain('md:text-9xl');
        expect(STYLE_RESPONSIVE_FONT_SIZE_SAFELIST).toContain('lg:text-6xl');
        expect(STYLE_RESPONSIVE_FONT_SIZE_SAFELIST).toContain('text-base');
    });

    it('scopes md rules to tablet+desktop and lg to desktop', () => {
        const css = buildEditorBreakpointFontSizeCss();

        expect(css).toContain("body[data-voodbuilder-editor-device='tablet'] .md\\:text-9xl");
        expect(css).toContain("body[data-voodbuilder-editor-device='desktop'] .md\\:text-9xl");
        expect(css).toContain("body[data-voodbuilder-editor-device='desktop'] .lg\\:text-9xl");
        expect(css).toContain('font-size: 8rem !important');
        expect(css).not.toContain("body[data-voodbuilder-editor-device='mobilePortrait'] .md\\:text-9xl");
    });
});
