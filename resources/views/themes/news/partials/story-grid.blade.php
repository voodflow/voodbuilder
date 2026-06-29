@php
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Voodbuilder\Models\SitePage> $posts */
    $lead = $posts->first();
    $rest = $posts->slice(1);
@endphp

@if ($lead)
    <article class="voodbuilder-news-lead">
        <p class="voodbuilder-news-lead-kicker">{{ __('voodbuilder::demo.news.lead_story') }}</p>
        <h2 class="voodbuilder-news-lead-title">
            <a href="{{ $lead->getUrl() }}">{{ $lead->title }}</a>
        </h2>
        <p class="voodbuilder-news-lead-excerpt">{{ $lead->displayExcerpt() }}</p>
        <div class="voodbuilder-news-lead-meta">
            <time datetime="{{ $lead->published_at?->toDateString() }}">{{ $lead->published_at?->translatedFormat('M j, Y') }}</time>
            <a href="{{ $lead->getUrl() }}" class="voodbuilder-news-lead-link">{{ __('voodbuilder::demo.news.read_story') }}</a>
        </div>
    </article>
@endif

<div class="voodbuilder-news-story-grid">
    @foreach ($rest as $post)
        <article class="voodbuilder-news-story-card">
            <time class="voodbuilder-news-story-date" datetime="{{ $post->published_at?->toDateString() }}">
                {{ $post->published_at?->translatedFormat('M j') }}
            </time>
            <h3 class="voodbuilder-news-story-title">
                <a href="{{ $post->getUrl() }}">{{ $post->title }}</a>
            </h3>
            <p class="voodbuilder-news-story-excerpt">{{ $post->displayExcerpt() }}</p>
        </article>
    @endforeach
</div>
