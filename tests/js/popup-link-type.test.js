/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import {
    POPUP_OPEN_ATTR,
    hasPopupLinkTargets,
    linkTypeSelectOptions,
    popupOpenAttrValue,
    readPopupAwareLinkType,
    resolveEditorLinkHref,
} from '../../resources/js/editor/editor-link-resolve.js';

describe('popup link type', () => {
    it('adds Popup to the select only when popups are available', () => {
        const without = linkTypeSelectOptions({}, { targets: { popups: [] } });
        expect(without.some((item) => item.value === 'popup')).toBe(false);

        const enabled = linkTypeSelectOptions({}, { targets: { popups: [], popupsEnabled: true } });
        expect(enabled.some((item) => item.value === 'popup')).toBe(true);

        const withPopups = linkTypeSelectOptions({}, {
            targets: { popups: [{ id: 'p1', label: 'Offer' }] },
        });
        expect(withPopups.some((item) => item.value === 'popup')).toBe(true);
        expect(hasPopupLinkTargets({ popups: [{ id: 'p1', label: 'Offer' }] })).toBe(true);
        expect(hasPopupLinkTargets({ popupsEnabled: true })).toBe(true);
    });

    it('resolves popup href to # and maps open attribute', () => {
        expect(resolveEditorLinkHref({}, 'popup', 'abc', '/x')).toBe('#');
        expect(popupOpenAttrValue('popup', 'abc')).toBe('abc');
        expect(popupOpenAttrValue('url', 'abc')).toBeNull();
    });

    it('reads data-vpopups-open as popup link type', () => {
        const read = readPopupAwareLinkType({
            'data-vb-link-type': 'url',
            [POPUP_OPEN_ATTR]: '01uuid',
        });

        expect(read).toEqual({ linkType: 'popup', linkRef: '01uuid' });
    });
});
