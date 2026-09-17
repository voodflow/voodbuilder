/**
 * @vitest-environment node
 */

import { describe, expect, it, vi } from 'vitest';
import {
    replaceGlobalTextTags,
    retagCurrentYear,
} from '../../resources/js/editor/global-text-tags.js';
import {
    FOOTER_DEFAULT_COPYRIGHT,
    applyFooterCopyPreview,
} from '../../resources/js/editor/chrome/blocks/footer/config.js';

describe('footer copy tags', () => {
    it('resolves current_year and brand_name in copyright template', () => {
        const year = String(new Date().getFullYear());
        const resolved = replaceGlobalTextTags(FOOTER_DEFAULT_COPYRIGHT, {
            current_year: year,
            brand_name: 'Bloom',
        });

        expect(resolved).toBe(`© ${year} Bloom`);
    });

    it('retags a resolved year back to {current_year}', () => {
        const year = new Date().getFullYear();

        expect(retagCurrentYear(`© ${year} Bloom`)).toBe('© {current_year} Bloom');
    });

    it('applyFooterCopyPreview writes resolved text into the DOM', () => {
        const el = {
            querySelectorAll(selector) {
                if (selector === '[data-voodbuilder-footer-tagline]') {
                    return [this.tagline];
                }

                if (selector === '[data-voodbuilder-footer-copyright]') {
                    return [this.copyright];
                }

                return [];
            },
            tagline: { textContent: '' },
            copyright: { textContent: '' },
        };

        const root = {
            get(name) {
                if (name === 'voodbuilderTagline') {
                    return 'Ship with {brand_name}';
                }

                if (name === 'voodbuilderCopyright') {
                    return '© {current_year} {brand_name}';
                }

                return undefined;
            },
            find() {
                return [];
            },
        };

        const editor = {
            __voodbuilderGlobalTextTags: {
                current_year: '2099',
                brand_name: 'Acme',
            },
        };

        applyFooterCopyPreview(el, root, editor);

        expect(el.tagline.textContent).toBe('Ship with Acme');
        expect(el.copyright.textContent).toBe('© 2099 Acme');
    });

    it('applyFooterCopyPreview syncs GrapesJS component content', () => {
        const taglineChild = { set: vi.fn(), get: () => 'textnode', components: () => [] };
        const copyrightChild = { set: vi.fn(), get: () => 'textnode', components: () => [] };
        const taglineComp = {
            set: vi.fn(),
            components: () => ({ length: 1, forEach: (fn) => fn(taglineChild) }),
        };
        const copyrightComp = {
            set: vi.fn(),
            components: () => ({ length: 1, forEach: (fn) => fn(copyrightChild) }),
        };

        const el = {
            querySelectorAll: () => [],
        };

        const root = {
            get(name) {
                if (name === 'voodbuilderTagline') {
                    return 'Custom tagline';
                }

                if (name === 'voodbuilderCopyright') {
                    return '© Custom';
                }

                return undefined;
            },
            find(selector) {
                if (selector.includes('footer-tagline')) {
                    return [taglineComp];
                }

                if (selector.includes('footer-copyright')) {
                    return [copyrightComp];
                }

                return [];
            },
        };

        applyFooterCopyPreview(el, root, null);

        expect(taglineChild.set).toHaveBeenCalledWith('content', 'Custom tagline', { silent: true });
        expect(copyrightChild.set).toHaveBeenCalledWith('content', '© Custom', { silent: true });
    });
});
