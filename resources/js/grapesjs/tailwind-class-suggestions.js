/**
 * Tailwind utility autocomplete for the GrapesJS class manager (selector panel).
 * Also: Copy all classes, multi-class paste, and animation helpers (Shuffle-style).
 */

import { componentClassString, copyTextToClipboard, splitClassTokens } from './clipboard.js';
import { choiceDialog } from './editor-dialog.js';
import { lucideIcon } from './editor-icons.js';
import { pageCssCoversClass } from './page-tailwind-autobuild.js';
import { safeFindComponents } from './tailwind-visual-style.js';

const SUGGEST_LIST_ATTR = 'data-voodbuilder-class-suggest-list';

const ANIMATION_CLASSES = [
    'animate-none',
    'animate-spin',
    'animate-ping',
    'animate-pulse',
    'animate-bounce',
];

const COMMON_TAILWIND_CLASSES = [
    'container', 'mx-auto', 'px-4', 'px-6', 'px-8', 'py-4', 'py-6', 'py-8', 'py-12', 'py-16', 'py-24',
    'flex', 'inline-flex', 'grid', 'block', 'inline-block', 'hidden', 'sr-only',
    'flex-col', 'flex-row', 'flex-wrap', 'items-center', 'items-start', 'items-end', 'justify-center',
    'justify-between', 'justify-start', 'justify-end', 'gap-2', 'gap-3', 'gap-4', 'gap-6', 'gap-8',
    'grid-cols-1', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4', 'col-span-2', 'col-span-full',
    'w-full', 'w-auto', 'max-w-xl', 'max-w-2xl', 'max-w-4xl', 'max-w-6xl', 'max-w-7xl', 'min-h-screen',
    'h-full', 'aspect-video', 'aspect-square',
    'text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl', 'text-2xl', 'text-3xl', 'text-4xl', 'text-5xl',
    'font-normal', 'font-medium', 'font-semibold', 'font-bold', 'uppercase', 'lowercase', 'capitalize',
    'tracking-tight', 'tracking-wide', 'leading-tight', 'leading-relaxed', 'text-center', 'text-left', 'text-right',
    'text-white', 'text-black', 'text-primary', 'text-foreground', 'text-muted-foreground',
    'bg-white', 'bg-black', 'bg-primary', 'bg-secondary', 'bg-card', 'bg-layer', 'bg-surface', 'bg-transparent',
    'rounded', 'rounded-md', 'rounded-lg', 'rounded-xl', 'rounded-2xl', 'rounded-full',
    'border', 'border-0', 'border-2', 'border-dashed', 'border-primary', 'border-divider',
    'shadow', 'shadow-sm', 'shadow-md', 'shadow-lg', 'shadow-none',
    'p-0', 'p-2', 'p-4', 'p-6', 'p-8', 'px-2', 'px-4', 'py-2', 'py-4', 'm-0', 'm-auto', 'mt-4', 'mb-4', 'my-4',
    'space-y-2', 'space-y-4', 'space-y-6', 'space-x-2', 'space-x-4',
    'divide-y', 'divide-x', 'overflow-hidden', 'overflow-auto', 'truncate', 'line-clamp-2', 'line-clamp-3',
    'object-cover', 'object-contain', 'opacity-0', 'opacity-50', 'opacity-100',
    'transition', 'duration-200', 'duration-300', 'ease-in-out', 'hover:opacity-80',
    ...ANIMATION_CLASSES,
    'md:flex', 'md:grid', 'md:hidden', 'md:block', 'md:grid-cols-2', 'md:grid-cols-3', 'md:px-8', 'md:py-24',
    'lg:grid-cols-3', 'lg:grid-cols-4', 'lg:px-12', 'lg:text-5xl',
    'relative', 'absolute', 'fixed', 'sticky', 'inset-0', 'top-0', 'z-10', 'z-20', 'z-50',
];

function pageCompiledClassNames(editor) {
    const names = new Set();

    for (const component of safeFindComponents(editor.getWrapper?.(), '*')) {
        for (const className of component.getClasses?.() ?? []) {
            if (className) {
                names.add(className);
            }
        }
    }

    return names;
}

const COMMON_TAILWIND_SET = new Set(COMMON_TAILWIND_CLASSES);

function allSuggestionPool(editor) {
    const pool = new Set(COMMON_TAILWIND_CLASSES);

    for (const className of pageCompiledClassNames(editor)) {
        pool.add(className);
    }

    return [...pool];
}

function filterSuggestions(pool, query) {
    const normalized = String(query ?? '').trim().toLowerCase();

    if (normalized.length < 1) {
        return [];
    }

    const prefixMatches = [];
    const containsMatches = [];

    for (const className of pool) {
        const lower = className.toLowerCase();

        if (lower.startsWith(normalized)) {
            prefixMatches.push(className);
        } else if (lower.includes(normalized)) {
            containsMatches.push(className);
        }
    }

    const rank = (className) => {
        let score = 0;

        if (COMMON_TAILWIND_SET.has(className)) {
            score -= 100;
        }

        if (className.startsWith('-') && ! normalized.startsWith('-')) {
            score += 40;
        }

        if (className.startsWith(normalized)) {
            score -= 20;
        }

        return score;
    };

    const sortMatches = (left, right) => {
        const scoreDiff = rank(left) - rank(right);

        if (scoreDiff !== 0) {
            return scoreDiff;
        }

        return left.localeCompare(right);
    };

    return [...prefixMatches.sort(sortMatches), ...containsMatches.sort(sortMatches)].slice(0, 12);
}

function ensureSuggestList(input) {
    const field = input.closest('.gjs-field, .clm-tags, .gjs-clm-tags') ?? input.parentElement;
    let list = field?.querySelector(`[${SUGGEST_LIST_ATTR}]`);

    if (! list && field) {
        field.classList.add('voodbuilder-gjs-class-suggest-field');
        list = document.createElement('ul');
        list.className = 'voodbuilder-gjs-class-suggest-list';
        list.setAttribute(SUGGEST_LIST_ATTR, '');
        list.hidden = true;
        field.appendChild(list);
    }

    return list;
}

function renderSuggestList(list, suggestions, compiled, onPick) {
    if (! list) {
        return;
    }

    list.replaceChildren();

    if (suggestions.length === 0) {
        list.hidden = true;

        return;
    }

    for (const className of suggestions) {
        const item = document.createElement('li');
        item.className = 'voodbuilder-gjs-class-suggest-list__item';
        item.dataset.className = className;

        const label = document.createElement('span');
        label.className = 'voodbuilder-gjs-class-suggest-list__label';
        label.textContent = className;

        const status = document.createElement('span');
        status.className = 'voodbuilder-gjs-class-suggest-list__status';
        status.textContent = compiled.has(className) ? 'on page' : 'new';

        item.append(label, status);
        item.addEventListener('mousedown', (event) => {
            event.preventDefault();
            onPick(className);
        });
        list.appendChild(item);
    }

    list.hidden = false;
}

function scheduleClassCompile(editor) {
    // Only compile when at least one class is missing from live CSS.
    editor.__voodbuilderSchedulePageCssRebuild?.(0);
}

export function addClassesToComponent(editor, component, tokens) {
    if (! component || ! Array.isArray(tokens) || tokens.length === 0) {
        return 0;
    }

    const existing = new Set(component.getClasses?.() ?? []);
    let added = 0;

    for (const token of tokens) {
        const name = String(token ?? '').trim().replace(/^\./, '');

        if (name === '' || existing.has(name)) {
            continue;
        }

        component.addClass(name);
        existing.add(name);
        added += 1;
    }

    if (added > 0) {
        scheduleClassCompile(editor);
    }

    return added;
}

export function replaceClassesOnComponent(editor, component, tokens) {
    if (! component || ! Array.isArray(tokens)) {
        return 0;
    }

    const next = [];

    for (const token of tokens) {
        const name = String(token ?? '').trim().replace(/^\./, '');

        if (name !== '' && ! next.includes(name)) {
            next.push(name);
        }
    }

    component.setClass(next);
    scheduleClassCompile(editor);

    return next.length;
}

function removeClassFromSelected(editor, className) {
    const selected = editor?.getSelected?.();
    const name = String(className ?? '').trim().replace(/^\./, '');

    if (! selected || name === '') {
        return false;
    }

    const selectors = selected.getSelectors?.();
    const selector = selectors?.find?.((item) => {
        const label = String(item?.getLabel?.() ?? item?.get?.('name') ?? item?.id ?? '');

        return label === name || label === `.${name}`;
    });

    if (selector && ! selector.get?.('protected')) {
        editor.SelectorManager?.removeSelected?.(selector);
    } else {
        selected.removeClass?.(name);
    }

    scheduleClassCompile(editor);

    return true;
}

async function applyPastedClassTokens(editor, selected, tokens, labels) {
    const existing = selected.getClasses?.() ?? [];

    if (existing.length === 0) {
        addClassesToComponent(editor, selected, tokens);
        showCopyToast(
            labels.classPasteApplied ?? 'Classes added and compiling…',
            tokens.join(' '),
        );

        return;
    }

    const choice = await choiceDialog({
        title: labels.classPasteTitle ?? 'Paste classes',
        message: labels.classPasteConflict
            ?? 'This element already has classes. Keep the existing ones and add the new ones, or replace them with the pasted set?',
        labels,
        choices: [
            {
                id: 'keep',
                label: labels.classPasteKeep ?? 'Keep existing + add new',
                primary: true,
            },
            {
                id: 'replace',
                label: labels.classPasteReplace ?? 'Replace with pasted',
            },
            {
                id: 'cancel',
                label: labels.dialogCancel ?? 'Cancel',
                ghost: true,
            },
        ],
    });

    if (choice === 'replace') {
        replaceClassesOnComponent(editor, selected, tokens);
        showCopyToast(
            labels.classPasteReplaced ?? 'Classes replaced and compiling…',
            tokens.join(' '),
        );

        return;
    }

    if (choice === 'keep') {
        addClassesToComponent(editor, selected, tokens);
        showCopyToast(
            labels.classPasteApplied ?? 'Classes added and compiling…',
            tokens.join(' '),
        );
    }
}

function showCopyToast(title, detail = '') {
    let toast = document.getElementById('voodbuilder-gjs-classes-toast');

    if (! toast) {
        toast = document.createElement('div');
        toast.id = 'voodbuilder-gjs-classes-toast';
        toast.className = 'voodbuilder-gjs-classes-toast';
        document.body.appendChild(toast);
    }

    toast.innerHTML = `
        <div class="voodbuilder-gjs-classes-toast__title">${escapeHtml(title)}</div>
        ${detail ? `<div class="voodbuilder-gjs-classes-toast__detail">${escapeHtml(detail)}</div>` : ''}
    `;
    toast.hidden = false;
    window.clearTimeout(toast._hideTimer);
    toast._hideTimer = window.setTimeout(() => {
        toast.hidden = true;
    }, 2200);
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

export async function copySelectedComponentClasses(editor, labels = {}) {
    const selected = editor?.getSelected?.();
    const text = componentClassString(selected);

    if (! text) {
        showCopyToast(labels.classCopyEmpty ?? 'No classes to copy');

        return false;
    }

    const ok = await copyTextToClipboard(text);

    showCopyToast(
        ok
            ? (labels.classCopySuccess ?? 'Classes copied to clipboard!')
            : (labels.classCopyFailed ?? 'Could not copy classes'),
        ok ? text : '',
    );

    return ok;
}

export async function copySelectedComponentAllStyles(editor, labels = {}) {
    const selected = editor?.getSelected?.();

    if (! selected) {
        showCopyToast(labels.classCopyEmpty ?? 'No classes to copy');

        return false;
    }

    const classes = componentClassString(selected);
    const style = {
        ...(selected.getStyle?.() ?? {}),
        ...(selected.getStyle?.({ inline: true }) ?? {}),
    };
    const styleParts = Object.entries(style)
        .filter(([, value]) => value != null && String(value).trim() !== '')
        .map(([property, value]) => `${property}: ${value}`);

    const chunks = [];

    if (classes) {
        chunks.push(`class="${classes}"`);
    }

    if (styleParts.length > 0) {
        chunks.push(`style="${styleParts.join('; ')}"`);
    }

    if (chunks.length === 0) {
        showCopyToast(labels.classCopyAllEmpty ?? 'Nothing to copy');

        return false;
    }

    const text = chunks.join(' ');
    const ok = await copyTextToClipboard(text);

    showCopyToast(
        ok
            ? (labels.classCopyAllSuccess ?? 'Classes + styles copied!')
            : (labels.classCopyFailed ?? 'Could not copy'),
        ok ? text : '',
    );

    return ok;
}

function ensureClassesCopyButtons(mount, editor, labels) {
    const sector = mount.closest('.voodbuilder-gjs-inspector-sector');
    const title = sector?.querySelector('.voodbuilder-gjs-inspector-sector__title');

    if (! title || title.querySelector('[data-voodbuilder-copy-classes]')) {
        return;
    }

    title.classList.add('voodbuilder-gjs-inspector-sector__title--with-actions');

    const actions = document.createElement('div');
    actions.className = 'voodbuilder-gjs-classes-copy-actions';

    const copyClassesBtn = document.createElement('button');
    copyClassesBtn.type = 'button';
    copyClassesBtn.className = 'voodbuilder-gjs-classes-copy-btn';
    copyClassesBtn.dataset.voodbuilderCopyClasses = '';
    copyClassesBtn.title = labels.classCopy ?? 'Copy classes';
    copyClassesBtn.innerHTML = `${lucideIcon('copy', 13)}<span>${labels.classCopy ?? 'Copy classes'}</span>`;
    copyClassesBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        await copySelectedComponentClasses(editor, labels);
    });

    const copyAllBtn = document.createElement('button');
    copyAllBtn.type = 'button';
    copyAllBtn.className = 'voodbuilder-gjs-classes-copy-btn voodbuilder-gjs-classes-copy-btn--all';
    copyAllBtn.dataset.voodbuilderCopyAll = '';
    copyAllBtn.title = labels.classCopyAll ?? 'Copy all';
    copyAllBtn.innerHTML = `${lucideIcon('clipboard', 13)}<span>${labels.classCopyAll ?? 'Copy all'}</span>`;
    copyAllBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        await copySelectedComponentAllStyles(editor, labels);
    });

    actions.append(copyClassesBtn, copyAllBtn);
    title.appendChild(actions);
}

function wireClassInput(editor, input, hintEl, labels = {}) {
    if (! input || input.dataset.voodbuilderTwSuggest === '1') {
        return;
    }

    input.dataset.voodbuilderTwSuggest = '1';
    input.setAttribute('autocomplete', 'off');
    input.placeholder = labels.classInputPlaceholder ?? 'Add new class…';

    const list = ensureSuggestList(input);
    const field = input.closest('.gjs-field, .clm-tags, .gjs-clm-tags') ?? input.parentElement;

    if (hintEl && field && hintEl.parentElement !== field) {
        field.appendChild(hintEl);
    }

    const refreshHint = () => {
        if (! hintEl) {
            return;
        }

        const value = String(input.value ?? '').trim();

        if (! value) {
            hintEl.hidden = true;

            return;
        }

        const tokens = splitClassTokens(value);

        if (tokens.length > 1) {
            hintEl.hidden = false;
            hintEl.textContent = labels.classPasteHint
                ?? 'Paste or Enter to add all classes and compile.';

            return;
        }

        const compiled = pageCssCoversClass(editor, value);

        if (compiled) {
            hintEl.hidden = true;

            return;
        }

        hintEl.hidden = false;
        hintEl.textContent = labels.classPendingCompile
            ?? 'Compiling for canvas preview…';
    };

    const refresh = () => {
        const value = String(input.value ?? '').trim();
        const tokens = splitClassTokens(value);
        const compiled = pageCompiledClassNames(editor);
        const pool = allSuggestionPool(editor);
        const suggestions = tokens.length > 1
            ? []
            : filterSuggestions(pool, value);

        renderSuggestList(list, suggestions, compiled, (className) => {
            input.value = className;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true, key: 'Enter' }));
            list.hidden = true;
            refreshHint();
        });

        refreshHint();
    };

    input.addEventListener('input', refresh);
    input.addEventListener('keyup', refresh);
    input.addEventListener('focus', refresh);
    input.addEventListener('blur', () => {
        window.setTimeout(() => {
            if (list) {
                list.hidden = true;
            }
        }, 140);
    });

    input.addEventListener('paste', async (event) => {
        const text = event.clipboardData?.getData('text') ?? '';
        const tokens = splitClassTokens(text);

        if (tokens.length <= 1) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const selected = editor.getSelected();

        if (! selected) {
            return;
        }

        await applyPastedClassTokens(editor, selected, tokens, labels);
        input.value = '';
        list.hidden = true;
        refreshHint();
    });

    input.addEventListener('keydown', async (event) => {
        if (event.key === 'Escape' && list) {
            list.hidden = true;

            return;
        }

        if (event.key !== 'Enter') {
            return;
        }

        const tokens = splitClassTokens(input.value);

        if (tokens.length <= 1) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const selected = editor.getSelected();

        if (selected) {
            await applyPastedClassTokens(editor, selected, tokens, labels);
            input.value = '';
        }

        list.hidden = true;
        refreshHint();
    }, true);

    refresh();
}

export function registerTailwindClassSuggestions(editor, options = {}) {
    const mount = options.mount;
    const labels = options.labels ?? {};

    if (! mount || editor.__voodbuilderTailwindClassSuggestionsRegistered) {
        return;
    }

    editor.__voodbuilderTailwindClassSuggestionsRegistered = true;

    let hintEl = mount.querySelector('[data-voodbuilder-class-suggest-hint]');

    if (! hintEl) {
        hintEl = document.createElement('p');
        hintEl.className = 'voodbuilder-gjs-class-suggest-hint';
        hintEl.dataset.voodbuilderClassSuggestHint = '';
        hintEl.hidden = true;
        mount.appendChild(hintEl);
    }

    // Fallback: ensure X on class chips always removes the class (CSS/SVG hit-testing can miss Grapes handlers).
    mount.addEventListener('pointerdown', (event) => {
        const close = event.target?.closest?.('[data-tag-remove], .gjs-clm-tag-close');

        if (! close || ! mount.contains(close)) {
            return;
        }

        const tag = close.closest('.gjs-clm-tag, .clm-tag, [class*="clm-tag"]');
        const label = tag?.querySelector?.('[data-tag-name]')?.textContent?.trim()
            ?? tag?.getAttribute?.('title')
            ?? '';

        if (! label) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        removeClassFromSelected(editor, label);
    }, true);

    const scan = () => {
        ensureClassesCopyButtons(mount, editor, labels);

        for (const input of mount.querySelectorAll('[data-input]')) {
            wireClassInput(editor, input, hintEl, labels);
        }
    };

    const observer = new MutationObserver(() => scan());
    observer.observe(mount, { childList: true, subtree: true });
    scan();

    editor.on('component:update', scan);
    editor.on('component:styleUpdate', scan);
    editor.on('voodbuilder:page-css-compiled', scan);
}
