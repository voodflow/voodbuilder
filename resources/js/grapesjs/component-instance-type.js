/**
 * GrapesJS component type for saved library instances (data-voodbuilder-component).
 * Kept in a standalone module so plugins can register it before page content loads.
 */

export const COMPONENT_ATTR = 'data-voodbuilder-component';
export const PROPS_ATTR = 'data-voodbuilder-component-props';
export const COMPONENT_TYPE = 'voodbuilder-component-instance';
export const COMPONENT_HYDRATED_KEY = '__vbComponentHydrated';

export function registerComponentInstanceType(editor, getCatalog = () => []) {
    if (editor.__voodbuilderComponentTypeRegistered) {
        return;
    }

    editor.__voodbuilderComponentTypeRegistered = true;

    const defaultType = editor.DomComponents.getType('default');
    const defaultDefaults = defaultType?.model?.prototype?.defaults ?? {};

    editor.DomComponents.addType(COMPONENT_TYPE, {
        extend: 'default',
        isComponent: (element) => element?.hasAttribute?.(COMPONENT_ATTR) === true,
        model: {
            defaults: {
                ...defaultDefaults,
                type: COMPONENT_TYPE,
                name: 'Component',
                tagName: 'div',
                draggable: true,
                droppable: true,
                removable: true,
                copyable: true,
                layerable: true,
                selectable: true,
                highlightable: true,
                editable: false,
                attributes: {
                    class: 'voodbuilder-gjs-component-instance',
                },
            },
            init() {
                const hydrate = () => {
                    editor.__voodbuilderHydrateComponentInstance?.(this, getCatalog());
                };

                this.on(`change:attributes:${COMPONENT_ATTR}`, () => {
                    this.set(COMPONENT_HYDRATED_KEY, false, { silent: true });
                    hydrate();
                });
                this.on(`change:attributes:${PROPS_ATTR}`, hydrate);
            },
        },
    });
}
