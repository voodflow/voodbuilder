@extends(\Voodflow\Voodbuilder\Support\PluginLayout::resolve('page'), [
    'title' => __('voodbuilder::search.title'),
])

@section('page')
    <div class="vb-search-page w-full">
    <header class="mb-10 pt-2 sm:mb-12 sm:pt-4">
        <h1 class="text-[2rem] font-bold leading-tight tracking-tight text-vp-text-1">
            {{ __('voodbuilder::search.title') }}
        </h1>
        @if ($query !== '')
            <p class="mt-2 text-sm text-vp-text-2">
                {{ trans_choice('voodbuilder::search.count', $total, ['query' => $query, 'count' => $total]) }}
            </p>
        @endif
    </header>

    <form
        action="{{ $searchUrl }}"
        method="get"
        class="vb-search-page__form mb-8 flex flex-col gap-3 rounded-2xl border border-vp-divider bg-vp-bg-alt p-3 sm:flex-row sm:items-center sm:p-2 sm:pl-4"
    >
        <label class="sr-only" for="site-search-input">{{ __('voodbuilder::search.label') }}</label>
        <input
            id="site-search-input"
            type="search"
            name="q"
            value="{{ $query }}"
            class="min-w-0 flex-1 rounded-xl border-0 bg-transparent px-1 py-2.5 text-base text-vp-text-1 outline-none ring-0 placeholder:text-vp-text-3 focus:ring-0"
            placeholder="{{ __('voodbuilder::search.placeholder') }}"
            autocomplete="off"
            spellcheck="false"
        >
        @if ($type)
            <input type="hidden" name="type" value="{{ $type }}">
        @endif
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-xl bg-vp-brand-1 px-5 py-2.5 text-sm font-medium text-vp-bg transition-colors hover:bg-vp-brand-1/90"
        >
            {{ __('voodbuilder::search.submit') }}
        </button>
    </form>

    @if ($query !== '' && $total > 0 && count($availableTypes) > 0)
        <div class="mb-10 flex flex-wrap gap-2">
            <a
                href="{{ \Voodflow\Voodbuilder\Support\VoodbuilderUrls::search(['q' => $query]) }}"
                @class([
                    'vb-search-page__filter inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm transition-colors',
                    'border-vp-brand-1/35 bg-vp-brand-1/15 font-medium text-vp-brand-1' => $type === null,
                    'border-vp-divider bg-vp-bg-alt text-vp-text-2 hover:border-vp-text-3 hover:text-vp-text-1' => $type !== null,
                ])
            >
                <span>{{ __('voodbuilder::search.filters.all') }}</span>
                <span @class([
                    'tabular-nums text-[11px]',
                    'text-vp-brand-1/80' => $type === null,
                    'text-vp-text-3' => $type !== null,
                ])>{{ $total }}</span>
            </a>
            @foreach ($availableTypes as $availableType)
                @php $count = (int) ($typeCounts[$availableType] ?? 0); @endphp
                <a
                    href="{{ \Voodflow\Voodbuilder\Support\VoodbuilderUrls::search(['q' => $query, 'type' => $availableType]) }}"
                    @class([
                        'vb-search-page__filter inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm transition-colors',
                        'border-vp-brand-1/35 bg-vp-brand-1/15 font-medium text-vp-brand-1' => $type === $availableType,
                        'border-vp-divider bg-vp-bg-alt text-vp-text-2 hover:border-vp-text-3 hover:text-vp-text-1' => $type !== $availableType,
                    ])
                >
                    <span>{{ $typeLabels[$availableType] ?? $availableType }}</span>
                    <span @class([
                        'tabular-nums text-[11px]',
                        'text-vp-brand-1/80' => $type === $availableType,
                        'text-vp-text-3' => $type !== $availableType,
                    ])>{{ $count }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($query === '')
        <p class="rounded-2xl border border-vp-divider bg-vp-bg-alt px-5 py-8 text-center text-sm text-vp-text-2">
            {{ __('voodbuilder::search.empty_query') }}
        </p>
    @elseif ($total === 0)
        <p class="rounded-2xl border border-vp-divider bg-vp-bg-alt px-5 py-8 text-center text-sm text-vp-text-2">
            {{ __('voodbuilder::search.no_results', ['query' => $query]) }}
        </p>
    @elseif ($paginator !== null)
        @php $previousChannel = null; @endphp
        <div class="flex flex-col gap-3">
            @foreach ($paginator as $item)
                @php $channelId = $item['channel'] ?? null; @endphp
                @if ($channelId !== $previousChannel)
                    <div @class(['mb-1 mt-6 flex items-baseline justify-between gap-3 first:mt-0' => true])>
                        <h2 class="text-sm font-semibold tracking-[0.08em] text-vp-text-2 uppercase">
                            {{ $typeLabels[$channelId] ?? $channelId }}
                        </h2>
                        @if ($type === null)
                            <span class="text-xs text-vp-text-3">{{ $typeCounts[$channelId] ?? '' }}</span>
                        @endif
                    </div>
                    @php $previousChannel = $channelId; @endphp
                @endif

                <a
                    href="{{ $item['url'] }}"
                    class="vb-search-hit group block rounded-2xl border border-vp-divider bg-vp-bg-elv px-5 py-4 transition-[border-color,background-color] hover:border-vp-brand-1/45 hover:bg-vp-bg-alt"
                >
                    @if (! empty($item['meta']))
                        <p class="mb-1.5 text-[12px] leading-snug text-vp-text-3">
                            {{ $item['meta'] }}
                        </p>
                    @endif
                    <h3 class="text-[1.05rem] font-semibold leading-snug text-vp-text-1 transition-colors group-hover:text-vp-brand-1">
                        @if (! empty($item['title_html']))
                            {!! $item['title_html'] !!}
                        @else
                            {{ $item['title'] }}
                        @endif
                    </h3>
                    @if (! empty($item['excerpt_html']) || ! empty($item['excerpt']))
                        <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-vp-text-2">
                            @if (! empty($item['excerpt_html']))
                                {!! $item['excerpt_html'] !!}
                            @else
                                {{ $item['excerpt'] }}
                            @endif
                        </p>
                    @endif
                </a>
            @endforeach
        </div>

        @if ($paginator->hasPages())
            <nav class="mt-10 flex flex-wrap items-center justify-between gap-3 border-t border-vp-divider pt-6" aria-label="{{ __('voodbuilder::search.pagination') }}">
                <div class="text-sm text-vp-text-3">
                    {{ __('voodbuilder::search.page_status', [
                        'from' => $paginator->firstItem(),
                        'to' => $paginator->lastItem(),
                        'total' => $paginator->total(),
                    ]) }}
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex cursor-not-allowed rounded-lg border border-vp-divider px-3 py-1.5 text-sm text-vp-text-3">
                            {{ __('voodbuilder::search.prev') }}
                        </span>
                    @else
                        <a
                            href="{{ $paginator->previousPageUrl() }}"
                            class="inline-flex rounded-lg border border-vp-divider px-3 py-1.5 text-sm text-vp-text-1 transition-colors hover:bg-vp-bg-alt"
                        >
                            {{ __('voodbuilder::search.prev') }}
                        </a>
                    @endif

                    @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $pageNum => $url)
                        <a
                            href="{{ $url }}"
                            @class([
                                'inline-flex min-w-9 items-center justify-center rounded-lg border px-2.5 py-1.5 text-sm tabular-nums transition-colors',
                                'border-vp-brand-1/35 bg-vp-brand-1/15 font-medium text-vp-brand-1' => $pageNum === $paginator->currentPage(),
                                'border-vp-divider text-vp-text-1 hover:bg-vp-bg-alt' => $pageNum !== $paginator->currentPage(),
                            ])
                            @if ($pageNum === $paginator->currentPage()) aria-current="page" @endif
                        >
                            {{ $pageNum }}
                        </a>
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a
                            href="{{ $paginator->nextPageUrl() }}"
                            class="inline-flex rounded-lg border border-vp-divider px-3 py-1.5 text-sm text-vp-text-1 transition-colors hover:bg-vp-bg-alt"
                        >
                            {{ __('voodbuilder::search.next') }}
                        </a>
                    @else
                        <span class="inline-flex cursor-not-allowed rounded-lg border border-vp-divider px-3 py-1.5 text-sm text-vp-text-3">
                            {{ __('voodbuilder::search.next') }}
                        </span>
                    @endif
                </div>
            </nav>
        @endif
    @endif
    </div>
@endsection
