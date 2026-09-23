import { describe, expect, it } from 'vitest';
import { sanitizeAnchorId } from '../../resources/js/editor/editor-utility-blocks.js';

describe('sanitizeAnchorId', () => {
    it('normalizes spaces and unsafe characters', () => {
        expect(sanitizeAnchorId('My Section!')).toBe('my-section');
        expect(sanitizeAnchorId('  Features  ')).toBe('features');
    });

    it('forces a letter-prefixed id', () => {
        expect(sanitizeAnchorId('123')).toBe('section-123');
        expect(sanitizeAnchorId('')).toBe('section');
        expect(sanitizeAnchorId('---')).toBe('section');
    });
});
