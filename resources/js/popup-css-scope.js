/**
 * Scope popup page CSS so Tailwind utilities cannot restyle the host page.
 */

export const POPUP_CSS_SCOPE = '.voodbuilder-popup-body';

/**
 * @param {string} css
 * @param {string} [scope]
 * @returns {string}
 */
export function scopePopupCss(css, scope = POPUP_CSS_SCOPE) {
    const source = String(css ?? '').trim();

    if (source === '' || scope === '') {
        return source;
    }

    try {
        return scopeWithStyleSheet(source, scope);
    } catch {
        return scopeWithDomStyleElement(source, scope);
    }
}

/**
 * @param {string} css
 * @param {string} scope
 * @returns {string}
 */
function scopeWithStyleSheet(css, scope) {
    if (typeof CSSStyleSheet === 'undefined' || typeof CSSStyleSheet.prototype.replaceSync !== 'function') {
        throw new Error('CSSStyleSheet.replaceSync unavailable');
    }

    const sheet = new CSSStyleSheet();
    sheet.replaceSync(css);

    return serializeCssRules(sheet.cssRules, scope);
}

/**
 * @param {string} css
 * @param {string} scope
 * @returns {string}
 */
function scopeWithDomStyleElement(css, scope) {
    if (typeof document === 'undefined') {
        return css;
    }

    const style = document.createElement('style');
    style.setAttribute('data-voodbuilder-popup-css-scope-parse', '');
    style.textContent = css;
    document.documentElement.appendChild(style);

    try {
        const sheet = style.sheet;

        if (! sheet) {
            return css;
        }

        return serializeCssRules(sheet.cssRules, scope);
    } finally {
        style.remove();
    }
}

/**
 * @param {CSSRuleList|ArrayLike<CSSRule>} rules
 * @param {string} scope
 * @returns {string}
 */
function serializeCssRules(rules, scope) {
    let output = '';

    for (let index = 0; index < rules.length; index += 1) {
        output += serializeCssRule(rules[index], scope);
    }

    return output;
}

/**
 * @param {CSSRule} rule
 * @param {string} scope
 * @returns {string}
 */
function serializeCssRule(rule, scope) {
    if (rule.type === CSSRule.STYLE_RULE) {
        /** @type {CSSStyleRule} */
        const styleRule = rule;

        return `${scopeSelectorList(styleRule.selectorText, scope)}{${styleRule.style.cssText}}`;
    }

    if (rule.type === CSSRule.MEDIA_RULE) {
        /** @type {CSSMediaRule} */
        const mediaRule = rule;

        return `@media ${mediaRule.media.mediaText}{${serializeCssRules(mediaRule.cssRules, scope)}}`;
    }

    if (rule.type === CSSRule.SUPPORTS_RULE) {
        /** @type {CSSSupportsRule} */
        const supportsRule = rule;

        return `@supports ${supportsRule.conditionText}{${serializeCssRules(supportsRule.cssRules, scope)}}`;
    }

    if (rule.type === CSSRule.KEYFRAMES_RULE || rule.type === CSSRule.FONT_FACE_RULE) {
        return rule.cssText;
    }

    if ('cssRules' in rule && rule.cssRules && typeof rule.cssText === 'string') {
        const prelude = rule.cssText.slice(0, rule.cssText.indexOf('{') + 1);

        if (prelude.includes('{')) {
            return `${prelude}${serializeCssRules(rule.cssRules, scope)}}`;
        }
    }

    return rule.cssText;
}

/**
 * @param {string} selectorText
 * @param {string} scope
 * @returns {string}
 */
function scopeSelectorList(selectorText, scope) {
    return String(selectorText ?? '')
        .split(',')
        .map((part) => scopeSingleSelector(part.trim(), scope))
        .filter(Boolean)
        .join(',');
}

/**
 * @param {string} selector
 * @param {string} scope
 * @returns {string}
 */
function scopeSingleSelector(selector, scope) {
    if (selector === '') {
        return selector;
    }

    if (selector.includes(scope)) {
        return selector;
    }

    if (
        selector === ':root'
        || selector === ':host'
        || selector === 'html'
        || selector === 'body'
        || selector === '*'
    ) {
        return scope;
    }

    // html.dark / :root[data-theme] → keep the rest on the popup root
    if (/^(?:html|body|:root|:host)(?=[\s.:#[>+~]|$).*/.test(selector)) {
        return selector.replace(/^(?:html|body|:root|:host)/, scope);
    }

    return `${scope} ${selector}`;
}
