/**
 * The save state readout in the topbar.
 *
 * Replaces a "Saved" flash that appeared for two and a half seconds and then left the
 * author with no idea whether the page on the live site matched the canvas. The state
 * that matters is not "an autosave ran", it is **whether visitors see this work yet**,
 * because only an explicit Save publishes:
 *
 * - `saved`    — published output matches the canvas.
 * - `saving`   — a save is in flight.
 * - `unsaved`  — the canvas has moved on; autosave has it, visitors do not.
 *
 * An autosave therefore does not get its own state. It annotates `unsaved` with the time
 * the draft was parked, which turns the anxious reading of "unsaved changes" into
 * "unsaved, but not lost".
 *
 * The element sits first in the topbar actions, and the actions group is right-anchored,
 * so the readout grows leftwards into empty space instead of pushing the buttons around
 * every time it changes.
 */

const STATE_CLASS_PREFIX = 'is-';

function formatClockTime(timestamp) {
    try {
        return new Intl.DateTimeFormat(undefined, {
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(timestamp));
    } catch {
        const date = new Date(timestamp);

        return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    }
}

/**
 * @param {HTMLElement|null} element  the topbar readout
 * @param {Record<string, string>} labels
 */
export function createSaveStatus(element, labels = {}) {
    let state = 'idle';
    let draftAt = null;

    const text = {
        saved: labels.statusSaved ?? labels.saved ?? 'Saved',
        saving: labels.statusSaving ?? labels.saving ?? 'Saving…',
        unsaved: labels.statusUnsaved ?? 'Unsaved changes',
        draftAt: labels.statusDraftAt ?? 'draft saved {time}',
        draftHint: labels.statusDraftHint
            ?? 'Your work is backed up and can be recovered from Revisions, but it is not published until you save.',
    };

    const render = () => {
        if (! element) {
            return;
        }

        for (const name of ['idle', 'saved', 'saving', 'unsaved']) {
            element.classList.toggle(`${STATE_CLASS_PREFIX}${name}`, state === name);
        }

        if (state === 'idle') {
            element.hidden = true;
            element.textContent = '';
            element.removeAttribute('title');

            return;
        }

        element.hidden = false;

        if (state === 'unsaved' && draftAt !== null) {
            element.textContent = `${text.unsaved} · ${text.draftAt.replace('{time}', formatClockTime(draftAt))}`;
            element.title = text.draftHint;

            return;
        }

        element.textContent = text[state] ?? '';
        element.removeAttribute('title');
    };

    render();

    return {
        /** The canvas matches the published page. */
        saved: () => {
            state = 'saved';
            draftAt = null;
            render();
        },
        saving: () => {
            state = 'saving';
            render();
        },
        /**
         * The canvas has unpublished changes.
         *
         * @param {{ draftAt?: number|null }} [detail]  when an autosave parked them
         */
        unsaved: (detail = {}) => {
            state = 'unsaved';

            if (typeof detail.draftAt === 'number') {
                draftAt = detail.draftAt;
            }

            render();
        },
        /** An autosave completed; only meaningful while there is unpublished work. */
        draftParked: (timestamp = Date.now()) => {
            draftAt = timestamp;

            if (state !== 'saving') {
                state = 'unsaved';
            }

            render();
        },
        get state() {
            return state;
        },
    };
}
