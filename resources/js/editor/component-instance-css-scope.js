/**
 * Scope compiled component CSS to a single library instance.
 */

import { COMPONENT_SCOPE_ATTR } from './component-instance-type.js';

export { COMPONENT_SCOPE_ATTR } from './component-instance-type.js';

export function scopeComponentCssToInstance(css, componentId) {
    const normalizedCss = String(css ?? '').trim();
    const id = String(componentId ?? '').trim();

    if (normalizedCss === '' || id === '') {
        return normalizedCss;
    }

    const escapedId = id.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    const scope = `[${COMPONENT_SCOPE_ATTR}="${escapedId}"]`;

    if (normalizedCss.includes(scope)) {
        return normalizedCss;
    }

    let scoped = normalizedCss.replace(
        /:where\(\.voodbuilder-editor-component-instance,\s*\.voodbuilder-component-rendered,\s*\.VPRichPage\)\s*:where\(\.voodbuilder-pasted-component\)/g,
        `${scope} .voodbuilder-pasted-component`,
    );

    scoped = scoped.replace(
        /\.dark\s+:where\(\.voodbuilder-editor-component-instance,\s*\.voodbuilder-component-rendered,\s*\.VPRichPage\)\s*:where\(\.voodbuilder-pasted-component\)/g,
        `.dark ${scope} .voodbuilder-pasted-component`,
    );

    for (const legacySelector of [
        '.voodbuilder-editor-component-instance .voodbuilder-pasted-component',
        '.voodbuilder-component-rendered .voodbuilder-pasted-component',
        '.VPRichPage .voodbuilder-pasted-component',
    ]) {
        scoped = scoped.split(legacySelector).join(`${scope} .voodbuilder-pasted-component`);
    }

    return prefixUnscopedPastedComponentSelectors(scoped, scope);
}

function prefixUnscopedPastedComponentSelectors(css, scope) {
    const needle = '.voodbuilder-pasted-component';
    let result = '';
    let offset = 0;

    while (offset < css.length) {
        const position = css.indexOf(needle, offset);

        if (position === -1) {
            result += css.slice(offset);

            break;
        }

        const prefix = css.slice(Math.max(0, position - 96), position);

        if (/\[data-vb-component-id="[^"]*"\]\s*$/.test(prefix)) {
            result += css.slice(offset, position + needle.length);
            offset = position + needle.length;

            continue;
        }

        result += `${css.slice(offset, position)}${scope} ${needle}`;
        offset = position + needle.length;
    }

    return result;
}
