/**
 * Voodbuilder GrapesJS plugin — uses the public GrapesJS plugin API only.
 * @see https://grapesjs.com/docs/modules/Plugins.html
 */

import { clearBackgroundCssRules, pruneRedundantSpacingZeros, resolveVisualStyleTarget } from '../tailwind-visual-style.js';
import { registerBoundComponentType } from '../bindings-ui.js';
import { registerComponentInstanceType } from '../component-instance-type.js';
import { encodeVpressConfig, parseVpressConfig } from '../voodbuilder-dynamic-config.js';
import { isComponentCategoryId } from '../component-block-utils.js';
import { resolveCategoryOrder, normalizeCategoryLabel } from '../section-block-meta.js';
import {
    isClearedBackground,
    restoreBackgroundClasses,
    stripBackgroundClasses,
    stripBorderColorClasses,
    stripRoundedClasses,
    stripTextColorClasses,
} from '../theme-tokens.js';

function isSiteFooterBlock(blockId) {
    return blockId === 'site_footer' || (typeof blockId === 'string' && blockId.startsWith('site_footer_'));
}

const SECTION_PADDING_CLASSES = ['py-0', 'py-8', 'py-12', 'py-16', 'py-20', 'py-24'];

const TAILWIND_SPACING_CLASS = /^(?:md:)?(?:[pm][xytblr]?|gap(?:-[xy])?)-/;

function stripTailwindSpacingClasses(component) {
    const classes = component
        .getClasses()
        .filter((className) => ! TAILWIND_SPACING_CLASS.test(className) && ! SECTION_PADDING_CLASSES.includes(className));

    component.setClass(classes);
}

function isSpacingStyleProperty(property) {
    return typeof property === 'string' && /^(padding|margin)(-(top|right|bottom|left))?$/.test(property);
}

function registerSpacingStyleSync(editor) {
    editor.on('component:styleUpdate', (component, property) => {
        if (! isSpacingStyleProperty(property)) {
            return;
        }

        stripTailwindSpacingClasses(component);
        window.requestAnimationFrame(() => {
            pruneRedundantSpacingZeros(component);

            for (const target of [component, resolveVisualStyleTarget(component)]) {
                if (target && target !== component) {
                    pruneRedundantSpacingZeros(target);
                }
            }
        });
    });
}

function isBorderRadiusProperty(property) {
    return typeof property === 'string' && /border-radius|border-top-left-radius|border-top-right-radius|border-bottom-left-radius|border-bottom-right-radius/.test(property);
}

function isBorderPaintProperty(property) {
    return typeof property === 'string' && /^border(-color|-width|-style)?$/.test(property);
}

function registerTailwindStyleSync(editor) {
    editor.on('component:styleUpdate', (component, property) => {
        if (editor.__voodbuilderPurgingBackground) {
            return;
        }

        if (! property || ! component) {
            return;
        }

        const target = resolveVisualStyleTarget(component);
        const wrapperStyle = component.getStyle?.() ?? {};
        const targetStyle = target?.getStyle?.() ?? {};

        if (property === 'background-color' || property === 'background') {
            const background = wrapperStyle[property]
                ?? wrapperStyle['background-color']
                ?? wrapperStyle.background
                ?? targetStyle[property]
                ?? targetStyle['background-color']
                ?? targetStyle.background;

            if (isClearedBackground(background)) {
                restoreBackgroundClasses(target);
                clearBackgroundCssRules(editor, component);
            } else if (background != null && background !== '') {
                stripBackgroundClasses(target);
            }
        }

        if (property === 'color') {
            stripTextColorClasses(target);
        }

        if (isBorderRadiusProperty(property)) {
            stripRoundedClasses(target);
        }

        if (isBorderPaintProperty(property)) {
            stripBorderColorClasses(target);
        }

        target.view?.updateStyles?.();
    });
}

function fixGrapesJsSrcUri(value) {
    let fixed = value.replace(/%(?![0-9A-Fa-f]{2})/g, '%25');

    if (fixed.startsWith('data:image/svg+xml,')) {
        fixed = fixed.replace(/ /g, '%20');
    }

    return fixed;
}

const NEUTRAL_PLACEHOLDER_SRC = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">'
    + '<rect width="800" height="500" fill="#e2e8f0"/>'
    + '<text x="400" y="250" text-anchor="middle" dominant-baseline="middle" fill="#94a3b8" font-family="system-ui,sans-serif" font-size="18">Image placeholder</text>'
    + '</svg>',
);

function normalizePlaceholderHtml(html) {
    if (typeof html !== 'string' || html === '' || ! html.includes('://')) {
        return html;
    }

    return html
        .replace(/\bsrc=(["'])(https?:\/\/(?:dummyimage|placehold|placekitten|placeimg|picsum)\.[^"']+)\1/gi, `src=$1${NEUTRAL_PLACEHOLDER_SRC}$1`)
        .replace(/\bbackground-image\s*:\s*url\((["']?)(https?:\/\/(?:dummyimage|placehold|placekitten|placeimg|picsum)\.[^"')]+)\1\)\s*;?/gi, `background-image: url(${NEUTRAL_PLACEHOLDER_SRC});`);
}

function sanitizeBlockHtml(html) {
    if (typeof html !== 'string' || html === '') {
        return html;
    }

    const normalized = normalizePlaceholderHtml(html);

    return normalized.replace(/\bsrc=(["'])(.*?)\1/gi, (match, quote, src) => `src=${quote}${fixGrapesJsSrcUri(src)}${quote}`);
}

function sectionPaddingTarget(section) {
    return section.find('.container')[0] ?? section.components().at(0);
}

function readSectionPadding(container) {
    const classes = container.getClasses?.() ?? [];

    return SECTION_PADDING_CLASSES.find((className) => classes.includes(className)) ?? 'py-24';
}

function registerLayoutSectionType(editor) {
    const paddingTrait = {
        type: 'select',
        label: 'Vertical padding (Tailwind)',
        name: 'vpressSectionPy',
        options: [
            { id: 'py-0', name: 'None' },
            { id: 'py-12', name: 'Compact (3rem)' },
            { id: 'py-16', name: 'Medium (4rem)' },
            { id: 'py-24', name: 'Large (6rem)' },
        ],
    };

    const sectionTypeDefinition = {
        isComponent: (element) => {
            if (element?.tagName !== 'SECTION') {
                return false;
            }

            return Boolean(element.querySelector?.('.container'))
                || element.classList.contains('body-font');
        },
        extend: 'default',
        model: {
            defaults: {
                name: 'Section',
                vpressSectionPy: 'py-24',
                traits: [paddingTrait],
            },
            init() {
                const container = sectionPaddingTarget(this);

                if (container) {
                    this.set('vpressSectionPy', readSectionPadding(container), { silent: true });
                }

                this.on('change:vpressSectionPy', () => {
                    applySectionPadding(this, this.get('vpressSectionPy'));
                });
            },
        },
    };

    editor.DomComponents.addType('voodbuilder-section', sectionTypeDefinition);

    // Legacy alias: existing projects may reference this type id in saved JSON.
    editor.DomComponents.addType('voodbuilder-tailblocks-section', {
        extend: 'voodbuilder-section',
    });

    editor.DomComponents.addType('voodbuilder-container', {
        isComponent: (element) => {
            if (element?.tagName !== 'DIV') {
                return false;
            }

            return element.classList.contains('container');
        },
        extend: 'default',
        model: {
            defaults: {
                name: 'Container',
                vpressSectionPy: 'py-24',
                traits: [paddingTrait],
            },
            init() {
                this.set('vpressSectionPy', readSectionPadding(this), { silent: true });

                this.on('change:vpressSectionPy', () => {
                    applySectionPaddingToElement(this, this.get('vpressSectionPy'));
                });
            },
        },
    });

    editor.DomComponents.addType('voodbuilder-tailblocks-container', {
        extend: 'voodbuilder-container',
    });
}

function applySectionPaddingToElement(component, pyClass) {
    const classes = component
        .getClasses()
        .filter((className) => ! SECTION_PADDING_CLASSES.includes(className) && ! className.startsWith('md:py-'));

    if (pyClass && pyClass !== 'py-0') {
        classes.push(pyClass);
    }

    component.setClass(classes);
}

function applySectionPadding(section, pyClass) {
    const container = sectionPaddingTarget(section);

    if (! container) {
        return;
    }

    applySectionPaddingToElement(container, pyClass);
}

function syncVpressDynamicAttributes(component) {
    const attributes = component.getAttributes();
    const blockId = attributes['data-voodbuilder-block'] ?? '';
    const config = parseVpressConfig(attributes['data-voodbuilder-config']);

    component.set('vpressConfig', config, { silent: true });
    component.set('name', blockId ? `Voodbuilder: ${blockId}` : 'Voodbuilder block');
    component.setAttributes({
        'data-voodbuilder-block': blockId,
        'data-voodbuilder-config': encodeVpressConfig(config),
        class: attributes.class ?? 'voodbuilder-gjs-dynamic',
    });
}

function lockComponentTree(component) {
    const attributes = component.getAttributes?.() ?? {};
    const protectedSlot = attributes['data-voodbuilder-menu'] || attributes['data-voodbuilder-brand'];

    component.set({
        removable: false,
        draggable: false,
        copyable: false,
        selectable: ! protectedSlot,
        hoverable: ! protectedSlot,
        layerable: ! protectedSlot,
        editable: false,
        stylable: ! protectedSlot,
    });

    component.components().forEach((child) => {
        lockComponentTree(child);
    });
}

function lockDynamicPreviewContent(component) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];

    if (isSiteFooterBlock(blockId)) {
        component.find('[data-voodbuilder-menu], [data-voodbuilder-brand]').forEach((slot) => {
            lockComponentTree(slot);
        });

        return;
    }

    component.components().forEach((child) => {
        lockComponentTree(child);
    });
}

function applySiteFooterColumns(component, columns) {
    const count = Math.max(1, Math.min(4, Number(columns) || 4));

    component.find('[data-voodbuilder-footer-col]').forEach((column) => {
        const index = Number(column.getAttributes()['data-voodbuilder-footer-col'] ?? 0);
        const classes = column
            .getClasses()
            .filter((className) => className !== 'hidden');

        if (index > count) {
            classes.push('hidden');
        }

        column.setClass(classes);
    });

    component.addAttributes({ 'data-voodbuilder-footer-columns': String(count) });

    const config = {
        ...(component.get('vpressConfig') ?? {}),
        columns: count,
    };

    component.set('vpressConfig', config, { silent: true });
    component.set('vpressFooterColumns', String(count), { silent: true });
    component.addAttributes({
        'data-voodbuilder-config': encodeVpressConfig(config),
    });
}

function configureSiteFooterTraits(component) {
    const blockId = component.getAttributes()['data-voodbuilder-block'];

    if (blockId === 'site_footer_d' || component.find('[data-voodbuilder-footer-col]').length === 0) {
        component.set('traits', []);

        return;
    }

    component.set('traits', [
        {
            type: 'select',
            label: 'Columns',
            name: 'vpressFooterColumns',
            changeProp: true,
            options: [
                { id: '1', name: '1 column' },
                { id: '2', name: '2 columns' },
                { id: '3', name: '3 columns' },
                { id: '4', name: '4 columns' },
            ],
        },
    ]);

    const columns = String(component.get('vpressConfig')?.columns ?? 4);

    component.set('vpressFooterColumns', columns, { silent: true });
    applySiteFooterColumns(component, columns);

    component.on('change:vpressFooterColumns', () => {
        applySiteFooterColumns(component, component.get('vpressFooterColumns'));
    });
}

function findVpressDynamicAncestor(component) {
    let parent = component?.parent?.();

    while (parent) {
        if (parent.get('type') === 'voodbuilder-dynamic') {
            return parent;
        }

        parent = parent.parent();
    }

    return null;
}

function isInsideProtectedSlot(component) {
    let current = component;

    while (current) {
        const attributes = current.getAttributes?.() ?? {};

        if (attributes['data-voodbuilder-menu'] || attributes['data-voodbuilder-brand']) {
            return true;
        }

        if (current.get('type') === 'voodbuilder-dynamic') {
            return false;
        }

        current = current.parent();
    }

    return false;
}

function registerDynamicBlockGuards(editor) {
    editor.on('component:remove', (removed) => {
        if (removed.get('type') === 'voodbuilder-dynamic') {
            return;
        }

        const dynamic = findVpressDynamicAncestor(removed);

        if (! dynamic?.parent()) {
            return;
        }

        if (isSiteFooterBlock(dynamic.getAttributes()['data-voodbuilder-block']) && ! isInsideProtectedSlot(removed)) {
            return;
        }

        window.queueMicrotask(() => {
            if (dynamic.parent()) {
                dynamic.remove();
            }
        });
    });
}

function pruneEmptyDynamicBlocks(editor) {
    editor.getWrapper().find('[data-voodbuilder-block]').forEach((component) => {
        const blockId = component.getAttributes()['data-voodbuilder-block'];

        if (isSiteFooterBlock(blockId)) {
            return;
        }

        if (component.components().length === 0) {
            component.remove();
        }
    });
}

function refreshDynamicSlots(component, freshRoot) {
    for (const selector of ['[data-voodbuilder-menu]', '[data-voodbuilder-brand]']) {
        const freshSlots = [...freshRoot.querySelectorAll(selector)];
        const componentSlots = component.find(selector);

        freshSlots.forEach((freshSlot, index) => {
            const target = componentSlots[index];

            if (! target) {
                return;
            }

            target.components(freshSlot.innerHTML);
            lockComponentTree(target);
        });
    }
}

function applyFreshFooterAttributes(component, fresh, blockId, freshConfig) {
    component.set('vpressConfig', freshConfig, { silent: true });
    component.addAttributes({
        'data-voodbuilder-block': fresh.getAttribute('data-voodbuilder-block') ?? blockId,
        'data-voodbuilder-config': fresh.getAttribute('data-voodbuilder-config') ?? encodeVpressConfig(freshConfig),
        class: fresh.getAttribute('class') ?? 'voodbuilder-gjs-dynamic voodbuilder-gjs-footer w-full',
        'data-voodbuilder-hydrate-slots': '1',
    });
}

function registerDynamicBlockType(editor) {
    editor.DomComponents.addType('voodbuilder-dynamic', {
        isComponent: (element) => {
            const blockId = element?.getAttribute?.('data-voodbuilder-block');

            if (! blockId) {
                return false;
            }

            const config = parseVpressConfig(element.getAttribute('data-voodbuilder-config') ?? '{}');
            const isFooter = element?.tagName === 'FOOTER' && isSiteFooterBlock(blockId);
            const defaultClass = isFooter
                ? 'voodbuilder-gjs-dynamic voodbuilder-gjs-footer w-full border-t border-vp-divider bg-vp-bg'
                : (element.getAttribute('class') ?? 'voodbuilder-gjs-dynamic');

            return {
                type: 'voodbuilder-dynamic',
                tagName: isFooter ? 'footer' : (element.tagName?.toLowerCase() ?? 'div'),
                vpressConfig: config,
                attributes: {
                    'data-voodbuilder-block': blockId,
                    'data-voodbuilder-config': encodeVpressConfig(config),
                    class: defaultClass,
                    ...(element.hasAttribute('data-voodbuilder-hydrate-slots')
                        ? { 'data-voodbuilder-hydrate-slots': '1' }
                        : {}),
                },
            };
        },
        model: {
            defaults: {
                tagName: 'div',
                name: 'Voodbuilder block',
                draggable: true,
                droppable: false,
                editable: false,
                copyable: true,
                removable: true,
                stylable: true,
                layerable: true,
                highlightable: true,
                vpressConfig: {},
                attributes: {
                    class: 'voodbuilder-gjs-dynamic',
                    'data-voodbuilder-block': '',
                    'data-voodbuilder-config': encodeVpressConfig({}),
                },
                traits: [],
            },
            init() {
                syncVpressDynamicAttributes(this);

                if (isSiteFooterBlock(this.getAttributes()['data-voodbuilder-block'])) {
                    configureSiteFooterTraits(this);
                }

                this.on('change:attributes:data-voodbuilder-config', () => {
                    syncVpressDynamicAttributes(this);
                });
            },
        },
    });
}

function registerBlocks(editor, blocks = []) {
    for (const block of blocks) {
        const content = typeof block.content === 'string'
            ? sanitizeBlockHtml(block.content)
            : block.content;

        const blockAttributes = {
            ...(block.attributes ?? {}),
            title: block.attributes?.title ?? block.label,
        };

        editor.BlockManager.add(block.id, {
            label: block.label,
            category: block.category,
            content,
            media: block.preview ?? block.media ?? `<div class="voodbuilder-gjs-block-fallback">${block.label}</div>`,
            attributes: blockAttributes,
        });
    }
}

function prioritizeBlockCategories(editor) {
    const categories = editor.BlockManager.getCategories?.();

    if (! categories?.each) {
        return;
    }

    categories.each((category) => {
        const categoryId = String(category.get('id') ?? '');

        if (isComponentCategoryId(categoryId)) {
            return;
        }

        const id = normalizeCategoryLabel(String(categoryId || category.get('label') || ''));

        category.set('label', id);
        category.set('order', resolveCategoryOrder(id));
    });
}

function pruneEmptySections(editor) {
    editor.getWrapper().find('section').forEach((section) => {
        if (section === editor.getWrapper()) {
            return;
        }

        const hasMeaningfulChild = section.find('img, h1, h2, h3, h4, h5, h6, p, a, button, ul, ol, table, form, svg').length > 0;

        if (! hasMeaningfulChild && section.components().length === 0) {
            section.remove();
        }
    });
}

function ensureLayoutSectionTraits(editor) {
    editor.getWrapper().find('section').forEach((section) => {
        const container = sectionPaddingTarget(section);

        if (! container) {
            return;
        }

        const type = section.get('type');

        if (type === 'default' || type === 'voodbuilder-tailblocks-section') {
            section.set('type', 'voodbuilder-section');
        }

        if (section.get('_vpressTraitsBound')) {
            return;
        }

        section.set('_vpressTraitsBound', true);

        if (! section.get('vpressSectionPy')) {
            section.set('vpressSectionPy', readSectionPadding(container), { silent: true });
        }

        section.on('change:vpressSectionPy', () => {
            applySectionPadding(section, section.get('vpressSectionPy'));
        });
    });

    editor.getWrapper().find('div.container').forEach((container) => {
        const parentSection = container.parent();

        if (! parentSection || parentSection.get('tagName') !== 'section') {
            return;
        }

        if (container.get('_vpressTraitsBound')) {
            return;
        }

        container.set('_vpressTraitsBound', true);

        if (! container.get('vpressSectionPy')) {
            container.set('vpressSectionPy', readSectionPadding(container), { silent: true });
        }

        container.on('change:vpressSectionPy', () => {
            applySectionPaddingToElement(container, container.get('vpressSectionPy'));
        });
    });
}

export {
    registerBlocks,
    sanitizeBlockHtml,
    syncVpressDynamicAttributes,
    lockDynamicPreviewContent,
    registerDynamicBlockGuards,
    pruneEmptyDynamicBlocks,
    applySiteFooterColumns,
    refreshDynamicSlots,
    isSiteFooterBlock,
    applyFreshFooterAttributes,
    prioritizeBlockCategories,
    ensureLayoutSectionTraits,
    ensureLayoutSectionTraits as ensureTailblocksSectionTraits,
    pruneEmptySections,
};

export default function vpressGrapesJsPlugin(editor, options = {}) {
    registerBoundComponentType(editor);
    registerComponentInstanceType(editor, () => editor.__voodbuilderComponentsCatalog ?? []);

    registerDynamicBlockType(editor);
    registerLayoutSectionType(editor);
    registerSpacingStyleSync(editor);
    registerTailwindStyleSync(editor);
    registerDynamicBlockGuards(editor);
    registerBlocks(editor, options.blocks ?? []);
    prioritizeBlockCategories(editor);
}
