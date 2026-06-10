@php
    /** @var \Voodflow\Vpress\Models\SitePage $page */
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Vpress\Models\SitePage> $sectionPosts */
    $featured = $sectionPosts->first();
@endphp

<aside class="vpress-blog-aside vpress-blog-aside-right" aria-label="{{ __('vpress::demo.blog.sidebar_extra') }}">
    <div class="vpress-blog-aside-card vpress-blog-aside-highlight">
        <h3 class="vpress-blog-aside-heading">{{ __('vpress::demo.blog.sidebar_featured') }}</h3>
        @if ($featured)
            <a href="{{ $featured->getUrl() }}" class="vpress-blog-featured-link">
                <span class="vpress-blog-featured-title">{{ $featured->title }}</span>
                <span class="vpress-blog-featured-excerpt">{{ $featured->displayExcerpt() }}</span>
            </a>
        @endif
    </div>

    <div class="vpress-blog-aside-card">
        <h3 class="vpress-blog-aside-heading">{{ __('vpress::demo.blog.sidebar_newsletter_title') }}</h3>
        <p class="vpress-blog-aside-text">{{ __('vpress::demo.blog.sidebar_newsletter_text') }}</p>
        <div class="vpress-blog-newsletter-fake" aria-hidden="true">
            <span class="vpress-blog-newsletter-input">{{ __('vpress::demo.blog.sidebar_newsletter_placeholder') }}</span>
            <span class="vpress-blog-newsletter-button">{{ __('vpress::demo.blog.sidebar_newsletter_cta') }}</span>
        </div>
    </div>

    <div class="vpress-blog-aside-card">
        <h3 class="vpress-blog-aside-heading">{{ __('vpress::demo.blog.sidebar_read_next') }}</h3>
        <ul class="vpress-blog-mini-list">
            @foreach ($sectionPosts->take(3) as $post)
                @continue($page->is($post))
                <li>
                    <a href="{{ $post->getUrl() }}" class="vpress-blog-mini-link">{{ $post->title }}</a>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
