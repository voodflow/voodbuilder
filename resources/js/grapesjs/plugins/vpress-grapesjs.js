/**
 * Vpress GrapesJS plugin — uses the public GrapesJS plugin API only.
 * @see https://grapesjs.com/docs/modules/Plugins.html
 */

import { configureEditorChrome } from '../editor-chrome.js';
import { isClearedBackground, stripBackgroundClasses } from '../theme-tokens.js';

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

function openImageAssetManager(editor, component) {
    if (! component?.is?.('image')) {
        return;
    }

    const am = editor.AssetManager;

    am.open({
        select: (asset, complete) => {
            component.set({ src: asset.getSrc() });
            complete && am.close();
        },
        target: component,
        types: ['image'],
        accept: 'image/*',
    });
}

function imageTraits() {
    return [
        {
            type: 'text',
            label: 'Image URL',
            name: 'src',
            changeProp: 1,
            placeholder: 'https://…',
        },
        {
            type: 'button',
            label: 'Media library',
            text: 'Choose or upload…',
            full: true,
            command: (editor, trait) => {
                const component = trait?.target ?? editor.getSelected();

                openImageAssetManager(editor, component);
            },
        },
        {
            type: 'text',
            label: 'Alt text',
            name: 'alt',
        },
    ];
}

function isHeroBackgroundImage(component) {
    const classes = component.getClasses?.() ?? [];

    return classes.includes('absolute') && classes.includes('inset-0');
}

function registerImageComponentEnhancements(editor) {
    editor.DomComponents.addType('image', {
        extend: 'image',
        model: {
            defaults: {
                editable: true,
                traits: imageTraits(),
            },
            init() {
                if (isHeroBackgroundImage(this)) {
                    this.set('name', 'Hero background');
                }
            },
        },
    });

    editor.on('component:selected', (component) => {
        if (component?.is?.('image')) {
            editor.runCommand('open-tm');
        }
    });

    editor.on('load', () => {
        const wrapper = editor.getWrapper?.();

        if (! wrapper) {
            return;
        }

        wrapper.find('img').forEach((component) => {
            if (component.get('type') !== 'image') {
                component.set('type', 'image');
            }

            component.set({
                editable: true,
                traits: imageTraits(),
            });

            if (isHeroBackgroundImage(component)) {
                component.set('name', 'Hero background');
            }
        });
    });
}

function registerDynamicBlockType(editor) {
    const parseVpressConfig = (component) => {
        const raw = component.getAttributes()['data-vpress-config'];

        if (! raw) {
            return {};
        }

        try {
            return JSON.parse(raw);
        } catch {
            return {};
        }
    };

    const writeVpressConfig = (component, config) => {
        component.addAttributes({
            'data-vpress-config': JSON.stringify(config),
        });
    };

    const landingFooterTraits = () => {
        const traits = [
            {
                type: 'select',
                label: 'Layout',
                name: 'vpressVariant',
                options: [
                    { id: 'a', name: 'Footer A' },
                    { id: 'b', name: 'Footer B' },
                    { id: 'c', name: 'Footer C' },
                    { id: 'd', name: 'Footer D' },
                    { id: 'e', name: 'Footer E' },
                ],
            },
        ];

        for (let index = 1; index <= 4; index += 1) {
            traits.push({
                type: 'text',
                label: `Column ${index} title`,
                name: `vpressColumn${index}Title`,
            });
        }

        return traits;
    };

    const landingNavbarTraits = () => [
        {
            type: 'select',
            label: 'Layout',
            name: 'vpressVariant',
            options: [
                { id: 'a', name: 'Header A' },
                { id: 'b', name: 'Header B' },
                { id: 'c', name: 'Header C' },
                { id: 'd', name: 'Header D' },
            ],
        },
        {
            type: 'text',
            label: 'Brand name',
            name: 'vpressBrandName',
        },
        {
            type: 'text',
            label: 'CTA label',
            name: 'vpressCtaLabel',
        },
        {
            type: 'text',
            label: 'CTA URL',
            name: 'vpressCtaUrl',
        },
    ];

    const syncTraitsFromConfig = (component) => {
        const blockId = component.getAttributes()['data-vpress-block'];
        const config = parseVpressConfig(component);

        if (blockId === 'landing_footer') {
            component.set('vpressVariant', config.variant ?? 'a', { silent: true });

            for (let index = 1; index <= 4; index += 1) {
                component.set(`vpressColumn${index}Title`, config[`column_${index}_title`] ?? '', { silent: true });
            }

            return;
        }

        if (blockId === 'landing_navbar') {
            component.set('vpressVariant', config.variant ?? 'a', { silent: true });
            component.set('vpressBrandName', config.brand_name ?? '', { silent: true });
            component.set('vpressCtaLabel', config.cta_label ?? '', { silent: true });
            component.set('vpressCtaUrl', config.cta_url ?? '', { silent: true });
        }
    };

    const syncConfigFromTraits = (component) => {
        const blockId = component.getAttributes()['data-vpress-block'];
        const config = parseVpressConfig(component);

        if (blockId === 'landing_footer') {
            config.variant = component.get('vpressVariant') ?? config.variant ?? 'a';

            for (let index = 1; index <= 4; index += 1) {
                const title = component.get(`vpressColumn${index}Title`);

                if (title) {
                    config[`column_${index}_title`] = title;
                } else {
                    delete config[`column_${index}_title`];
                }
            }

            writeVpressConfig(component, config);

            return;
        }

        if (blockId === 'landing_navbar') {
            config.variant = component.get('vpressVariant') ?? config.variant ?? 'a';

            const brandName = component.get('vpressBrandName');
            const ctaLabel = component.get('vpressCtaLabel');
            const ctaUrl = component.get('vpressCtaUrl');

            if (brandName) {
                config.brand_name = brandName;
            }

            if (ctaLabel) {
                config.cta_label = ctaLabel;
            }

            if (ctaUrl) {
                config.cta_url = ctaUrl;
            }

            writeVpressConfig(component, config);
        }
    };

    const applyDynamicTraits = (component) => {
        const blockId = component.getAttributes()['data-vpress-block'];

        if (blockId === 'landing_footer') {
            component.set('traits', landingFooterTraits());
            syncTraitsFromConfig(component);

            return;
        }

        if (blockId === 'landing_navbar') {
            component.set('traits', landingNavbarTraits());
            syncTraitsFromConfig(component);
        }
    };

    editor.DomComponents.addType('vpress-dynamic', {
        isComponent: (element) => {
            if (element?.getAttribute?.('data-vpress-block')) {
                return { type: 'vpress-dynamic' };
            }

            return false;
        },
        model: {
            defaults: {
                tagName: 'div',
                name: 'Vpress block',
                draggable: true,
                droppable: false,
                editable: false,
                copyable: true,
                stylable: false,
                layerable: true,
                highlightable: true,
                attributes: {
                    class: 'vpress-gjs-dynamic',
                },
                traits: [
                    {
                        type: 'text',
                        label: 'Block ID',
                        name: 'data-vpress-block',
                    },
                    {
                        type: 'text',
                        label: 'Config (JSON)',
                        name: 'data-vpress-config',
                    },
                ],
            },
            init() {
                applyDynamicTraits(this);

                const traitNames = [
                    'vpressVariant',
                    'vpressBrandName',
                    'vpressCtaLabel',
                    'vpressCtaUrl',
                    'vpressColumn1Title',
                    'vpressColumn2Title',
                    'vpressColumn3Title',
                    'vpressColumn4Title',
                ];

                traitNames.forEach((traitName) => {
                    this.on(`change:${traitName}`, () => syncConfigFromTraits(this));
                });
            },
        },
    });

    editor.on('component:selected', (component) => {
        if (component?.getAttributes?.()['data-vpress-block']) {
            applyDynamicTraits(component);
        }
    });
}

function registerBackgroundClearSupport(editor) {
    editor.on('component:styleUpdate', (component, property) => {
        if (property !== 'background-color' && property !== 'background') {
            return;
        }

        const style = component.getStyle?.() ?? {};
        const background = style['background-color'] ?? style.background;

        if (isClearedBackground(background)) {
            stripBackgroundClasses(component);
        }
    });
}

function registerBlocks(editor, blocks = []) {
    for (const block of blocks) {
        const content = typeof block.content === 'string'
            ? sanitizeBlockHtml(block.content)
            : block.content;

        editor.BlockManager.add(block.id, {
            label: block.label,
            category: block.category,
            content,
            media: block.preview ?? block.media ?? `<div class="vpress-gjs-block-fallback">${block.label}</div>`,
            attributes: block.attributes ?? {},
        });
    }
}

export { registerBlocks, sanitizeBlockHtml };

export default function vpressGrapesJsPlugin(editor, options = {}) {
    registerDynamicBlockType(editor);
    registerTailblocksSectionType(editor);
    registerImageComponentEnhancements(editor);
    registerSpacingStyleSync(editor);
    registerBackgroundClearSupport(editor);
    configureEditorChrome(editor);
    registerBlocks(editor, options.blocks ?? []);
}
