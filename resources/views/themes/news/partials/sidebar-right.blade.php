@php
    /** @var \Voodflow\Voodbuilder\Models\SitePage $page */
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Voodbuilder\Models\SitePage> $sectionPosts */
@endphp

<aside class="voodbuilder-news-aside voodbuilder-news-aside-right" aria-label="{{ __('voodbuilder::demo.news.sidebar_extra') }}">
    <div class="voodbuilder-news-aside-card">
        <h3 class="voodbuilder-news-aside-heading">{{ __('voodbuilder::demo.news.sidebar_trending') }}</h3>
        <ul class="voodbuilder-news-trending-list">
            @foreach ($sectionPosts->take(4) as $post)
                <li>
                    <a href="{{ $post->getUrl() }}" @class(['voodbuilder-news-trending-link', 'is-active' => $page->is($post)])>
                        {{ $post->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="voodbuilder-news-aside-card voodbuilder-news-aside-accent">
        <h3 class="voodbuilder-news-aside-heading">{{ __('voodbuilder::demo.news.sidebar_edition') }}</h3>
        <p class="voodbuilder-news-aside-text">{{ __('voodbuilder::demo.news.sidebar_edition_text') }}</p>
        <ul class="voodbuilder-news-edition-list">
            @foreach (__('voodbuilder::demo.news.sidebar_edition_items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>

    <div class="voodbuilder-news-aside-card">
        <h3 class="voodbuilder-news-aside-heading">{{ __('voodbuilder::demo.news.sidebar_alerts') }}</h3>
        <p class="voodbuilder-news-aside-text">{{ __('voodbuilder::demo.news.sidebar_alerts_text') }}</p>
    </div>
</aside>
