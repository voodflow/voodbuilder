/**
 * Item-count / columns settings for section blocks annotated with data-vb-items-root / data-vb-item.
 */

import { registerBlockSettings } from './blocks/settings/index.js';
import {
    applyAnimatedStatsCounterDefaults,
    applyCounterConfig,
    normalizeCounterTrigger,
    readCounterConfig,
} from './editor-animated-blocks.js';
import { createFormSection, createSelectField, createTextField } from './editor-form-ui.js';

function isAnimatedStatsRoot(root) {
    const attrs = root?.getAttributes?.() ?? {};

    return Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-animated-stats')
        || root?.get?.('type') === 'voodbuilder-animated-stats';
}

const WIDTH_CLASS_PATTERN = /^(?:sm|md|lg|xl):w-1\/\d+$|^w-1\/\d+$|^w-full$/;
const FLEX_LAYOUT_CLASS_PATTERN = /^(?:flex|flex-wrap|-m-4|gap-\d+|grid|grid-cols-\d+|sm:grid-cols-\d+|md:grid-cols-\d+|lg:grid-cols-\d+)$/;

/**
 * @param {number} count
 * @returns {string[]}
 */
export function widthClassesForItemCount(count) {
    return widthClassesForColumns(Math.min(4, Math.max(1, count)));
}

/**
 * Explicit column count for equal-width items (desktop).
 *
 * @param {number} columns
 * @returns {string[]}
 */
export function widthClassesForColumns(columns) {
    const cols = Math.max(1, Math.min(6, Number(columns) || 1));

    switch (cols) {
        case 1:
            return ['w-full'];
        case 2:
            return ['w-full', 'sm:w-1/2'];
        case 3:
            return ['w-full', 'sm:w-1/2', 'md:w-1/3'];
        case 4:
            return ['w-1/2', 'sm:w-1/4', 'md:w-1/4'];
        case 5:
            return ['w-full', 'sm:w-1/2', 'md:w-1/5'];
        case 6:
            return ['w-1/2', 'sm:w-1/3', 'md:w-1/6'];
        default:
            return ['w-full', 'sm:w-1/2', 'md:w-1/3'];
    }
}

/**
 * Grid classes for the items root.
 * Column count is driven by --vb-item-columns CSS (animated-blocks.css) so the
 * editor canvas does not depend on md/lg breakpoints.
 *
 * @param {number} columns
 * @returns {string[]}
 */
export function gridClassesForColumns(columns) {
    void columns;

    return ['grid', 'gap-4'];
}

/**
 * @param {object} root
 * @param {number} columns
 */
/**
 * @param {object} root
 * @param {number} columns
 */
export function applyItemsRootColumnVar(root, columns) {
    const cols = Math.max(1, Math.min(6, Number(columns) || 1));

    root.addAttributes?.({
        'data-vb-items-root': root.getAttributes?.()?.['data-vb-items-root'] || '1',
        'data-vb-item-columns': String(cols),
    });

    // Persist CSS var on the model for reload/export. Editor setStyle often
    // drops custom properties from the live canvas element — patch the DOM too.
    if (typeof root.addStyle === 'function') {
        root.addStyle({ '--vb-item-columns': String(cols) }, { inline: true });
    } else {
        const style = { ...(root.getStyle?.() ?? {}) };
        style['--vb-item-columns'] = String(cols);
        delete style['grid-template-columns'];
        root.setStyle?.(style);
    }

    const paintCanvas = () => {
        const el = root.getEl?.()
            ?? root.getView?.()?.el
            ?? root.view?.el;

        if (! el || typeof el.style?.setProperty !== 'function') {
            return;
        }

        el.setAttribute('data-vb-items-root', el.getAttribute('data-vb-items-root') || '1');
        el.setAttribute('data-vb-item-columns', String(cols));
        el.style.setProperty('--vb-item-columns', String(cols));
        el.style.setProperty('display', 'grid');
        el.style.setProperty('gap', '1rem');
        el.style.setProperty('grid-template-columns', `repeat(${cols}, minmax(0, 1fr))`);
    };

    paintCanvas();
    // Editor may re-render the view after attribute/class updates — repaint after.
    if (typeof window !== 'undefined') {
        window.requestAnimationFrame(paintCanvas);
        window.setTimeout(paintCanvas, 0);
        window.setTimeout(paintCanvas, 40);
    }
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
 * @param {number} columns
 */
function applyItemsRootLayout(root, columns) {
    const classes = [...(root.getClasses?.() ?? [])];
    const kept = classes.filter((className) => ! FLEX_LAYOUT_CLASS_PATTERN.test(className)
        && ! WIDTH_CLASS_PATTERN.test(className));

    root.setClass([...kept, ...gridClassesForColumns(columns), 'text-center'].filter((value, index, all) => {
        return all.indexOf(value) === index;
    }));
    applyItemsRootColumnVar(root, columns);
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
 * @returns {{ root: object|null, items: object[], min: number, max: number, columns: number }}
 */
/**
 * Query a section for its items container.
 *
 * `Component.find()` runs a selector against the component's rendered element, so it
 * throws for a component that has no view yet — which is exactly the state components are
 * in while the canvas is being replaced (draft recovery, revision restore). This is called
 * from selection matching, where a throw surfaces as an uncaught error and abandons the
 * whole inspector pass, so an unrendered section has to read as "no items".
 *
 * @param {object} section
 * @returns {object|null}
 */
function findItemsRoot(section) {
    try {
        return section.find?.('[data-vb-items-root]')?.[0] ?? null;
    } catch {
        return null;
    }
}

export function findSectionItems(section) {
    const attrs = section.getAttributes?.() ?? {};
    const min = Math.max(1, Number.parseInt(attrs['data-vb-item-min'] ?? '1', 10) || 1);
    const max = Math.max(min, Number.parseInt(attrs['data-vb-item-max'] ?? '8', 10) || 8);
    const itemCount = Math.max(
        min,
        Math.min(max, Number.parseInt(attrs['data-vb-item-count'] ?? '0', 10) || 0),
    );
    const columnsAttr = Number.parseInt(attrs['data-vb-item-columns'] ?? '0', 10);
    const columns = columnsAttr > 0
        ? Math.max(1, Math.min(6, columnsAttr))
        : Math.max(1, Math.min(6, itemCount || 4));
    const root = findItemsRoot(section);

    if (! root) {
        return { root: null, items: [], min, max, columns };
    }

    return {
        root,
        items: markedItems(root),
        min,
        max,
        columns,
    };
}

/**
 * @param {object} section
 * @param {number} nextCount
 * @param {{ columns?: number }} [options]
 */
export function applySectionItemCount(section, nextCount, options = {}) {
    const { root, items, min, max, columns: currentColumns } = findSectionItems(section);

    if (! root || items.length === 0) {
        return;
    }

    const target = Math.max(min, Math.min(max, Number(nextCount) || min));
    const columns = Math.max(
        1,
        Math.min(6, Number(options.columns ?? currentColumns ?? target) || target),
    );
    const template = items[0];
    const widthClasses = widthClassesForColumns(columns);
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

    applyItemsRootLayout(root, columns);

    markedItems(root).forEach((item) => {
        // Grid parent owns columns; keep padding/alignment utilities only.
        replaceWidthClasses(item, []);
        const classes = [...(item.getClasses?.() ?? [])];

        if (! classes.includes('p-4')) {
            item.addClass('p-4');
        }

        if (! classes.includes('text-center')) {
            item.addClass('text-center');
        }

        void widthClasses;
    });

    section.addAttributes({
        'data-vb-item-count': String(target),
        'data-vb-item-columns': String(columns),
        'data-vb-item-min': String(min),
        'data-vb-item-max': String(max),
    });
    section.set?.({
        'data-vb-item-count': target,
        'data-vb-item-columns': columns,
    }, { silent: true });
}

/**
 * @param {object} section
 * @param {number} columns
 */
export function applySectionItemColumns(section, columns) {
    const { items } = findSectionItems(section);
    const count = Number.parseInt(
        section.getAttributes?.()?.['data-vb-item-count'] ?? String(items.length),
        10,
    ) || items.length;

    applySectionItemCount(section, count, { columns });
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
            const { items, min, max, columns } = findSectionItems(root);
            const current = Math.max(
                min,
                Math.min(max, Number.parseInt(attrs['data-vb-item-count'] ?? String(items.length), 10) || items.length),
            );
            const currentColumns = Math.max(
                1,
                Math.min(6, Number.parseInt(attrs['data-vb-item-columns'] ?? String(columns), 10) || columns),
            );

            const { section, fields } = createFormSection('Layout items');
            const itemOptions = [];

            for (let value = min; value <= max; value += 1) {
                itemOptions.push({ value: String(value), label: String(value) });
            }

            const columnOptions = [];

            for (let value = 1; value <= 6; value += 1) {
                columnOptions.push({ value: String(value), label: String(value) });
            }

            fields.append(
                createSelectField({
                    label: 'Number of items',
                    name: 'vbItemCount',
                    value: String(current),
                    options: itemOptions,
                    onChange: (value) => {
                        const nextCount = Number.parseInt(value, 10);
                        const cols = Number.parseInt(
                            root.getAttributes?.()?.['data-vb-item-columns'] ?? String(currentColumns),
                            10,
                        ) || currentColumns;

                        applySectionItemCount(root, nextCount, { columns: cols });
                    },
                }),
            );

            fields.append(
                createSelectField({
                    label: 'Columns',
                    name: 'vbItemColumns',
                    value: String(currentColumns),
                    options: columnOptions,
                    onChange: (value) => applySectionItemColumns(root, Number.parseInt(value, 10)),
                }),
            );

            mount.appendChild(section);

            if (! isAnimatedStatsRoot(root)) {
                return;
            }

            {
                const sampleCounter = root.findType?.('voodbuilder-animated-counter')?.[0]
                    ?? root.find?.('[data-voodbuilder-animated-counter], [data-vb-count-to], .vb-animated-counter')?.[0]
                    ?? null;
                const sample = sampleCounter ? readCounterConfig(sampleCounter) : {
                    trigger: 'visible',
                    duration: 1600,
                    easing: 'ease-out',
                    delay: 0,
                };

                const { section: animSection, fields: animFields } = createFormSection('Counter animation');

                animFields.append(createSelectField({
                    label: 'Start when',
                    name: 'vbStatsCountTrigger',
                    value: normalizeCounterTrigger(sample.trigger),
                    options: [
                        { value: 'always', label: 'Always' },
                        { value: 'visible', label: 'On visible' },
                        { value: 'hover', label: 'On hover' },
                        { value: 'click', label: 'On click' },
                    ],
                    onChange: (value) => applyAnimatedStatsCounterDefaults(root, { trigger: value }),
                }));

                const { field: durationField, input: durationInput } = createTextField({
                    label: 'Duration (ms)',
                    name: 'vbStatsCountDuration',
                    type: 'number',
                    value: String(sample.duration),
                    min: 200,
                    max: 8000,
                });
                const commitDuration = () => applyAnimatedStatsCounterDefaults(root, {
                    duration: Math.max(200, Math.min(8000, Number(durationInput.value) || 1600)),
                });
                durationInput.addEventListener('change', commitDuration);
                durationInput.addEventListener('blur', commitDuration);
                animFields.appendChild(durationField);

                animFields.append(createSelectField({
                    label: 'Easing',
                    name: 'vbStatsCountEasing',
                    value: sample.easing || 'ease-out',
                    options: [
                        { value: 'ease-out', label: 'Ease out' },
                        { value: 'linear', label: 'Linear' },
                        { value: 'ease-in-out', label: 'Ease in-out' },
                    ],
                    onChange: (value) => applyAnimatedStatsCounterDefaults(root, { easing: value }),
                }));

                const { field: staggerField, input: staggerInput } = createTextField({
                    label: 'Stagger delay (ms)',
                    name: 'vbStatsCountStagger',
                    type: 'number',
                    value: '120',
                    min: 0,
                    max: 1000,
                });
                const commitStagger = () => {
                    const step = Math.max(0, Math.min(1000, Number(staggerInput.value) || 0));
                    const counters = typeof root.findType === 'function'
                        ? root.findType('voodbuilder-animated-counter')
                        : [];
                    const fallback = [...(root.find?.('[data-voodbuilder-animated-counter], [data-vb-count-to], .vb-animated-counter') ?? [])];
                    const unique = [...new Set([...counters, ...fallback])];

                    unique.forEach((counter, index) => {
                        applyCounterConfig(counter, { delay: index * step });
                    });
                };
                staggerInput.addEventListener('change', commitStagger);
                staggerInput.addEventListener('blur', commitStagger);
                animFields.appendChild(staggerField);

                mount.appendChild(animSection);
            }
        },
    });
}
