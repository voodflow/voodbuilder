@props([
    'title' => null,
    'intro' => null,
    'kicker' => null,
])

<header class="voodbuilder-news-desk-header">
    @if (filled($kicker))
        <p class="voodbuilder-news-desk-kicker">{{ $kicker }}</p>
    @else
        <p class="voodbuilder-news-desk-kicker">{{ __('voodbuilder::demo.news.title') }}</p>
    @endif
    <h1 class="voodbuilder-news-desk-title">{{ $title ?? config('ink.feed.title', __('Blog')) }}</h1>
    @if (filled($intro))
        <p class="voodbuilder-news-desk-intro">{{ $intro }}</p>
    @endif
</header>
