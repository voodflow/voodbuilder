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
import {
    isClearedBackground,
    isClearedBackgroundImage,
    isClearedStyleValue,
    isStyleManagerDefaultWhiteBackground,
    styleHasAuthorBackgroundPaint,
    enforceStyleManagerColorOverUtilities,
} from './theme-tokens.js';

const BACKGROUND_STYLE_PROPERTIES = [
    'background',
    'background-color',
    'background-image',
    'background-size',
    'background-position',
    'background-repeat',
    'background-attachment',
    'background-origin',
    'background-clip',
];

function isBackgroundStyleProperty(property) {
    return typeof property === 'string' && /^background([A-Z-]|$)/i.test(property);
}

const BACKGROUND_PAINT_PROPERTIES = new Set([
    'background',
    'background-color',
    'background-image',
]);

function isBackgroundPaintProperty(property) {
    return BACKGROUND_PAINT_PROPERTIES.has(property);
}

function isBackgroundClearValue(value, opts = {}) {
    if (opts?.__clear === true) {
        return true;
    }

    if (isClearedBackground(value) || isClearedBackgroundImage(value)) {
        return true;
    }

    // Legacy SM default on clear was #ffffff — treat as remove, not as a paint.
    if (isStyleManagerDefaultWhiteBackground(value) && opts?.__fromCustom === true) {
        return true;
    }

    return false;
}

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

/**
 * Cheap tree fingerprint so rapid identical Layers.render calls can be skipped.
 * Editor rebinds non-passive touchstart listeners on every render.
 *
 * @param {object} editor
 * @returns {string}
 */
function editorLayersFingerprint(editor) {
    const wrapper = editor.getWrapper?.();

    if (! isGrapesComponent(wrapper)) {
        return '0';
    }

    let count = 0;

    walkComponentTree(wrapper, () => {
        count += 1;
    });

    return String(count);
}

/**
 * @param {object} editor
 * @param {{ immediate?: boolean }} [options]
 * @returns {boolean}
 */
export function safeRenderEditorLayers(editor, options = {}) {
    if (! editor?.Layers?.render || ! isEditorLayersReady(editor)) {
        return false;
    }

    guardEditorLayersRender(editor);

    const immediate = options.immediate === true;

    const run = () => {
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

        const fingerprint = editorLayersFingerprint(editor);

        if (
            ! immediate
            && editor.__voodbuilderLayersFingerprint === fingerprint
            && editor.__voodbuilderLayersRenderedOnce
        ) {
            return true;
        }

        try {
            editor.Layers.render();
            editor.__voodbuilderLayersFingerprint = fingerprint;
            editor.__voodbuilderLayersRenderedOnce = true;

            return true;
        } catch {
            return false;
        }
    };

    if (immediate) {
        window.clearTimeout(editor.__voodbuilderLayersRenderTimer);

        return run();
    }

    window.clearTimeout(editor.__voodbuilderLayersRenderTimer);
    editor.__voodbuilderLayersRenderTimer = window.setTimeout(() => {
        run();
    }, 120);

    return true;
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
    return editor?.__voodbuilderPurgingBackground === true
        || editor?.__voodbuilderPurgingStyles === true;
}

function setPurgingStyles(editor, active) {
    editor.__voodbuilderPurgingStyles = active;
    editor.__voodbuilderPurgingBackground = active;
}

function stripPropertyFromRule(editor, rule, property) {
    const style = { ...(rule.getStyle?.() ?? {}) };

    if (! Object.prototype.hasOwnProperty.call(style, property)) {
        return;
    }

    delete style[property];

    if (Object.keys(style).length === 0) {
        editor.Css.remove(rule);
    } else {
        rule.setStyle(style);
    }
}

function wipePropertyFromTarget(target, property) {
    target.removeStyle?.(property);

    const rewrite = (style) => {
        if (! style || typeof style !== 'object' || ! Object.prototype.hasOwnProperty.call(style, property)) {
            return null;
        }

        const next = { ...style };
        delete next[property];

        return next;
    };

    const inline = rewrite(target.getStyle?.({ inline: true }));

    if (inline) {
        target.setStyle?.(inline, { inline: true });
    }

    const merged = rewrite(target.getStyle?.());

    if (merged) {
        target.setStyle?.(merged);
    }

    const attrs = target.getAttributes?.() ?? {};
    const rawStyle = typeof attrs.style === 'string' ? attrs.style : '';

    if (rawStyle !== '') {
        const propPattern = new RegExp(`^${property.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\$&')}\\s*:`, 'i');
        const cleaned = rawStyle
            .split(';')
            .map((chunk) => chunk.trim())
            .filter((chunk) => chunk !== '' && ! propPattern.test(chunk))
            .join('; ');

        if (cleaned === '') {
            target.removeAttributes?.('style');
        } else {
            target.addAttributes?.({ style: cleaned.endsWith(';') ? cleaned : `${cleaned};` });
        }
    }

    target.view?.updateStyles?.();
}

/**
 * Force-remove one Style Manager property from component inline styles,
 * Styles model, and CssComposer #id / private-class rules.
 */
export function clearStyleProperty(editor, component, property, options = {}) {
    if (! editor || ! component || ! property) {
        return;
    }

    const properties = options.family === true && isBackgroundPaintProperty(property)
        ? BACKGROUND_STYLE_PROPERTIES
        : [property];

    const targets = resolveVisualStyleTarget(component) === component
        ? [component]
        : [component, resolveVisualStyleTarget(component)];

    for (const target of targets) {
        for (const prop of properties) {
            wipePropertyFromTarget(target, prop);

            const styleModel = editor.Styles?.getModelToStyle?.(target);

            if (styleModel && styleModel !== target) {
                wipePropertyFromTarget(styleModel, prop);
            }

            if (editor.Css) {
                for (const rule of collectComponentStyleRules(editor, target)) {
                    stripPropertyFromRule(editor, rule, prop);
                }
            }
        }

        if (properties === BACKGROUND_STYLE_PROPERTIES || isBackgroundPaintProperty(property)) {
            pruneEmptyPrivateClassRules(editor, target);
        }
    }
}

/**
 * Persist Style Manager paints for save/export.
 *
 * Dual storage is valid: component inline (avoidInlineStyle:false) and/or CssComposer
 * #id rules (loaded CSS, SM in some modes). Never delete composer-only styles just
 * because component live style looks empty — that wiped SM paints on every update/save.
 *
 * Strategy: union(component live, existing #id rule) → write both #id rule and inline
 * so getHtml() and getCss() both carry author styles to the front and through reload.
 */
export function bakeAuthorStylesToComposerForExport(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper || ! editor.Css) {
        return;
    }

    // HTML clones reuse .cXXXX — promote every private class onto unique #id
    // rules before baking, with #id/inline winning over shared class paints.
    promotePrivateStyleClassesToIdRules(editor);

    const seen = new Set();

    wrapper.onAll((component) => {
        if (seen.has(component.cid)) {
            return;
        }

        seen.add(component.cid);

        const id = component.getId?.();

        if (! id) {
            return;
        }

        const existingRule = editor.Css.getIdRule?.(id);
        const existing = { ...(existingRule?.getStyle?.() ?? {}) };
        // Prefer inline + current #id. Avoid getStyle() — it can reintroduce paints
        // from leftover private classes and clobber a divergent sibling #id.
        const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };
        const fromPrivate = {};

        for (const rule of collectPrivateClassRules(editor, component)) {
            Object.assign(fromPrivate, rule.getStyle?.() ?? {});
        }

        const live = { ...fromPrivate, ...existing, ...inline };
        const merged = {};

        for (const [property, value] of Object.entries(live)) {
            if (value == null || value === '') {
                continue;
            }

            if (isClearedStyleValue(property, value)) {
                continue;
            }

            merged[property] = value;
        }

        // Inline explicitly cleared a key that still exists only on the #id rule.
        for (const property of Object.keys(inline)) {
            if (isClearedStyleValue(property, inline[property])) {
                delete merged[property];
            }
        }

        if (Object.keys(merged).length === 0) {
            if (existingRule && Object.keys(existing).length > 0) {
                // Author cleared everything that was on the #id rule.
                const stillLive = Object.entries(live).some(([property, value]) => {
                    return value != null
                        && value !== ''
                        && ! isClearedStyleValue(property, value);
                });

                if (! stillLive && Object.keys(live).length > 0) {
                    editor.Css.remove(existingRule);
                }
            }

            return;
        }

        // Style Manager paints beat utility classes (same node + inheritance).
        for (const [property, value] of Object.entries(merged)) {
            if (
                property === 'color'
                || property === 'fill'
                || property === 'stroke'
                || property === 'background'
                || property === 'background-color'
                || property === 'background-image'
                || property === 'border-color'
                || property === 'box-shadow'
                || property === 'text-shadow'
                || property === 'font-family'
                || property === 'font-size'
                || property === 'font-weight'
                || property === 'letter-spacing'
                || property === 'line-height'
            ) {
                merged[property] = ensureImportantStyleValue(value);
            }
        }

        editor.Css.setIdRule(id, merged);

        // Inline must be SM-friendly: no !important (breaks select matching),
        // single-quoted font stacks. Keep !important only on the #id rule above.
        const inlineStyles = {};

        for (const [property, value] of Object.entries(merged)) {
            if (value == null || value === '') {
                continue;
            }

            let next = String(value).replace(/\s*!important\s*$/i, '').trim();

            if (property === 'font-family') {
                next = next.replace(/"([^"]+)"/g, "'$1'");
            }

            inlineStyles[property] = next;
        }

        // Ensure HTML serialization carries the same paints (reload + front without CSS).
        component.addStyle?.(inlineStyles, { inline: true });
        enforceStyleManagerColorOverUtilities(component);
    });
}

/**
 * After reload, CssComposer #id rules may exist while the component model/inline
 * style is empty — Style Manager then shows "-" for selects. Copy #id paints into
 * inline (without !important) so the right-column inspector matches the canvas.
 *
 * @param {object} editor
 * @returns {number}
 */
export function hydrateAuthorStylesFromIdRules(editor) {
    const wrapper = editor?.getWrapper?.();
    const css = editor?.Css;

    if (! wrapper?.onAll || ! css) {
        return 0;
    }

    let updated = 0;

    wrapper.onAll((component) => {
        const id = component.getId?.();

        if (! id) {
            return;
        }

        const fromId = { ...(css.getIdRule?.(id)?.getStyle?.() ?? {}) };

        if (Object.keys(fromId).length === 0) {
            return;
        }

        const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };
        const next = { ...fromId };

        for (const [property, value] of Object.entries(next)) {
            let cleaned = String(value ?? '').replace(/\s*!important\s*$/i, '').trim();

            if (property === 'font-family') {
                cleaned = cleaned
                    .replace(/"([^"]+)"/g, "'$1'")
                    .replace(/\s*!important\s*$/i, '')
                    .trim();
            }

            // Prefer existing inline when already set (author just edited).
            if (inline[property] != null && String(inline[property]).trim() !== '') {
                let kept = String(inline[property]).replace(/\s*!important\s*$/i, '').trim();

                if (property === 'font-family') {
                    kept = kept.replace(/"([^"]+)"/g, "'$1'");
                }

                next[property] = kept;
            } else {
                next[property] = cleaned;
            }
        }

        component.addStyle?.(next, { inline: true });
        updated += 1;
    });

    return updated;
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

/**
 * @param {object} editor
 * @returns {Map<string, number>}
 */
function countPrivateClassUsage(editor) {
    const counts = new Map();
    const wrapper = editor?.getWrapper?.();

    if (! wrapper?.onAll) {
        return counts;
    }

    wrapper.onAll((component) => {
        for (const className of normalizeClassNames(component.getClasses?.() ?? [])) {
            if (! isComponentPrivateClass(className)) {
                continue;
            }

            counts.set(className, (counts.get(className) ?? 0) + 1);
        }
    });

    return counts;
}

/**
 * @param {unknown} rawClasses
 * @returns {string[]}
 */
function normalizeClassNames(rawClasses) {
    return [...(rawClasses ?? [])].map((item) => (
        typeof item === 'string' ? item : String(item?.id ?? item?.get?.('name') ?? item ?? '')
    )).filter(Boolean);
}

/**
 * @param {object} styles
 * @returns {object}
 */
function stylesForInlinePersist(styles) {
    const inlineStyles = { ...styles };

    if (inlineStyles['font-family']) {
        inlineStyles['font-family'] = String(inlineStyles['font-family'])
            .replace(/\s*!important\s*$/i, '')
            .trim()
            .replace(/"([^"]+)"/g, "'$1'");
    }

    return inlineStyles;
}

/**
 * @param {object} editor
 * @param {string} className
 * @returns {object|null}
 */
function getPrivateClassRule(editor, className) {
    const css = editor?.Css;

    if (! css || ! className) {
        return null;
    }

    const classRule = css.getClassRule?.(className);

    if (classRule) {
        return classRule;
    }

    const rules = css.getRules?.(`.${className}`);

    if (Array.isArray(rules) && rules[0]) {
        return rules[0];
    }

    if (rules?.length) {
        return rules.at?.(0) ?? rules[0];
    }

    return null;
}

/**
 * Promote GrapesJS private style classes (.c1234) onto unique #id rules and remove
 * the classes from the component. Critical precedence: #id + inline win over the
 * private-class snapshot, otherwise a shared .cXXXX updated while styling clone A
 * overwrites clone B's #id font on the next save/reload.
 *
 * @param {object} editor
 * @returns {number} components updated
 */
export function promotePrivateStyleClassesToIdRules(editor) {
    const wrapper = editor?.getWrapper?.();
    const css = editor?.Css;

    if (! wrapper?.onAll || ! css) {
        return 0;
    }

    let updated = 0;
    const removedClassNames = new Set();

    wrapper.onAll((component) => {
        const privateNames = normalizeClassNames(component.getClasses?.() ?? [])
            .filter((name) => isComponentPrivateClass(name));

        if (privateNames.length === 0) {
            return;
        }

        const fromPrivate = {};

        for (const className of privateNames) {
            Object.assign(fromPrivate, getPrivateClassRule(editor, className)?.getStyle?.() ?? {});
            component.removeClass?.(className);
            removedClassNames.add(className);
        }

        const id = component.getId?.();
        const existing = id ? { ...(css.getIdRule?.(id)?.getStyle?.() ?? {}) } : {};
        // Do NOT use getStyle() here — it re-reads private-class paints and would
        // put the shared font back on top of a divergent #id rule.
        const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };
        const merged = { ...fromPrivate, ...existing, ...inline };

        if (id && Object.keys(merged).length > 0) {
            css.setIdRule(id, merged);
        }

        if (Object.keys(merged).length > 0) {
            component.addStyle?.(stylesForInlinePersist(merged), { inline: true });
        }

        updated += 1;
    });

    for (const className of removedClassNames) {
        const stillUsed = countPrivateClassUsage(editor).get(className) ?? 0;

        if (stillUsed > 0) {
            continue;
        }

        const rule = getPrivateClassRule(editor, className);

        if (rule) {
            css.remove(rule);
        }
    }

    return updated;
}

/**
 * @deprecated Prefer promotePrivateStyleClassesToIdRules — kept for callers.
 * @param {object} editor
 * @returns {number}
 */
export function splitSharedPrivateStyleClasses(editor) {
    return promotePrivateStyleClassesToIdRules(editor);
}

/**
 * After duplicating via HTML, private classes are copied with the markup. Detach
 * them onto unique #id rules so the clone can be styled independently.
 *
 * @param {object} editor
 * @param {object} rootComponent
 */
export function detachPrivateStyleClassesOntoId(editor, rootComponent) {
    const css = editor?.Css;

    if (! css || ! rootComponent) {
        return;
    }

    const walk = (component) => {
        if (! component) {
            return;
        }

        const privateNames = normalizeClassNames(component.getClasses?.() ?? [])
            .filter((name) => isComponentPrivateClass(name));

        if (privateNames.length > 0) {
            const fromPrivate = {};

            for (const className of privateNames) {
                Object.assign(fromPrivate, getPrivateClassRule(editor, className)?.getStyle?.() ?? {});
                component.removeClass?.(className);
            }

            const id = component.getId?.();
            const existing = id ? { ...(css.getIdRule?.(id)?.getStyle?.() ?? {}) } : {};
            const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };
            // At clone time styles start equal; #id/inline still win if already set.
            const next = { ...fromPrivate, ...existing, ...inline };

            if (id && Object.keys(next).length > 0) {
                css.setIdRule(id, next);
            }

            if (Object.keys(next).length > 0) {
                component.addStyle?.(stylesForInlinePersist(next), { inline: true });
            }
        }

        const kids = component.components?.();

        if (kids?.forEach) {
            kids.forEach((child) => walk(child));
        } else if (kids?.each) {
            kids.each((child) => walk(child));
        }
    };

    walk(rootComponent);
}

function backgroundStyleTargets(component) {
    const target = resolveVisualStyleTarget(component);

    return target === component ? [component] : [component, target];
}

function readInlineBackgroundStyle(component) {
    const inline = component.getStyle?.({ inline: true });

    if (! inline || typeof inline !== 'object') {
        return {};
    }

    return inline;
}

function readMergedBackgroundStyle(editor, component) {
    const merged = { ...readInlineBackgroundStyle(component) };

    for (const rule of collectBackgroundRules(editor, component)) {
        Object.assign(merged, rule.getStyle?.() ?? {});
    }

    return merged;
}

function componentHasAuthorBackgroundPaint(editor, component) {
    return styleHasAuthorBackgroundPaint(readMergedBackgroundStyle(editor, component));
}

/**
 * What the author sees on the canvas for this element (computed style).
 * null = cannot determine (no mounted view).
 */
function canvasShowsAuthorBackgroundPaint(component) {
    const el = component?.getEl?.();

    if (! el || el.nodeType !== 1) {
        return null;
    }

    const view = el.ownerDocument?.defaultView;

    if (! view?.getComputedStyle) {
        return null;
    }

    let computed;

    try {
        computed = view.getComputedStyle(el);
    } catch {
        return null;
    }

    const image = String(computed.backgroundImage ?? '').trim().toLowerCase();
    const color = String(computed.backgroundColor ?? '').trim().toLowerCase();

    const hasImage = image !== ''
        && image !== 'none'
        && /url\s*\(|gradient\s*\(/i.test(image);

    if (hasImage) {
        return true;
    }

    if (isClearedBackground(color) || isStyleManagerDefaultWhiteBackground(color)) {
        return false;
    }

    // Non-white solid color still visible on canvas.
    return color !== '' && color !== 'transparent';
}

function readInlineBackground(component) {
    const inline = readInlineBackgroundStyle(component);

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

function pruneEditorSpacingArtifactZeros(style) {
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
    const pruned = pruneEditorSpacingArtifactZeros(style);
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
    const ruleStyle = pruneEditorSpacingArtifactZeros(rule.getStyle?.() ?? {});
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
    const pruned = pruneEditorSpacingArtifactZeros(inline);

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

    const wipeInlineBackground = (target) => {
        for (const property of BACKGROUND_STYLE_PROPERTIES) {
            target.removeStyle?.(property);
        }

        const inline = { ...(target.getStyle?.({ inline: true }) ?? {}) };
        let changed = false;

        for (const key of Object.keys(inline)) {
            if (! /^background/i.test(key)) {
                continue;
            }

            delete inline[key];
            changed = true;
        }

        if (changed) {
            if (typeof target.setStyle === 'function') {
                target.setStyle(inline, { inline: true });
            } else if (Object.keys(inline).length > 0) {
                target.addStyle?.(inline, { inline: true });
            }
        }

        const merged = { ...(target.getStyle?.() ?? {}) };
        let mergedChanged = false;

        for (const key of Object.keys(merged)) {
            if (! /^background/i.test(key)) {
                continue;
            }

            delete merged[key];
            mergedChanged = true;
        }

        if (mergedChanged && typeof target.setStyle === 'function') {
            target.setStyle(merged);
        }

        // Also clear raw style attribute leftovers Grapes may not track as props.
        const attrs = target.getAttributes?.() ?? {};
        const rawStyle = typeof attrs.style === 'string' ? attrs.style : '';

        if (rawStyle && /background/i.test(rawStyle)) {
            const cleaned = rawStyle
                .split(';')
                .map((chunk) => chunk.trim())
                .filter((chunk) => chunk !== '' && ! /^background(?:-[\w-]+)?\s*:/i.test(chunk))
                .join('; ');

            if (cleaned === '') {
                target.removeAttributes?.('style');
            } else if (cleaned !== rawStyle.trim().replace(/;\s*$/, '')) {
                target.addAttributes?.({ style: cleaned.endsWith(';') ? cleaned : `${cleaned};` });
            }
        }

        target.view?.updateStyles?.();
    };

    for (const target of backgroundStyleTargets(component)) {
        if (! rulesOnly) {
            wipeInlineBackground(target);

            const styleModel = editor.Styles?.getModelToStyle?.(target);

            if (styleModel && styleModel !== target) {
                wipeInlineBackground(styleModel);
            }
        }

        for (const rule of collectBackgroundRules(editor, target)) {
            stripBackgroundFromRule(editor, rule);
        }

        pruneEmptyPrivateClassRules(editor, target);
    }
}

/**
 * True when inline background is empty but CssComposer still has author paint.
 * Kept for diagnostics; export no longer treats this as a clear (dual storage).
 */
export function hasStaleBackgroundRule(editor, component) {
    if (! component) {
        return false;
    }

    for (const target of backgroundStyleTargets(component)) {
        const inlineStyle = readInlineBackgroundStyle(target);
        const inlineHasPaint = styleHasAuthorBackgroundPaint(inlineStyle);
        const ruleHasPaint = collectBackgroundRules(editor, target).some((rule) => {
            return styleHasAuthorBackgroundPaint(rule.getStyle?.() ?? {});
        });

        if (! inlineHasPaint && ruleHasPaint) {
            return true;
        }
    }

    return false;
}

/**
 * On select: if CssComposer still has background paint but the component inline
 * style is empty (typical after reload from saved CSS), hydrate inline from the
 * rule so Style Manager / getHtml see the same paints. Never wipe composer-only
 * styles — that made customizations vanish on reload/select.
 */
export function syncStaleBackgroundRules(editor, component) {
    if (! component || isPurgingBackground(editor)) {
        return;
    }

    const target = resolveVisualStyleTarget(component);
    const rules = collectBackgroundRules(editor, target);
    const ruleStyle = {};

    for (const rule of rules) {
        Object.assign(ruleStyle, rule.getStyle?.() ?? {});
    }

    if (! styleHasAuthorBackgroundPaint(ruleStyle)) {
        return;
    }

    if (styleHasAuthorBackgroundPaint(readInlineBackgroundStyle(target))) {
        return;
    }

    const hydrate = {};

    for (const property of BACKGROUND_STYLE_PROPERTIES) {
        const value = ruleStyle[property];

        if (value == null || value === '' || isClearedStyleValue(property, value)) {
            continue;
        }

        hydrate[property] = value;
    }

    if (Object.keys(hydrate).length === 0) {
        return;
    }

    target.addStyle?.(hydrate, { inline: true });
}

/**
 * Strip only cleared *tokens* (none / transparent / empty) left on background
 * rules. Do not treat "composer has paint, inline empty" as a clear — dual storage.
 */
export function purgeDesyncedBackgroundCssRules(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper || ! editor.Css) {
        return;
    }

    setPurgingStyles(editor, true);

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

                for (const rule of collectBackgroundRules(editor, target)) {
                    const style = { ...(rule.getStyle?.() ?? {}) };
                    let changed = false;

                    for (const property of Object.keys(style)) {
                        if (! /^background/i.test(property)) {
                            continue;
                        }

                        if (isClearedStyleValue(property, style[property])) {
                            delete style[property];
                            changed = true;
                        }
                    }

                    if (! changed) {
                        continue;
                    }

                    if (Object.keys(style).length === 0) {
                        editor.Css.remove(rule);
                    } else {
                        rule.setStyle(style);
                    }
                }
            }
        });
    } finally {
        setPurgingStyles(editor, false);
    }
}

/**
 * Before getHtml(): only strip background when the canvas proves the author
 * cleared it (computed style has no paint) while the model still claims paint.
 * Never delete composer-only backgrounds when inline is empty — that is valid
 * dual storage after reload from saved CSS.
 */
export function purgeClearedBackgroundInlineForExport(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    setPurgingStyles(editor, true);

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

                if (! componentHasAuthorBackgroundPaint(editor, target)) {
                    continue;
                }

                const canvasPaint = canvasShowsAuthorBackgroundPaint(target);

                // Canvas clearly shows no bg (cleared in SM) while model still has paint.
                if (canvasPaint === false) {
                    clearBackgroundCssRules(editor, target);
                }
            }
        });
    } finally {
        setPurgingStyles(editor, false);
    }
}

export function registerVisualStyleInspector(editor) {
    editor.on('component:selected', (component) => {
        if (! component) {
            return;
        }

        const blockId = component.getAttributes?.()?.['data-voodbuilder-block'];
        const isChromeBlock = blockId === 'site_header'
            || isSiteNavBlockId(blockId)
            || isSiteFooterBlockId(blockId);

        // Page shell: chrome is managed by the layout — skip Style Manager thrash.
        // Layout editor: allow Style/Classes on nav/footer and nested chrome.
        if (editor.__voodbuilderChromeShellMode && ! editor.__voodbuilderChromeLayoutMode && isChromeBlock) {
            return;
        }

        if (! editor.__voodbuilderChromeLayoutMode && isChromeBlock) {
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

        if (! propertyName || ! selected) {
            return;
        }

        if (EXPORT_PAINT_PROPERTIES.includes(propertyName)) {
            propagateSvgExportStyle(editor, selected, propertyName, value);
        }

        // Only wipe on explicit Style Manager clear (__clear). Intermediate empty
        // values while picking a color used to call clearStyleProperty and erase
        // paints from the model while the canvas still showed cached CSS.
        const explicitClear = event?.opts?.__clear === true;

        if (! explicitClear) {
            return;
        }

        clearStyleProperty(editor, selected, propertyName, {
            family: isBackgroundPaintProperty(propertyName),
        });

        if (isBackgroundPaintProperty(propertyName)) {
            clearBackgroundCssRules(editor, selected);
        }

        window.requestAnimationFrame(() => {
            const target = resolveVisualStyleTarget(selected);
            editor.StyleManager.select(target, { component: selected });
        });
    });
}

function clearForwardedStyle(target, property) {
    target.removeStyle(property);

    if (isBackgroundPaintProperty(property)) {
        for (const backgroundProperty of BACKGROUND_STYLE_PROPERTIES) {
            target.removeStyle(backgroundProperty);
        }
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
        const isBackgroundPaint = isBackgroundPaintProperty(property);
        const shouldClear = value == null
            || value === ''
            || (isBackgroundPaint && isBackgroundClearValue(value));

        if (shouldClear) {
            component.removeStyle(property);
            clearForwardedStyle(target, property);

            if (isBackgroundPaint) {
                clearBackgroundCssRules(editor, component);
            } else {
                clearStyleProperty(editor, component, property);
            }

            target.view?.updateStyles?.();

            return;
        }

        component.removeStyle(property);
        target.addStyle({ [property]: ensureImportantStyleValue(value) });
        target.view?.updateStyles?.();
    });
}
