/**
 * Content-panel settings for static images (inline + hero / background media).
 * Skips dynamically bound images — those stay driven by bindings.
 */

import {
    createFormSection,
    createImageUrlField,
    createSelectField,
    createTextField,
    createTextareaField,
} from './editor-form-ui.js';
import { CMD_EDIT_IMAGE, isDynamicallyBoundImage, isRasterEditableSrc } from './jodit-image-editor.js';
import { safeFindComponents } from './tailwind-visual-style.js';

const CAPTION_DISPLAY_NONE = 'none';
const CAPTION_DISPLAY_BELOW = 'below';
const CAPTION_DISPLAY_OVERLAY = 'overlay';

function runWithSettingsChangeGuard(editor, callback) {
    if (! editor || typeof callback !== 'function') {
        return;
    }

    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;

    try {
        callback();
    } finally {
        const nextDepth = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;
        editor.__voodbuilderSettingsChangeDepth = nextDepth;

        if (nextDepth <= 0) {
            editor.__voodbuilderSettingsChange = false;
            delete editor.__voodbuilderSettingsChangeDepth;
        }
    }
}

function componentKey(component) {
    return String(component?.cid ?? component?.getId?.() ?? component?.get?.('id') ?? '');
}

function componentTag(component) {
    return String(component?.get?.('tagName') ?? '').toLowerCase();
}

function componentClasses(component) {
    const classes = component?.getClasses?.() ?? component?.get?.('classes') ?? [];

    if (Array.isArray(classes)) {
        return classes.map((item) => (typeof item === 'string' ? item : String(item?.id ?? item?.get?.('name') ?? '')));
    }

    return [];
}

/**
 * Flex/grid slot utilities that must live on the figure wrapper (not the img),
 * otherwise wrapping for captions forces w-full and the image drops to the next row.
 *
 * @param {string} className
 * @returns {boolean}
 */
function isLayoutSlotUtilityClass(className) {
    const name = String(className ?? '').trim();

    if (name === '' || name === 'vb-image' || name.startsWith('vb-image--')) {
        return false;
    }

    return /^(?:!?(?:sm|md|lg|xl|2xl):)?(?:w-|min-w-|max-w-|flex-|grow(?:-\d+)?$|shrink(?:-0)?$|basis-|order-|self-|m[trblxyse]?-|!-?m[trblxyse]?-)/.test(name)
        || name === 'grow'
        || name === 'shrink'
        || name === 'shrink-0';
}

/**
 * @param {import('grapesjs').Component} from
 * @param {import('grapesjs').Component} to
 */
function transferLayoutSlotClasses(from, to) {
    for (const className of componentClasses(from).filter(isLayoutSlotUtilityClass)) {
        from.removeClass(className);
        to.addClass(className);
    }
}

/**
 * @param {import('grapesjs').Component} figure
 * @returns {boolean}
 */
function figureHasWidthUtility(figure) {
    return componentClasses(figure).some((className) => /^(?:!?(?:sm|md|lg|xl|2xl):)?w-/.test(className));
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {boolean}
 */
export function isImageComponent(component) {
    if (! component?.get) {
        return false;
    }

    if (component.get('type') === 'image') {
        return true;
    }

    return componentTag(component) === 'img';
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {boolean}
 */
function isVbImageFigure(component) {
    return componentTag(component) === 'figure' && componentClasses(component).includes('vb-image');
}

/**
 * @param {import('grapesjs').Component} component
 * @returns {import('grapesjs').Component | null}
 */
function findAncestorSection(component) {
    let current = component;

    while (current) {
        if (componentTag(current) === 'section') {
            return current;
        }

        current = current.parent?.() ?? null;
    }

    return null;
}

/**
 * @param {import('grapesjs').Component} section
 * @returns {import('grapesjs').Component | undefined}
 */
function findHeroMediaImage(section) {
    return safeFindComponents(
        section,
        '[data-voodbuilder-role="media"] img, .voodbuilder-hero-media__img',
    )[0];
}

/**
 * @param {import('grapesjs').Component} figure
 * @returns {import('grapesjs').Component | undefined}
 */
function findFigureImage(figure) {
    return safeFindComponents(figure, 'img')[0]
        ?? figure.findType?.('image')?.[0]
        ?? figure.components?.()?.find?.((child) => isImageComponent(child));
}

/**
 * @param {import('grapesjs').Component} figure
 * @returns {import('grapesjs').Component | undefined}
 */
function findFigureCaption(figure) {
    return safeFindComponents(figure, 'figcaption')[0]
        ?? figure.components?.()?.find?.((child) => componentTag(child) === 'figcaption');
}

/**
 * @param {import('grapesjs').Component} component
 * @returns {boolean}
 */
function isHeroMediaImage(component) {
    if (! isImageComponent(component)) {
        return false;
    }

    const classes = componentClasses(component);

    if (classes.includes('voodbuilder-hero-media__img')) {
        return true;
    }

    const parent = component.parent?.();
    const parentAttrs = parent?.getAttributes?.() ?? {};
    const parentClasses = parent ? componentClasses(parent) : [];

    return parentAttrs['data-voodbuilder-role'] === 'media'
        || parentClasses.includes('voodbuilder-hero-media');
}

/**
 * @param {import('grapesjs').Component} component
 * @param {import('grapesjs').Component} section
 * @returns {boolean}
 */
function shouldOfferHeroBackgroundSettings(component, section) {
    if (component === section) {
        return true;
    }

    if (String(component.get?.('type') ?? '') === 'vb-bg-image') {
        return true;
    }

    const attrs = component.getAttributes?.() ?? {};
    const role = String(attrs['data-voodbuilder-role'] ?? '');
    const classes = componentClasses(component);

    if (role === 'media' || role === 'shade' || role === 'content') {
        return true;
    }

    if (classes.includes('voodbuilder-hero-media') || classes.includes('voodbuilder-hero-media__shade')) {
        return true;
    }

    return false;
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {{ mode: 'image'|'hero', image: object, section: object|null, source: object } | null}
 */
export function resolveImageSettingsContext(component) {
    if (! component?.get) {
        return null;
    }

    if (isVbImageFigure(component) || (componentTag(component) === 'figure' && findFigureImage(component))) {
        const image = findFigureImage(component);

        if (! image || isDynamicallyBoundImage(image) || isHeroMediaImage(image)) {
            return null;
        }

        return {
            mode: 'image',
            image,
            section: null,
            source: component,
        };
    }

    if (componentTag(component) === 'figcaption') {
        const figure = component.parent?.();

        if (figure && (isVbImageFigure(figure) || findFigureImage(figure))) {
            return resolveImageSettingsContext(figure);
        }
    }

    if (isImageComponent(component)) {
        if (isDynamicallyBoundImage(component)) {
            return null;
        }

        const section = findAncestorSection(component);

        return {
            mode: isHeroMediaImage(component) ? 'hero' : 'image',
            image: component,
            section,
            source: component,
        };
    }

    const section = componentTag(component) === 'section'
        ? component
        : findAncestorSection(component);

    if (! section) {
        return null;
    }

    const image = findHeroMediaImage(section);

    if (! image || isDynamicallyBoundImage(image)) {
        return null;
    }

    if (! shouldOfferHeroBackgroundSettings(component, section)) {
        return null;
    }

    return {
        mode: 'hero',
        image,
        section,
        source: component,
    };
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {boolean}
 */
export function isImageSettingsComponent(component) {
    return resolveImageSettingsContext(component) != null;
}

/**
 * @param {import('grapesjs').Component} image
 * @returns {string}
 */
function readImageSrc(image) {
    return String(image.get?.('src') ?? image.getAttributes?.()?.src ?? '').trim();
}

/**
 * @param {import('grapesjs').Component} image
 * @returns {{ caption: string, display: string }}
 */
function readCaptionState(image) {
    const attrs = image.getAttributes?.() ?? {};
    const parent = image.parent?.();
    const parentAttrs = parent?.getAttributes?.() ?? {};
    const caption = String(attrs['data-vb-caption'] ?? '').trim();
    let display = String(
        attrs['data-vb-caption-display']
        ?? parentAttrs['data-vb-caption-display']
        ?? CAPTION_DISPLAY_NONE,
    ).trim();

    if (! [CAPTION_DISPLAY_NONE, CAPTION_DISPLAY_BELOW, CAPTION_DISPLAY_OVERLAY].includes(display)) {
        display = CAPTION_DISPLAY_NONE;
    }

    if (display === CAPTION_DISPLAY_NONE && parent && findFigureCaption(parent) && caption !== '') {
        const classes = componentClasses(parent);

        if (classes.includes('vb-image--caption-overlay')) {
            display = CAPTION_DISPLAY_OVERLAY;
        } else if (classes.includes('vb-image--caption-below') || isVbImageFigure(parent)) {
            display = CAPTION_DISPLAY_BELOW;
        }
    }

    const figcaption = parent ? findFigureCaption(parent) : null;
    const fromFigcaption = figcaption
        ? String(figcaption.get?.('content') ?? figcaption.view?.el?.textContent ?? '').trim()
        : '';

    return {
        caption: caption || fromFigcaption,
        display,
    };
}

/**
 * @param {import('grapesjs').Component} image
 * @returns {import('grapesjs').Component | null}
 */
function ensureVbImageFigure(image) {
    const parent = image.parent?.();

    if (parent && isVbImageFigure(parent)) {
        transferLayoutSlotClasses(image, parent);

        return parent;
    }

    if (parent && componentTag(parent) === 'figure') {
        parent.addClass('vb-image');
        transferLayoutSlotClasses(image, parent);

        if (! figureHasWidthUtility(parent) && componentClasses(image).includes('w-full')) {
            parent.addClass('w-full');
            image.removeClass('w-full');
        }

        return parent;
    }

    if (! parent?.append || typeof image.index !== 'function') {
        return null;
    }

    const index = image.index();
    const layoutClasses = componentClasses(image).filter(isLayoutSlotUtilityClass);
    const figureClasses = ['vb-image', ...layoutClasses];

    if (! layoutClasses.some((className) => /^(?:!?(?:sm|md|lg|xl|2xl):)?w-/.test(className))) {
        figureClasses.push('w-full');
    }

    const appended = parent.append({
        tagName: 'figure',
        classes: figureClasses,
        attributes: {
            'data-vb-image': '',
        },
    }, { at: index });

    const figure = Array.isArray(appended) ? appended[0] : appended;

    if (! figure) {
        return null;
    }

    image.move(figure, { at: 0 });
    transferLayoutSlotClasses(image, figure);

    return figure;
}

/**
 * @param {import('grapesjs').Component} captionEl
 * @param {string} text
 */
function setFigcaptionText(captionEl, text) {
    if (typeof captionEl.components === 'function') {
        captionEl.components(text);
    }

    captionEl.set?.('content', text);
}

/**
 * @param {import('grapesjs').Component} image
 * @param {{ caption?: string, display?: string }} values
 */
function syncImageCaption(image, values) {
    const caption = String(values.caption ?? '').trim();
    const display = [CAPTION_DISPLAY_BELOW, CAPTION_DISPLAY_OVERLAY].includes(String(values.display ?? ''))
        ? String(values.display)
        : CAPTION_DISPLAY_NONE;

    image.addAttributes({
        'data-vb-caption': caption || null,
        'data-vb-caption-display': display === CAPTION_DISPLAY_NONE ? null : display,
    });

    const parent = image.parent?.();

    if (display === CAPTION_DISPLAY_NONE || caption === '') {
        if (parent && (isVbImageFigure(parent) || componentTag(parent) === 'figure')) {
            const existing = findFigureCaption(parent);

            if (existing) {
                existing.remove();
            }

            parent.removeClass('vb-image--caption-below');
            parent.removeClass('vb-image--caption-overlay');
            parent.addAttributes({ 'data-vb-caption-display': null });

            const remaining = parent.components?.() ?? [];
            const onlyImage = remaining.length === 1 && remaining.at?.(0) === image;

            if (onlyImage && isVbImageFigure(parent) && parent.parent?.()) {
                const grandParent = parent.parent();
                const at = parent.index();
                transferLayoutSlotClasses(parent, image);
                image.move(grandParent, { at });
                parent.remove();
            }
        }

        return;
    }

    const figure = ensureVbImageFigure(image);

    if (! figure) {
        return;
    }

    figure.removeClass('vb-image--caption-below');
    figure.removeClass('vb-image--caption-overlay');
    figure.addClass(display === CAPTION_DISPLAY_OVERLAY ? 'vb-image--caption-overlay' : 'vb-image--caption-below');
    figure.addAttributes({ 'data-vb-caption-display': display });

    let captionEl = findFigureCaption(figure);

    if (! captionEl) {
        const appended = figure.append({
            tagName: 'figcaption',
            classes: ['vb-image__caption'],
            content: caption,
        });

        captionEl = Array.isArray(appended) ? appended[0] : appended;
    }

    if (captionEl) {
        captionEl.addClass?.('vb-image__caption');
        setFigcaptionText(captionEl, caption);
    }
}

/**
 * @param {import('grapesjs').Component} image
 * @param {import('grapesjs').Component | null} section
 * @param {string} url
 */
function applyImageSrc(image, section, url) {
    const next = String(url ?? '').trim();

    image.set('src', next);
    image.addAttributes({ src: next || null });

    if (section) {
        const attrs = section.getAttributes?.() ?? {};
        const type = String(section.get?.('type') ?? '');
        const blockId = String(attrs['data-voodbuilder-section-block'] ?? '');

        if (
            type === 'vb-bg-image'
            || blockId.includes('vb-bg-image')
            || Object.prototype.hasOwnProperty.call(attrs, 'data-vb-bg-src')
            || findHeroMediaImage(section) === image
        ) {
            section.addAttributes({ 'data-vb-bg-src': next || null });
        }
    }
}

/**
 * @param {import('grapesjs').Component} image
 * @param {import('grapesjs').Component | null} section
 * @param {{ opacity?: string, fit?: string, position?: string }} values
 */
function applyHeroPresentation(image, section, values) {
    const opacity = values.opacity != null ? String(values.opacity) : null;
    const fit = values.fit != null ? String(values.fit) : null;
    const position = values.position != null ? String(values.position) : null;

    const styles = {
        position: 'absolute',
        inset: '0',
        display: 'block',
        width: '100%',
        height: '100%',
        'max-width': 'none',
    };

    if (opacity != null) {
        styles.opacity = opacity;
    }

    if (fit != null) {
        styles['object-fit'] = fit;
        styles['--vb-object-fit'] = fit;
    }

    if (position != null) {
        const objectPosition = position === 'top'
            ? 'center top'
            : (position === 'bottom' ? 'center bottom' : 'center');
        styles['object-position'] = objectPosition;
        styles['--vb-object-position'] = objectPosition;
    }

    image.addStyle(styles);

    if (section) {
        const attrs = {};

        if (opacity != null) {
            attrs['data-vb-bg-opacity'] = opacity;
        }

        if (fit != null) {
            attrs['data-vb-bg-size'] = fit;
        }

        if (position != null) {
            attrs['data-vb-bg-position'] = position;
        }

        if (Object.keys(attrs).length > 0) {
            section.addAttributes(attrs);
        }
    }
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderImageContentSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    const context = resolveImageSettingsContext(component);

    if (! mount || ! context) {
        return false;
    }

    const { mode, image, section } = context;
    const key = `${mode}:${componentKey(image)}`;
    const existing = mount.querySelector('[data-voodbuilder-image-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const title = mode === 'hero'
        ? (labels.imageSettingsHeroTitle ?? 'Background image')
        : (labels.imageSettingsTitle ?? 'Image');

    const { section: form, fields } = createFormSection(title);
    form.setAttribute('data-voodbuilder-image-settings', '');
    form.setAttribute('data-component-key', key);

    let src = readImageSrc(image);
    let alt = String(image.getAttributes?.()?.alt ?? '');
    const captionState = readCaptionState(image);
    let caption = captionState.caption;
    let captionDisplay = captionState.display;
    const imageStyle = image.getStyle?.() ?? {};
    let opacity = String(
        section?.getAttributes?.()?.['data-vb-bg-opacity']
        ?? imageStyle.opacity
        ?? '0.55',
    );
    let fit = String(
        section?.getAttributes?.()?.['data-vb-bg-size']
        ?? imageStyle['object-fit']
        ?? 'cover',
    );
    let position = String(section?.getAttributes?.()?.['data-vb-bg-position'] ?? 'center');

    if (imageStyle['object-position']?.includes('top')) {
        position = 'top';
    } else if (imageStyle['object-position']?.includes('bottom')) {
        position = 'bottom';
    }

    fields.append(
        createImageUrlField({
            label: mode === 'hero'
                ? (labels.imageSettingsHeroSrc ?? 'Background image')
                : (labels.imageSettingsSrc ?? 'Image'),
            name: 'imageSrc',
            value: /^data:image\/svg\+xml/i.test(src) ? '' : src,
            editor,
            chooseLabel: labels.imageSettingsChoose ?? labels.logoChoose ?? 'Choose',
            clearLabel: labels.imageSettingsClear ?? labels.logoClear ?? 'Clear',
            onChange: (url) => {
                src = url;
                runWithSettingsChangeGuard(editor, () => {
                    applyImageSrc(image, section, url);
                });
            },
        }),
    );

    if (mode === 'image') {
        const { field: altField, input: altInput } = createTextField({
            label: labels.imageSettingsAlt ?? 'Alt text',
            name: 'imageAlt',
            value: alt,
            placeholder: labels.imageSettingsAltPlaceholder ?? 'Describe the image',
        });

        altInput.addEventListener('change', () => {
            alt = String(altInput.value ?? '').trim();
            runWithSettingsChangeGuard(editor, () => {
                image.addAttributes({ alt });
            });
        });

        fields.append(altField);

        const { field: captionField, input: captionInput } = createTextareaField({
            label: labels.imageSettingsCaption ?? 'Caption',
            name: 'imageCaption',
            value: caption,
            rows: 2,
            placeholder: labels.imageSettingsCaptionPlaceholder ?? 'Optional caption',
        });

        captionInput.addEventListener('change', () => {
            caption = String(captionInput.value ?? '').trim();
            runWithSettingsChangeGuard(editor, () => {
                syncImageCaption(image, { caption, display: captionDisplay });
            });
        });

        fields.append(captionField);

        fields.append(
            createSelectField({
                label: labels.imageSettingsCaptionDisplay ?? 'Caption display',
                name: 'imageCaptionDisplay',
                value: captionDisplay,
                options: [
                    {
                        value: CAPTION_DISPLAY_NONE,
                        label: labels.imageSettingsCaptionDisplayNone ?? 'None (manual)',
                    },
                    {
                        value: CAPTION_DISPLAY_BELOW,
                        label: labels.imageSettingsCaptionDisplayBelow ?? 'Below image',
                    },
                    {
                        value: CAPTION_DISPLAY_OVERLAY,
                        label: labels.imageSettingsCaptionDisplayOverlay ?? 'Overlay',
                    },
                ],
                onChange: (value) => {
                    captionDisplay = value;
                    runWithSettingsChangeGuard(editor, () => {
                        syncImageCaption(image, { caption, display: value });
                    });
                },
            }),
        );
    }

    if (mode === 'hero') {
        fields.append(
            createSelectField({
                label: labels.imageSettingsOpacity ?? 'Opacity',
                name: 'bgOpacity',
                value: opacity,
                options: [
                    { value: '0.35', label: '35%' },
                    { value: '0.45', label: '45%' },
                    { value: '0.55', label: '55%' },
                    { value: '0.65', label: '65%' },
                    { value: '0.75', label: '75%' },
                    { value: '0.9', label: '90%' },
                    { value: '1', label: '100%' },
                ],
                onChange: (value) => {
                    opacity = value;
                    runWithSettingsChangeGuard(editor, () => {
                        applyHeroPresentation(image, section, { opacity: value });
                    });
                },
            }),
        );

        fields.append(
            createSelectField({
                label: labels.imageSettingsFit ?? 'Image fit',
                name: 'bgFit',
                value: fit,
                options: [
                    { value: 'cover', label: labels.imageSettingsFitCover ?? 'Cover' },
                    { value: 'contain', label: labels.imageSettingsFitContain ?? 'Contain' },
                    { value: 'fill', label: labels.imageSettingsFitFill ?? 'Fill' },
                ],
                onChange: (value) => {
                    fit = value;
                    runWithSettingsChangeGuard(editor, () => {
                        applyHeroPresentation(image, section, { fit: value });
                    });
                },
            }),
        );

        fields.append(
            createSelectField({
                label: labels.imageSettingsPosition ?? 'Image position',
                name: 'bgPosition',
                value: position,
                options: [
                    { value: 'center', label: labels.imageSettingsPositionCenter ?? 'Center' },
                    { value: 'top', label: labels.imageSettingsPositionTop ?? 'Top' },
                    { value: 'bottom', label: labels.imageSettingsPositionBottom ?? 'Bottom' },
                ],
                onChange: (value) => {
                    position = value;
                    runWithSettingsChangeGuard(editor, () => {
                        applyHeroPresentation(image, section, { position: value });
                    });
                },
            }),
        );
    }

    if (editor.__voodbuilderImageEditorEnabled && isRasterEditableSrc(src)) {
        const editWrap = document.createElement('div');
        editWrap.className = 'voodbuilder-editor-form-field';
        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'voodbuilder-editor-topbar__btn voodbuilder-editor-topbar__btn--ghost';
        editBtn.textContent = labels.editImage ?? 'Edit image';
        editBtn.addEventListener('click', () => {
            editor.select?.(image);
            editor.runCommand?.(CMD_EDIT_IMAGE, { target: image });
        });
        editWrap.appendChild(editBtn);
        fields.append(editWrap);
    }

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-hint';
    hint.textContent = mode === 'hero'
        ? (labels.imageSettingsHeroHint ?? 'Choose a photo for the hero background. SVG placeholders cannot be cropped until you upload a real image.')
        : (labels.imageSettingsCaptionHint ?? labels.imageSettingsHint ?? 'Choose or upload an image. Use “None” to keep the caption in data attributes and place text manually.');
    fields.append(hint);

    mount.appendChild(form);

    return true;
}
