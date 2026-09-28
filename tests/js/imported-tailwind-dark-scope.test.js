/**
 * @vitest-environment node
 */

import { describe, expect, it, vi } from 'vitest';
import {
    migrateImportedTailwindHtml,
    stripForcedDarkScopeFromComponents,
    stripForcedDarkScopeHtml,
} from '../../resources/js/editor/imported-tailwind-support.js';

describe('pasted blocks follow the site theme', () => {
    it('does not add a dark class when the snippet uses dark: variants', () => {
        const html = migrateImportedTailwindHtml('<section class="bg-white dark:bg-black">x</section>');

        expect(html).toContain('voodbuilder-pasted-component');
        expect(html).toContain('dark:bg-black');
        expect(html).not.toMatch(/class="[^"]*(?<![\w:-])dark(?![\w:-])/);
    });

    it('strips a previously forced dark class from pasted roots only', () => {
        const html = '<section class="dark voodbuilder-pasted-component dark:lg:bg-top">A</section><div class="dark">B</div>';

        expect(stripForcedDarkScopeHtml(html)).toBe(
            '<section class="voodbuilder-pasted-component dark:lg:bg-top">A</section><div class="dark">B</div>',
        );
    });

    it('removes the forced dark class from live pasted components', () => {
        const pasted = {
            getClasses: () => ['dark', 'voodbuilder-pasted-component', 'dark:lg:bg-top'],
            removeClass: vi.fn(),
        };
        const plain = {
            getClasses: () => ['dark'],
            removeClass: vi.fn(),
        };
        const editor = {
            getWrapper: () => ({
                onAll: (fn) => {
                    fn(pasted);
                    fn(plain);
                },
            }),
        };

        expect(stripForcedDarkScopeFromComponents(editor)).toBe(1);
        expect(pasted.removeClass).toHaveBeenCalledWith('dark');
        expect(plain.removeClass).not.toHaveBeenCalled();
    });
});
