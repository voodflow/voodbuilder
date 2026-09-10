@props([
    'canvasPreview' => false,
])

@php
    use Illuminate\Support\Facades\Route;
    use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

    $enabled = Route::has('voodbuilder.search')
        || Route::has('vtuts.index')
        || Route::has('vtuts.localized.index');
    $searchUrl = Route::has('voodbuilder.search')
        ? VoodbuilderUrls::search()
        : null;
    $suggestUrl = Route::has('voodbuilder.search.suggest')
        ? VoodbuilderUrls::searchSuggest()
        : null;
@endphp

@if ($canvasPreview)
    <div class="flex items-center" data-voodbuilder-search data-gjs-type="default" data-gjs-selectable="false">
        <button
            type="button"
            class="voodbuilder-header-icon-btn"
            data-voodbuilder-search-open
            data-gjs-type="default"
            data-gjs-selectable="false"
            aria-label="{{ __('voodbuilder::search.button') }}"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>
    </div>
@elseif ($enabled && $searchUrl)
    <div
        class="flex items-center"
        data-voodbuilder-search
        @if ($suggestUrl) data-voodbuilder-search-suggest-url="{{ $suggestUrl }}" @endif
        data-voodbuilder-search-url="{{ $searchUrl }}"
        data-voodbuilder-search-i18n="{{ e(json_encode([
            'hint' => __('voodbuilder::search.hint'),
            'recent' => __('voodbuilder::search.recent'),
            'results' => __('voodbuilder::search.suggest_results'),
            'no_results' => __('voodbuilder::search.suggest_no_results'),
            'view_all' => __('voodbuilder::search.view_all'),
            'loading' => __('voodbuilder::search.suggest_loading'),
            'select' => __('voodbuilder::search.kbd_select'),
            'navigate' => __('voodbuilder::search.kbd_navigate'),
            'close' => __('voodbuilder::search.kbd_close'),
        ], JSON_UNESCAPED_UNICODE)) }}"
    >
        <button
            type="button"
            class="voodbuilder-header-icon-btn"
            data-voodbuilder-search-open
            aria-haspopup="dialog"
            aria-controls="voodbuilder-search-dialog"
            aria-expanded="false"
            aria-label="{{ __('voodbuilder::search.button') }}"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>

        <div
            id="voodbuilder-search-dialog"
            class="fixed inset-0 z-[60] items-start justify-center px-4 pt-28 sm:px-6 sm:pt-32 md:pt-[22vh]"
            data-voodbuilder-search-dialog
            hidden
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('voodbuilder::search.button') }}"
        >
            <div class="absolute inset-0 bg-black/60" data-voodbuilder-search-close tabindex="-1"></div>
            <div class="relative z-10 flex w-full max-w-[560px] flex-col overflow-hidden rounded-xl bg-vp-bg-elv shadow-xl ring-1 ring-black/5">
                <form action="{{ $searchUrl }}" method="get" class="flex items-center gap-2 border-b border-vp-divider px-4 py-3" data-voodbuilder-search-form>
                    <label class="sr-only" for="voodbuilder-search-input">{{ __('voodbuilder::search.button') }}</label>
                    <span class="text-vp-text-3" aria-hidden="true">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input
                        id="voodbuilder-search-input"
                        type="search"
                        name="q"
                        class="min-w-0 flex-1 border-0 bg-transparent text-base text-vp-text-1 outline-none placeholder:text-vp-text-3"
                        placeholder="{{ __('voodbuilder::search.placeholder') }}"
                        autocomplete="off"
                        spellcheck="false"
                        data-voodbuilder-search-input
                        role="combobox"
                        aria-autocomplete="list"
                        aria-controls="voodbuilder-search-listbox"
                        aria-expanded="false"
                    >
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-text-1" data-voodbuilder-search-close aria-label="{{ __('voodbuilder::search.close') }}">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>

                <div class="max-h-[min(22rem,55vh)] overflow-y-auto overscroll-contain" data-voodbuilder-search-panel>
                    <p class="px-4 py-3 text-[13px] text-vp-text-3" data-voodbuilder-search-hint>
                        {{ __('voodbuilder::search.hint') }}
                    </p>
                    <p class="hidden px-4 py-3 text-[13px] text-vp-text-3" data-voodbuilder-search-loading>
                        {{ __('voodbuilder::search.suggest_loading') }}
                    </p>
                    <p class="hidden px-4 py-3 text-[13px] text-vp-text-3" data-voodbuilder-search-empty></p>
                    <div
                        id="voodbuilder-search-listbox"
                        class="hidden py-2"
                        data-voodbuilder-search-list
                        role="listbox"
                    ></div>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-vp-divider px-4 py-2.5 text-[11px] text-vp-text-3">
                    <span class="inline-flex items-center gap-1.5">
                        <kbd class="rounded bg-vp-bg-alt px-1.5 py-0.5 font-sans text-[10px] text-vp-text-2 ring-1 ring-black/5">↵</kbd>
                        {{ __('voodbuilder::search.kbd_select') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <kbd class="rounded bg-vp-bg-alt px-1.5 py-0.5 font-sans text-[10px] text-vp-text-2 ring-1 ring-black/5">↑</kbd>
                        <kbd class="rounded bg-vp-bg-alt px-1.5 py-0.5 font-sans text-[10px] text-vp-text-2 ring-1 ring-black/5">↓</kbd>
                        {{ __('voodbuilder::search.kbd_navigate') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <kbd class="rounded bg-vp-bg-alt px-1.5 py-0.5 font-sans text-[10px] text-vp-text-2 ring-1 ring-black/5">esc</kbd>
                        {{ __('voodbuilder::search.kbd_close') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
@endif
