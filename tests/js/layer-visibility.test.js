import { describe, expect, it } from 'vitest';
import {
    LAYER_HIDDEN_ATTR,
    componentHasLayerHiddenMarker,
    isLayerHidden,
    persistLayerHiddenMarker,
    syncLayerVisibilityForExport,
} from '../../resources/js/editor/layer-visibility.js';

function makeComponent(initial = {}) {
    const attrs = { ...(initial.attrs ?? {}) };
    let style = { ...(initial.style ?? {}) };
    const em = initial.em ?? {
        Css: {
            rules: {},
            getIdRule(id) {
                return this.rules[id] ? { getStyle: () => ({ ...this.rules[id] }) } : null;
            },
            setIdRule(id, next) {
                this.rules[id] = { ...next };
            },
        },
    };

    return {
        em,
        getId: () => initial.id ?? 'cmp1',
        getAttributes: () => ({ ...attrs }),
        addAttributes: (next) => Object.assign(attrs, next),
        setAttributes: (next) => {
            Object.keys(attrs).forEach((key) => delete attrs[key]);
            Object.assign(attrs, next);
        },
        removeAttributes: (key) => {
            delete attrs[key];
        },
        getStyle: (opts = {}) => ({ ...style }),
        addStyle: (next) => {
            Object.assign(style, next);
        },
        removeStyle: (prop) => {
            delete style[prop];
        },
        get: (key) => initial.props?.[key],
        unset: (key) => {
            if (initial.props) {
                delete initial.props[key];
            }
        },
        _attrs: attrs,
        _style: () => style,
    };
}

describe('layer visibility persistence', () => {
    it('marks hidden components with data-vb-layer-hidden and inline display:none', () => {
        const component = makeComponent();

        persistLayerHiddenMarker(component, true);

        expect(componentHasLayerHiddenMarker(component)).toBe(true);
        expect(component.getAttributes()[LAYER_HIDDEN_ATTR]).toBe('1');
        expect(component.getStyle({ inline: true }).display).toBe('none');
        expect(component.em.Css.rules.cmp1.display).toBe('none');
    });

    it('syncLayerVisibilityForExport forces marker when only #id CSS hides the node', () => {
        const em = {
            Css: {
                rules: { card: { display: 'none' } },
                getIdRule(id) {
                    return this.rules[id] ? { getStyle: () => ({ ...this.rules[id] }) } : null;
                },
                setIdRule(id, next) {
                    this.rules[id] = { ...next };
                },
            },
        };
        const component = makeComponent({ id: 'card', em, style: {} });
        const editor = {
            getWrapper: () => ({
                onAll: (fn) => fn(component),
            }),
            Css: em.Css,
            LayerManager: {
                isVisible: () => true,
            },
        };

        expect(isLayerHidden(editor, component)).toBe(true);

        syncLayerVisibilityForExport(editor);

        expect(component.getAttributes()[LAYER_HIDDEN_ATTR]).toBe('1');
        expect(component.getStyle().display).toBe('none');
    });
});
