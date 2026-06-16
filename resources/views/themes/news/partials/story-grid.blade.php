@php
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Vpress\Models\SitePage> $posts */
    $lead = $posts->first();
    $rest = $posts->slice(1);
@endphp

@if ($lead)
    <article class="vpress-news-lead">
        <p class="vpress-news-lead-kicker">{{ __('vpress::demo.news.lead_story') }}</p>
        <h2 class="vpress-news-lead-title">
            <a href="{{ $lead->getUrl() }}">{{ $lead->title }}</a>
        </h2>
        <p class="vpress-news-lead-excerpt">{{ $lead->displayExcerpt() }}</p>
        <div class="vpress-news-lead-meta">
            <time datetime="{{ $lead->published_at?->toDateString() }}">{{ $lead->published_at?->translatedFormat('M j, Y') }}</time>
            <a href="{{ $lead->getUrl() }}" class="vpress-news-lead-link">{{ __('vpress::demo.news.read_story') }}</a>
        </div>
    </article>
@endif

<div class="vpress-news-story-grid">
    @foreach ($rest as $post)
        <article class="vpress-news-story-card">
            <time class="vpress-news-story-date" datetime="{{ $post->published_at?->toDateString() }}">
                {{ $post->published_at?->translatedFormat('M j') }}
            </time>
            <h3 class="vpress-news-story-title">
                <a href="{{ $post->getUrl() }}">{{ $post->title }}</a>
            </h3>
            <p class="vpress-news-story-excerpt">{{ $post->displayExcerpt() }}</p>
        </article>
    @endforeach
</div>
