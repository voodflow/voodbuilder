@props([
    'title' => null,
    'intro' => null,
    'kicker' => null,
])

<header class="vpress-news-desk-header">
    @if (filled($kicker))
        <p class="vpress-news-desk-kicker">{{ $kicker }}</p>
    @else
        <p class="vpress-news-desk-kicker">{{ __('vpress::demo.news.title') }}</p>
    @endif
    <h1 class="vpress-news-desk-title">{{ $title ?? config('ink.feed.title', __('Blog')) }}</h1>
    @if (filled($intro))
        <p class="vpress-news-desk-intro">{{ $intro }}</p>
    @endif
</header>
