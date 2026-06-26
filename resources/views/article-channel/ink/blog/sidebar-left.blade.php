@php
    /** @var \Illuminate\Support\Collection<int, \Relaticle\Ink\Models\Post> $posts */
    /** @var \Relaticle\Ink\Models\Post|null $currentPost */
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
            <li>
                <a
                    href="{{ route('blog.index') }}"
                    @class([
                        'vpress-blog-nav-link',
                        'is-active' => request()->routeIs('blog.index'),
                    ])
                >
                    {{ __('vpress::demo.blog.all_posts') }}
                </a>
            </li>
            @foreach ($posts as $post)
                <li>
                    <a
                        href="{{ $post->getUrl() }}"
                        @class([
                            'vpress-blog-nav-link',
                            'is-active' => $currentPost?->is($post),
                        ])
                    >
                        {{ $post->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</aside>
