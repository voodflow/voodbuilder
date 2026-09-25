/**
 * Style panel · Spacing sector (margin / padding box, link modes, scale popover).
 * Split out of style-tailwind-panel.js; panel-owned helpers are injected through
 * configureSpacingPanel() to avoid a circular import.
 */

import {
    MARGIN_B_OPTIONS,
    MARGIN_L_OPTIONS,
    MARGIN_OPTIONS,
    MARGIN_R_OPTIONS,
    MARGIN_T_OPTIONS,
    MARGIN_X_OPTIONS,
    MARGIN_Y_OPTIONS,
    PADDING_B_OPTIONS,
    PADDING_L_OPTIONS,
    PADDING_OPTIONS,
    PADDING_R_OPTIONS,
    PADDING_T_OPTIONS,
    PADDING_X_OPTIONS,
    PADDING_Y_OPTIONS,
    SPACING_SCALE,
    componentClassList,
} from './style-tailwind-class-groups.js';
import {
    currentStyleVariantPrefix,
    resolveGroupValueAtBreakpoint,
    resolveGroupValueExact,
    stripVariantPrefixes,
} from './style-tailwind-breakpoints.js';
/** @type {{ applyGroup: Function, resolveStyleGroup: Function, syncSelectsFromComponent: Function }} */
const panel = {
    applyGroup: () => {},
    resolveStyleGroup: () => '',
    syncSelectsFromComponent: () => {},
};

/**
 * @param {{ applyGroup: Function, resolveStyleGroup: Function, syncSelectsFromComponent: Function }} helpers
 */
export function configureSpacingPanel(helpers) {
    Object.assign(panel, helpers);
}

const applyGroup = (...args) => panel.applyGroup(...args);
const resolveStyleGroup = (...args) => panel.resolveStyleGroup(...args);
const syncSelectsFromComponent = (...args) => panel.syncSelectsFromComponent(...args);

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escapeAttr(value) {
    return escapeHtml(value).replace(/'/g, '&#39;');
}

function spacingTokenFromClass(value) {
    if (! value) {
        return '';
    }

    return stripVariantPrefixes(value).replace(/^(m|p|mt|mr|mb|ml|pt|pr|pb|pl|mx|my|px|py)-/, '') || '';
}

function spacingSideCellHtml(kind, side, title, scaleLabel) {
    return `
        <div class="voodbuilder-editor-spacing-cross__cell voodbuilder-editor-spacing-cross__cell--${side}">
            <input
                type="text"
                class="voodbuilder-editor-spacing-cross__input"
                data-voodbuilder-spacing-kind="${kind}"
                data-voodbuilder-spacing-side="${side}"
                inputmode="decimal"
                autocomplete="off"
                spellcheck="false"
                placeholder="—"
                title="${escapeAttr(title)}"
                aria-label="${escapeAttr(title)}"
            />
            <button
                type="button"
                class="voodbuilder-editor-spacing-cross__scale"
                data-voodbuilder-spacing-scale="${kind}"
                data-voodbuilder-spacing-side="${side}"
                title="${escapeAttr(scaleLabel)}"
                aria-label="${escapeAttr(scaleLabel)}"
            >tw</button>
        </div>
    `;
}

function spacingBlockHtml(kind, label, labels) {
    const linkGroup = labels.classStyleSpacingLinkSides ?? 'Link sides';
    const linkIndependent = labels.classStyleSpacingLinkIndependent ?? 'Independent sides';
    const linkOpposites = labels.classStyleSpacingLinkOpposites ?? 'Opposites linked';
    const linkAll = labels.classStyleSpacingLinkAll ?? 'All sides linked';
    const scaleLabel = labels.classStyleSpacingScale ?? 'Tailwind scale';

    return `
        <div class="voodbuilder-editor-spacing-block" data-voodbuilder-spacing-box="${kind}" data-link="all">
            <div class="voodbuilder-editor-spacing-block__head">
                <span class="voodbuilder-editor-spacing-block__label">${escapeHtml(label)}</span>
                <span class="voodbuilder-editor-spacing-block__dot" data-voodbuilder-spacing-dot hidden title="${escapeAttr(labels.classStyleAuthoredHint ?? 'Value set')}" aria-hidden="true"></span>
                <div class="voodbuilder-editor-spacing-block__links" role="group" aria-label="${escapeAttr(linkGroup)}">
                    <button type="button" class="voodbuilder-editor-spacing-block__link" data-voodbuilder-spacing-link="independent" title="${escapeAttr(linkIndependent)}" aria-pressed="false">
                        <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4.5 3.5a2 2 0 0 1 2.8 0l.7.7-.7.7-.7-.7a1 1 0 1 0-1.4 1.4l.7.7-.7.7-.7-.7a2 2 0 0 1 0-2.8zm7 7a2 2 0 0 1-2.8 0l-.7-.7.7-.7.7.7a1 1 0 1 0 1.4-1.4l-.7-.7.7-.7.7.7a2 2 0 0 1 0 2.8zM6.2 8.5l1.3-1.3.7.7-1.3 1.3-.7-.7zm2.6-2.6l1.3-1.3.7.7-1.3 1.3-.7-.7z"/></svg>
                    </button>
                    <button type="button" class="voodbuilder-editor-spacing-block__link" data-voodbuilder-spacing-link="opposites" title="${escapeAttr(linkOpposites)}" aria-pressed="false">
                        <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4 2h8v2H4V2zm0 10h8v2H4v-2zM2.5 6.5h3v3h-3v-3zm8 0h3v3h-3v-3z"/></svg>
                    </button>
                    <button type="button" class="voodbuilder-editor-spacing-block__link is-active" data-voodbuilder-spacing-link="all" title="${escapeAttr(linkAll)}" aria-pressed="true">
                        <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4.5 2.5a2.5 2.5 0 0 1 3.5 0l.7.7-.7.7a1.5 1.5 0 1 0 0 2.1l.7.7-.7.7a2.5 2.5 0 1 1-3.5-3.5l.7-.7-.7-.7zm7 7a2.5 2.5 0 0 1-3.5 0l-.7-.7.7-.7a1.5 1.5 0 1 0 0-2.1l-.7-.7.7-.7a2.5 2.5 0 1 1 3.5 3.5l-.7.7.7.7z"/></svg>
                    </button>
                </div>
            </div>
            <div class="voodbuilder-editor-spacing-cross" data-voodbuilder-spacing-cross="${kind}">
                ${spacingSideCellHtml(kind, 't', `${label} top`, scaleLabel)}
                ${spacingSideCellHtml(kind, 'l', `${label} left`, scaleLabel)}
                <div class="voodbuilder-editor-spacing-cross__core" aria-hidden="true"></div>
                ${spacingSideCellHtml(kind, 'r', `${label} right`, scaleLabel)}
                ${spacingSideCellHtml(kind, 'b', `${label} bottom`, scaleLabel)}
            </div>
        </div>
    `;
}

export function spacingBoxHtml(labels) {
    return `
        <div class="voodbuilder-editor-spacing" data-voodbuilder-spacing>
            ${spacingBlockHtml('margin', labels.classStyleMargin ?? 'Margin', labels)}
            ${spacingBlockHtml('padding', labels.classStylePadding ?? 'Padding', labels)}
        </div>
    `;
}

const SPACING_KIND = {
    margin: {
        all: MARGIN_OPTIONS,
        x: MARGIN_X_OPTIONS,
        y: MARGIN_Y_OPTIONS,
        t: MARGIN_T_OPTIONS,
        r: MARGIN_R_OPTIONS,
        b: MARGIN_B_OPTIONS,
        l: MARGIN_L_OPTIONS,
        prefix: { all: 'm', x: 'mx', y: 'my', t: 'mt', r: 'mr', b: 'mb', l: 'ml' },
        group: { all: 'margin', x: 'margin-x', y: 'margin-y', t: 'margin-t', r: 'margin-r', b: 'margin-b', l: 'margin-l' },
    },
    padding: {
        all: PADDING_OPTIONS,
        x: PADDING_X_OPTIONS,
        y: PADDING_Y_OPTIONS,
        t: PADDING_T_OPTIONS,
        r: PADDING_R_OPTIONS,
        b: PADDING_B_OPTIONS,
        l: PADDING_L_OPTIONS,
        prefix: { all: 'p', x: 'px', y: 'py', t: 'pt', r: 'pr', b: 'pb', l: 'pl' },
        group: { all: 'padding', x: 'padding-x', y: 'padding-y', t: 'padding-t', r: 'padding-r', b: 'padding-b', l: 'padding-l' },
    },
};

const SPACING_SCALE_SET = new Set([...SPACING_SCALE, 'auto']);

function normalizeSpacingToken(raw) {
    let token = String(raw ?? '').trim().toLowerCase();

    if (token === '' || token === '—' || token === '-') {
        return '';
    }

    token = token.replace(/^(m|p|mt|mr|mb|ml|pt|pr|pb|pl|mx|my|px|py)-/, '');
    // Authors often type CSS units; Spacing is a Tailwind scale, so strip them.
    token = token.replace(/(?:px|rem|em|%)$/i, '');

    if (! SPACING_SCALE_SET.has(token)) {
        return null;
    }

    return token;
}

function spacingClass(prefix, token) {
    if (token === '' || token == null) {
        return '';
    }

    return `${prefix}-${token}`;
}

export function resolveSpacingState(kind, classes, prefix = '') {
    const cfg = SPACING_KIND[kind];

    if (! cfg) {
        return { link: 'independent', sides: { t: '', r: '', b: '', l: '' }, hasValue: false };
    }

    // Prefer exact utilities at this breakpoint so base `p-4` does not hide `md:py-12`.
    const allExact = resolveGroupValueExact(classes, cfg.all, prefix);
    const xExact = resolveGroupValueExact(classes, cfg.x, prefix);
    const yExact = resolveGroupValueExact(classes, cfg.y, prefix);
    const tExact = resolveGroupValueExact(classes, cfg.t, prefix);
    const rExact = resolveGroupValueExact(classes, cfg.r, prefix);
    const bExact = resolveGroupValueExact(classes, cfg.b, prefix);
    const lExact = resolveGroupValueExact(classes, cfg.l, prefix);
    const hasExact = Boolean(allExact || xExact || yExact || tExact || rExact || bExact || lExact);

    const all = hasExact
        ? allExact
        : resolveGroupValueAtBreakpoint(classes, cfg.all, prefix);

    if (all) {
        const token = spacingTokenFromClass(all);

        return {
            link: 'all',
            sides: { t: token, r: token, b: token, l: token },
            hasValue: Boolean(token),
        };
    }

    const x = hasExact ? xExact : resolveGroupValueAtBreakpoint(classes, cfg.x, prefix);
    const y = hasExact ? yExact : resolveGroupValueAtBreakpoint(classes, cfg.y, prefix);
    const tSide = hasExact ? tExact : resolveGroupValueAtBreakpoint(classes, cfg.t, prefix);
    const rSide = hasExact ? rExact : resolveGroupValueAtBreakpoint(classes, cfg.r, prefix);
    const bSide = hasExact ? bExact : resolveGroupValueAtBreakpoint(classes, cfg.b, prefix);
    const lSide = hasExact ? lExact : resolveGroupValueAtBreakpoint(classes, cfg.l, prefix);

    const sides = {
        t: spacingTokenFromClass(tSide) || spacingTokenFromClass(y),
        r: spacingTokenFromClass(rSide) || spacingTokenFromClass(x),
        b: spacingTokenFromClass(bSide) || spacingTokenFromClass(y),
        l: spacingTokenFromClass(lSide) || spacingTokenFromClass(x),
    };

    const hasAxes = Boolean(x || y);
    const hasSides = Boolean(tSide || rSide || bSide || lSide);
    let link = 'independent';

    if (hasAxes && ! hasSides) {
        link = 'opposites';
    }

    return {
        link,
        sides,
        hasValue: Boolean(sides.t || sides.r || sides.b || sides.l),
    };
}

export function spacingGroupFor(kind, side, linkMode) {
    const cfg = SPACING_KIND[kind];

    if (! cfg) {
        return null;
    }

    if (linkMode === 'all') {
        return { groupId: cfg.group.all, prefix: cfg.prefix.all, options: cfg.all };
    }

    if (linkMode === 'opposites') {
        if (side === 't' || side === 'b') {
            return { groupId: cfg.group.y, prefix: cfg.prefix.y, options: cfg.y };
        }

        return { groupId: cfg.group.x, prefix: cfg.prefix.x, options: cfg.x };
    }

    return { groupId: cfg.group[side], prefix: cfg.prefix[side], options: cfg[side] };
}

/**
 * When editing one axis while a shorthand (p-N / m-N) is still present, expand to
 * both axes first so the untouched axis is preserved.
 */
function expandSpacingShorthandToAxes(editor, component, kind) {
    const cfg = SPACING_KIND[kind];

    if (! cfg || ! component) {
        return false;
    }

    const all = resolveStyleGroup(componentClassList(component), cfg.all, editor);

    if (! all) {
        return false;
    }

    const token = spacingTokenFromClass(all);
    applyGroup(editor, component, cfg.group.y, spacingClass(cfg.prefix.y, token));
    applyGroup(editor, component, cfg.group.x, spacingClass(cfg.prefix.x, token));

    return true;
}

export function applySpacingToken(editor, component, kind, side, rawToken, linkMode) {
    const token = normalizeSpacingToken(rawToken);

    if (token === null) {
        return false;
    }

    if (linkMode === 'opposites') {
        expandSpacingShorthandToAxes(editor, component, kind);
    }

    const target = spacingGroupFor(kind, side, linkMode);

    if (! target) {
        return false;
    }

    applyGroup(editor, component, target.groupId, spacingClass(target.prefix, token));

    return true;
}

export function setSpacingLinkMode(editor, component, kind, nextLink) {
    const bp = currentStyleVariantPrefix(editor);
    const state = resolveSpacingState(kind, componentClassList(component), bp);
    const { sides } = state;
    const token = sides.t || sides.r || sides.b || sides.l || '';

    if (nextLink === 'all') {
        applySpacingToken(editor, component, kind, 't', token, 'all');

        return;
    }

    if (nextLink === 'opposites') {
        // From shorthand: keep equal axes (py+px / my+mx). From independent sides:
        // map T/B → Y and L/R → X without copying a token across axes (that made
        // opposites feel identical to "all sides").
        if (state.link === 'all') {
            applySpacingToken(editor, component, kind, 't', token, 'opposites');
            applySpacingToken(editor, component, kind, 'l', token, 'opposites');
        } else {
            applySpacingToken(editor, component, kind, 't', sides.t || sides.b || '', 'opposites');
            applySpacingToken(editor, component, kind, 'l', sides.l || sides.r || '', 'opposites');
        }

        return;
    }

    // Expand to four independent sides.
    for (const side of ['t', 'r', 'b', 'l']) {
        applySpacingToken(editor, component, kind, side, sides[side] || '', 'independent');
    }
}

function oppositeSpacingSide(side) {
    if (side === 't') {
        return 'b';
    }

    if (side === 'b') {
        return 't';
    }

    if (side === 'l') {
        return 'r';
    }

    if (side === 'r') {
        return 'l';
    }

    return null;
}

function mirrorLinkedSpacingInputs(block, side, value, linkMode) {
    if (! block || ! side) {
        return;
    }

    const write = (targetSide) => {
        const input = block.querySelector(`input[data-voodbuilder-spacing-side="${targetSide}"]`);

        if (! input || document.activeElement === input) {
            return;
        }

        input.value = value;
        input.classList.toggle('is-set', Boolean(String(value ?? '').trim()));
    };

    if (linkMode === 'all') {
        for (const targetSide of ['t', 'r', 'b', 'l']) {
            if (targetSide !== side) {
                write(targetSide);
            }
        }

        return;
    }

    if (linkMode === 'opposites') {
        const pair = oppositeSpacingSide(side);

        if (pair) {
            write(pair);
        }
    }
}

export function syncSpacingBox(root, component, options = {}, editor = null) {
    const resetLinkPref = options.resetLinkPref === true;
    const bp = currentStyleVariantPrefix(editor);
    const inheritedHint = options.inheritedHint
        ?? 'Inherited from a smaller viewport — change to override here';

    for (const kind of ['margin', 'padding']) {
        const block = root.querySelector(`[data-voodbuilder-spacing-box="${kind}"]`);

        if (! block) {
            continue;
        }

        const classes = componentClassList(component);
        const state = resolveSpacingState(kind, classes, bp);
        const cfg = SPACING_KIND[kind];

        if (resetLinkPref || ! block.dataset.linkPref) {
            block.dataset.linkPref = state.link;
        }

        // User-chosen link mode wins over class inference so "opposites" does not
        // snap back to "all" when both axes still share the same token.
        const link = block.dataset.linkPref || state.link;
        block.dataset.link = link;

        for (const button of block.querySelectorAll('[data-voodbuilder-spacing-link]')) {
            const active = button.getAttribute('data-voodbuilder-spacing-link') === link;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        }

        const dot = block.querySelector('[data-voodbuilder-spacing-dot]');

        if (dot) {
            // Dot = authored at this breakpoint (not merely cascaded).
            const exactHere = Boolean(
                resolveGroupValueExact(classes, cfg.all, bp)
                || resolveGroupValueExact(classes, cfg.x, bp)
                || resolveGroupValueExact(classes, cfg.y, bp)
                || resolveGroupValueExact(classes, cfg.t, bp)
                || resolveGroupValueExact(classes, cfg.r, bp)
                || resolveGroupValueExact(classes, cfg.b, bp)
                || resolveGroupValueExact(classes, cfg.l, bp),
            );
            dot.hidden = ! exactHere;
        }

        for (const side of ['t', 'r', 'b', 'l']) {
            const input = block.querySelector(
                `input[data-voodbuilder-spacing-kind="${kind}"][data-voodbuilder-spacing-side="${side}"]`,
            );

            if (! input || document.activeElement === input) {
                continue;
            }

            const value = state.sides[side] || '';
            const target = spacingGroupFor(kind, side, link);
            const exactClass = target
                ? resolveGroupValueExact(classes, target.options, bp)
                : '';
            const exactToken = spacingTokenFromClass(exactClass);
            const inherited = Boolean(value) && exactToken !== value;

            input.value = value;
            input.classList.toggle('is-set', Boolean(value) && ! inherited);
            input.classList.toggle('is-inherited', inherited);
            input.title = inherited ? inheritedHint : (input.getAttribute('aria-label') || '');
        }
    }
}

let activeSpacingPopover = null;

function closeSpacingPopover() {
    if (! activeSpacingPopover) {
        return;
    }

    activeSpacingPopover.remove();
    activeSpacingPopover = null;
    document.removeEventListener('pointerdown', onSpacingPopoverOutside, true);
    document.removeEventListener('keydown', onSpacingPopoverKeydown, true);
}

function onSpacingPopoverOutside(event) {
    if (! activeSpacingPopover) {
        return;
    }

    if (
        activeSpacingPopover.contains(event.target)
        || event.target?.closest?.('[data-voodbuilder-spacing-scale]')
        || event.target?.closest?.('.voodbuilder-editor-spacing-cross__cell')
    ) {
        return;
    }

    closeSpacingPopover();
}

function onSpacingPopoverKeydown(event) {
    if (event.key === 'Escape') {
        closeSpacingPopover();
    }
}

function openSpacingScalePopover(editor, sector, anchor, kind, side, labels = {}) {
    const block = sector.querySelector(`[data-voodbuilder-spacing-box="${kind}"]`);
    const linkMode = block?.dataset.link || 'all';
    const target = spacingGroupFor(kind, side, linkMode);

    if (! target || ! (anchor instanceof HTMLElement)) {
        return;
    }

    closeSpacingPopover();

    const selected = editor.getSelected();
    const currentToken = spacingTokenFromClass(
        resolveStyleGroup(componentClassList(selected), target.options, editor),
    );

    const pop = document.createElement('div');
    pop.className = 'voodbuilder-editor-spacing-popover';
    pop.dataset.voodbuilderSpacingPopover = `${kind}-${side}`;
    pop.innerHTML = `
        <div class="voodbuilder-editor-spacing-popover__head">
            <span>${escapeHtml(labels.classStyleSpacingScale ?? 'Tailwind scale')}</span>
            <input type="search" class="voodbuilder-editor-spacing-popover__search" aria-label="${escapeAttr(labels.classStyleSpacingSearch ?? 'Search…')}" placeholder="${escapeAttr(labels.classStyleSpacingSearch ?? 'Search…')}" autocomplete="off" />
        </div>
        <div class="voodbuilder-editor-spacing-popover__grid" role="listbox" aria-label="${escapeAttr(labels.classStyleSpacingScale ?? 'Tailwind scale')}"></div>
    `;

    const grid = pop.querySelector('.voodbuilder-editor-spacing-popover__grid');
    const search = pop.querySelector('.voodbuilder-editor-spacing-popover__search');

    const render = (query = '') => {
        const needle = String(query).trim().toLowerCase();
        grid.replaceChildren();

        for (const opt of target.options) {
            const label = String(opt.label ?? opt.value ?? '');
            const token = spacingTokenFromClass(opt.value);
            const hay = `${label} ${opt.value}`.toLowerCase();

            if (needle !== '' && ! hay.includes(needle)) {
                continue;
            }

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'voodbuilder-editor-spacing-popover__opt';
            btn.role = 'option';
            btn.textContent = label || '—';
            btn.title = opt.value || 'clear';
            btn.dataset.token = token;

            if ((opt.value === '' && currentToken === '') || (token && token === currentToken)) {
                btn.classList.add('is-selected');
            }

            btn.addEventListener('click', () => {
                const component = editor.getSelected();

                if (! component) {
                    closeSpacingPopover();

                    return;
                }

                const liveLink = block?.dataset.link || linkMode;
                applySpacingToken(editor, component, kind, side, token, liveLink);
                syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
                closeSpacingPopover();
            });

            grid.appendChild(btn);
        }
    };

    render();
    search?.addEventListener('input', () => render(search.value));

    document.body.appendChild(pop);
    activeSpacingPopover = pop;

    const rect = anchor.getBoundingClientRect();
    const pad = 8;
    const width = Math.min(240, window.innerWidth - pad * 2);
    let left = rect.left + (rect.width / 2) - (width / 2);
    left = Math.max(pad, Math.min(left, window.innerWidth - width - pad));
    let top = rect.bottom + 6;

    pop.style.width = `${width}px`;
    pop.style.left = `${left}px`;
    pop.style.top = `${top}px`;

    requestAnimationFrame(() => {
        const popRect = pop.getBoundingClientRect();

        if (popRect.bottom > window.innerHeight - pad) {
            top = Math.max(pad, rect.top - popRect.height - 6);
            pop.style.top = `${top}px`;
        }

        search?.focus();
    });

    document.addEventListener('pointerdown', onSpacingPopoverOutside, true);
    document.addEventListener('keydown', onSpacingPopoverKeydown, true);
}

export function wireSpacingBoxes(editor, sector, labels = {}) {
    for (const block of sector.querySelectorAll('[data-voodbuilder-spacing-box]')) {
        const kind = block.getAttribute('data-voodbuilder-spacing-box');

        block.querySelectorAll('[data-voodbuilder-spacing-link]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const component = editor.getSelected();
                const nextLink = button.getAttribute('data-voodbuilder-spacing-link') || 'all';

                if (! component) {
                    return;
                }

                block.dataset.linkPref = nextLink;
                block.dataset.link = nextLink;
                setSpacingLinkMode(editor, component, kind, nextLink);
                syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
            });
        });

        block.querySelectorAll('input[data-voodbuilder-spacing-kind]').forEach((input) => {
            let liveTimer = null;

            const commit = () => {
                window.clearTimeout(liveTimer);
                liveTimer = null;

                const component = editor.getSelected();
                const side = input.getAttribute('data-voodbuilder-spacing-side');
                const linkMode = block.dataset.link || 'all';

                if (! component || ! side) {
                    return;
                }

                const ok = applySpacingToken(editor, component, kind, side, input.value, linkMode);

                if (! ok) {
                    syncSpacingBox(sector.closest('.gjs-sm-sectors') ?? sector, component, {}, editor);
                    input.classList.add('is-invalid');

                    return;
                }

                input.classList.remove('is-invalid');
                syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
            };

            input.addEventListener('input', () => {
                const linkMode = block.dataset.link || 'all';
                const side = input.getAttribute('data-voodbuilder-spacing-side');
                mirrorLinkedSpacingInputs(block, side, input.value, linkMode);

                // Live-apply valid scale tokens so authors need not blur first.
                window.clearTimeout(liveTimer);
                liveTimer = window.setTimeout(() => {
                    if (normalizeSpacingToken(input.value) === null) {
                        return;
                    }

                    commit();
                }, 280);
            });

            input.addEventListener('change', commit);

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    commit();
                    input.blur();
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    window.clearTimeout(liveTimer);
                    const component = editor.getSelected();
                    syncSpacingBox(sector.closest('.gjs-sm-sectors') ?? sector, component, {}, editor);
                    input.blur();
                }
            });

            input.addEventListener('blur', (event) => {
                if (event.relatedTarget?.closest?.('[data-voodbuilder-spacing-scale]')) {
                    return;
                }

                commit();
            });
        });

        block.querySelectorAll('[data-voodbuilder-spacing-scale]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                openSpacingScalePopover(
                    editor,
                    sector,
                    button,
                    kind,
                    button.getAttribute('data-voodbuilder-spacing-side'),
                    labels,
                );
            });
        });
    }
}
