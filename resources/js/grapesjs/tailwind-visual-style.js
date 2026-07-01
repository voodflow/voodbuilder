/**
 * Tailblocks cards use a layout wrapper (p-4, lg:w-1/3) around the visual surface
 * (rounded-lg, bg-gray-100). Forward decoration styles to that inner element.
 */

import { isClearedBackground } from './theme-tokens.js';

const VISUAL_SURFACE_PATTERN = /(?:^|-)(?:rounded|bg-|border-(?:gray|vp|indigo|slate|white|black|opacity)|shadow)/;

function elementChildren(component) {
    return (component?.components?.()?.models ?? []).filter((child) => {
        const tag = child.get?.('tagName');

        return tag && tag !== 'textnode';
    });
}

export function resolveVisualStyleTarget(component) {
    if (! component) {
        return component;
    }

    const children = elementChildren(component);

    if (children.length !== 1) {
        return component;
    }

    const selfClasses = component.getClasses?.() ?? [];

    if (selfClasses.some((className) => VISUAL_SURFACE_PATTERN.test(className))) {
        return component;
    }

    const child = children[0];
    const childClasses = child.getClasses?.() ?? [];

    if (childClasses.some((className) => VISUAL_SURFACE_PATTERN.test(className))) {
        return child;
    }

    return component;
}

const FORWARDED_STYLE_PREFIXES = [
    'background',
    'border',
    'border-radius',
    'border-top-left-radius',
    'border-top-right-radius',
    'border-bottom-left-radius',
    'border-bottom-right-radius',
    'box-shadow',
    'color',
];

function shouldForwardStyleProperty(property) {
    return typeof property === 'string'
        && FORWARDED_STYLE_PREFIXES.some((prefix) => property === prefix || property.startsWith(`${prefix}-`));
}

function ensureImportantStyleValue(value) {
    if (value == null || value === '') {
        return value;
    }

    const stringValue = String(value);

    return stringValue.includes('!important') ? stringValue : `${stringValue} !important`;
}

export function registerVisualStyleInspector(editor) {
    editor.on('component:selected', (component) => {
        if (! component) {
            return;
        }

        window.requestAnimationFrame(() => {
            const target = resolveVisualStyleTarget(component);

            if (target && target !== component) {
                editor.StyleManager.select(target, { component });
            }
        });
    });
}

function clearForwardedStyle(target, property) {
    target.removeStyle(property);

    if (property === 'background' || property === 'background-color') {
        target.removeStyle('background');
        target.removeStyle('background-color');
    }
}

export function registerVisualStyleTarget(editor) {
    editor.on('component:styleUpdate', (component, property) => {
        if (! component || ! shouldForwardStyleProperty(property)) {
            return;
        }

        const target = resolveVisualStyleTarget(component);

        if (target === component) {
            return;
        }

        const style = component.getStyle?.() ?? {};
        const value = style[property];
        const isBackground = property === 'background' || property === 'background-color';
        const shouldClear = value == null
            || value === ''
            || (isBackground && isClearedBackground(value));

        if (shouldClear) {
            component.removeStyle(property);
            clearForwardedStyle(target, property);
            target.view?.updateStyles?.();

            return;
        }

        component.removeStyle(property);
        target.addStyle({ [property]: ensureImportantStyleValue(value) });
        target.view?.updateStyles?.();
    });
}
