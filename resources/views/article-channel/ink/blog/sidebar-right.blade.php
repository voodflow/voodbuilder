@php
    /** @var \Illuminate\Support\Collection<int, \Relaticle\Ink\Models\Post> $posts */
    /** @var \Relaticle\Ink\Models\Post|null $currentPost */
    $featured = $posts->first();
@endphp

<aside class="voodbuilder-blog-aside voodbuilder-blog-aside-right" aria-label="{{ __('voodbuilder::demo.blog.sidebar_extra') }}">
    @if ($featured)
        <div class="voodbuilder-blog-aside-card voodbuilder-blog-aside-highlight">
            <h3 class="voodbuilder-blog-aside-heading">{{ __('voodbuilder::demo.blog.sidebar_featured') }}</h3>
            <a href="{{ $featured->getUrl() }}" class="voodbuilder-blog-featured-link">
                <span class="voodbuilder-blog-featured-title">{{ $featured->title }}</span>
                @if ($featured->excerpt)
                    <span class="voodbuilder-blog-featured-excerpt">{{ $featured->excerpt }}</span>
                @endif
            </a>
        </div>
    @endif

    <div class="voodbuilder-blog-aside-card">
        <h3 class="voodbuilder-blog-aside-heading">{{ __('voodbuilder::demo.blog.sidebar_read_next') }}</h3>
        <ul class="voodbuilder-blog-mini-list">
            @foreach ($posts->take(4) as $post)
                @continue($currentPost?->is($post))
                <li>
                    <a href="{{ $post->getUrl() }}" class="voodbuilder-blog-mini-link">{{ $post->title }}</a>
                </li>
            @endforeach
        </ul>
    </div>

    @if (Route::has('blog.feed'))
        <div class="voodbuilder-blog-aside-card">
            <h3 class="voodbuilder-blog-aside-heading">{{ __('RSS') }}</h3>
            <a href="{{ route('blog.feed') }}" class="voodbuilder-blog-nav-link">{{ __('Subscribe to the feed') }}</a>
        </div>
    @endif
</aside>
