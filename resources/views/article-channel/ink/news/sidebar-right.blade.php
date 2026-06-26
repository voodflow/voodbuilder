@php
    /** @var \Illuminate\Support\Collection<int, \Relaticle\Ink\Models\Post> $posts */
    /** @var \Relaticle\Ink\Models\Post|null $currentPost */
@endphp

<aside class="vpress-news-aside vpress-news-aside-right" aria-label="{{ __('vpress::demo.news.sidebar_extra') }}">
    <div class="vpress-news-aside-card">
        <h3 class="vpress-news-aside-heading">{{ __('vpress::demo.news.sidebar_trending') }}</h3>
        <ul class="vpress-news-trending-list">
            @foreach ($posts->take(4) as $post)
                @continue($currentPost?->is($post))
                <li>
                    <a href="{{ $post->getUrl() }}" class="vpress-news-trending-link">
                        {{ $post->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="vpress-news-aside-card vpress-news-aside-accent">
        <h3 class="vpress-news-aside-heading">{{ __('vpress::demo.news.sidebar_edition') }}</h3>
        <p class="vpress-news-aside-text">{{ __('vpress::demo.news.sidebar_edition_text') }}</p>
        <ul class="vpress-news-edition-list">
            @foreach (__('vpress::demo.news.sidebar_edition_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>

    @if (Route::has('blog.feed'))
        <div class="vpress-news-aside-card">
            <h3 class="vpress-news-aside-heading">{{ __('vpress::demo.news.sidebar_alerts') }}</h3>
            <p class="vpress-news-aside-text">{{ __('vpress::demo.news.sidebar_alerts_text') }}</p>
            <a href="{{ route('blog.feed') }}" class="vpress-news-desk-link">{{ __('Subscribe to the feed') }}</a>
        </div>
    @endif
</aside>
