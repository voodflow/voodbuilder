/**
 * @vitest-environment node
 */

import { describe, expect, it } from 'vitest';
import { shouldMountElementsCompanion } from '../../../voodbuilder-elements/resources/js/editor/plugin.js';

describe('voodbuilder-elements companion gate', () => {
    it('does not mount when elementsLibrary entitlement is off', () => {
        expect(shouldMountElementsCompanion({
            entitlements: { elementsLibrary: false },
        })).toBe(false);

        expect(shouldMountElementsCompanion({
            entitlements: {},
        })).toBe(false);

        expect(shouldMountElementsCompanion({})).toBe(false);
    });

    it('mounts when EditorGate sets elementsLibrary true', () => {
        expect(shouldMountElementsCompanion({
            entitlements: { elementsLibrary: true },
        })).toBe(true);
    });
});
