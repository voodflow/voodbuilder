@php
    /** @var \Illuminate\Support\Collection<int, \Relaticle\Ink\Models\Post> $posts */
    /** @var \Relaticle\Ink\Models\Post|null $currentPost */
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
            <li>
                <a
                    href="{{ route('blog.index') }}"
                    @class([
                        'voodbuilder-blog-nav-link',
                        'is-active' => request()->routeIs('blog.index'),
                    ])
                >
                    {{ __('voodbuilder::demo.blog.all_posts') }}
                </a>
            </li>
            @foreach ($posts as $post)
                <li>
                    <a
                        href="{{ $post->getUrl() }}"
                        @class([
                            'voodbuilder-blog-nav-link',
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
