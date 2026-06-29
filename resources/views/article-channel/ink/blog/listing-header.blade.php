@props([
    'title' => null,
    'intro' => null,
    'kicker' => null,
])

<header class="voodbuilder-blog-index-header">
    @if (filled($kicker))
        <p class="voodbuilder-blog-post-date">{{ $kicker }}</p>
    @endif
    <h1 class="voodbuilder-blog-index-title">{{ $title ?? config('ink.feed.title', __('Blog')) }}</h1>
    @if (filled($intro))
        <p class="voodbuilder-blog-index-intro">{{ $intro }}</p>
    @endif
</header>
