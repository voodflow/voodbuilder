/**
 * Normalize GrapesJS color inputs — HTML color inputs require #rrggbb, not named colors.
 */

const NAMED_COLORS = {
    aliceblue: '#f0f8ff',
    antiquewhite: '#faebd7',
    aqua: '#00ffff',
    aquamarine: '#7fffd4',
    azure: '#f0ffff',
    beige: '#f5f5dc',
    bisque: '#ffe4c4',
    black: '#000000',
    blanchedalmond: '#ffebcd',
    blue: '#0000ff',
    blueviolet: '#8a2be2',
    brown: '#a52a2a',
    burlywood: '#deb887',
    cadetblue: '#5f9ea0',
    chartreuse: '#7fff00',
    chocolate: '#d2691e',
    coral: '#ff7f50',
    cornflowerblue: '#6495ed',
    cornsilk: '#fff8dc',
    crimson: '#dc143c',
    cyan: '#00ffff',
    darkblue: '#00008b',
    darkcyan: '#008b8b',
    darkgoldenrod: '#b8860b',
    darkgray: '#a9a9a9',
    darkgreen: '#006400',
    darkgrey: '#a9a9a9',
    darkkhaki: '#bdb76b',
    darkmagenta: '#8b008b',
    darkolivegreen: '#556b2f',
    darkorange: '#ff8c00',
    darkorchid: '#9932cc',
    darkred: '#8b0000',
    darksalmon: '#e9967a',
    darkseagreen: '#8fbc8f',
    darkslateblue: '#483d8b',
    darkslategray: '#2f4f4f',
    darkslategrey: '#2f4f4f',
    darkturquoise: '#00ced1',
    darkviolet: '#9400d3',
    deeppink: '#ff1493',
    deepskyblue: '#00bfff',
    dimgray: '#696969',
    dimgrey: '#696969',
    dodgerblue: '#1e90ff',
    firebrick: '#b22222',
    floralwhite: '#fffaf0',
    forestgreen: '#228b22',
    fuchsia: '#ff00ff',
    gainsboro: '#dcdcdc',
    ghostwhite: '#f8f8ff',
    gold: '#ffd700',
    goldenrod: '#daa520',
    gray: '#808080',
    green: '#008000',
    greenyellow: '#adff2f',
    grey: '#808080',
    honeydew: '#f0fff0',
    hotpink: '#ff69b4',
    indianred: '#cd5c5c',
    indigo: '#4b0082',
    ivory: '#fffff0',
    khaki: '#f0e68c',
    lavender: '#e6e6fa',
    lavenderblush: '#fff0f5',
    lawngreen: '#7cfc00',
    lemonchiffon: '#fffacd',
    lightblue: '#add8e6',
    lightcoral: '#f08080',
    lightcyan: '#e0ffff',
    lightgoldenrodyellow: '#fafad2',
    lightgray: '#d3d3d3',
    lightgreen: '#90ee90',
    lightgrey: '#d3d3d3',
    lightpink: '#ffb6c1',
    lightsalmon: '#ffa07a',
    lightseagreen: '#20b2aa',
    lightskyblue: '#87cefa',
    lightslategray: '#778899',
    lightslategrey: '#778899',
    lightsteelblue: '#b0c4de',
    lightyellow: '#ffffe0',
    lime: '#00ff00',
    limegreen: '#32cd32',
    linen: '#faf0e6',
    magenta: '#ff00ff',
    maroon: '#800000',
    mediumaquamarine: '#66cdaa',
    mediumblue: '#0000cd',
    mediumorchid: '#ba55d3',
    mediumpurple: '#9370db',
    mediumseagreen: '#3cb371',
    mediumslateblue: '#7b68ee',
    mediumspringgreen: '#00fa9a',
    mediumturquoise: '#48d1cc',
    mediumvioletred: '#c71585',
    midnightblue: '#191970',
    mintcream: '#f5fffa',
    mistyrose: '#ffe4e1',
    moccasin: '#ffe4b5',
    navajowhite: '#ffdead',
    navy: '#000080',
    oldlace: '#fdf5e6',
    olive: '#808000',
    olivedrab: '#6b8e23',
    orange: '#ffa500',
    orangered: '#ff4500',
    orchid: '#da70d6',
    palegoldenrod: '#eee8aa',
    palegreen: '#98fb98',
    paleturquoise: '#afeeee',
    palevioletred: '#db7093',
    papayawhip: '#ffefd5',
    peachpuff: '#ffdab9',
    peru: '#cd853f',
    pink: '#ffc0cb',
    plum: '#dda0dd',
    powderblue: '#b0e0e6',
    purple: '#800080',
    rebeccapurple: '#663399',
    red: '#ff0000',
    rosybrown: '#bc8f8f',
    royalblue: '#4169e1',
    saddlebrown: '#8b4513',
    salmon: '#fa8072',
    sandybrown: '#f4a460',
    seagreen: '#2e8b57',
    seashell: '#fff5ee',
    sienna: '#a0522d',
    silver: '#c0c0c0',
    skyblue: '#87ceeb',
    slateblue: '#6a5acd',
    slategray: '#708090',
    slategrey: '#708090',
    snow: '#fffafa',
    springgreen: '#00ff7f',
    steelblue: '#4682b4',
    tan: '#d2b48c',
    teal: '#008080',
    thistle: '#d8bfd8',
    tomato: '#ff6347',
    turquoise: '#40e0d0',
    violet: '#ee82ee',
    wheat: '#f5deb3',
    white: '#ffffff',
    whitesmoke: '#f5f5f5',
    yellow: '#ffff00',
    yellowgreen: '#9acd32',
    transparent: '#000000',
};

function normalizeHex(value) {
    if (! value) {
        return '';
    }

    const trimmed = String(value).trim().toLowerCase();

    if (trimmed.startsWith('#')) {
        if (trimmed.length === 4) {
            return `#${trimmed[1]}${trimmed[1]}${trimmed[2]}${trimmed[2]}${trimmed[3]}${trimmed[3]}`;
        }

        return trimmed;
    }

    if (NAMED_COLORS[trimmed]) {
        return NAMED_COLORS[trimmed];
    }

    if (typeof CSS !== 'undefined' && typeof CSS.supports === 'function' && CSS.supports('color', trimmed)) {
        const probe = document.createElement('span');
        probe.style.color = trimmed;
        document.body.appendChild(probe);
        const computed = getComputedStyle(probe).color;
        probe.remove();

        const match = computed.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i);

        if (match) {
            const toHex = (channel) => Number(channel).toString(16).padStart(2, '0');

            return `#${toHex(match[1])}${toHex(match[2])}${toHex(match[3])}`;
        }
    }

    return '';
}

function fixColorInput(input) {
    if (!(input instanceof HTMLInputElement) || input.type !== 'color') {
        return;
    }

    if (input.dataset.vbColorFixed === '1') {
        return;
    }

    input.dataset.vbColorFixed = '1';

    const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

    if (! descriptor?.set || ! descriptor?.get) {
        return;
    }

    Object.defineProperty(input, 'value', {
        configurable: true,
        enumerable: true,
        get() {
            return descriptor.get.call(input);
        },
        set(next) {
            const normalized = normalizeHex(next) || '#000000';
            descriptor.set.call(input, normalized);
        },
    });

    const current = normalizeHex(descriptor.get.call(input));

    if (current) {
        descriptor.set.call(input, current);
    }
}

export function fixInspectorColorInputs(root = document) {
    if (! root) {
        return;
    }

    const scope = root instanceof Document ? root : root;

    scope.querySelectorAll?.('input[type="color"]').forEach(fixColorInput);
}

/**
 * Patch HTMLInputElement.prototype.value once so GrapesJS StyleManager never
 * assigns named colors ("black"/"white") to <input type="color">.
 * Must run before grapesjs.init().
 *
 * Also sanitizes innerHTML snippets from Grapick (style-bg), which embeds
 * value="black" in markup and bypasses the value setter.
 */
export function installGlobalColorInputValueFix() {
    if (typeof window === 'undefined' || window.__voodbuilderColorInputValueFixed) {
        return;
    }

    const valueDescriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

    if (! valueDescriptor?.set || ! valueDescriptor?.get) {
        return;
    }

    window.__voodbuilderColorInputValueFixed = true;

    Object.defineProperty(HTMLInputElement.prototype, 'value', {
        configurable: true,
        enumerable: valueDescriptor.enumerable,
        get() {
            return valueDescriptor.get.call(this);
        },
        set(next) {
            if (this.type === 'color') {
                valueDescriptor.set.call(this, normalizeHex(next) || '#000000');

                return;
            }

            valueDescriptor.set.call(this, next);
        },
    });

    const originalSetAttribute = Element.prototype.setAttribute;

    Element.prototype.setAttribute = function setAttribute(name, value) {
        if (
            this instanceof HTMLInputElement
            && this.type === 'color'
            && String(name).toLowerCase() === 'value'
        ) {
            return originalSetAttribute.call(this, name, normalizeHex(value) || '#000000');
        }

        return originalSetAttribute.call(this, name, value);
    };

    // Grapick builds handlers via innerHTML with value="black"/"white" — rewrite before parse.
    const innerHtmlDescriptor = Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML');

    if (innerHtmlDescriptor?.set && innerHtmlDescriptor?.get) {
        Object.defineProperty(Element.prototype, 'innerHTML', {
            configurable: true,
            enumerable: innerHtmlDescriptor.enumerable,
            get() {
                return innerHtmlDescriptor.get.call(this);
            },
            set(html) {
                if (typeof html === 'string' && /type\s*=\s*["']color["']/i.test(html)) {
                    html = html.replace(
                        /(type\s*=\s*["']color["'][^>]*?\svalue\s*=\s*["'])(black|white)(["'])/gi,
                        (_, start, named, end) => `${start}${named.toLowerCase() === 'white' ? '#ffffff' : '#000000'}${end}`,
                    );
                    html = html.replace(
                        /(\svalue\s*=\s*["'])(black|white)(["'][^>]*?\stype\s*=\s*["']color["'])/gi,
                        (_, start, named, end) => `${start}${named.toLowerCase() === 'white' ? '#ffffff' : '#000000'}${end}`,
                    );
                }

                innerHtmlDescriptor.set.call(this, html);
            },
        });
    }

    // GrapesJS sometimes sets value before type="color". Normalize on type change too.
    const typeDescriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'type');

    if (typeDescriptor?.set && typeDescriptor?.get) {
        Object.defineProperty(HTMLInputElement.prototype, 'type', {
            configurable: true,
            enumerable: typeDescriptor.enumerable,
            get() {
                return typeDescriptor.get.call(this);
            },
            set(next) {
                typeDescriptor.set.call(this, next);

                if (String(next).toLowerCase() === 'color') {
                    const current = valueDescriptor.get.call(this);
                    const normalized = normalizeHex(current);

                    if (normalized && normalized !== current) {
                        valueDescriptor.set.call(this, normalized);
                    }
                }
            },
        });
    }
}

export function registerInspectorColorFix(editor, mounts = {}) {
    if (editor.__voodbuilderInspectorColorFixRegistered) {
        return;
    }

    editor.__voodbuilderInspectorColorFixRegistered = true;

    installGlobalColorInputValueFix();

    const roots = [mounts.styles, mounts.traits, mounts.siteChromeSettings].filter(Boolean);
    let timer = null;

    const refresh = () => {
        for (const root of roots) {
            fixInspectorColorInputs(root);
        }
    };

    const debouncedRefresh = () => {
        if (timer != null) {
            window.clearTimeout(timer);
        }

        timer = window.setTimeout(() => {
            timer = null;
            refresh();
        }, 120);
    };

    editor.on('component:selected', debouncedRefresh);
    editor.on('load', refresh);

    for (const root of roots) {
        if (! root || root.__vbColorObserver) {
            continue;
        }

        const observer = new MutationObserver((mutations) => {
            const hasColorInput = mutations.some((mutation) => {
                for (const node of mutation.addedNodes) {
                    if (node instanceof HTMLInputElement && node.type === 'color') {
                        return true;
                    }

                    if (node instanceof Element && node.querySelector?.('input[type="color"]')) {
                        return true;
                    }
                }

                return false;
            });

            if (hasColorInput) {
                refresh();
            }
        });

        observer.observe(root, { childList: true, subtree: true });
        root.__vbColorObserver = observer;
    }

    refresh();
}
