@php
    /** @var \Voodflow\Vpress\Models\SitePage $page */
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Vpress\Models\SitePage> $sectionPosts */
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
            @foreach ($sectionPosts as $index => $post)
                <li>
                    <a
                        href="{{ $post->getUrl() }}"
                        @class([
                            'vpress-news-headline-link',
                            'is-active' => $page->is($post),
                        ])
                    >
                        <span class="vpress-news-headline-rank">{{ $index + 1 }}</span>
                        <span>{{ $post->title }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>

    @if ($sectionHome)
        <a href="{{ $sectionHome->getUrl() }}" @class(['vpress-news-desk-link', 'is-active' => $page->is($sectionHome)])>
            {{ __('vpress::demo.news.back_to_desk') }}
        </a>
    @endif
</aside>
