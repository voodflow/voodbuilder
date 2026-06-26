@php
    /** @var \Illuminate\Support\Collection<int, \Relaticle\Ink\Models\Post> $posts */
    /** @var \Relaticle\Ink\Models\Post|null $currentPost */
@endphp

<aside class="vpress-news-aside vpress-news-aside-left" aria-label="{{ __('vpress::demo.news.sidebar_nav') }}">
    <div class="vpress-news-aside-card">
        <p class="vpress-news-aside-kicker">{{ __('vpress::demo.news.title') }}</p>
        <h2 class="vpress-news-aside-title">{{ __('vpress::demo.news.sidebar_briefing') }}</h2>
        <p class="vpress-news-aside-text">{{ __('vpress::demo.news.sidebar_briefing_text') }}</p>
    </div>

    <nav class="vpress-news-aside-card">
        <h3 class="vpress-news-aside-heading">{{ __('vpress::demo.news.sidebar_headlines') }}</h3>
        <ol class="vpress-news-headline-list">
            <li>
                <a
                    href="{{ route('blog.index') }}"
                    @class([
                        'vpress-news-headline-link',
                        'is-active' => request()->routeIs('blog.index'),
                    ])
                >
                    <span class="vpress-news-headline-rank">•</span>
                    <span>{{ __('vpress::demo.blog.all_posts') }}</span>
                </a>
            </li>
            @foreach ($posts as $index => $post)
                <li>
                    <a
                        href="{{ $post->getUrl() }}"
                        @class([
                            'vpress-news-headline-link',
                            'is-active' => $currentPost?->is($post),
                        ])
                    >
                        <span class="vpress-news-headline-rank">{{ $index + 1 }}</span>
                        <span>{{ $post->title }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>
</aside>
