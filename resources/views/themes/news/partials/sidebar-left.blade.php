@php
    /** @var \Voodflow\Voodbuilder\Models\SitePage $page */
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Voodbuilder\Models\SitePage> $sectionPosts */
@endphp

<aside class="voodbuilder-news-aside voodbuilder-news-aside-left" aria-label="{{ __('voodbuilder::demo.news.sidebar_nav') }}">
    <div class="voodbuilder-news-aside-card">
        <p class="voodbuilder-news-aside-kicker">{{ __('voodbuilder::demo.news.title') }}</p>
        <h2 class="voodbuilder-news-aside-title">{{ __('voodbuilder::demo.news.sidebar_briefing') }}</h2>
        <p class="voodbuilder-news-aside-text">{{ __('voodbuilder::demo.news.sidebar_briefing_text') }}</p>
    </div>

    <nav class="voodbuilder-news-aside-card">
        <h3 class="voodbuilder-news-aside-heading">{{ __('voodbuilder::demo.news.sidebar_headlines') }}</h3>
        <ol class="voodbuilder-news-headline-list">
            @foreach ($sectionPosts as $index => $post)
                <li>
                    <a
                        href="{{ $post->getUrl() }}"
                        @class([
                            'voodbuilder-news-headline-link',
                            'is-active' => $page->is($post),
                        ])
                    >
                        <span class="voodbuilder-news-headline-rank">{{ $index + 1 }}</span>
                        <span>{{ $post->title }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>

    @if ($sectionHome)
        <a href="{{ $sectionHome->getUrl() }}" @class(['voodbuilder-news-desk-link', 'is-active' => $page->is($sectionHome)])>
            {{ __('voodbuilder::demo.news.back_to_desk') }}
        </a>
    @endif
</aside>
