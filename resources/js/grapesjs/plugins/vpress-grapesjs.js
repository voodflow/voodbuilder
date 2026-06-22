/**
 * Vpress GrapesJS plugin — uses the public GrapesJS plugin API only.
 * @see https://grapesjs.com/docs/modules/Plugins.html
 */

import { configureEditorChrome } from '../editor-chrome.js';
import { encodeVpressConfig, parseVpressConfig } from '../vpress-dynamic-config.js';

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
    });
}

function fixGrapesJsSrcUri(value) {
    return value.replace(/%(?![0-9A-Fa-f]{2})/g, '%25');
}

function sanitizeBlockHtml(html) {
    if (typeof html !== 'string' || html === '') {
        return html;
    }

    return html.replace(/\bsrc=(["'])(.*?)\1/gi, (match, quote, src) => `src=${quote}${fixGrapesJsSrcUri(src)}${quote}`);
}

function sectionPaddingTarget(section) {
    return section.find('.container')[0] ?? section.components().at(0);
}

function readSectionPadding(container) {
    const classes = container.getClasses?.() ?? [];

    return SECTION_PADDING_CLASSES.find((className) => classes.includes(className)) ?? 'py-24';
}

function registerTailblocksSectionType(editor) {
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

    editor.DomComponents.addType('vpress-tailblocks-section', {
        isComponent: (element) => {
            if (element?.tagName !== 'SECTION') {
                return false;
            }

            return element.classList.contains('body-font');
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
    });

    editor.DomComponents.addType('vpress-tailblocks-container', {
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
    const blockId = attributes['data-vpress-block'] ?? '';
    const config = parseVpressConfig(attributes['data-vpress-config']);

    component.set('vpressConfig', config, { silent: true });
    component.set('name', blockId ? `Vpress: ${blockId}` : 'Vpress block');
    component.setAttributes({
        'data-vpress-block': blockId,
        'data-vpress-config': encodeVpressConfig(config),
        class: attributes.class ?? 'vpress-gjs-dynamic',
    });
}

function lockComponentTree(component) {
    component.set({
        removable: false,
        draggable: false,
        copyable: false,
        selectable: false,
        hoverable: false,
        layerable: false,
    });

    component.components().forEach((child) => {
        lockComponentTree(child);
    });
}

function lockDynamicPreviewContent(component) {
    const blockId = component.getAttributes()['data-vpress-block'];

    if (isSiteFooterBlock(blockId)) {
        component.find('[data-vpress-menu], [data-vpress-brand]').forEach((slot) => {
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

    component.find('[data-vpress-footer-col]').forEach((column) => {
        const index = Number(column.getAttributes()['data-vpress-footer-col'] ?? 0);
        const classes = column
            .getClasses()
            .filter((className) => className !== 'hidden');

        if (index > count) {
            classes.push('hidden');
        }

        column.setClass(classes);
    });

    component.addAttributes({ 'data-vpress-footer-columns': String(count) });

    const config = {
        ...(component.get('vpressConfig') ?? {}),
        columns: count,
    };

    component.set('vpressConfig', config, { silent: true });
    component.set('vpressFooterColumns', String(count), { silent: true });
    component.addAttributes({
        'data-vpress-config': encodeVpressConfig(config),
    });
}

function configureSiteFooterTraits(component) {
    const blockId = component.getAttributes()['data-vpress-block'];

    if (blockId === 'site_footer_d' || component.find('[data-vpress-footer-col]').length === 0) {
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
        if (parent.get('type') === 'vpress-dynamic') {
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

        if (attributes['data-vpress-menu'] || attributes['data-vpress-brand']) {
            return true;
        }

        if (current.get('type') === 'vpress-dynamic') {
            return false;
        }

        current = current.parent();
    }

    return false;
}

function registerDynamicBlockGuards(editor) {
    editor.on('component:remove', (removed) => {
        if (removed.get('type') === 'vpress-dynamic') {
            return;
        }

        const dynamic = findVpressDynamicAncestor(removed);

        if (! dynamic?.parent()) {
            return;
        }

        if (isSiteFooterBlock(dynamic.getAttributes()['data-vpress-block']) && ! isInsideProtectedSlot(removed)) {
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
    editor.getWrapper().find('[data-vpress-block]').forEach((component) => {
        const blockId = component.getAttributes()['data-vpress-block'];

        if (isSiteFooterBlock(blockId)) {
            return;
        }

        if (component.components().length === 0) {
            component.remove();
        }
    });
}

function refreshDynamicSlots(component, freshRoot) {
    for (const selector of ['[data-vpress-menu]', '[data-vpress-brand]']) {
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
        'data-vpress-block': fresh.getAttribute('data-vpress-block') ?? blockId,
        'data-vpress-config': fresh.getAttribute('data-vpress-config') ?? encodeVpressConfig(freshConfig),
        class: fresh.getAttribute('class') ?? 'vpress-gjs-dynamic vpress-gjs-footer w-full',
        'data-vpress-hydrate-slots': '1',
    });
}

function registerDynamicBlockType(editor) {
    editor.DomComponents.addType('vpress-dynamic', {
        isComponent: (element) => {
            const blockId = element?.getAttribute?.('data-vpress-block');

            if (! blockId) {
                return false;
            }

            const config = parseVpressConfig(element.getAttribute('data-vpress-config') ?? '{}');
            const isFooter = element?.tagName === 'FOOTER' && isSiteFooterBlock(blockId);
            const defaultClass = isFooter
                ? 'vpress-gjs-dynamic vpress-gjs-footer w-full border-t border-vp-divider bg-vp-bg'
                : (element.getAttribute('class') ?? 'vpress-gjs-dynamic');

            return {
                type: 'vpress-dynamic',
                tagName: isFooter ? 'footer' : (element.tagName?.toLowerCase() ?? 'div'),
                vpressConfig: config,
                attributes: {
                    'data-vpress-block': blockId,
                    'data-vpress-config': encodeVpressConfig(config),
                    class: defaultClass,
                    ...(element.hasAttribute('data-vpress-hydrate-slots')
                        ? { 'data-vpress-hydrate-slots': '1' }
                        : {}),
                },
            };
        },
        model: {
            defaults: {
                tagName: 'div',
                name: 'Vpress block',
                draggable: true,
                droppable: false,
                editable: false,
                copyable: true,
                removable: true,
                stylable: false,
                layerable: true,
                highlightable: true,
                vpressConfig: {},
                attributes: {
                    class: 'vpress-gjs-dynamic',
                    'data-vpress-block': '',
                    'data-vpress-config': encodeVpressConfig({}),
                },
                traits: [],
            },
            init() {
                syncVpressDynamicAttributes(this);

                if (isSiteFooterBlock(this.getAttributes()['data-vpress-block'])) {
                    configureSiteFooterTraits(this);
                }

                this.on('change:attributes:data-vpress-config', () => {
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
            media: block.preview ?? block.media ?? `<div class="vpress-gjs-block-fallback">${block.label}</div>`,
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
        const id = String(category.get('id') ?? category.get('label') ?? '');

        if (id === 'Vpress' || id === 'Dynamic') {
            category.set('order', -100);
            category.set('open', true);
        } else if (id.startsWith('Tailblocks')) {
            category.set('order', 100);
        }
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
};

export default function vpressGrapesJsPlugin(editor, options = {}) {
    registerDynamicBlockType(editor);
    registerTailblocksSectionType(editor);
    registerSpacingStyleSync(editor);
    registerDynamicBlockGuards(editor);
    configureEditorChrome(editor);
    registerBlocks(editor, options.blocks ?? []);
    prioritizeBlockCategories(editor);
}
