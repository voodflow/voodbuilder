/**
 * Voodbuilder section cards use a layout wrapper (p-4, lg:w-1/3) around the visual surface
 * (rounded-lg, bg-gray-100). Forward decoration styles to that inner element.
 */

import {
    guardEditorLayersRender,
    hasInvalidLayerChildren,
    sanitizeComponentTreeForLayers,
    sanitizeEditorLayerTree,
} from './core/component-model.js';
import { isChromeLayoutModeEditor } from './chrome-content-slot-utils.js';
import { isClearedBackground } from './theme-tokens.js';

export { guardEditorLayersRender, sanitizeEditorLayerTree } from './core/component-model.js';

const NAMED_CSS_COLORS = {
    black: '#000000',
    white: '#ffffff',
    red: '#ff0000',
    green: '#008000',
    blue: '#0000ff',
    transparent: 'transparent',
};

export function componentHasRenderableView(component) {
    const el = component?.getEl?.();

    return Boolean(
        el
        && el.nodeType === 1
        && typeof el.querySelectorAll === 'function',
    );
}

function isSiteNavBlockId(blockId) {
    if (typeof blockId !== 'string' || blockId === '') {
        return false;
    }

    if (blockId === 'site_header' || blockId === 'site_nav_simple') {
        return true;
    }

    return blockId.startsWith('site_nav_');
}

function isSiteFooterBlockId(blockId) {
    return typeof blockId === 'string' && blockId.startsWith('site_footer_');
}

export function isGrapesComponent(component) {
    return Boolean(component?.get && typeof component.get === 'function');
}

export function forEachGrapesComponent(parent, callback) {
    if (! parent?.components) {
        return;
    }

    const collection = parent.components();

    if (! collection?.forEach) {
        return;
    }

    collection.forEach((child) => {
        if (! isGrapesComponent(child)) {
            return;
        }

        callback(child);
    });
}

export function isEditorLayersReady(editor) {
    if (isChromeLayoutModeEditor(editor) && ! editor.__voodbuilderChromeLayoutReady) {
        return false;
    }

    return true;
}

export function safeRenderEditorLayers(editor) {
    if (! editor?.Layers?.render || ! isEditorLayersReady(editor)) {
        return false;
    }

    guardEditorLayersRender(editor);

    const wrapper = editor.getWrapper?.();

    if (! isGrapesComponent(wrapper)) {
        return false;
    }

    sanitizeEditorLayerTree(editor);

    if (hasInvalidLayerChildren(wrapper)) {
        sanitizeComponentTreeForLayers(wrapper);
    }

    if (hasInvalidLayerChildren(wrapper)) {
        return false;
    }

    try {
        editor.Layers.render();

        return true;
    } catch {
        return false;
    }
}

export function walkComponentTree(component, callback) {
    if (! isGrapesComponent(component)) {
        return;
    }

    callback(component);

    forEachGrapesComponent(component, (child) => {
        walkComponentTree(child, callback);
    });
}

export function safeFindComponents(component, selector) {
    if (! component?.find || ! componentHasRenderableView(component)) {
        return [];
    }

    try {
        const matches = component.find(selector);

        if (! matches) {
            return [];
        }

        return typeof matches[Symbol.iterator] === 'function' ? [...matches] : [];
    } catch {
        return [];
    }
}

export function safeGetClasses(component) {
    if (! component?.getClasses) {
        return [];
    }

    try {
        return component.getClasses() ?? [];
    } catch {
        return [];
    }
}

function normalizeCssColorValue(value) {
    const stripped = stripImportant(value).trim().toLowerCase();

    return NAMED_CSS_COLORS[stripped] ?? stripImportant(value);
}

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

function isPurgingBackground(editor) {
    return editor?.__voodbuilderPurgingBackground === true;
}

const UTILITY_CLASS_PATTERN = /^(?:container|flex(?:-|$)|grid|mx-|my-|mt-|mb-|ml-|mr-|px-|py-|pt-|pb-|pl-|pr-|md:|lg:|sm:|xl:|2xl:|items-|justify-|gap-|text-|bg-|rounded|w-|h-|max-|min-|object-|overflow-|border(?:-|$)|hidden|block|inline|relative|absolute|static|sticky|grow|shrink|basis-|col-|row-|place-|self-|order-|z-|opacity-|shadow|ring-|aspect-|space-|divide-|font-|leading-|tracking-|list-|uppercase|lowercase|capitalize|italic|antialiased|voodbuilder-|vb-|gjs-)/;

function isLikelyUtilityClass(className) {
    return UTILITY_CLASS_PATTERN.test(String(className ?? ''));
}

function isComponentPrivateClass(className) {
    const name = String(className ?? '');

    if (name === '' || isLikelyUtilityClass(name)) {
        return false;
    }

    return /^c\d+$/i.test(name) || /^id[a-z0-9]+$/i.test(name) || /^gjs-[a-z0-9-]+$/i.test(name);
}

function backgroundStyleTargets(component) {
    const target = resolveVisualStyleTarget(component);

    return target === component ? [component] : [component, target];
}

function readInlineBackground(component) {
    const inline = component.getStyle?.({ inline: true });

    if (! inline || typeof inline !== 'object') {
        return null;
    }

    return inline['background-color'] ?? inline.background ?? null;
}

function readRuleBackground(editor, component) {
    for (const rule of collectBackgroundRules(editor, component)) {
        const style = rule.getStyle?.() ?? {};
        const background = style['background-color'] ?? style.background;

        if (! isClearedBackground(background)) {
            return background;
        }
    }

    return null;
}

function collectPrivateClassRules(editor, component) {
    const css = editor?.Css;

    if (! css || ! component) {
        return [];
    }

    const rules = new Set();

    for (const className of component.getClasses?.() ?? []) {
        if (! isComponentPrivateClass(className)) {
            continue;
        }

        const classRule = css.getClassRule?.(className);

        if (classRule) {
            rules.add(classRule);
        }

        css.getRules?.(`.${className}`)?.forEach?.((rule) => {
            rules.add(rule);
        });
    }

    return [...rules];
}

function collectComponentStyleRules(editor, component) {
    const css = editor?.Css;

    if (! css || ! component) {
        return [];
    }

    const rules = new Set();
    const id = component.getId?.();

    if (id) {
        const idRule = css.getIdRule?.(id);

        if (idRule) {
            rules.add(idRule);
        }

        css.getComponentRules?.(component)?.forEach?.((rule) => {
            rules.add(rule);
        });

        css.getRules?.(`#${id}`)?.forEach?.((rule) => {
            rules.add(rule);
        });
    }

    for (const rule of collectPrivateClassRules(editor, component)) {
        rules.add(rule);
    }

    return [...rules];
}

function collectBackgroundRules(editor, component) {
    return collectComponentStyleRules(editor, component);
}

const EXPORT_SPACING_PROPERTIES = [
    'margin',
    'margin-top',
    'margin-right',
    'margin-bottom',
    'margin-left',
    'padding',
    'padding-top',
    'padding-right',
    'padding-bottom',
    'padding-left',
];

const SPACING_SIDE_GROUPS = [
    ['margin-top', 'margin-right', 'margin-bottom', 'margin-left'],
    ['padding-top', 'padding-right', 'padding-bottom', 'padding-left'],
];

function isZeroSpacingValue(value) {
    if (value == null || value === '') {
        return false;
    }

    const normalized = String(value).trim().toLowerCase().replace(/\s*!important\s*$/, '');

    return normalized === '0'
        || normalized === '0px'
        || normalized === '0rem'
        || normalized === '0em'
        || normalized === '0%';
}

function pruneGrapesJsSpacingArtifactZeros(style) {
    const next = { ...style };

    for (const group of SPACING_SIDE_GROUPS) {
        const shorthand = group[0].startsWith('margin') ? 'margin' : 'padding';
        const hasShorthand = next[shorthand] != null && next[shorthand] !== '';

        if (hasShorthand) {
            continue;
        }

        const meaningfulSides = group.filter((property) => {
            const value = next[property];

            return value != null && value !== '' && ! isZeroSpacingValue(value);
        });

        if (meaningfulSides.length === 0 || meaningfulSides.length === group.length) {
            continue;
        }

        for (const property of group) {
            if (isZeroSpacingValue(next[property])) {
                delete next[property];
            }
        }
    }

    return next;
}

function applySpacingStyle(component, style) {
    const pruned = pruneGrapesJsSpacingArtifactZeros(style);
    const current = component.getStyle?.({ inline: true }) ?? {};

    for (const property of EXPORT_SPACING_PROPERTIES) {
        if (pruned[property] == null && current[property] != null) {
            component.removeStyle?.(property);
        }
    }

    if (Object.keys(pruned).length > 0) {
        component.addStyle(pruned, { inline: true });
    }
}

function pruneSpacingZerosFromRule(editor, rule) {
    const ruleStyle = pruneGrapesJsSpacingArtifactZeros(rule.getStyle?.() ?? {});
    const original = rule.getStyle?.() ?? {};

    if (JSON.stringify(ruleStyle) === JSON.stringify(original)) {
        return;
    }

    if (Object.keys(ruleStyle).length === 0) {
        editor.Css.remove(rule);

        return;
    }

    rule.setStyle(ruleStyle);
}

export function pruneRedundantSpacingZeros(component) {
    if (! component) {
        return;
    }

    const inline = component.getStyle?.({ inline: true }) ?? {};
    const pruned = pruneGrapesJsSpacingArtifactZeros(inline);

    if (JSON.stringify(pruned) !== JSON.stringify(inline)) {
        applySpacingStyle(component, pruned);
    }
}

export function pruneRedundantSpacingZerosForExport(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const seen = new Set();

    wrapper.onAll((component) => {
        if (seen.has(component.cid)) {
            return;
        }

        seen.add(component.cid);
        pruneRedundantSpacingZeros(component);

        for (const rule of collectComponentStyleRules(editor, component)) {
            pruneSpacingZerosFromRule(editor, rule);
        }
    });
}

export function syncSpacingStylesForExport(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const seen = new Set();

    wrapper.onAll((component) => {
        if (seen.has(component.cid)) {
            return;
        }

        seen.add(component.cid);

        const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };
        let changed = false;

        for (const rule of collectComponentStyleRules(editor, component)) {
            const ruleStyle = rule.getStyle?.() ?? {};

            for (const property of EXPORT_SPACING_PROPERTIES) {
                const value = ruleStyle[property];

                if (value == null || value === '' || inline[property] != null) {
                    continue;
                }

                inline[property] = value;
                changed = true;
            }
        }

        if (changed) {
            applySpacingStyle(component, inline);
        }
    });
}

const EXPORT_PAINT_PROPERTIES = [
    'color',
    'fill',
    'stroke',
    'stroke-width',
    'opacity',
];

const TEXT_COLOR_CLASS_PATTERN = /^(?:(?:sm|md|lg|xl|2xl):)?text-(?:white|black|primary(?:-foreground)?|foreground(?:-inverse)?|inverse|layer-foreground|gray-\d+|vp-(?:text-[123]|brand-\d+))(?:\/[\d.]+)?$/;

const TAILWIND_TEXT_COLOR_CLASS_PATTERN = /^(?:(?:sm|md|lg|xl|2xl):)?text-(?:vp-|gray-|white|black|primary|foreground)/;

const SVG_SHAPE_SELECTOR = 'path, circle, rect, polygon, polyline, line, ellipse';

const SVG_TAGS = new Set(['svg', 'path', 'circle', 'rect', 'g', 'polygon', 'polyline', 'line', 'ellipse']);

function isSvgElement(component) {
    const tag = String(component?.get?.('tagName') ?? '').toLowerCase();

    return SVG_TAGS.has(tag);
}

function isGenericBlackPaint(paint) {
    const normalized = normalizeCssColorValue(stripImportant(paint)).toLowerCase();

    return normalized === '#000000' || normalized === '#000' || normalized === 'black';
}

function svgUsesCurrentColorPaint(component) {
    const attributes = component?.getAttributes?.() ?? {};
    const rootFill = String(attributes.fill ?? '').toLowerCase();

    if (rootFill === 'currentcolor') {
        return true;
    }

    for (const child of safeFindComponents(component, SVG_SHAPE_SELECTOR)) {
        const fill = String(child.getAttributes?.()?.fill ?? '').toLowerCase();
        const stroke = String(child.getAttributes?.()?.stroke ?? '').toLowerCase();

        if (fill === 'currentcolor' || stroke === 'currentcolor') {
            return true;
        }
    }

    return false;
}

function svgPreservesTailwindPaint(component) {
    if (! isSvgElement(component)) {
        return false;
    }

    if (isStrokeOnlyCurrentColorSvg(component)) {
        return true;
    }

    const classes = component?.getClasses?.() ?? [];
    const hasTailwindTextColor = classes.some((className) => TAILWIND_TEXT_COLOR_CLASS_PATTERN.test(className));

    if (! hasTailwindTextColor) {
        return false;
    }

    return svgUsesCurrentColorPaint(component);
}

function stripSpuriousSvgBakedPaint(component) {
    if (! svgPreservesTailwindPaint(component)) {
        return false;
    }

    const attributes = { ...(component.getAttributes?.() ?? {}) };
    const attributeStyle = parseStyleAttribute(attributes.style);
    const bakedPaint = attributeStyle.color || attributeStyle.fill || attributeStyle.stroke;

    if (! bakedPaint || ! isGenericBlackPaint(bakedPaint)) {
        return false;
    }

    for (const property of ['color', 'fill', 'stroke']) {
        delete attributeStyle[property];
        component.removeStyle?.(property);
    }

    const remainingStyle = Object.entries(attributeStyle)
        .map(([key, value]) => `${key}: ${value}`)
        .join('; ');

    if (remainingStyle) {
        attributes.style = remainingStyle;
    } else {
        delete attributes.style;
    }

    component.setAttributes(attributes);

    for (const child of safeFindComponents(component, SVG_SHAPE_SELECTOR)) {
        const childAttributes = { ...(child.getAttributes?.() ?? {}) };
        let changed = false;

        if (isGenericBlackPaint(childAttributes.fill)) {
            childAttributes.fill = svgRootFillIsNone(component) ? 'none' : 'currentColor';
            changed = true;
        }

        if (isGenericBlackPaint(childAttributes.stroke)) {
            childAttributes.stroke = 'currentColor';
            changed = true;
        }

        if (! changed) {
            continue;
        }

        child.removeStyle?.('fill');
        child.removeStyle?.('stroke');
        child.removeStyle?.('color');
        child.setAttributes(childAttributes);
    }

    return true;
}

function stripConflictingTextColorClasses(component) {
    const classes = component?.getClasses?.() ?? [];
    const kept = classes.filter((className) => ! TEXT_COLOR_CLASS_PATTERN.test(className));

    if (kept.length === classes.length) {
        return;
    }

    component.setClass(kept);
}

function svgRootFillIsNone(component) {
    const rootFill = String(component?.getAttributes?.()?.fill ?? '').toLowerCase();

    return rootFill === 'none';
}

function isStrokeOnlyCurrentColorSvg(component) {
    if (! isSvgElement(component)) {
        return false;
    }

    if (! svgRootFillIsNone(component)) {
        return false;
    }

    const attributes = component.getAttributes?.() ?? {};
    const rootStroke = String(attributes.stroke ?? '').toLowerCase();

    if (rootStroke === 'currentcolor') {
        return true;
    }

    for (const child of safeFindComponents(component, SVG_SHAPE_SELECTOR)) {
        const stroke = String(child.getAttributes?.()?.stroke ?? '').toLowerCase();

        if (stroke === 'currentcolor') {
            return true;
        }
    }

    return false;
}

function isPaintableFillValue(fill, { rootFillIsNone = false } = {}) {
    const value = String(fill ?? '').trim().toLowerCase();

    if (value === 'none' || value.startsWith('url(')) {
        return false;
    }

    if (value === '' && rootFillIsNone) {
        return false;
    }

    return true;
}

function isPaintableStrokeValue(stroke) {
    const value = String(stroke ?? '').trim().toLowerCase();

    if (value === '' || value === 'none' || value.startsWith('url(')) {
        return false;
    }

    return true;
}

function applyPaintToSvgShapeDescendants(svgComponent, paint) {
    const normalizedPaint = stripImportant(paint);
    const rootFillIsNone = svgRootFillIsNone(svgComponent);

    for (const child of safeFindComponents(svgComponent, '*')) {
        const tag = String(child.get?.('tagName') ?? '').toLowerCase();

        if (! ['path', 'circle', 'rect', 'polygon', 'polyline', 'line', 'ellipse'].includes(tag)) {
            continue;
        }

        const childAttributes = { ...(child.getAttributes?.() ?? {}) };
        const fill = String(childAttributes.fill ?? '');
        const stroke = String(childAttributes.stroke ?? '');
        let changed = false;

        if (isPaintableFillValue(fill, { rootFillIsNone })) {
            childAttributes.fill = normalizedPaint;
            changed = true;
        }

        if (isPaintableStrokeValue(stroke)) {
            childAttributes.stroke = normalizedPaint;
            changed = true;
        }

        if (! changed) {
            continue;
        }

        child.removeStyle?.('fill');
        child.removeStyle?.('stroke');
        child.removeStyle?.('color');
        child.setAttributes(childAttributes);
    }
}

function syncSvgCurrentColorChildren(component, value) {
    if (String(component?.get?.('tagName') ?? '').toLowerCase() !== 'svg') {
        return;
    }

    const paint = ensureImportantStyleValue(value);

    component.components?.()?.forEach?.((child) => {
        const fill = String(child.getAttributes?.()?.fill ?? '').toLowerCase();
        const stroke = String(child.getAttributes?.()?.stroke ?? '').toLowerCase();

        if (fill === 'currentcolor' || stroke === 'currentcolor') {
            child.addStyle({ color: paint }, { inline: true });
        }
    });
}

function svgRootPaintStyles(component, paint) {
    const attributes = component.getAttributes?.() ?? {};
    const fillAttr = String(attributes.fill ?? '').toLowerCase();
    const strokeAttr = String(attributes.stroke ?? '').toLowerCase();
    const styles = { color: paint };

    if (fillAttr !== 'none') {
        styles.fill = paint;
    }

    if (strokeAttr === 'currentcolor' || strokeAttr === '' || strokeAttr === 'none') {
        styles.stroke = paint;
    }

    return styles;
}

function applyCustomSvgPaint(editor, component, property, value) {
    if (! isSvgElement(component) || value == null || value === '') {
        return;
    }

    if (! componentHasRenderableView(component)) {
        return;
    }

    stripConflictingTextColorClasses(component);

    const paint = ensureImportantStyleValue(normalizeCssColorValue(value));

    if (property === 'fill' || property === 'color' || property === 'stroke') {
        component.addStyle(svgRootPaintStyles(component, paint), { inline: true });
        syncSvgCurrentColorChildren(component, value);
        applyPaintToSvgShapeDescendants(component, paint);

        if (editor) {
            bakeSvgPaintOnComponent(editor, component);
            clearPaintCssComposerRules(editor, component);
            restoreSvgPaintInspectorStyle(component);
            component.view?.render?.();
        }
    }
}

function applySvgExportStyle(editor, component, property, value) {
    if (! isSvgElement(component) || value == null || value === '') {
        return;
    }

    if (! componentHasRenderableView(component)) {
        return;
    }

    const normalized = property === 'stroke-width' || property === 'opacity'
        ? stripImportant(value)
        : normalizeCssColorValue(value);

    if (! normalized) {
        return;
    }

    const attributes = { ...(component.getAttributes?.() ?? {}) };
    attributes.style = mergeStyleAttribute(attributes.style, { [property]: normalized });
    component.setAttributes(attributes);
    component.addStyle({ [property]: ensureImportantStyleValue(normalized) }, { inline: true });

    if (editor) {
        bakeSvgPaintOnComponent(editor, component);
        clearPaintCssComposerRules(editor, component);
        restoreSvgPaintInspectorStyle(component);
        component.view?.render?.();
    }
}

function propagateSvgPaint(editor, component, property, value) {
    if (! component || value == null || value === '') {
        return;
    }

    if (isSvgElement(component)) {
        applyCustomSvgPaint(editor, component, property, value);

        return;
    }

    if (! componentHasRenderableView(component)) {
        return;
    }

    for (const svg of safeFindComponents(component, 'svg')) {
        if (svgPreservesTailwindPaint(svg) && isGenericBlackPaint(value)) {
            continue;
        }

        applyCustomSvgPaint(editor, svg, property, value);
    }
}

function propagateSvgExportStyle(editor, component, property, value) {
    if (! component || value == null || value === '') {
        return;
    }

    if (property === 'color' || property === 'fill' || property === 'stroke') {
        propagateSvgPaint(editor, component, property, value);

        return;
    }

    if (! EXPORT_PAINT_PROPERTIES.includes(property)) {
        return;
    }

    if (isSvgElement(component)) {
        applySvgExportStyle(editor, component, property, value);

        return;
    }

    if (! componentHasRenderableView(component)) {
        return;
    }

    for (const svg of safeFindComponents(component, 'svg')) {
        applySvgExportStyle(editor, svg, property, value);
    }
}

function stripImportant(value) {
    return String(value ?? '').replace(/\s*!important\s*$/i, '').trim();
}

function parseStyleAttribute(style) {
    const result = {};

    if (! style) {
        return result;
    }

    for (const chunk of String(style).split(';')) {
        const index = chunk.indexOf(':');

        if (index === -1) {
            continue;
        }

        const key = chunk.slice(0, index).trim().toLowerCase();
        const value = stripImportant(chunk.slice(index + 1).trim());

        if (key && value) {
            result[key] = value;
        }
    }

    return result;
}

function mergeStyleAttribute(existing, declarations) {
    const parsed = parseStyleAttribute(existing);

    for (const [key, value] of Object.entries(declarations)) {
        if (value != null && value !== '') {
            parsed[key] = stripImportant(value);
        }
    }

    return Object.entries(parsed)
        .map(([key, value]) => `${key}: ${value}`)
        .join('; ');
}

function resolvePaintFromSvgAttributes(component) {
    const attributeStyle = parseStyleAttribute(component.getAttributes?.()?.style);
    let paint = attributeStyle.color || attributeStyle.fill || attributeStyle.stroke;

    if (paint) {
        return paint;
    }

    for (const child of safeFindComponents(component, '*')) {
        const fill = String(child.getAttributes?.()?.fill ?? '');

        if (fill && ! ['currentcolor', 'none', ''].includes(fill.toLowerCase())) {
            return fill;
        }

        const stroke = String(child.getAttributes?.()?.stroke ?? '');

        if (stroke && ! ['currentcolor', 'none', ''].includes(stroke.toLowerCase())) {
            return stroke;
        }
    }

    return '';
}

function resolveEffectivePaint(editor, component) {
    const inline = component.getStyle?.() ?? {};
    let paint = stripImportant(inline.color || inline.fill || inline.stroke);

    if (paint) {
        return paint;
    }

    const attributeStyle = parseStyleAttribute(component.getAttributes?.()?.style);
    paint = attributeStyle.color || attributeStyle.fill || attributeStyle.stroke;

    if (paint) {
        return paint;
    }

    paint = resolvePaintFromSvgAttributes(component);

    if (paint) {
        return paint;
    }

    for (const rule of collectComponentStyleRules(editor, component)) {
        const ruleStyle = rule.getStyle?.() ?? {};
        paint = stripImportant(ruleStyle.color || ruleStyle.fill || ruleStyle.stroke);

        if (paint) {
            return paint;
        }
    }

    return '';
}

function resolveSvgExportStyles(editor, component) {
    const inline = { ...(component.getStyle?.() ?? {}) };
    const attributeStyle = parseStyleAttribute(component.getAttributes?.()?.style);
    const styles = {};

    for (const property of EXPORT_PAINT_PROPERTIES) {
        const value = stripImportant(
            inline[property]
            ?? attributeStyle[property],
        );

        if (value) {
            styles[property] = value;
        }
    }

    for (const rule of collectComponentStyleRules(editor, component)) {
        const ruleStyle = rule.getStyle?.() ?? {};

        for (const property of EXPORT_PAINT_PROPERTIES) {
            if (styles[property]) {
                continue;
            }

            const value = stripImportant(ruleStyle[property]);

            if (value) {
                styles[property] = value;
            }
        }
    }

    return styles;
}

function bakeSvgPaintOnComponent(editor, component) {
    if (! componentHasRenderableView(component)) {
        return;
    }

    stripSpuriousSvgBakedPaint(component);

    const exportStyles = resolveSvgExportStyles(editor, component);
    const paint = normalizeCssColorValue(
        exportStyles.color
        || exportStyles.fill
        || exportStyles.stroke
        || resolveEffectivePaint(editor, component),
    );

    if (paint && svgPreservesTailwindPaint(component) && isGenericBlackPaint(paint)) {
        return;
    }

    if (! paint && Object.keys(exportStyles).length === 0) {
        return;
    }

    if (isStrokeOnlyCurrentColorSvg(component) && (! paint || isGenericBlackPaint(paint))) {
        stripSpuriousSvgBakedPaint(component);

        return;
    }

    stripConflictingTextColorClasses(component);

    const attributes = { ...(component.getAttributes?.() ?? {}) };
    const rootStyles = {};

    if (paint) {
        rootStyles.color = paint;

        const fillAttr = String(attributes.fill ?? '').toLowerCase();

        if (fillAttr !== 'none') {
            rootStyles.fill = paint;
        }

        const strokeAttr = String(attributes.stroke ?? '').toLowerCase();

        if (strokeAttr === 'currentcolor' || strokeAttr === '' || strokeAttr === 'none') {
            rootStyles.stroke = paint;
        }
    }

    for (const property of ['stroke-width', 'opacity']) {
        if (exportStyles[property]) {
            rootStyles[property] = exportStyles[property];
        }
    }

    attributes.style = mergeStyleAttribute(attributes.style, rootStyles);
    component.setAttributes(attributes);

    if (paint) {
        applyPaintToSvgShapeDescendants(component, paint);
    }
}

export function bakeSvgPaintForExport(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const seen = new Set();

    wrapper.onAll((component) => {
        if (seen.has(component.cid)) {
            return;
        }

        seen.add(component.cid);

        if (String(component.get?.('tagName') ?? '').toLowerCase() !== 'svg') {
            return;
        }

        bakeSvgPaintOnComponent(editor, component);
    });

    purgeDesyncedPaintCssRules(editor);
}

function stripPaintFromRule(editor, rule) {
    const style = { ...(rule.getStyle?.() ?? {}) };
    let changed = false;

    for (const key of EXPORT_PAINT_PROPERTIES) {
        if (style[key] == null) {
            continue;
        }

        delete style[key];
        changed = true;
    }

    if (! changed) {
        return;
    }

    if (Object.keys(style).length === 0) {
        editor.Css.remove(rule);
    } else {
        rule.setStyle(style);
    }
}

function inspectorStylesFromSvgAttributes(component) {
    const attributeStyle = parseStyleAttribute(component.getAttributes?.()?.style);
    const paint = normalizeCssColorValue(
        attributeStyle.color || attributeStyle.fill || attributeStyle.stroke || resolvePaintFromSvgAttributes(component),
    );
    const styles = {};

    if (paint) {
        Object.assign(styles, svgRootPaintStyles(component, ensureImportantStyleValue(paint)));
    }

    for (const property of ['stroke-width', 'opacity']) {
        if (attributeStyle[property]) {
            styles[property] = ensureImportantStyleValue(attributeStyle[property]);
        }
    }

    return styles;
}

export function restoreSvgPaintInspectorStyle(component) {
    if (String(component?.get?.('tagName') ?? '').toLowerCase() !== 'svg') {
        return;
    }

    const styles = inspectorStylesFromSvgAttributes(component);

    if (Object.keys(styles).length === 0) {
        return;
    }

    component.addStyle(styles, { inline: true });

    const paint = stripImportant(styles.color || styles.fill || styles.stroke);

    if (paint) {
        syncSvgCurrentColorChildren(component, paint);
    }
}

export function restoreSvgPaintInspectorStyles(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const seen = new Set();

    wrapper.onAll((component) => {
        if (seen.has(component.cid)) {
            return;
        }

        seen.add(component.cid);

        if (String(component.get?.('tagName') ?? '').toLowerCase() !== 'svg') {
            return;
        }

        restoreSvgPaintInspectorStyle(component);
    });
}

function clearPaintCssComposerRules(editor, component) {
    if (! editor?.Css || ! component) {
        return;
    }

    for (const rule of collectComponentStyleRules(editor, component)) {
        stripPaintFromRule(editor, rule);
    }

    pruneEmptyPrivateClassRules(editor, component);
}

export function clearPaintCssRules(editor, component) {
    if (! editor?.Css || ! component) {
        return;
    }

    component.removeStyle?.('color');
    component.removeStyle?.('fill');
    component.removeStyle?.('stroke');

    clearPaintCssComposerRules(editor, component);
}

function svgHasBakedPaint(component) {
    const paint = resolvePaintFromSvgAttributes(component);

    if (paint) {
        return true;
    }

    for (const child of safeFindComponents(component, '*')) {
        const fill = String(child.getAttributes?.()?.fill ?? '').toLowerCase();
        const stroke = String(child.getAttributes?.()?.stroke ?? '').toLowerCase();

        if (fill && fill !== 'currentcolor' && fill !== 'none') {
            return true;
        }

        if (stroke && stroke !== 'currentcolor' && stroke !== 'none') {
            return true;
        }
    }

    return false;
}

export function purgeDesyncedPaintCssRules(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const seen = new Set();

    wrapper.onAll((component) => {
        if (seen.has(component.cid)) {
            return;
        }

        seen.add(component.cid);

        if (String(component.get?.('tagName') ?? '').toLowerCase() !== 'svg') {
            return;
        }

        if (! svgHasBakedPaint(component)) {
            return;
        }

        clearPaintCssComposerRules(editor, component);
    });
}

export function hydrateSvgPaintFromAttributes(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const seen = new Set();

    wrapper.onAll((component) => {
        if (seen.has(component.cid)) {
            return;
        }

        seen.add(component.cid);

        if (String(component.get?.('tagName') ?? '').toLowerCase() !== 'svg') {
            return;
        }

        if (! componentHasRenderableView(component)) {
            return;
        }

        stripSpuriousSvgBakedPaint(component);

        if (! resolvePaintFromSvgAttributes(component)) {
            return;
        }

        try {
            bakeSvgPaintOnComponent(editor, component);
            clearPaintCssComposerRules(editor, component);
            restoreSvgPaintInspectorStyle(component);
        } catch {
            // Canvas nodes may not be mounted yet during boot.
        }
    });
}

export function bakeSvgPaintForComponent(editor, root) {
    if (! root) {
        return;
    }

    const seen = new Set();
    const candidates = [];

    if (String(root.get?.('tagName') ?? '').toLowerCase() === 'svg') {
        candidates.push(root);
    }

    for (const component of safeFindComponents(root, 'svg')) {
        candidates.push(component);
    }

    for (const component of candidates) {
        if (seen.has(component.cid)) {
            continue;
        }

        seen.add(component.cid);
        bakeSvgPaintOnComponent(editor, component);
    }
}

function applyPaintStyle(component, style) {
    const current = component.getStyle?.({ inline: true }) ?? {};

    for (const property of EXPORT_PAINT_PROPERTIES) {
        if (style[property] == null && current[property] != null) {
            component.removeStyle?.(property);
        }
    }

    if (Object.keys(style).length > 0) {
        component.addStyle(style, { inline: true });
    }
}

export function syncPaintStylesForExport(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const seen = new Set();

    wrapper.onAll((component) => {
        if (seen.has(component.cid)) {
            return;
        }

        seen.add(component.cid);

        const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };
        let changed = false;

        for (const rule of collectComponentStyleRules(editor, component)) {
            const ruleStyle = rule.getStyle?.() ?? {};

            for (const property of EXPORT_PAINT_PROPERTIES) {
                const value = ruleStyle[property];

                if (value == null || value === '' || inline[property] != null) {
                    continue;
                }

                inline[property] = value;
                changed = true;
            }
        }

        if (changed) {
            applyPaintStyle(component, inline);
        }
    });
}

function stripBackgroundFromRule(editor, rule) {
    const style = { ...(rule.getStyle?.() ?? {}) };
    let changed = false;

    for (const key of Object.keys(style)) {
        if (! /^background/i.test(key)) {
            continue;
        }

        delete style[key];
        changed = true;
    }

    if (! changed) {
        return;
    }

    if (Object.keys(style).length === 0) {
        editor.Css.remove(rule);
    } else {
        rule.setStyle(style);
    }
}

function pruneEmptyPrivateClassRules(editor, component) {
    const css = editor?.Css;

    if (! css || ! component) {
        return;
    }

    for (const className of [...(component.getClasses?.() ?? [])]) {
        if (! isComponentPrivateClass(className)) {
            continue;
        }

        const rule = css.getClassRule?.(className);

        if (! rule) {
            component.removeClass?.(className);

            continue;
        }

        const style = rule.getStyle?.() ?? {};

        if (Object.keys(style).length === 0) {
            css.remove(rule);
            component.removeClass?.(className);
        }
    }
}

export function clearBackgroundCssRules(editor, component, options = {}) {
    const { rulesOnly = false } = options;

    if (! editor?.Css || ! component) {
        return;
    }

    for (const target of backgroundStyleTargets(component)) {
        if (! rulesOnly) {
            target.removeStyle('background');
            target.removeStyle('background-color');

            const styleModel = editor.Styles?.getModelToStyle?.(target);

            if (styleModel && styleModel !== target) {
                styleModel.removeStyle?.('background');
                styleModel.removeStyle?.('background-color');
            }
        }

        for (const rule of collectBackgroundRules(editor, target)) {
            stripBackgroundFromRule(editor, rule);
        }

        pruneEmptyPrivateClassRules(editor, target);
    }
}

export function hasStaleBackgroundRule(editor, component) {
    if (! component) {
        return false;
    }

    for (const target of backgroundStyleTargets(component)) {
        const inlineBackground = readInlineBackground(target);
        const ruleBackground = readRuleBackground(editor, target);

        if (isClearedBackground(inlineBackground) && ! isClearedBackground(ruleBackground)) {
            return true;
        }
    }

    return false;
}

export function syncStaleBackgroundRules(editor, component) {
    if (! component) {
        return;
    }

    const target = resolveVisualStyleTarget(component);
    const inlineBackground = readInlineBackground(target);
    const hasGhostBackground = collectBackgroundRules(editor, target).some((rule) => {
        const style = rule.getStyle?.() ?? {};
        const background = style['background-color'] ?? style.background;

        return ! isClearedBackground(background);
    });

    if (! hasGhostBackground) {
        return;
    }

    if (! isClearedBackground(inlineBackground) && ! isPurgingBackground(editor)) {
        return;
    }

    clearBackgroundCssRules(editor, component);

    window.requestAnimationFrame(() => {
        const selected = editor.getSelected();

        if (! selected) {
            return;
        }

        editor.StyleManager.select(target, { component: selected });
    });
}

export function purgeDesyncedBackgroundCssRules(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    editor.__voodbuilderPurgingBackground = true;

    try {
        const seen = new Set();

        wrapper.onAll((component) => {
            if (seen.has(component.cid)) {
                return;
            }

            for (const target of backgroundStyleTargets(component)) {
                if (seen.has(target.cid)) {
                    continue;
                }

                seen.add(target.cid);

                if (hasStaleBackgroundRule(editor, target)
                    || (isClearedBackground(readInlineBackground(target)) && collectBackgroundRules(editor, target).length > 0)) {
                    clearBackgroundCssRules(editor, target, { rulesOnly: true });
                }
            }
        });
    } finally {
        editor.__voodbuilderPurgingBackground = false;
    }
}

export function registerVisualStyleInspector(editor) {
    editor.on('component:selected', (component) => {
        if (! component) {
            return;
        }

        // Layout chrome editor: Style Manager updates on every select were thrashing
        // the main thread (color defaults black/white + full sector re-render).
        if (editor.__voodbuilderChromeLayoutMode) {
            return;
        }

        const blockId = component.getAttributes?.()?.['data-voodbuilder-block'];

        if (blockId === 'site_header' || isSiteNavBlockId(blockId) || isSiteFooterBlockId(blockId)) {
            return;
        }

        window.requestAnimationFrame(() => {
            if (String(component.get?.('tagName') ?? '').toLowerCase() === 'svg') {
                restoreSvgPaintInspectorStyle(component);
            }

            syncStaleBackgroundRules(editor, component);

            const target = resolveVisualStyleTarget(component);

            if (target && target !== component) {
                editor.StyleManager.select(target, { component });
            } else if (target) {
                editor.StyleManager.select(target);
            }
        });
    });

    editor.on('style:property:update', (event) => {
        const propertyName = event?.property?.getName?.() ?? event?.property?.get?.('property');
        const value = event?.value ?? event?.to?.value ?? '';
        const selected = editor.getSelected();

        if (selected && EXPORT_PAINT_PROPERTIES.includes(propertyName)) {
            propagateSvgExportStyle(editor, selected, propertyName, value);
        }

        if (propertyName !== 'background-color' && propertyName !== 'background') {
            return;
        }

        const isClear = event?.opts?.__clear === true || isClearedBackground(value);

        if (! isClear) {
            return;
        }

        if (selected) {
            clearBackgroundCssRules(editor, selected);

            window.requestAnimationFrame(() => {
                const target = resolveVisualStyleTarget(selected);
                editor.StyleManager.select(target, { component: selected });
            });
        }
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
        if (isPurgingBackground(editor)) {
            return;
        }

        if (component && EXPORT_PAINT_PROPERTIES.includes(property)) {
            const value = component.getStyle?.()?.[property];

            if (value != null && value !== '') {
                propagateSvgExportStyle(editor, component, property, value);
            }
        }

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

            if (isBackground) {
                clearBackgroundCssRules(editor, component);
            }

            target.view?.updateStyles?.();

            return;
        }

        component.removeStyle(property);
        target.addStyle({ [property]: ensureImportantStyleValue(value) });
        target.view?.updateStyles?.();
    });
}
