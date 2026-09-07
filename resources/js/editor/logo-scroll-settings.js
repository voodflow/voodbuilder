/**
 * Logo scroll block — Tailwind-only marquee (animate-logo-marquee from theme.css).
 */

import { registerBlockSettings } from './blocks/settings/index.js';
import { createFormSection, createSelectField, createTextField } from './editor-form-ui.js';
import {
    findLogoScrollItems,
    getLogoScrollItemHref,
    LOGO_SCROLL_SIZES,
    normalizeLogoScrollSize,
    setLogoScrollItemHref,
} from './editor-animated-blocks.js';

const SPEED_SECONDS = {
    slow: 45,
    normal: 28,
    fast: 14,
};

/**
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function findLogoScrollRoot(component) {
    let current = component;

    while (current) {
        const attrs = current.getAttributes?.() ?? {};

        if (Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-logo-scroll')) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * @param {object} editor
 * @param {object} root
 */
function refreshLogoScroll(editor, root) {
    editor?.trigger?.('voodbuilder:logo-scroll-config', root);
}

/**
 * @param {object} editor
 */
function rerenderLogoScrollSettings(editor) {
    editor?.__voodbuilderBlockSettingsInvalidate?.();
    editor?.__voodbuilderBlockSettingsRender?.();
}

/**
 * @param {HTMLElement} fields
 * @param {string} text
 */
function appendHint(fields, text) {
    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-form-hint text-xs text-vp-text-2 mt-1';
    hint.textContent = text;
    fields.appendChild(hint);
}

/**
 * @param {object} editor
 */
export function registerLogoScrollSettings(editor) {
    if (editor.__voodbuilderLogoScrollSettingsRegistered) {
        return;
    }

    editor.__voodbuilderLogoScrollSettingsRegistered = true;

    registerBlockSettings({
        id: 'logo_scroll',
        matchBlockId: () => false,
        findRoot: (component) => findLogoScrollRoot(component),
        matchesRoot: (root) => {
            const attrs = root?.getAttributes?.() ?? {};

            return Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-logo-scroll');
        },
        render: ({ mount, root, editor: gjsEditor }) => {
            const attrs = root.getAttributes?.() ?? {};
            const count = Math.max(3, Math.min(12, Number.parseInt(attrs['data-vb-logo-count'] ?? '6', 10) || 6));
            const speed = attrs['data-vb-logo-speed'] || root.get('data-vb-logo-speed') || 'normal';
            const direction = attrs['data-vb-logo-direction'] || root.get('data-vb-logo-direction') || 'left';
            const pauseHover = attrs['data-vb-logo-pause-hover'] || root.get('data-vb-logo-pause-hover') || '1';
            const source = attrs['data-vb-logo-source'] || root.get('data-vb-logo-source') || 'static';
            const size = normalizeLogoScrollSize(
                attrs['data-vb-logo-size'] || root.get('data-vb-logo-size') || 'lg',
            );
            const isDynamic = source === 'dynamic';

            const apply = (name, value) => {
                const next = name === 'data-vb-logo-count' ? Number.parseInt(String(value), 10) || 6 : value;
                root.set(name, next);
                root.addAttributes({ [name]: String(next) });
                refreshLogoScroll(gjsEditor, root);

                if (name === 'data-vb-logo-count' || name === 'data-vb-logo-source') {
                    window.requestAnimationFrame(() => rerenderLogoScrollSettings(gjsEditor));
                }
            };

            const { section, fields } = createFormSection('Logo scroll');
            const countOptions = [];

            for (let value = 3; value <= 12; value += 1) {
                countOptions.push({ value: String(value), label: String(value) });
            }

            fields.append(
                createSelectField({
                    label: 'Source',
                    name: 'vbLogoSource',
                    value: isDynamic ? 'dynamic' : 'static',
                    options: [
                        { value: 'static', label: 'Static logos' },
                        { value: 'dynamic', label: 'Dynamic (list repeat)' },
                    ],
                    onChange: (value) => apply('data-vb-logo-source', value),
                }),
            );

            if (isDynamic) {
                appendHint(
                    fields,
                    'Select this block (or the logo track), open the Dynamic tab, apply List repeat, then bind image/URL on the template logo (img and link).',
                );
            } else {
                fields.append(
                    createSelectField({
                        label: 'Unique logos',
                        name: 'vbLogoCount',
                        value: String(count),
                        options: countOptions,
                        onChange: (value) => apply('data-vb-logo-count', value),
                    }),
                );
            }

            fields.append(
                createSelectField({
                    label: 'Logo size',
                    name: 'vbLogoSize',
                    value: size,
                    options: [
                        { value: 'sm', label: 'Small' },
                        { value: 'md', label: 'Medium' },
                        { value: 'lg', label: 'Large' },
                        { value: 'xl', label: 'Extra large' },
                    ],
                    onChange: (value) => apply('data-vb-logo-size', value),
                }),
                createSelectField({
                    label: 'Speed',
                    name: 'vbLogoSpeed',
                    value: String(speed),
                    options: [
                        { value: 'slow', label: `Slow (${SPEED_SECONDS.slow}s)` },
                        { value: 'normal', label: `Normal (${SPEED_SECONDS.normal}s)` },
                        { value: 'fast', label: `Fast (${SPEED_SECONDS.fast}s)` },
                    ],
                    onChange: (value) => apply('data-vb-logo-speed', value),
                }),
                createSelectField({
                    label: 'Direction',
                    name: 'vbLogoDirection',
                    value: String(direction),
                    options: [
                        { value: 'left', label: 'Left' },
                        { value: 'right', label: 'Right' },
                    ],
                    onChange: (value) => apply('data-vb-logo-direction', value),
                }),
                createSelectField({
                    label: 'Pause on hover',
                    name: 'vbLogoPauseHover',
                    value: String(pauseHover),
                    options: [
                        { value: '1', label: 'Yes' },
                        { value: '0', label: 'No' },
                    ],
                    onChange: (value) => apply('data-vb-logo-pause-hover', value),
                }),
            );

            mount.appendChild(section);

            if (isDynamic) {
                return;
            }

            const linksSection = createFormSection('Logo links');
            const items = findLogoScrollItems(root);

            items.forEach((item, index) => {
                const href = getLogoScrollItemHref(item);
                const field = createTextField({
                    label: `Logo ${index + 1} URL`,
                    name: `vbLogoLink${index}`,
                    type: 'url',
                    value: href === '#' ? '' : href,
                    placeholder: 'https://…',
                });

                const commitLink = () => {
                    setLogoScrollItemHref(item, field.input.value);
                    refreshLogoScroll(gjsEditor, root);
                };

                field.input.addEventListener('change', commitLink);
                field.input.addEventListener('blur', commitLink);
                linksSection.fields.append(field.field);
            });

            if (items.length > 0) {
                mount.appendChild(linksSection.section);
            }
        },
    });
}

export { SPEED_SECONDS as LOGO_SCROLL_SPEED_SECONDS, LOGO_SCROLL_SIZES };
