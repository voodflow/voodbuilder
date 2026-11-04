import { describe, expect, it } from 'vitest';
import { CONTAINER_LINK_ATTR, isContainerLink } from '../../resources/js/editor/basic-elements-settings.js';

function fakeComponent(attrs = {}) {
    return {
        getAttributes: () => ({ ...attrs }),
    };
}

describe('container links', () => {
    it('detects a card anchor whose label must not be editable', () => {
        expect(isContainerLink(fakeComponent({ [CONTAINER_LINK_ATTR]: 'none' }))).toBe(true);
        expect(isContainerLink(fakeComponent({ [CONTAINER_LINK_ATTR]: 'NONE' }))).toBe(true);
    });

    it('keeps regular text links editable', () => {
        expect(isContainerLink(fakeComponent({ href: '#' }))).toBe(false);
        expect(isContainerLink(fakeComponent({ [CONTAINER_LINK_ATTR]: 'text' }))).toBe(false);
    });

    it('ignores components without attributes', () => {
        expect(isContainerLink(null)).toBe(false);
        expect(isContainerLink({})).toBe(false);
    });
});
