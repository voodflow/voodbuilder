@php
    /** @var \Illuminate\Support\Collection<int, \Relaticle\Ink\Models\Post> $posts */
    /** @var \Relaticle\Ink\Models\Post|null $currentPost */
    $featured = $posts->first();
@endphp

<aside class="vpress-blog-aside vpress-blog-aside-right" aria-label="{{ __('vpress::demo.blog.sidebar_extra') }}">
    @if ($featured)
        <div class="vpress-blog-aside-card vpress-blog-aside-highlight">
            <h3 class="vpress-blog-aside-heading">{{ __('vpress::demo.blog.sidebar_featured') }}</h3>
            <a href="{{ $featured->getUrl() }}" class="vpress-blog-featured-link">
                <span class="vpress-blog-featured-title">{{ $featured->title }}</span>
                @if ($featured->excerpt)
                    <span class="vpress-blog-featured-excerpt">{{ $featured->excerpt }}</span>
                @endif
            </a>
        </div>
    @endif

    <div class="vpress-blog-aside-card">
        <h3 class="vpress-blog-aside-heading">{{ __('vpress::demo.blog.sidebar_read_next') }}</h3>
        <ul class="vpress-blog-mini-list">
            @foreach ($posts->take(4) as $post)
                @continue($currentPost?->is($post))
                <li>
                    <a href="{{ $post->getUrl() }}" class="vpress-blog-mini-link">{{ $post->title }}</a>
                </li>
            @endforeach
        </ul>
    </div>

    @if (Route::has('blog.feed'))
        <div class="vpress-blog-aside-card">
            <h3 class="vpress-blog-aside-heading">{{ __('RSS') }}</h3>
            <a href="{{ route('blog.feed') }}" class="vpress-blog-nav-link">{{ __('Subscribe to the feed') }}</a>
        </div>
    @endif
</aside>
