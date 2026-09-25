@props([
    'prev' => null,
    'next' => null,
])

{{-- VitePress-style prev / next links at the bottom of a companion reading page. --}}
@if (filled($prev['url'] ?? null) || filled($next['url'] ?? null))
    <nav class="vp-pager" aria-label="{{ __('voodbuilder::reading.pager') }}">
        @if (filled($prev['url'] ?? null))
            <div class="vp-pager__cell vp-pager__cell--prev">
                <a class="vp-pager__link vp-pager__link--prev" href="{{ $prev['url'] }}" rel="prev">
                    <span class="vp-pager__desc">{{ __('voodbuilder::reading.prev') }}</span>
                    <span class="vp-pager__title">{{ $prev['title'] ?? '' }}</span>
                </a>
            </div>
        @endif

        @if (filled($next['url'] ?? null))
            <div class="vp-pager__cell vp-pager__cell--next">
                <a class="vp-pager__link vp-pager__link--next" href="{{ $next['url'] }}" rel="next">
                    <span class="vp-pager__desc">{{ __('voodbuilder::reading.next') }}</span>
                    <span class="vp-pager__title">{{ $next['title'] ?? '' }}</span>
                </a>
            </div>
        @endif
    </nav>
@endif
