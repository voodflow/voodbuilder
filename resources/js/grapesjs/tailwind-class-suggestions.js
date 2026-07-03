/**
 * Tailwind utility autocomplete for the GrapesJS class manager (selector panel).
 */

const SUGGESTION_LIST_ID = 'voodbuilder-tailwind-class-suggestions';

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

function ensureSuggestionDatalist() {
    let datalist = document.getElementById(SUGGESTION_LIST_ID);

    if (datalist) {
        return datalist;
    }

    datalist = document.createElement('datalist');
    datalist.id = SUGGESTION_LIST_ID;

    for (const className of COMMON_TAILWIND_CLASSES) {
        const option = document.createElement('option');
        option.value = className;
        datalist.appendChild(option);
    }

    document.body.appendChild(datalist);

    return datalist;
}

function pageCompiledClassNames(editor) {
    const names = new Set();

    editor.getWrapper()?.find?.('*')?.forEach?.((component) => {
        for (const className of component.getClasses?.() ?? []) {
            if (className) {
                names.add(className);
            }
        }
    });

    return names;
}

function wireClassInput(editor, input, hintEl, labels = {}) {
    if (! input || input.dataset.voodbuilderTwSuggest === '1') {
        return;
    }

    input.dataset.voodbuilderTwSuggest = '1';
    input.setAttribute('list', SUGGESTION_LIST_ID);
    input.setAttribute('autocomplete', 'off');
    input.placeholder = labels.classInputPlaceholder ?? 'Add Tailwind class…';

    const refreshHint = () => {
        if (! hintEl) {
            return;
        }

        const value = String(input.value ?? '').trim();

        if (! value) {
            hintEl.hidden = true;

            return;
        }

        const compiled = pageCompiledClassNames(editor);

        if (compiled.has(value)) {
            hintEl.hidden = true;

            return;
        }

        hintEl.hidden = false;
        hintEl.textContent = labels.classPendingCompile
            ?? 'This class is not on the page yet. It will be compiled when you save if used in component markup.';
    };

    input.addEventListener('input', refreshHint);
    input.addEventListener('change', refreshHint);
    refreshHint();
}

export function registerTailwindClassSuggestions(editor, options = {}) {
    const mount = options.mount;
    const labels = options.labels ?? {};

    if (! mount || editor.__voodbuilderTailwindClassSuggestionsRegistered) {
        return;
    }

    editor.__voodbuilderTailwindClassSuggestionsRegistered = true;
    ensureSuggestionDatalist();

    let hintEl = mount.querySelector('[data-voodbuilder-class-suggest-hint]');

    if (! hintEl) {
        hintEl = document.createElement('p');
        hintEl.className = 'voodbuilder-gjs-class-suggest-hint';
        hintEl.dataset.voodbuilderClassSuggestHint = '';
        hintEl.hidden = true;
        mount.appendChild(hintEl);
    }

    const scan = () => {
        const input = mount.querySelector('[data-input]');

        wireClassInput(editor, input, hintEl, labels);
    };

    const observer = new MutationObserver(() => scan());
    observer.observe(mount, { childList: true, subtree: true });
    scan();

    editor.on('component:update', scan);
    editor.on('component:styleUpdate', scan);
}
