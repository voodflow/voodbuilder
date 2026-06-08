@php
    use Illuminate\Support\Facades\Route;
    use Voodflow\Vpress\Support\VpressUrls;

    $enabled = Route::has('vpress.search')
        || Route::has('vtuts.index')
        || Route::has('vtuts.localized.index');
    $searchUrl = Route::has('vpress.search')
        ? VpressUrls::search()
        : null;
@endphp

@if ($enabled && $searchUrl)
    <div class="flex items-center" data-vpress-search>
        <button
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
            data-vpress-search-open
            aria-haspopup="dialog"
            aria-controls="vpress-search-dialog"
            aria-expanded="false"
            aria-label="{{ __('vpress::search.button') }}"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>

        <div
            id="vpress-search-dialog"
            class="fixed inset-0 z-[60] items-start justify-center px-6 pt-24"
            data-vpress-search-dialog
            hidden
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('vpress::search.button') }}"
        >
            <div class="absolute inset-0 bg-black/60" data-vpress-search-close tabindex="-1"></div>
            <div class="relative z-10 w-full max-w-[560px] overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-elv shadow-xl">
                <form action="{{ $searchUrl }}" method="get" class="flex items-center gap-2 border-b border-vp-divider px-4 py-3" data-vpress-search-form>
                    <label class="sr-only" for="vpress-search-input">{{ __('vpress::search.button') }}</label>
                    <span class="text-vp-text-3" aria-hidden="true">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input
                        id="vpress-search-input"
                        type="search"
                        name="q"
                        class="min-w-0 flex-1 border-0 bg-transparent text-base text-vp-text-1 outline-none placeholder:text-vp-text-3"
                        placeholder="{{ __('vpress::search.placeholder') }}"
                        autocomplete="off"
                        spellcheck="false"
                        data-vpress-search-input
                    >
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-text-1" data-vpress-search-close aria-label="{{ __('vpress::search.close') }}">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>
                <p class="px-4 py-3 text-[13px] text-vp-text-3">{{ __('vpress::search.hint') }}</p>
            </div>
        </div>
    </div>
@endif
