import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

describe('component page instances stay detached from library', () => {
    it('does not push catalog HTML onto canvas instances', () => {
        const root = join(dirname(fileURLToPath(import.meta.url)), '../..');
        const source = readFileSync(join(root, 'resources/js/editor/components-ui.js'), 'utf8');
        const codeOnly = source
            .split('\n')
            .filter((line) => ! line.trim().startsWith('//'))
            .join('\n');

        expect(codeOnly).not.toMatch(/component\.components\(\s*item\.html\s*\)/);
        expect(source).toContain('Page instances are snapshots');
    });
});
