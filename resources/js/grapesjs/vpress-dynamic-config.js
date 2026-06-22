/**
 * Encode/decode data-vpress-config for safe HTML attributes.
 * GrapesJS getHtml() breaks raw JSON quotes inside attributes.
 */

export function encodeVpressConfig(config) {
    const json = typeof config === 'string' ? config : JSON.stringify(config ?? {});

    return json
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

export function parseVpressConfig(raw) {
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
        } catch {
            // try next candidate
        }
    }

    return {};
}

export function serializeVpressConfig(config) {
    return JSON.stringify(config ?? {});
}
