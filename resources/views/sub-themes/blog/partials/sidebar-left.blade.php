@php
    /** @var \Voodflow\Vpress\Models\SitePage $page */
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Vpress\Models\SitePage> $sectionPosts */
@endphp

<aside class="vpress-blog-aside vpress-blog-aside-left" aria-label="{{ __('vpress::demo.blog.sidebar_nav') }}">
    <div class="vpress-blog-aside-card">
        <p class="vpress-blog-aside-kicker">{{ __('vpress::demo.blog.title') }}</p>
        <h2 class="vpress-blog-aside-title">{{ __('vpress::demo.blog.sidebar_about_title') }}</h2>
        <p class="vpress-blog-aside-text">{{ __('vpress::demo.blog.sidebar_about_text') }}</p>
    </div>

    <nav class="vpress-blog-aside-card">
        <h3 class="vpress-blog-aside-heading">{{ __('vpress::demo.blog.sidebar_posts') }}</h3>
        <ul class="vpress-blog-nav-list">
            @if ($sectionHome)
                <li>
                    <a
                        href="{{ $sectionHome->getUrl() }}"
                        @class([
                            'vpress-blog-nav-link',
                            'is-active' => $page->is($sectionHome),
                        ])
                    >
                        {{ __('vpress::demo.blog.all_posts') }}
                    </a>
                </li>
            @endif

            @foreach ($sectionPosts as $post)
                <li>
                    <a
                        href="{{ $post->getUrl() }}"
                        @class([
                            'vpress-blog-nav-link',
                            'is-active' => $page->is($post),
                        ])
                    >
                        {{ $post->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="vpress-blog-aside-card">
        <h3 class="vpress-blog-aside-heading">{{ __('vpress::demo.blog.sidebar_topics') }}</h3>
        <ul class="vpress-blog-tag-list">
            @foreach (__('vpress::demo.blog.sidebar_topic_tags') as $tag)
                <li><span class="vpress-blog-tag">{{ $tag }}</span></li>
            @endforeach
        </ul>
    </div>
</aside>
