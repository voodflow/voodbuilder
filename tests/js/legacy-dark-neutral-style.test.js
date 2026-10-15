/**
 * Legacy neutral `dark:` utilities (`dark:lg:my-20`, the pre-1.11.76 policy).
 *
 * Three regressions are pinned here:
 *  A) the Style panel must *read* the value the element carries, falling back to the
 *     legacy `dark:` prefix for theme-neutral groups only (a dark background paint is
 *     never a neutral value);
 *  B) the neutral `dark:` must not survive a Save, even on the read-only Save path that
 *     skips the tree heal;
 *  C) the heal must run *before* the payload HTML is serialized, so the class the heal
 *     adds is the class the server compiles the CSS from.
 *
 * @vitest-environment node
 */

import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import {
    BACKGROUND_OPTIONS,
    BORDER_WIDTH_OPTIONS,
    FONT_SIZE_OPTIONS,
    GRADIENT_FROM_OPTIONS,
    MARGIN_Y_OPTIONS,
    PADDING_OPTIONS,
    ROUNDED_OPTIONS,
    SHADOW_OPTIONS,
    TEXT_COLOR_OPTIONS,
} from '../../resources/js/editor/style-tailwind-class-groups.js';
import {
    resolveSpacingState,
    syncSpacingBox,
} from '../../resources/js/editor/style-tailwind-spacing.js';
import {
    acceptsLegacyDarkFallback,
    healLegacyNeutralThemeClassesForComponent,
    healNonThemeScopedThemeClassesInHtml,
    healNonThemeScopedThemeClassesForExport,
    healNonThemeScopedThemeClasses,
    resolveNeutralStyleGroupValue,
    resolveNeutralStyleGroupValueExact,
} from '../../resources/js/editor/style-theme-scope.js';
import { buildPayload } from '../../resources/js/editor/editor/payload.js';

function makeComponent(initial = []) {
    let classes = [...initial];
    const attrs = {};

    return {
        getClasses: () => [...classes],
        removeClass: (name) => {
            classes = classes.filter((item) => item !== name);
        },
        addClass: (name) => {
            if (! classes.includes(name)) {
                classes = [...classes, name];
            }
        },
        getId: () => 'isection',
        get: (key) => (key === 'type' ? 'default' : undefined),
        getAttributes: () => ({ ...attrs }),
        addAttributes: () => {},
        setAttributes: () => {},
        removeAttributes: () => {},
        components: () => [],
        parent: () => null,
        find: () => [],
        findType: () => [],
        is: () => false,
        getEl: () => null,
        getStyle: () => ({}),
        addStyle: () => {},
        isRemoved: () => false,
        view: { updateClasses: () => {}, updateStyle: () => {}, updateStyles: () => {} },
    };
}

function makeEditor(components = []) {
    return {
        __voodbuilderTwStyleApplying: false,
        __voodbuilderStyleThemeDark: true,
        getDevice: () => 'desktop',
        Devices: { getSelected: () => ({ get: () => 'desktop' }) },
        trigger: () => {},
        getSelected: () => components[0] ?? null,
    };
}

/**
 * Enough of a DOM for the export steps `buildPayload` touches outside its guards.
 */
beforeEach(() => {
    const classList = {
        add: () => {},
        remove: () => {},
        toggle: () => {},
        contains: () => false,
    };

    globalThis.document = {
        body: { classList },
        documentElement: { classList },
        activeElement: null,
        createElement: () => ({
            style: {},
            dataset: {},
            classList,
            appendChild: () => {},
            removeChild: () => {},
            remove: () => {},
            setAttribute: () => {},
            addEventListener: () => {},
            querySelector: () => null,
            querySelectorAll: () => [],
        }),
        querySelector: () => null,
        querySelectorAll: () => [],
        addEventListener: () => {},
        removeEventListener: () => {},
    };
});

afterEach(() => {
    delete globalThis.window;
    delete globalThis.document;
});

describe('Compito A — the panel reads the legacy dark: value', () => {
    it('shows the margin carried by a legacy dark:lg: class', () => {
        const state = resolveSpacingState(
            'margin',
            ['rounded-2xl', 'border-vp-divider', 'dark:lg:m-20'],
            'lg:',
        );

        expect(state.link).toBe('all');
        expect(state.sides).toEqual({ t: '20', r: '20', b: '20', l: '20' });
        expect(state.hasValue).toBe(true);
    });

    it('shows a legacy axis value the same way the neutral class would', () => {
        const legacy = resolveSpacingState('margin', ['dark:lg:my-20'], 'lg:');
        const neutral = resolveSpacingState('margin', ['lg:my-20'], 'lg:');

        expect(legacy).toEqual(neutral);
        expect(legacy.sides).toEqual({ t: '20', r: '', b: '20', l: '' });
    });

    it('shows a per-side legacy value and keeps the light value when both exist', () => {
        expect(resolveSpacingState('padding', ['dark:lg:pt-8'], 'lg:').sides.t).toBe('8');

        const both = resolveSpacingState('margin', ['lg:my-10', 'dark:lg:my-20'], 'lg:');

        expect(both.sides.t).toBe('10');
    });

    it('tolerates the legacy prefix for every neutral group', () => {
        const cases = [
            ['margin-y', MARGIN_Y_OPTIONS, 'my-20'],
            ['padding', PADDING_OPTIONS, 'p-6'],
            ['border-width', BORDER_WIDTH_OPTIONS, 'border-2'],
            ['rounded', ROUNDED_OPTIONS, 'rounded-lg'],
            ['shadow', SHADOW_OPTIONS, 'shadow-lg'],
            ['font-size', FONT_SIZE_OPTIONS, 'text-lg'],
            ['text-color', TEXT_COLOR_OPTIONS, 'text-vp-text-1'],
        ];

        for (const [groupId, options, utility] of cases) {
            expect(acceptsLegacyDarkFallback(groupId, options)).toBe(true);
            expect(
                resolveNeutralStyleGroupValue([`dark:lg:${utility}`], options, groupId, 'lg:'),
            ).toBe(utility);
            expect(
                resolveNeutralStyleGroupValueExact([`dark:lg:${utility}`], options, groupId, 'lg:'),
            ).toBe(utility);
        }
    });

    it('never reads a theme-scoped dark paint as a neutral value', () => {
        expect(acceptsLegacyDarkFallback('background', BACKGROUND_OPTIONS)).toBe(false);
        expect(acceptsLegacyDarkFallback('text-gradient-from', GRADIENT_FROM_OPTIONS)).toBe(false);

        // Light editing view: the dark paint is not the light value.
        expect(
            resolveNeutralStyleGroupValue(
                ['dark:lg:bg-red-500'],
                BACKGROUND_OPTIONS,
                'background',
                'lg:',
            ),
        ).toBe('');

        // Dark editing view: the paint is read at its own variant.
        expect(
            resolveNeutralStyleGroupValue(
                ['dark:lg:bg-red-500'],
                BACKGROUND_OPTIONS,
                'background',
                'dark:lg:',
            ),
        ).toBe('bg-red-500');

        // Shared gradient stops cannot be attributed to a neutral text gradient.
        expect(
            resolveNeutralStyleGroupValue(
                ['dark:from-purple-500'],
                GRADIENT_FROM_OPTIONS,
                'text-gradient-from',
                'lg:',
            ),
        ).toBe('');
    });

    it('heals on selection, and never while the panel is applying an author edit', () => {
        const component = makeComponent(['p-6', 'dark:lg:my-20']);
        const editor = makeEditor([component]);

        expect(healLegacyNeutralThemeClassesForComponent(editor, component)).toBe(1);
        expect(component.getClasses()).toEqual(['p-6', 'lg:my-20']);
        expect(editor.__voodbuilderTwStyleApplying).toBe(false);

        // The author is mid-write: the flag is set, nothing may be re-written under them.
        const writing = makeComponent(['p-6', 'dark:lg:my-20']);
        const busyEditor = makeEditor([writing]);
        busyEditor.__voodbuilderTwStyleApplying = true;

        expect(healLegacyNeutralThemeClassesForComponent(busyEditor, writing)).toBe(0);
        expect(writing.getClasses()).toEqual(['p-6', 'dark:lg:my-20']);
        expect(busyEditor.__voodbuilderTwStyleApplying).toBe(true);
    });

    it('surfaces the healed value in the margin/padding boxes', () => {
        const component = makeComponent(['dark:lg:m-20']);
        const editor = makeEditor([component]);
        const sector = makeSpacingRoot();

        globalThis.document = { activeElement: null };

        syncSpacingBox(sector, component, {}, editor);

        const marginTop = sector.querySelector(
            'input[data-voodbuilder-spacing-kind="margin"][data-voodbuilder-spacing-side="t"]',
        );

        expect(marginTop.value).toBe('20');
        expect(marginTop.classList.contains('is-set')).toBe(true);
        expect(marginTop.classList.contains('is-inherited')).toBe(false);

        // The dot marks the value as authored at this breakpoint — the author can see
        // that something is set here and rewrite it into a theme-neutral class.
        expect(sector.querySelector('[data-voodbuilder-spacing-box="margin"]').dot.hidden).toBe(false);
    });
});

/**
 * Minimal stand-in for the `.gjs-sm-sectors` subtree `syncSpacingBox` walks.
 */
function makeSpacingRoot() {
    const inputs = new Map();

    const makeInput = (kind, side) => inputStub(`${kind}.${side}`);

    const blocks = ['margin', 'padding'].map((kind) => {
        const sides = ['t', 'r', 'b', 'l'].map((side) => {
            const input = makeInput(kind, side);
            inputs.set(`${kind}:${side}`, input);

            return input;
        });
        const block = {
            dot: { hidden: true },
            dataset: { linkPref: 'all', link: 'all' },
            querySelectorAll: (selector) => {
                if (selector === '[data-voodbuilder-spacing-link]') {
                    return [
                        linkStub('independent'),
                        linkStub('opposites'),
                        linkStub('all'),
                    ];
                }

                if (selector === 'input[data-voodbuilder-spacing-kind]') {
                    return sides;
                }

                return [];
            },
            querySelector: (selector) => {
                const sideMatch = selector.match(/data-voodbuilder-spacing-side="(\w)"/);

                if (selector.includes('spacing-dot')) {
                    return block.dot;
                }

                if (sideMatch) {
                    return inputs.get(`${kind}:${sideMatch[1]}`) ?? null;
                }

                return null;
            },
        };

        return block;
    });

    return {
        querySelector: (selector) => {
            const blockMatch = selector.match(/data-voodbuilder-spacing-box="(\w+)"/);
            const sideMatch = selector.match(/data-voodbuilder-spacing-side="(\w)"/);
            const kindMatch = selector.match(/data-voodbuilder-spacing-kind="(\w+)"/);

            if (blockMatch) {
                return blocks[blockMatch[1] === 'margin' ? 0 : 1] ?? null;
            }

            if (sideMatch && kindMatch) {
                return inputs.get(`${kindMatch[1]}:${sideMatch[1]}`) ?? null;
            }

            return null;
        },
    };
}

function inputStub(id) {
    const classes = new Set();

    return {
        id,
        value: '',
        title: '',
        dataset: {},
        classList: {
            toggle: (name, on) => {
                if (on) {
                    classes.add(name);
                } else {
                    classes.delete(name);
                }
            },
            contains: (name) => classes.has(name),
            add: (name) => classes.add(name),
            remove: (name) => classes.delete(name),
        },
        getAttribute: (name) => (name === 'aria-label' ? id : ''),
        setAttribute: () => {},
        removeAttribute: () => {},
    };
}

function linkStub(mode) {
    const classes = new Set();

    return {
        dataset: {},
        classList: {
            toggle: () => {},
            contains: () => false,
        },
        getAttribute: (name) => (name === 'data-voodbuilder-spacing-link' ? mode : null),
        setAttribute: () => {},
        addEventListener: () => {},
        _classes: classes,
    };
}

describe('Compito B — a neutral dark: never survives Save', () => {
    it('strips neutral dark: utilities from serialized class attributes', () => {
        const html = '<section class="p-6 dark:lg:my-20 dark:bg-red-500">'
            + '<div class="dark:text-lg dark:md:p-4">x</div>'
            + '</section>';

        const healed = healNonThemeScopedThemeClassesInHtml(html);

        expect(healed).toContain('lg:my-20');
        expect(healed).not.toContain('dark:lg:my-20');
        expect(healed).not.toContain('dark:text-lg');
        expect(healed).not.toContain('dark:md:p-4');
        // The dark paint stays: only neutral groups are healed.
        expect(healed).toContain('dark:bg-red-500');
    });

    it('leaves the html untouched when there is nothing to heal', () => {
        const html = '<section class="p-6 lg:my-20 dark:bg-red-500">x</section>';

        expect(healNonThemeScopedThemeClassesInHtml(html)).toBe(html);
        expect(healNonThemeScopedThemeClassesInHtml('')).toBe('');
    });

    it('heals the read-only Save payload (mutate: false) too', () => {
        const dirty = '<section data-voodbuilder-block="demo_pages_explorer" '
            + 'class="p-6 voodbuilder-editor-section dark:lg:my-20"><p>x</p></section>';
        const payload = buildPayload(makePayloadEditor(dirty), { mutate: false });

        expect(payload.html).toContain('lg:my-20');
        expect(payload.html).not.toContain('dark:lg:my-20');
    });
});

describe('Compito C — the healed class is the class that gets compiled', () => {
    const dirty = '<section data-voodbuilder-block="demo_pages_explorer" '
        + 'class="p-6 voodbuilder-editor-section dark:lg:my-20">x</section>';

    it('serializes the tree after the heal (mutate Save)', () => {
        const component = makeComponent(['p-6', 'dark:lg:my-20']);
        const editor = makePayloadEditor(dirty, { treeComponent: component, htmlFromTree: true });

        const payload = buildPayload(editor, { mutate: true });

        expect(component.getClasses()).toEqual(['p-6', 'lg:my-20']);
        expect(payload.html).toContain('lg:my-20');
        expect(payload.html).not.toContain('dark:lg:my-20');
    });

    it('ships the same class list on both Save paths, so html and compiled css agree', () => {
        const tree = () => makeComponent(['p-6', 'dark:lg:my-20']);
        const readonly = buildPayload(
            makePayloadEditor(dirty, { treeComponent: tree(), htmlFromTree: true }),
            { mutate: false },
        );
        const mutated = buildPayload(
            makePayloadEditor(dirty, { treeComponent: tree(), htmlFromTree: true }),
            { mutate: true },
        );

        // The read-only Save skipped the tree heal before: it shipped `dark:lg:my-20`
        // while the mutate Save shipped `lg:my-20`, and the CSS followed whichever
        // class list actually arrived — the light margin was lost.
        expect(readonly.html).toBe(mutated.html);
        expect(readonly.html).toContain('lg:my-20');
        expect(readonly.html).not.toContain('dark:lg:my-20');
    });
});

/**
 * The load-time heal is what makes a "just re-save this page" fix real: a Save with no
 * other edit compares the payload hash against the seeded baseline, so a tree left alone
 * would send no request and the broken page would stay broken in the database.
 */
describe('Compito D — the load heal makes the first Save persist the fix', () => {
    it('reports the heal and rewrites the tree so a clean Save is not a no-op', () => {
        const component = makeComponent(['p-6', 'dark:lg:my-20']);
        const editor = makePayloadEditor('', { treeComponent: component });

        expect(healNonThemeScopedThemeClassesForExport(editor)).toBeGreaterThan(0);
        expect(component.getClasses()).toEqual(['p-6', 'lg:my-20']);
    });

    it('stays a no-op on an already-healed page, so the page is not falsely dirty', () => {
        const component = makeComponent(['p-6', 'lg:my-20', 'dark:bg-red-500']);
        const editor = makePayloadEditor('', { treeComponent: component });

        expect(healNonThemeScopedThemeClassesForExport(editor)).toBe(0);
        // The dark background paint is legitimate and must survive.
        expect(component.getClasses()).toEqual(['p-6', 'lg:my-20', 'dark:bg-red-500']);
    });
});

/**
 * Minimal editor for `buildPayload`: only the surface that path touches.
 */
function makePayloadEditor(html, options = {}) {
    const component = options.treeComponent ?? null;
    const wrapper = {
        onAll: (callback) => {
            if (component) {
                callback(component);
            }
        },
        components: () => (component ? [component] : []),
        find: () => [],
        findType: () => [],
        getId: () => 'wrapper',
        getAttributes: () => ({}),
        addAttributes: () => {},
        setAttributes: () => {},
        removeAttributes: () => {},
        getClasses: () => [],
        getStyle: () => ({}),
        parent: () => null,
        is: () => false,
        get: () => '',
        getEl: () => null,
    };

    return {
        __voodbuilderChromeShellMode: false,
        __voodbuilderChromeLayoutMode: false,
        getWrapper: () => wrapper,
        getHtml: () => {
            if (options.htmlFromTree && component) {
                return `<section class="${component.getClasses().join(' ')}">x</section>`;
            }

            return html;
        },
        getJs: () => '',
        getSelected: () => null,
        getCss: () => '',
        Css: {
            getAll: () => [],
            getIdRule: () => null,
        },
        DomComponents: { getType: () => null },
        setCss: () => {},
        setJs: () => {},
        trigger: () => {},
        on: () => {},
        off: () => {},
        UndoManager: null,
        StyleManager: { select: () => {} },
        Devices: { getSelected: () => ({ get: () => 'desktop' }) },
        getDevice: () => 'desktop',
    };
}
