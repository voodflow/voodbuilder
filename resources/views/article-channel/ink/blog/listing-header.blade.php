@props([
    'title' => null,
    'intro' => null,
    'kicker' => null,
])

<header class="vpress-blog-index-header">
    @if (filled($kicker))
        <p class="vpress-blog-post-date">{{ $kicker }}</p>
    @endif
    <h1 class="vpress-blog-index-title">{{ $title ?? config('ink.feed.title', __('Blog')) }}</h1>
    @if (filled($intro))
        <p class="vpress-blog-index-intro">{{ $intro }}</p>
    @endif
</header>
