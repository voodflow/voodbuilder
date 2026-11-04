/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    FOOTER_CONTACT_ITEMS_PROP,
    FOOTER_CONTACT_ITEM_LIMIT,
    FOOTER_CONTACT_DEFAULT_ICON,
    footerContactIconForText,
    footerContactItemsJson,
    normalizeFooterContactItems,
    syncSiteFooterConfig,
} from '../../resources/js/editor/chrome/blocks/footer/config.js';

describe('footer contact items', () => {
    it('normalizes icon + text rows', () => {
        expect(normalizeFooterContactItems([
            { icon: 'Mail', text: ' led@polito.it ' },
            { text: '+39 011 090 4000' },
        ])).toEqual([
            { icon: 'mail', text: 'led@polito.it' },
            { icon: FOOTER_CONTACT_DEFAULT_ICON, text: '+39 011 090 4000' },
        ]);
    });

    it('accepts the stored JSON prop and plain lines', () => {
        expect(normalizeFooterContactItems('[{"icon":"phone","text":"+39 011"}]')).toEqual([
            { icon: 'phone', text: '+39 011' },
        ]);

        expect(normalizeFooterContactItems('led@polito.it\nVia Roma 1')).toEqual([
            { icon: FOOTER_CONTACT_DEFAULT_ICON, text: 'led@polito.it' },
            { icon: FOOTER_CONTACT_DEFAULT_ICON, text: 'Via Roma 1' },
        ]);
    });

    it('drops empty rows and caps the list', () => {
        const rows = Array.from({ length: FOOTER_CONTACT_ITEM_LIMIT + 3 }, (_, index) => `Row ${index}`);

        expect(normalizeFooterContactItems(['', '  ', 'led@polito.it'])).toEqual([
            { icon: FOOTER_CONTACT_DEFAULT_ICON, text: 'led@polito.it' },
        ]);
        expect(normalizeFooterContactItems(rows)).toHaveLength(FOOTER_CONTACT_ITEM_LIMIT);
    });

    it('serializes rows for the component prop', () => {
        expect(footerContactItemsJson([
            { icon: 'mail', text: 'led@polito.it' },
            { icon: 'point', text: '' },
        ])).toBe('[{"icon":"mail","text":"led@polito.it"}]');
    });

    it('guesses a meaningful icon from the contact text', () => {
        expect(footerContactIconForText('led@polito.it')).toBe('mail');
        expect(footerContactIconForText('+39 011 090 4000')).toBe('phone');
        expect(footerContactIconForText('https://polito.it')).toBe('world');
        expect(footerContactIconForText('Corso Duca degli Abruzzi, 24')).toBe('map-pin');
    });

    it('guesses a brand icon from a social destination', () => {
        expect(footerContactIconForText('Facebook', 'https://facebook.com/led')).toBe('brand-facebook');
        expect(footerContactIconForText('Profilo', 'linkedin.com/in/led')).toBe('brand-linkedin');
        expect(footerContactIconForText('Canale', 'https://youtube.com/@led')).toBe('brand-youtube');
        expect(footerContactIconForText('Sito', 'https://polito.it')).toBe('world');
    });

    it('keeps a link title separate from its destination', () => {
        expect(normalizeFooterContactItems([
            { icon: 'brand-facebook', text: ' Facebook ', url: ' https://facebook.com/led ' },
            { text: 'Instagram', url: 'https://instagram.com/led' },
        ])).toEqual([
            { icon: 'brand-facebook', text: 'Facebook', url: 'https://facebook.com/led' },
            { icon: 'brand-instagram', text: 'Instagram', url: 'https://instagram.com/led' },
        ]);
    });

    it('uses the destination as the title when the label is blank', () => {
        expect(normalizeFooterContactItems([{ icon: 'world', text: '', url: 'https://facebook.com' }]))
            .toEqual([{ icon: 'world', text: 'https://facebook.com', url: 'https://facebook.com' }]);
    });

    it('serializes rows with their link', () => {
        expect(footerContactItemsJson([
            { icon: 'brand-facebook', text: 'Facebook', url: 'https://facebook.com' },
            { icon: 'mail', text: 'led@polito.it' },
        ])).toBe('[{"icon":"brand-facebook","text":"Facebook","url":"https://facebook.com"},{"icon":"mail","text":"led@polito.it"}]');
    });

    it('writes contact_items and vertical_dividers into the stored config', () => {
        const props = {
            [FOOTER_CONTACT_ITEMS_PROP]: '[{"icon":"mail","text":"led@polito.it"}]',
            voodbuilderFooterVerticalDividers: true,
        };

        const component = {
            attributes: { 'data-voodbuilder-block': 'site_footer_columns_contact' },
            config: {},
            getAttributes() {
                return this.attributes;
            },
            get(name) {
                return props[name] ?? this.config[name];
            },
            set(name, value) {
                if (name === 'voodbuilderConfig') {
                    this.config = value;

                    return;
                }

                props[name] = value;
            },
            addAttributes(attributes) {
                this.attributes = { ...this.attributes, ...attributes };
            },
        };

        syncSiteFooterConfig(component);

        expect(component.config.contact_items).toEqual([{ icon: 'mail', text: 'led@polito.it' }]);
        expect(component.config.vertical_dividers).toBe(true);
    });
});
