/**
 * Autosave and unsaved-work recovery for the page editor.
 *
 * Saving is an explicit button, and an authoring session runs for tens of minutes, so
 * losing work was a matter of when rather than if. Two nets, deliberately different:
 *
 * - A local draft in IndexedDB, written every few seconds. Cheap, private to the browser,
 *   survives a reload, a crashed tab and a closed laptop.
 * - A server-side autosave revision, written on a slower timer. Survives the machine
 *   itself, and reuses the revision storage and restore path that already existed.
 *
 * Neither publishes: the autosave endpoint writes a revision and never touches
 * `builder_payload`, so nothing an author has not saved can reach the live site.
 */

import { confirmDialog } from './editor-dialog.js';

const DB_NAME = 'voodbuilder-editor-drafts';
const DB_VERSION = 1;
const STORE = 'drafts';

/** Drafts older than this are noise: the page has almost certainly moved on. */
const LOCAL_DRAFT_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1_000;

function openDraftDb() {
    if (! ('indexedDB' in window)) {
        return Promise.resolve(null);
    }

    return new Promise((resolve) => {
        let request;

        try {
            request = window.indexedDB.open(DB_NAME, DB_VERSION);
        } catch {
            // Private browsing modes reject the open outright.
            resolve(null);

            return;
        }

        request.onupgradeneeded = () => {
            const db = request.result;

            if (! db.objectStoreNames.contains(STORE)) {
                db.createObjectStore(STORE, { keyPath: 'key' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        // Storage can be disabled or over quota; the server autosave still covers us.
        request.onerror = () => resolve(null);
        request.onblocked = () => resolve(null);
    });
}

function draftTransaction(db, mode) {
    try {
        return db.transaction(STORE, mode).objectStore(STORE);
    } catch {
        return null;
    }
}

async function readLocalDraft(key) {
    const db = await openDraftDb();
    const store = db && draftTransaction(db, 'readonly');

    if (! store) {
        return null;
    }

    return new Promise((resolve) => {
        const request = store.get(key);

        request.onsuccess = () => resolve(request.result ?? null);
        request.onerror = () => resolve(null);
    });
}

async function writeLocalDraft(record) {
    const db = await openDraftDb();
    const store = db && draftTransaction(db, 'readwrite');

    if (! store) {
        return false;
    }

    return new Promise((resolve) => {
        const request = store.put(record);

        request.onsuccess = () => resolve(true);
        request.onerror = () => resolve(false);
    });
}

async function deleteLocalDraft(key) {
    const db = await openDraftDb();
    const store = db && draftTransaction(db, 'readwrite');

    if (! store) {
        return;
    }

    try {
        store.delete(key);
    } catch {
        // Nothing to clean up.
    }
}

/**
 * Tell "the author changed something" from "the canvas re-serialized itself identically".
 *
 * A 32-bit hash rather than the markup itself: the fingerprint is held for the whole
 * session, and a page can serialize to hundreds of kilobytes. The lengths are kept
 * alongside it so a collision would also have to preserve both sizes.
 */
function payloadFingerprint(payload) {
    const html = String(payload?.html ?? '');
    const css = String(payload?.css ?? '');
    const source = `${html}\u0000${css}`;

    let hash = 0x811c9dc5;

    for (let index = 0; index < source.length; index += 1) {
        hash ^= source.charCodeAt(index);
        hash = Math.imul(hash, 0x01000193);
    }

    return `${html.length}:${css.length}:${(hash >>> 0).toString(36)}`;
}

function formatDraftAge(timestamp, labels) {
    const minutes = Math.max(1, Math.round((Date.now() - timestamp) / 60_000));

    if (minutes < 60) {
        return (labels.autosaveAgeMinutes ?? '{minutes} minutes ago')
            .replace('{minutes}', String(minutes));
    }

    const hours = Math.round(minutes / 60);

    return (labels.autosaveAgeHours ?? '{hours} hours ago').replace('{hours}', String(hours));
}

/**
 * Ask before overwriting the canvas: recovery replaces whatever the author is looking at.
 */
async function offerRecovery({ editor, labels, draft, applyPayload }) {
    const confirmed = await confirmDialog({
        title: labels.autosaveRecoverTitle ?? 'Unsaved changes found',
        message: (labels.autosaveRecoverMessage
            ?? 'This page has unsaved changes from {age}. Restore them?')
            .replace('{age}', formatDraftAge(draft.savedAt, labels)),
        confirmLabel: labels.autosaveRecoverConfirm ?? 'Restore',
        cancelLabel: labels.autosaveRecoverDiscard ?? 'Discard',
        labels,
    });

    if (confirmed !== true) {
        return false;
    }

    applyPayload(editor, draft.payload);

    return true;
}

/**
 * @param {object} editor
 * @param {object} options
 * @param {() => object} options.buildPayload      serialize the current canvas
 * @param {(editor: object, payload: object) => void} options.applyPayload  load a payload back
 * @param {object} options.config                  editor config (autosave urls + intervals)
 */
export function registerEditorAutosave(editor, options = {}) {
    const config = options.config ?? {};
    const settings = config.autosave ?? {};
    const labels = config.labels ?? {};

    if (settings.enabled === false) {
        return null;
    }

    const buildPayload = options.buildPayload;
    const applyPayload = options.applyPayload;

    if (typeof buildPayload !== 'function' || typeof applyPayload !== 'function') {
        return null;
    }

    const pageKey = String(config.pageId ?? config.saveUrl ?? window.location.pathname);
    const draftKey = `page:${pageKey}`;
    const localIntervalMs = Math.max(2_000, Number(settings.localDraftIntervalMs ?? 5_000));
    const serverIntervalMs = Math.max(30_000, Number(settings.intervalMs ?? 120_000));

    let lastLocalFingerprint = null;
    let lastServerFingerprint = null;
    let busy = false;
    let stopped = false;

    // Baseline: whatever was on the page when the editor opened is already saved, so it
    // must not be offered back as unsaved work.
    const markClean = (payload) => {
        const fingerprint = payloadFingerprint(payload ?? {});
        lastLocalFingerprint = fingerprint;
        lastServerFingerprint = fingerprint;
    };

    const currentPayload = () => {
        try {
            return buildPayload(editor);
        } catch (error) {
            // A serialization failure must not take the editor down; the next tick retries.
            console.warn('VoodBuilder autosave: could not serialize the canvas.', error);

            return null;
        }
    };

    const runLocalDraft = async () => {
        if (busy || stopped) {
            return;
        }

        const payload = currentPayload();

        if (! payload) {
            return;
        }

        const fingerprint = payloadFingerprint(payload);

        if (fingerprint === lastLocalFingerprint) {
            return;
        }

        busy = true;

        try {
            const written = await writeLocalDraft({
                key: draftKey,
                savedAt: Date.now(),
                payload,
            });

            if (written) {
                lastLocalFingerprint = fingerprint;
            }
        } finally {
            busy = false;
        }
    };

    const runServerAutosave = async () => {
        if (stopped || ! config.autosaveUrl) {
            return;
        }

        const payload = currentPayload();

        if (! payload) {
            return;
        }

        const fingerprint = payloadFingerprint(payload);

        if (fingerprint === lastServerFingerprint) {
            return;
        }

        try {
            const response = await fetch(config.autosaveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': options.csrf ?? '',
                },
                body: JSON.stringify({
                    html: payload.html ?? '',
                    css: payload.css ?? '',
                    js: payload.js ?? '',
                }),
                // A redirect here means the session expired: treat it as a failure rather
                // than reading an HTML login page as a successful autosave.
                redirect: 'manual',
            });

            if (response.ok) {
                lastServerFingerprint = fingerprint;
            } else {
                console.warn('VoodBuilder autosave: server refused the draft.', response.status);
            }
        } catch (error) {
            // Offline or a dropped connection: the local draft still holds the work.
            console.warn('VoodBuilder autosave: could not reach the server.', error);
        }
    };

    // Serializing the canvas is not free, so only do it while the tab is in the
    // foreground and the browser is idle.
    const schedule = (callback, intervalMs) => window.setInterval(() => {
        if (document.hidden || stopped) {
            return;
        }

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(() => void callback(), { timeout: 1_000 });

            return;
        }

        void callback();
    }, intervalMs);

    const timers = [
        schedule(runLocalDraft, localIntervalMs),
        schedule(runServerAutosave, serverIntervalMs),
    ];

    // Closing the tab is the moment a draft matters most, and it is also the moment an
    // async write is least likely to finish. Best effort, synchronous entry point.
    const onHide = () => {
        void runLocalDraft();
    };

    const onVisibilityChange = () => {
        if (document.hidden) {
            onHide();
        }
    };

    window.addEventListener('pagehide', onHide);
    document.addEventListener('visibilitychange', onVisibilityChange);

    const api = {
        /** Called after a real save: the nets are empty again. */
        markSaved: (payload) => {
            markClean(payload ?? currentPayload() ?? {});
            void deleteLocalDraft(draftKey);

            if (config.autosaveDiscardUrl) {
                void fetch(config.autosaveDiscardUrl, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': options.csrf ?? '',
                    },
                }).catch(() => {
                    // The server also clears autosaves on save; this is only for the
                    // case where the author saved from a different tab.
                });
            }
        },
        stop: () => {
            stopped = true;
            timers.forEach((timer) => window.clearInterval(timer));
            window.removeEventListener('pagehide', onHide);
            document.removeEventListener('visibilitychange', onVisibilityChange);
        },
        /**
         * Offer the local draft if it is newer than what the page was opened with.
         *
         * Only the local draft is offered automatically. The server autosave is reachable
         * from the revisions panel, because prompting twice for the same work — once per
         * net — is worse than not prompting at all.
         */
        recover: async () => {
            markClean(currentPayload() ?? {});

            const draft = await readLocalDraft(draftKey);

            if (! draft?.payload || typeof draft.savedAt !== 'number') {
                return false;
            }

            if (Date.now() - draft.savedAt > LOCAL_DRAFT_MAX_AGE_MS) {
                void deleteLocalDraft(draftKey);

                return false;
            }

            if (payloadFingerprint(draft.payload) === lastLocalFingerprint) {
                // The draft matches the saved page: nothing was lost.
                void deleteLocalDraft(draftKey);

                return false;
            }

            const restored = await offerRecovery({ editor, labels, draft, applyPayload });

            void deleteLocalDraft(draftKey);

            if (restored) {
                lastLocalFingerprint = null;
                lastServerFingerprint = null;
            }

            return restored;
        },
    };

    editor.__voodbuilderAutosave = api;

    return api;
}
