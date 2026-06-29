@php
    /** @var \Voodflow\Voodbuilder\Models\SitePage $page */
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Voodbuilder\Models\SitePage> $sectionPosts */
@endphp

<aside class="voodbuilder-blog-aside voodbuilder-blog-aside-left" aria-label="{{ __('voodbuilder::demo.blog.sidebar_nav') }}">
    <div class="voodbuilder-blog-aside-card">
        <p class="voodbuilder-blog-aside-kicker">{{ __('voodbuilder::demo.blog.title') }}</p>
        <h2 class="voodbuilder-blog-aside-title">{{ __('voodbuilder::demo.blog.sidebar_about_title') }}</h2>
        <p class="voodbuilder-blog-aside-text">{{ __('voodbuilder::demo.blog.sidebar_about_text') }}</p>
    </div>

    <nav class="voodbuilder-blog-aside-card">
        <h3 class="voodbuilder-blog-aside-heading">{{ __('voodbuilder::demo.blog.sidebar_posts') }}</h3>
        <ul class="voodbuilder-blog-nav-list">
            @if ($sectionHome)
                <li>
                    <a
                        href="{{ $sectionHome->getUrl() }}"
                        @class([
                            'voodbuilder-blog-nav-link',
                            'is-active' => $page->is($sectionHome),
                        ])
                    >
                        {{ __('voodbuilder::demo.blog.all_posts') }}
                    </a>
                </li>
            @endif

            @foreach ($sectionPosts as $post)
                <li>
                    <a
                        href="{{ $post->getUrl() }}"
                        @class([
                            'voodbuilder-blog-nav-link',
                            'is-active' => $page->is($post),
                        ])
                    >
                        {{ $post->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="voodbuilder-blog-aside-card">
        <h3 class="voodbuilder-blog-aside-heading">{{ __('voodbuilder::demo.blog.sidebar_topics') }}</h3>
        <ul class="voodbuilder-blog-tag-list">
            @foreach (__('voodbuilder::demo.blog.sidebar_topic_tags') as $tag)
                <li><span class="voodbuilder-blog-tag">{{ $tag }}</span></li>
            @endforeach
        </ul>
    </div>
</aside>
