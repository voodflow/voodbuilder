@extends(config('vpress.layouts.page', 'vpress::layouts.page'))

@section('page')
    <header class="mb-8 border-b border-vp-divider pb-6">
        <h1 class="text-[2rem] font-bold leading-tight tracking-tight text-vp-text-1">
            {{ __('vpress::search.title') }}
        </h1>
        @if ($query !== '')
            <p class="mt-2 text-sm text-vp-text-2">
                {{ trans_choice('vpress::search.count', $total, ['query' => $query, 'count' => $total]) }}
            </p>
        @endif
    </header>

    <form action="{{ $searchUrl }}" method="get" class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center">
        <label class="sr-only" for="site-search-input">{{ __('vpress::search.label') }}</label>
        <input
            id="site-search-input"
            type="search"
            name="q"
            value="{{ $query }}"
            class="min-w-0 flex-1 rounded-lg border border-vp-divider bg-vp-bg px-4 py-2.5 text-base text-vp-text-1 outline-none transition-colors placeholder:text-vp-text-3 focus:border-vp-brand-1/40"
            placeholder="{{ __('vpress::search.placeholder') }}"
            autocomplete="off"
            spellcheck="false"
        >
        @if ($type)
            <input type="hidden" name="type" value="{{ $type }}">
        @endif
        <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-vp-brand-1 bg-vp-brand-1 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-vp-brand-1/90">
            {{ __('vpress::search.submit') }}
        </button>
    </form>

    @if ($query !== '' && count($availableTypes) > 1)
        <div class="mb-8 flex flex-wrap gap-2">
            <a
                href="{{ \Voodflow\Vpress\Support\VpressUrls::search(['q' => $query]) }}"
                class="inline-flex items-center rounded-full border px-3 py-1 text-sm transition-colors {{ $type === null ? 'border-vp-brand-1 bg-vp-brand-1/10 text-vp-brand-1' : 'border-vp-divider text-vp-text-2 hover:border-vp-brand-1/30 hover:text-vp-text-1' }}"
            >
                {{ __('vpress::search.filters.all') }}
            </a>
            @foreach ($availableTypes as $availableType)
                <a
                    href="{{ \Voodflow\Vpress\Support\VpressUrls::search(['q' => $query, 'type' => $availableType]) }}"
                    class="inline-flex items-center rounded-full border px-3 py-1 text-sm transition-colors {{ $type === $availableType ? 'border-vp-brand-1 bg-vp-brand-1/10 text-vp-brand-1' : 'border-vp-divider text-vp-text-2 hover:border-vp-brand-1/30 hover:text-vp-text-1' }}"
                >
                    {{ $typeLabels[$availableType] ?? $availableType }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($query === '')
        <p class="text-sm text-vp-text-2">{{ __('vpress::search.empty_query') }}</p>
    @elseif ($total === 0)
        <p class="text-sm text-vp-text-2">{{ __('vpress::search.no_results', ['query' => $query]) }}</p>
    @else
        @foreach ($results as $channelId => $items)
            <section class="mb-10">
                <h2 class="mb-4 text-lg font-semibold text-vp-text-1">
                    {{ $typeLabels[$channelId] ?? $channelId }}
                </h2>
                <div class="divide-y divide-vp-divider rounded-lg border border-vp-divider">
                    @foreach ($items as $item)
                        <article class="px-4 py-4">
                            <h3 class="text-base font-semibold">
                                <a href="{{ $item['url'] }}" class="text-vp-text-1 transition-colors hover:text-vp-brand-1">
                                    {{ $item['title'] }}
                                </a>
                            </h3>
                            @if (! empty($item['excerpt']))
                                <p class="mt-1 line-clamp-2 text-sm text-vp-text-2">
                                    {{ $item['excerpt'] }}
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
@endsection
