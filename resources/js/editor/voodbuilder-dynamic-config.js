/**
 * Encode/decode data-voodbuilder-config for safe HTML attributes.
 * Editor getHtml() breaks raw JSON quotes inside attributes.
 */

import { debugSwallowed } from './debug-swallowed.js';

export function encodeBlockConfig(config) {
    const json = typeof config === 'string' ? config : JSON.stringify(config ?? {});

    return json
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

export function parseBlockConfig(raw) {
    if (! raw || raw === '{}') {
        return {};
    }

    const textarea = document.createElement('textarea');

    textarea.innerHTML = raw;
    const entityDecoded = textarea.value || raw;

    for (const candidate of [entityDecoded, raw]) {
        try {
            const parsed = JSON.parse(candidate);

            if (parsed && typeof parsed === 'object' && ! Array.isArray(parsed)) {
                return parsed;
            }
        } catch (error) {
            // try next candidate
            debugSwallowed(error);
        }
    }

    return {};
}

export function serializeBlockConfig(config) {
    return JSON.stringify(config ?? {});
}

/**
 * Identity of one dynamic block render: the block plus the config it was rendered for.
 *
 * @param {string} blockId
 * @param {object|null|undefined} config
 * @returns {string}
 */
export function dynamicBlockRenderFingerprint(blockId, config) {
    return `${blockId}::${serializeBlockConfig(config)}`;
}

/**
 * Did the author outrun a dynamic block render while it was in flight?
 *
 * A dynamic preview refresh is a network round trip: the config it was asked for is
 * captured when the request starts and merged back into the component when the response
 * lands (see `refreshDynamicBlockComponent`). A declarative Content field write happens
 * in between — the author keeps typing — so applying the response would merge a pre-edit
 * snapshot over the newer value and silently revert the last edit (the canvas, and every
 * later save, keep the old value with no error).
 *
 * Callers must drop such a response and let the queued refresh render the newest config.
 *
 * @param {{ requestFingerprint?: string|null, blockId?: string|null, liveConfig?: object|null }} render
 * @returns {boolean} true when the response no longer matches the live block config.
 */
export function isStaleDynamicRender({ requestFingerprint, blockId, liveConfig }) {
    return dynamicBlockRenderFingerprint(blockId ?? '', liveConfig) !== String(requestFingerprint ?? '');
}

/**
 * Is a dynamic block render already running (this block, or any other block)?
 *
 * The refresh scheduler used to *drop* a request that arrived in this window, so the
 * newest config never reached the server and the preview kept the value the author had
 * just replaced. Busy renders must queue the request instead.
 *
 * @param {object|null|undefined} editor
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isDynamicRefreshBusy(editor, component) {
    return Boolean(component?.__voodbuilderRefreshing || editor?.__voodbuilderDynamicBlockRefreshing);
}
