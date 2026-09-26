import { describe, expect, it } from 'vitest';
import { truncateDialogMessage } from '../../resources/js/editor/editor-api.js';

describe('truncateDialogMessage', () => {
    it('leaves short messages intact', () => {
        expect(truncateDialogMessage('Could not save.')).toBe('Could not save.');
    });

    it('prefers a short SQLSTATE summary for QueryException dumps', () => {
        const dump = 'SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column \'css\' at row 1 (Connection: mysql, SQL: insert into `voodbuilder_page_templates` (`css`) values ('
            + '.border-vp-divider{border-color:red}'.repeat(200)
            + '))';

        expect(truncateDialogMessage(dump)).toBe(
            'SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column \'css\' at row 1…',
        );
    });

    it('hard-caps other long messages', () => {
        const long = 'x'.repeat(600);

        expect(truncateDialogMessage(long, 40)).toBe(`${'x'.repeat(40)}…`);
    });
});
