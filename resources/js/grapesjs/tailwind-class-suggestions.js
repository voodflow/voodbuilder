/**
 * Tailwind utility autocomplete for the GrapesJS class manager (selector panel).
 */

import { pageCssCoversClass } from './page-tailwind-autobuild.js';
import { safeFindComponents } from './tailwind-visual-style.js';

const SUGGEST_LIST_ATTR = 'data-voodbuilder-class-suggest-list';

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

function wireClassInput(editor, input, hintEl, labels = {}) {
    if (! input || input.dataset.voodbuilderTwSuggest === '1') {
        return;
    }

    input.dataset.voodbuilderTwSuggest = '1';
    input.setAttribute('autocomplete', 'off');
    input.placeholder = labels.classInputPlaceholder ?? 'Add Tailwind class…';

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
        const compiled = pageCompiledClassNames(editor);
        const pool = allSuggestionPool(editor);
        const suggestions = filterSuggestions(pool, value);

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
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && list) {
            list.hidden = true;
        }
    });

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

    const scan = () => {
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
