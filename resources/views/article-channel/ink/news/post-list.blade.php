@php
    /** @var \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection $posts */
    $items = $posts instanceof \Illuminate\Pagination\AbstractPaginator
        ? collect($posts->items())
        : collect($posts);
    $lead = $items->first();
    $rest = $lead ? $items->slice(1) : $items;
@endphp

@if ($lead)
    <article class="vpress-news-lead">
        <p class="vpress-news-lead-kicker">{{ __('vpress::demo.news.lead_story') }}</p>
        <h2 class="vpress-news-lead-title">
            <a href="{{ $lead->getUrl() }}">{{ $lead->title }}</a>
        </h2>
        @if ($lead->excerpt)
            <p class="vpress-news-lead-excerpt">{{ $lead->excerpt }}</p>
        @endif
        <div class="vpress-news-lead-meta">
            <time datetime="{{ $lead->published_at?->toDateString() }}">
                {{ $lead->published_at?->translatedFormat('M j, Y') }}
            </time>
            <a href="{{ $lead->getUrl() }}" class="vpress-news-lead-link">
                {{ __('vpress::demo.news.read_story') }}
            </a>
        </div>
    </article>
@endif

<div class="vpress-news-story-grid">
    @foreach ($rest as $post)
        <article class="vpress-news-story-card">
            <time class="vpress-news-story-date" datetime="{{ $post->published_at?->toDateString() }}">
                {{ $post->published_at?->translatedFormat('M j') }}
            </time>
            @if ($post->category)
                <p class="mt-1 text-xs font-bold uppercase tracking-wide text-vp-brand-1">{{ $post->category->name }}</p>
            @endif
            <h3 class="vpress-news-story-title">
                <a href="{{ $post->getUrl() }}">{{ $post->title }}</a>
            </h3>
            @if ($post->excerpt)
                <p class="vpress-news-story-excerpt">{{ $post->excerpt }}</p>
            @endif
        </article>
    @endforeach
</div>
