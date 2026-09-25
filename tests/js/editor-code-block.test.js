import { describe, expect, it } from 'vitest';
import { normalizeCodeContent } from '../../resources/js/editor/editor-code-block.js';

describe('normalizeCodeContent', () => {
    it('trims leading and trailing blank lines', () => {
        expect(normalizeCodeContent('\n\ncomposer require x\nphp artisan y\n\n\n'))
            .toBe('composer require x\nphp artisan y');
    });

    it('keeps intentional blank lines inside the snippet', () => {
        expect(normalizeCodeContent('a\n\nb')).toBe('a\n\nb');
    });

    it('normalizes CRLF', () => {
        expect(normalizeCodeContent('a\r\nb\r\n')).toBe('a\nb');
    });
});
