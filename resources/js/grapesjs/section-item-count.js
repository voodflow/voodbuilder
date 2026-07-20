/**
 * Item-count settings for section blocks annotated with data-vb-items-root / data-vb-item.
 */

import { registerBlockSettings } from './blocks/settings/index.js';
import { createFormSection, createSelectField } from './editor-form-ui.js';

const WIDTH_CLASS_PATTERN = /^(?:sm|md|lg|xl):w-1\/\d+$|^w-1\/\d+$|^w-full$/;

/**
 * @param {number} count
 * @returns {string[]}
 */
export function widthClassesForItemCount(count) {
    const safe = Math.max(1, Math.min(8, count));

    if (safe <= 1) {
        return ['w-full'];
    }

    if (safe === 2) {
        return ['w-full', 'sm:w-1/2'];
    }

    if (safe === 3) {
        return ['w-full', 'md:w-1/3'];
    }

    if (safe === 4) {
        return ['w-1/2', 'sm:w-1/4', 'md:w-1/4'];
    }

    if (safe === 5 || safe === 6) {
        return ['w-full', 'sm:w-1/2', 'md:w-1/3'];
    }

    return ['w-full', 'sm:w-1/2', 'lg:w-1/4'];
}

/**
 * @param {object} component
 * @param {string[]} nextWidthClasses
 */
function replaceWidthClasses(component, nextWidthClasses) {
    const classes = [...(component.getClasses?.() ?? [])];
    const kept = classes.filter((className) => ! WIDTH_CLASS_PATTERN.test(className));

    component.setClass([...kept, ...nextWidthClasses]);
}

/**
 * @param {object} root
 * @returns {object[]}
 */
function markedItems(root) {
    return [...(root.components?.() ?? [])].filter((child) => {
        const attrs = child.getAttributes?.() ?? {};

        return Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item');
    });
}

/**
 * @param {object} section
 * @returns {{ root: object|null, items: object[], min: number, max: number }}
 */
export function findSectionItems(section) {
    const attrs = section.getAttributes?.() ?? {};
    const min = Math.max(1, Number.parseInt(attrs['data-vb-item-min'] ?? '1', 10) || 1);
    const max = Math.max(min, Number.parseInt(attrs['data-vb-item-max'] ?? '8', 10) || 8);
    const roots = section.find?.('[data-vb-items-root]') ?? [];
    const root = roots[0] ?? null;

    if (! root) {
        return { root: null, items: [], min, max };
    }

    return {
        root,
        items: markedItems(root),
        min,
        max,
    };
}

/**
 * @param {object} section
 * @param {number} nextCount
 */
export function applySectionItemCount(section, nextCount) {
    const { root, items, min, max } = findSectionItems(section);

    if (! root || items.length === 0) {
        return;
    }

    const target = Math.max(min, Math.min(max, Number(nextCount) || min));
    const template = items[0];
    const widthClasses = widthClassesForItemCount(target);
    const working = [...items];

    while (working.length > target) {
        working.pop()?.remove?.();
    }

    while (working.length < target) {
        const clone = template.clone();
        clone.addAttributes({ 'data-vb-item': '' });
        root.append(clone);
        working.push(root.components().at(root.components().length - 1));
    }

    markedItems(root).forEach((item) => {
        replaceWidthClasses(item, widthClasses);
    });

    section.addAttributes({
        'data-vb-item-count': String(target),
        'data-vb-item-min': String(min),
        'data-vb-item-max': String(max),
    });
}

/**
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
function findItemCountSection(component) {
    let current = component;

    while (current) {
        const attrs = current.getAttributes?.() ?? {};

        if (Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item-count')) {
            if (Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-logo-scroll')) {
                current = current.parent?.();

                continue;
            }

            const { items } = findSectionItems(current);

            if (items.length > 0) {
                return current;
            }
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * @param {object} editor
 */
export function registerSectionItemCountSettings(editor) {
    if (editor.__voodbuilderSectionItemCountSettingsRegistered) {
        return;
    }

    editor.__voodbuilderSectionItemCountSettingsRegistered = true;

    registerBlockSettings({
        id: 'section_item_count',
        matchBlockId: () => false,
        findRoot: (component) => findItemCountSection(component),
        matchesRoot: (root) => {
            if (! root) {
                return false;
            }

            const attrs = root.getAttributes?.() ?? {};

            // Logo scroll has its own settings (unique logos + speed).
            if (Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-logo-scroll')) {
                return false;
            }

            const { items } = findSectionItems(root);

            return Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item-count') && items.length > 0;
        },
        render: ({ mount, root }) => {
            const attrs = root.getAttributes?.() ?? {};
            const { items, min, max } = findSectionItems(root);
            const current = Math.max(
                min,
                Math.min(max, Number.parseInt(attrs['data-vb-item-count'] ?? String(items.length), 10) || items.length),
            );

            const { section, fields } = createFormSection('Layout items');
            const options = [];

            for (let value = min; value <= max; value += 1) {
                options.push({ value: String(value), label: String(value) });
            }

            fields.append(
                createSelectField({
                    label: 'Number of items',
                    name: 'vbItemCount',
                    value: String(current),
                    options,
                    onChange: (value) => applySectionItemCount(root, Number.parseInt(value, 10)),
                }),
            );

            mount.appendChild(section);
        },
    });
}
