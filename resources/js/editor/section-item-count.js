/**
 * Item-count / columns settings for section blocks annotated with data-vb-items-root / data-vb-item.
 */

import { registerBlockSettings } from './blocks/settings/index.js';
import {
    appendDeclarativeFields,
    appendDeclarativeItemEditors,
    findClosestItem,
    findDeclarativeFields,
    findItemDeclarativeFields,
    resolveFocusedItem,
    scopeHasDeclarativeFields,
} from './declarative-fields.js';
import {
    applyAnimatedStatsCounterDefaults,
    applyCounterConfig,
    normalizeCounterTrigger,
    readCounterConfig,
} from './editor-animated-blocks.js';
import { createCheckboxField, createCheckboxGrid, createFormSection, createSelectField, createTextField } from './editor-form-ui.js';

function componentTag(component) {
    return String(component?.get?.('tagName') ?? '').toLowerCase();
}

function readAttrs(component) {
    return {
        ...(component?.get?.('attributes') ?? {}),
        ...(component?.getAttributes?.() ?? {}),
    };
}

function isAnimatedStatsRoot(root) {
    const attrs = readAttrs(root);

    return Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-animated-stats')
        || root?.get?.('type') === 'voodbuilder-animated-stats';
}

function isTableRowComponent(component) {
    return componentTag(component) === 'tr' || component?.get?.('type') === 'row';
}

function isTableCellComponent(component) {
    const tag = componentTag(component);
    const type = String(component?.get?.('type') ?? '');

    return tag === 'td' || tag === 'th' || type === 'cell';
}

function isTheadAncestor(component, stop) {
    let current = component;

    while (current && current !== stop) {
        const tag = componentTag(current);
        const type = String(current.get?.('type') ?? '');

        if (tag === 'thead' || type === 'thead') {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

function isTabularRoot(root) {
    const tag = componentTag(root);
    const type = String(root?.get?.('type') ?? '');

    return tag === 'table' || tag === 'tbody' || tag === 'thead'
        || type === 'table' || type === 'tbody' || type === 'thead';
}

export function isTableSection(section) {
    const attrs = readAttrs(section);

    if (Object.prototype.hasOwnProperty.call(attrs, 'data-vb-table')) {
        return true;
    }

    const root = findItemsRoot(section);

    if (root && isTabularRoot(root)) {
        return true;
    }

    let tabular = false;

    walkComponents(section, (component) => {
        if (tabular) {
            return;
        }

        if (isTableRowComponent(component) || isTabularRoot(component)) {
            tabular = true;
        }
    });

    return tabular;
}

function tableColRole(component) {
    if (! isTableCellComponent(component) && ! Object.prototype.hasOwnProperty.call(readAttrs(component), 'data-vb-table-col')) {
        return null;
    }

    const value = readAttrs(component)['data-vb-table-col'];

    if (value === 'action' || value === 'label') {
        return value;
    }

    if (Object.prototype.hasOwnProperty.call(readAttrs(component), 'data-vb-table-col')) {
        return 'data';
    }

    const row = component.parent?.();
    const cells = [...(row?.components?.() ?? [])].filter((child) => isTableCellComponent(child));
    const index = cells.indexOf(component);

    if (index === 0) {
        return 'label';
    }

    if (index === cells.length - 1 && readAttrs(component)['data-vb-optional'] === 'select') {
        return 'action';
    }

    return 'data';
}

function tableRowCells(row) {
    return [...(row?.components?.() ?? [])].filter((child) => tableColRole(child) !== null || isTableCellComponent(child));
}

function tableDataCells(row) {
    return tableRowCells(row).filter((child) => tableColRole(child) === 'data');
}

function walkComponents(component, visit) {
    if (! component) {
        return;
    }

    visit(component);

    for (const child of [...(component.components?.() ?? [])]) {
        walkComponents(child, visit);
    }
}

function findTableHeadRow(section) {
    let found = null;

    walkComponents(section, (component) => {
        if (found) {
            return;
        }

        const tag = componentTag(component);
        const type = String(component.get?.('type') ?? '');
        const cells = tableRowCells(component);

        if (
            (tag === 'tr' || type === 'row')
            && cells.length > 0
            && ! hasItemAttr(component)
            && (isTheadAncestor(component, section) || tableColRole(cells[0]) === 'label')
        ) {
            found = component;
        }
    });

    return found;
}

function clearTableCellCopy(cell, placeholder) {
    let wrote = false;

    walkComponents(cell, (component) => {
        if (! Object.prototype.hasOwnProperty.call(component.getAttributes?.() ?? {}, 'data-vb-field')) {
            return;
        }

        if (typeof component.components === 'function') {
            component.components(placeholder);
        }

        component.set?.('content', placeholder);
        wrote = true;
    });

    if (! wrote && typeof cell.components === 'function') {
        cell.components(placeholder);
    }
}

function setComponentHidden(component, hidden) {
    if (hidden) {
        component.addAttributes?.({ hidden: '' });
        component.addClass?.('hidden');
    } else {
        component.removeAttributes?.('hidden');
        component.removeClass?.('hidden');
    }
}

const TABLE_COUNT_SAFETY_MAX = 99;

export function tableColumnBounds(section) {
    const attrs = readAttrs(section);
    const min = Math.max(1, Number.parseInt(attrs['data-vb-table-min-columns'] ?? '1', 10) || 1);

    return { min, max: Math.max(min, TABLE_COUNT_SAFETY_MAX) };
}

function currentTableColumnCount(section, headRow) {
    const attrs = section?.getAttributes?.() ?? {};
    const { min, max } = tableColumnBounds(section);
    const fromAttr = Number.parseInt(attrs['data-vb-table-columns'] ?? '0', 10) || 0;
    const fromHead = headRow ? tableRowCells(headRow).filter((cell) => tableColRole(cell) !== 'action').length : 0;
    const value = fromAttr > 0 ? fromAttr : fromHead;

    return Math.max(min, Math.min(max, value || min));
}

function syncRowColumnCount(row, target, { header = false } = {}) {
    const action = tableRowCells(row).find((cell) => tableColRole(cell) === 'action') ?? null;
    let dataCells = tableDataCells(row);

    while (dataCells.length > Math.max(0, target - 1)) {
        dataCells.pop()?.remove?.();
        dataCells = tableDataCells(row);
    }

    while (dataCells.length < Math.max(0, target - 1)) {
        const before = dataCells.length;
        const template = dataCells[dataCells.length - 1];

        if (! template?.clone) {
            break;
        }

        const clone = template.clone();
        const siblings = [...(row.components?.() ?? [])];
        const insertAt = action ? siblings.indexOf(action) : siblings.length;

        if (typeof row.append === 'function') {
            if (insertAt >= 0) {
                row.append(clone, { at: insertAt });
            } else {
                row.append(clone);
            }
        }

        clearTableCellCopy(clone, header ? 'Column' : '—');
        dataCells = tableDataCells(row);

        if (dataCells.length <= before) {
            break;
        }
    }
}

export function applyTableColumnCount(section, nextCount) {
    if (! isTableSection(section)) {
        return;
    }

    const { min, max } = tableColumnBounds(section);
    const target = Math.max(min, Math.min(max, Number(nextCount) || min));
    const headRow = findTableHeadRow(section);
    const { items } = findSectionItems(section);

    if (headRow) {
        syncRowColumnCount(headRow, target, { header: true });
    }

    for (const row of items) {
        syncRowColumnCount(row, target, { header: false });
    }

    section.addAttributes?.({
        'data-vb-table-columns': String(target),
    });
    section.set?.({ 'data-vb-table-columns': target }, { silent: true });
}

function optionalVisible(section, key) {
    const raw = section.getAttributes?.()?.[`data-vb-show-${key}`];

    return raw !== '0' && raw !== 'false';
}

export function applySectionOptional(section, key, visible) {
    section.addAttributes?.({
        [`data-vb-show-${key}`]: visible ? '1' : '0',
    });

    walkComponents(section, (component) => {
        if (component === section) {
            return;
        }

        const optional = component.getAttributes?.()?.['data-vb-optional'];
        const colRole = tableColRole(component);

        if (optional === key || (key === 'select' && colRole === 'action')) {
            setComponentHidden(component, ! visible);
        }
    });
}

function collectSectionOptionals(section) {
    const found = [];
    const seen = new Set();

    walkComponents(section, (component) => {
        if (component === section) {
            return;
        }

        const attrs = readAttrs(component);
        const key = String(attrs['data-vb-optional'] ?? '').trim();

        if (! key || seen.has(key)) {
            return;
        }

        seen.add(key);
        found.push({
            key,
            label: String(attrs['data-vb-optional-label'] || attrs['data-vb-field-label'] || key).trim() || key,
        });
    });

    return found;
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
    const direct = [...(root.components?.() ?? [])].filter((child) => hasItemAttr(child));

    if (direct.length > 0) {
        return direct;
    }

    // GrapesJS may insert a wrapper between items-root and data-vb-item children.
    const unwrapped = [];

    for (const child of [...(root.components?.() ?? [])]) {
        for (const grand of [...(child.components?.() ?? [])]) {
            if (hasItemAttr(grand)) {
                unwrapped.push(grand);
            }
        }
    }

    if (unwrapped.length > 0) {
        return unwrapped;
    }

    const nested = [];

    for (const child of [...(root.components?.() ?? [])]) {
        for (const grand of [...(child.components?.() ?? [])]) {
            for (const great of [...(grand.components?.() ?? [])]) {
                if (hasItemAttr(great)) {
                    nested.push(great);
                }
            }
        }
    }

    if (nested.length > 0) {
        return nested;
    }

    if (! isTabularRoot(root) && componentTag(root) !== 'div') {
        return [];
    }

    const rows = [];

    walkComponents(root, (component) => {
        if (component === root || ! isTableRowComponent(component) || isTheadAncestor(component, root)) {
            return;
        }

        rows.push(component);
    });

    return rows;
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
function hasItemAttr(component) {
    return Object.prototype.hasOwnProperty.call(readAttrs(component), 'data-vb-item');
}

/**
 * Prefer the items-root owned by `scope`, not one nested inside a child item
 * (FAQ: categories root vs questions root inside a category).
 *
 * Prefer a component-tree walk over `find()` — GrapesJS `find()` needs a rendered
 * view and can miss roots inside `<details>` while the inspector resolves.
 *
 * @param {object} scope
 * @returns {object|null}
 */
function findItemsRoot(scope) {
    if (! scope) {
        return null;
    }

    let found = null;

    const visit = (component, insideNestedItem) => {
        if (! component || found) {
            return;
        }

        if (component !== scope) {
            const attrs = readAttrs(component);

            if (Object.prototype.hasOwnProperty.call(attrs, 'data-vb-items-root') && ! insideNestedItem) {
                found = component;

                return;
            }
        }

        const nextInside = insideNestedItem || (component !== scope && hasItemAttr(component));

        for (const child of [...(component.components?.() ?? [])]) {
            visit(child, nextInside);
        }
    };

    try {
        visit(scope, false);
    } catch {
        return null;
    }

    if (found) {
        return found;
    }

    // Fallback for still-unparsed trees: CSS find on the rendered view.
    try {
        const roots = [...(scope.find?.('[data-vb-items-root]') ?? [])];

        if (roots.length === 0) {
            return findTabularItemsRoot(scope);
        }

        if (hasItemAttr(scope)) {
            for (const root of roots) {
                let parent = root.parent?.();
                let insideChildItem = false;

                while (parent && parent !== scope) {
                    if (hasItemAttr(parent)) {
                        insideChildItem = true;
                        break;
                    }

                    parent = parent.parent?.();
                }

                if (! insideChildItem) {
                    return root;
                }
            }

            return roots[0] ?? null;
        }

        for (const root of roots) {
            let parent = root.parent?.();
            let insideChildItem = false;

            while (parent && parent !== scope) {
                if (hasItemAttr(parent)) {
                    insideChildItem = true;
                    break;
                }

                parent = parent.parent?.();
            }

            if (! insideChildItem) {
                return root;
            }
        }

        return roots[0] ?? findTabularItemsRoot(scope);
    } catch {
        return findTabularItemsRoot(scope);
    }
}

function findTabularItemsRoot(scope) {
    let tbody = null;
    let table = null;

    walkComponents(scope, (component) => {
        if (component === scope) {
            return;
        }

        const tag = componentTag(component);
        const type = String(component.get?.('type') ?? '');

        if (! tbody && (tag === 'tbody' || type === 'tbody')) {
            tbody = component;
        }

        if (! table && (tag === 'table' || type === 'table')) {
            table = component;
        }
    });

    return tbody ?? table;
}

export function findSectionItems(section) {
    const attrs = section.getAttributes?.() ?? {};
    const root = findItemsRoot(section);
    const tabular = Boolean(
        root
        && (isTabularRoot(root) || markedItems(root).some((item) => isTableRowComponent(item))),
    );
    const min = Math.max(1, Number.parseInt(attrs['data-vb-item-min'] ?? '1', 10) || 1);
    const max = tabular
        ? Math.max(min, TABLE_COUNT_SAFETY_MAX)
        : Math.max(min, Number.parseInt(attrs['data-vb-item-max'] ?? '8', 10) || 8);
    const itemCount = Math.max(
        min,
        Math.min(max, Number.parseInt(attrs['data-vb-item-count'] ?? '0', 10) || 0),
    );
    const columnsAttr = Number.parseInt(attrs['data-vb-item-columns'] ?? '0', 10);
    const columns = columnsAttr > 0
        ? Math.max(1, Math.min(6, columnsAttr))
        : Math.max(1, Math.min(6, itemCount || 4));

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
/**
 * @param {object} section
 * @param {object|null} [itemsRoot]
 * @returns {boolean}
 */
export function shouldPreserveItemsLayout(section, itemsRoot = null) {
    const sectionAttrs = section?.getAttributes?.() ?? {};

    if (sectionAttrs['data-vb-items-layout'] === 'preserve') {
        return true;
    }

    const root = itemsRoot ?? findItemsRoot(section);
    const rootAttrs = root?.getAttributes?.() ?? {};

    return rootAttrs['data-vb-items-layout'] === 'preserve';
}

export function applySectionItemCount(section, nextCount, options = {}) {
    const { root, items, min, max, columns: currentColumns } = findSectionItems(section);

    if (! root || items.length === 0) {
        return;
    }

    const table = isTableSection(section);
    const preserveLayout = table || shouldPreserveItemsLayout(section, root);
    const target = Math.max(min, Math.min(max, Number(nextCount) || min));
    const columns = Math.max(
        1,
        Math.min(6, Number(options.columns ?? currentColumns ?? target) || target),
    );
    const widthClasses = widthClassesForColumns(columns);
    const working = [...items];
    const template = table ? working[working.length - 1] : items[0];

    while (working.length > target) {
        working.pop()?.remove?.();
    }

    while (working.length < target) {
        const clone = template.clone();
        clone.addAttributes({ 'data-vb-item': '' });
        root.append(clone);
        working.push(root.components().at(root.components().length - 1));
    }

    if (! preserveLayout) {
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
    }

    section.addAttributes({
        'data-vb-item-count': String(target),
        'data-vb-item-min': String(min),
        'data-vb-item-max': String(max),
        ...(table ? {} : { 'data-vb-item-columns': String(columns) }),
        ...(preserveLayout ? { 'data-vb-items-layout': 'preserve' } : {}),
    });
    section.set?.({
        'data-vb-item-count': target,
        ...(table ? {} : { 'data-vb-item-columns': columns }),
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
function isCatalogSection(component) {
    const attrs = readAttrs(component);

    return Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item-count')
        || Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-section-block')
        || Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-block');
}

function findItemCountSection(component) {
    let current = component;

    while (current) {
        const attrs = readAttrs(current);

        if (Object.prototype.hasOwnProperty.call(attrs, 'data-voodbuilder-logo-scroll')) {
            current = current.parent?.();

            continue;
        }

        if (isCatalogSection(current)) {
            const { items } = findSectionItems(current);

            if (
                items.length > 0
                && (
                    Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item-count')
                    || isTableSection(current)
                )
            ) {
                return current;
            }
        }

        current = current.parent?.();
    }

    return null;
}

/**
 * Keep data-vb-item-count in sync when an author deletes a repeating item from the
 * canvas (pill / FAQ category / question), including middle items.
 *
 * @param {object} editor
 */
function registerRepeatingItemCountSync(editor) {
    if (editor.__voodbuilderRepeatingItemCountSyncRegistered) {
        return;
    }

    editor.__voodbuilderRepeatingItemCountSyncRegistered = true;

    editor.on('component:remove:before', (component) => {
        if (! hasItemAttr(component)) {
            return;
        }

        // Whole section/table delete: skip bookkeeping — owner is dying too.
        let ancestor = component;

        while (ancestor) {
            if (ancestor.__voodbuilderRemoving) {
                return;
            }

            ancestor = ancestor.parent?.();
        }

        if (editor.__voodbuilderBulkStructureUpdate) {
            return;
        }

        const parent = component.parent?.();
        const parentAttrs = parent?.getAttributes?.() ?? {};

        if (! Object.prototype.hasOwnProperty.call(parentAttrs, 'data-vb-items-root')) {
            return;
        }

        let owner = parent;

        while (owner) {
            if (owner.__voodbuilderRemoving) {
                return;
            }

            const attrs = owner.getAttributes?.() ?? {};

            if (Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item-count')) {
                component.__vbItemCountOwner = owner;
                break;
            }

            owner = owner.parent?.();
        }
    });

    editor.on('component:remove', (component) => {
        const owner = component?.__vbItemCountOwner;

        if (
            ! owner
            || owner.isRemoved?.()
            || owner.__voodbuilderRemoving
            || editor.__voodbuilderBulkStructureUpdate
        ) {
            return;
        }

        const { items, min, max } = findSectionItems(owner);
        const next = Math.max(min, Math.min(max, items.length));

        owner.addAttributes?.({
            'data-vb-item-count': String(next),
        });
        owner.set?.({ 'data-vb-item-count': next }, { silent: true });
    });
}

/**
 * @param {object} editor
 */
export function registerSectionItemCountSettings(editor) {
    if (editor.__voodbuilderSectionItemCountSettingsRegistered) {
        return;
    }

    editor.__voodbuilderSectionItemCountSettingsRegistered = true;

    registerRepeatingItemCountSync(editor);

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

            if (items.length === 0) {
                return false;
            }

            return Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item-count')
                || isTableSection(root);
        },
        render: ({ mount, root, editor, selected }) => {
            const attrs = root.getAttributes?.() ?? {};
            const { items, min, max, columns } = findSectionItems(root);
            const preserveLayout = shouldPreserveItemsLayout(root);
            const selection = selected ?? editor?.getSelected?.() ?? null;
            const focused = scopeHasDeclarativeFields(root)
                ? resolveFocusedItem(items, selection)
                : null;

            const itemSingular = String(attrs['data-vb-item-singular'] || 'Item').trim() || 'Item';

            // Selecting a repeating item that is itself an item-count group (FAQ category):
            // open that group's Questions UI instead of only the category title fields.
            if (focused) {
                const nestedAttrs = focused.item.getAttributes?.() ?? {};

                if (Object.prototype.hasOwnProperty.call(nestedAttrs, 'data-vb-item-count')) {
                    const nested = findSectionItems(focused.item);

                    if (nested.items.length > 0) {
                        renderSectionItemCountPanel({
                            mount,
                            root: focused.item,
                            editor,
                            selected: selection,
                        });

                        return;
                    }
                }

                appendDeclarativeItemEditors(mount, items, editor, {
                    selected: selection,
                    itemSingular,
                    selectHint: attrs['data-vb-items-select-hint']
                        || (isTableSection(root)
                            ? 'Select a row on the canvas to edit its content.'
                            : 'Select an item on the canvas to edit its content.'),
                });

                return;
            }

            renderSectionItemCountPanel({
                mount,
                root,
                editor,
                selected: selection,
                items,
                min,
                max,
                columns,
                preserveLayout,
                attrs,
                itemSingular,
            });
        },
    });
}

function appendCountInput(fields, { label, name, value, min, onCommit }) {
    const { field, input } = createTextField({
        label,
        name,
        type: 'number',
        value: String(value),
        min,
    });
    input.step = '1';
    const commit = () => {
        const next = Math.max(min, Math.min(TABLE_COUNT_SAFETY_MAX, Math.floor(Number.parseFloat(input.value) || min)));
        input.value = String(next);
        onCommit(next);
    };

    input.addEventListener('change', commit);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            input.blur();
        }
    });
    fields.appendChild(field);
}

/**
 * Render item-count + Content for one item-count root (section or nested group).
 *
 * @param {{
 *   mount: HTMLElement,
 *   root: object,
 *   editor: object,
 *   selected?: object|null,
 *   items?: object[],
 *   min?: number,
 *   max?: number,
 *   columns?: number,
 *   preserveLayout?: boolean,
 *   attrs?: Record<string, string>,
 *   itemSingular?: string,
 * }} params
 */
function renderSectionItemCountPanel({
    mount,
    root,
    editor,
    selected = null,
    items: itemsArg,
    min: minArg,
    max: maxArg,
    columns: columnsArg,
    preserveLayout: preserveArg,
    attrs: attrsArg,
    itemSingular: singularArg,
}) {
    const attrs = attrsArg ?? root.getAttributes?.() ?? {};
    const resolved = (itemsArg && minArg != null && maxArg != null)
        ? {
            items: itemsArg,
            min: minArg,
            max: maxArg,
            columns: columnsArg ?? 1,
        }
        : findSectionItems(root);
    const items = resolved.items;
    const min = resolved.min;
    const max = resolved.max;
    const columns = resolved.columns;
    const preserveLayout = preserveArg ?? shouldPreserveItemsLayout(root);
    const selection = selected ?? editor?.getSelected?.() ?? null;
    const itemSingular = singularArg
        ?? (String(attrs['data-vb-item-singular'] || 'Item').trim() || 'Item');

    const nestedFocused = scopeHasDeclarativeFields(root)
        ? resolveFocusedItem(items, selection)
        : null;

    if (nestedFocused) {
        appendDeclarativeItemEditors(mount, items, editor, {
            selected: selection,
            itemSingular,
        });

        return;
    }

            const table = isTableSection(root);
            const current = Math.max(
                min,
                Math.min(max, Number.parseInt(attrs['data-vb-item-count'] ?? String(items.length), 10) || items.length),
            );
            const currentColumns = Math.max(
                1,
                Math.min(6, Number.parseInt(attrs['data-vb-item-columns'] ?? String(columns), 10) || columns),
            );
            const { min: tableColMin } = tableColumnBounds(root);
            const tableColumns = currentTableColumnCount(root, findTableHeadRow(root));

            const itemsLabel = String(
                attrs['data-vb-items-label'] || (table ? 'Number of rows' : 'Number of items'),
            ).trim() || (table ? 'Number of rows' : 'Number of items');
            const { section, fields } = createFormSection(itemsLabel);

            if (table) {
                appendCountInput(fields, {
                    label: itemsLabel,
                    name: 'vbItemCount',
                    value: current,
                    min,
                    onCommit: (nextCount) => applySectionItemCount(root, nextCount),
                });
                appendCountInput(fields, {
                    label: 'Number of columns',
                    name: 'vbTableColumns',
                    value: tableColumns,
                    min: tableColMin,
                    onCommit: (nextCount) => applyTableColumnCount(root, nextCount),
                });
            } else {
                const itemOptions = [];

                for (let value = min; value <= max; value += 1) {
                    itemOptions.push({ value: String(value), label: String(value) });
                }

                fields.append(
                    createSelectField({
                        label: itemsLabel,
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

                if (! preserveLayout) {
                    const columnOptions = [];

                    for (let value = 1; value <= 6; value += 1) {
                        columnOptions.push({ value: String(value), label: String(value) });
                    }

                    fields.append(
                        createSelectField({
                            label: 'Columns',
                            name: 'vbItemColumns',
                            value: String(currentColumns),
                            options: columnOptions,
                            onChange: (value) => applySectionItemColumns(root, Number.parseInt(value, 10)),
                        }),
                    );
                }
            }

            const optionalFields = collectSectionOptionals(root).map(({ key, label }) => createCheckboxField({
                label,
                name: `vbShow-${key}`,
                checked: optionalVisible(root, key),
                onChange: (checked) => applySectionOptional(root, key, checked),
            }));

            if (optionalFields.length > 0) {
                fields.append(createCheckboxGrid(optionalFields));
            }

            mount.appendChild(section);

            if (scopeHasDeclarativeFields(root)) {
                // Section → heading/intro. Category item → its title only (not questions).
                const ownFields = hasItemAttr(root)
                    ? findItemDeclarativeFields(root)
                    : findDeclarativeFields(root).filter((field) => {
                        const owner = findClosestItem(field.component);

                        return ! owner || owner === root;
                    });

                if (ownFields.length > 0) {
                    appendDeclarativeFields(mount, ownFields, editor, {
                        heading: hasItemAttr(root) ? itemSingular : 'Content',
                    });
                }

                appendDeclarativeItemEditors(mount, items, editor, {
                    selected: selection,
                    itemSingular,
                    selectHint: attrs['data-vb-items-select-hint']
                        || (table
                            ? 'Select a row on the canvas to edit its content.'
                            : 'Select an item on the canvas to edit its content.'),
                });
            }

            if (! isAnimatedStatsRoot(root)) {
                return;
            }

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
