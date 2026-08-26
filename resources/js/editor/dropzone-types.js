/**
 * Editor types for data-voodbuilder-dropzone="content|copy|actions"
 * and common layout containers (flex/grid) so nesting is possible.
 */

import { safeFindComponents } from './tailwind-visual-style.js';
import { isInnerDropLayoutContainer } from './inner-drop-slots.js';

function componentTag(component) {
    return String(component?.get?.('tagName') ?? '').toLowerCase();
}

function componentType(component) {
    return String(component?.get?.('type') ?? '');
}

function componentAttrs(component) {
    return component?.getAttributes?.() ?? {};
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {boolean}
 */
export function isCtaLikeComponent(component) {
    if (! component) {
        return false;
    }

    if (componentType(component) === 'voodbuilder-cta-button') {
        return true;
    }

    const attrs = componentAttrs(component);

    if (attrs['data-voodbuilder-cta'] === 'true' || attrs['data-voodbuilder-cta-label'] != null) {
        return true;
    }

    try {
        if (safeFindComponents(component, '[data-voodbuilder-cta="true"], [data-voodbuilder-cta-label]').length > 0) {
            return true;
        }
    } catch {
        // ignore
    }

    return false;
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {boolean}
 */
function isSectionLikeComponent(component) {
    if (! component) {
        return false;
    }

    if (componentTag(component) === 'section') {
        return true;
    }

    return Boolean(componentAttrs(component)['data-voodbuilder-section-block']);
}

/**
 * @param {string} zone
 * @returns {string}
 */
function dropzoneLayerName(zone) {
    switch (zone) {
        case 'actions':
            return 'Actions';
        case 'copy':
            return 'Copy';
        case 'content':
            return 'Content';
        default:
            return 'Dropzone';
    }
}

/**
 * @param {string} zone
 * @param {import('grapesjs').Component} srcComponent
 * @returns {boolean}
 */
function dropzoneAccepts(zone, srcComponent) {
    if (! srcComponent?.get) {
        return false;
    }

    if (isSectionLikeComponent(srcComponent)) {
        return false;
    }

    if (zone === 'actions') {
        return true;
    }

    if (zone === 'copy' || zone === 'content') {
        return ! isCtaLikeComponent(srcComponent);
    }

    return true;
}

/**
 * Sections that already expose an Actions dropzone must not accept CTAs themselves
 * (otherwise Buttons land as siblings of the whole hero column).
 *
 * @param {import('grapesjs').Editor} editor
 */
function registerSectionCtaDropGuard(editor) {
    if (editor.__voodbuilderSectionCtaDropGuardRegistered) {
        return;
    }

    editor.__voodbuilderSectionCtaDropGuardRegistered = true;

    editor.DomComponents.addType('voodbuilder-section-dropzones', {
        isComponent: (element) => {
            if (element?.tagName !== 'SECTION') {
                return false;
            }

            // Companion smart-wrap roots must remain voodbuilder-dynamic.
            if (element.getAttribute?.('data-voodbuilder-block')) {
                return false;
            }

            if (! element.querySelector?.('[data-voodbuilder-dropzone="actions"]')) {
                return false;
            }

            return { type: 'voodbuilder-section-dropzones' };
        },
        extend: 'default',
        model: {
            defaults: {
                tagName: 'section',
                droppable: true,
                name: 'Section',
            },
            init() {
                this.set('droppable', (srcComponent) => {
                    if (isSectionLikeComponent(srcComponent)) {
                        return false;
                    }

                    if (isCtaLikeComponent(srcComponent)) {
                        return false;
                    }

                    return true;
                });
            },
        },
    });
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function registerDropzoneTypes(editor) {
    if (editor.__voodbuilderDropzoneTypesRegistered) {
        return;
    }

    editor.__voodbuilderDropzoneTypesRegistered = true;

    registerSectionCtaDropGuard(editor);

    editor.DomComponents.addType('voodbuilder-dropzone', {
        isComponent: (element) => {
            if (element?.tagName !== 'DIV') {
                return false;
            }

            const zone = element.getAttribute?.('data-voodbuilder-dropzone');

            if (! zone) {
                return false;
            }

            return { type: 'voodbuilder-dropzone' };
        },
        model: {
            defaults: {
                tagName: 'div',
                droppable: true,
                highlightable: true,
                selectable: true,
                hoverable: true,
                name: 'Dropzone',
            },
            init() {
                const zone = String(this.getAttributes?.()?.['data-voodbuilder-dropzone'] ?? 'content');

                this.set('name', dropzoneLayerName(zone));
                this.set('droppable', (srcComponent) => dropzoneAccepts(zone, srcComponent));

                this.on('component:add', (child) => {
                    if (! child?.set) {
                        return;
                    }

                    if (isCtaLikeComponent(child) || componentTag(child) === 'a') {
                        child.set('droppable', false);
                    }
                });

                this.components?.()?.forEach?.((child) => {
                    if (isCtaLikeComponent(child) || componentTag(child) === 'a') {
                        child.set('droppable', false);
                    }
                });
            },
        },
    });

    // Layout wrappers (flex/grid/gap) without an explicit dropzone attribute.
    editor.DomComponents.addType('voodbuilder-layout-container', {
        isComponent: (element) => {
            if (element?.tagName !== 'DIV') {
                return false;
            }

            if (element.getAttribute?.('data-voodbuilder-dropzone')) {
                return false;
            }

            if (element.getAttribute?.('data-voodbuilder-inner-drop')) {
                return false;
            }

            // List repeat hosts are a dedicated type (Must win over generic Layout).
            if (element.getAttribute?.('data-voodbuilder-repeat')) {
                return false;
            }

            const className = String(element.getAttribute?.('class') ?? '');
            const looksLikeLayout = /\b(flex|inline-flex|grid|gap-|space-[xy]-|voodbuilder-editor-container)\b/.test(className)
                || element.getAttribute?.('data-voodbuilder-role') === 'content';

            if (! looksLikeLayout) {
                return false;
            }

            return { type: 'voodbuilder-layout-container' };
        },
        extend: 'default',
        model: {
            defaults: {
                tagName: 'div',
                droppable: true,
                highlightable: true,
                selectable: true,
                hoverable: true,
                name: 'Layout',
            },
            init() {
                if (! isInnerDropLayoutContainer(this)) {
                    return;
                }

                this.set('droppable', (srcComponent) => {
                    if (isSectionLikeComponent(srcComponent)) {
                        return false;
                    }

                    return true;
                });
            },
        },
    });
}
