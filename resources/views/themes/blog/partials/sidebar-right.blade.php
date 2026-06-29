@php
    /** @var \Voodflow\Voodbuilder\Models\SitePage $page */
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Voodbuilder\Models\SitePage> $sectionPosts */
    $featured = $sectionPosts->first();
@endphp

<aside class="voodbuilder-blog-aside voodbuilder-blog-aside-right" aria-label="{{ __('voodbuilder::demo.blog.sidebar_extra') }}">
    <div class="voodbuilder-blog-aside-card voodbuilder-blog-aside-highlight">
        <h3 class="voodbuilder-blog-aside-heading">{{ __('voodbuilder::demo.blog.sidebar_featured') }}</h3>
        @if ($featured)
            <a href="{{ $featured->getUrl() }}" class="voodbuilder-blog-featured-link">
                <span class="voodbuilder-blog-featured-title">{{ $featured->title }}</span>
                <span class="voodbuilder-blog-featured-excerpt">{{ $featured->displayExcerpt() }}</span>
            </a>
        @endif
    </div>

    <div class="voodbuilder-blog-aside-card">
        <h3 class="voodbuilder-blog-aside-heading">{{ __('voodbuilder::demo.blog.sidebar_newsletter_title') }}</h3>
        <p class="voodbuilder-blog-aside-text">{{ __('voodbuilder::demo.blog.sidebar_newsletter_text') }}</p>
        <div class="voodbuilder-blog-newsletter-fake" aria-hidden="true">
            <span class="voodbuilder-blog-newsletter-input">{{ __('voodbuilder::demo.blog.sidebar_newsletter_placeholder') }}</span>
            <span class="voodbuilder-blog-newsletter-button">{{ __('voodbuilder::demo.blog.sidebar_newsletter_cta') }}</span>
        </div>
    </div>

    <div class="voodbuilder-blog-aside-card">
        <h3 class="voodbuilder-blog-aside-heading">{{ __('voodbuilder::demo.blog.sidebar_read_next') }}</h3>
        <ul class="voodbuilder-blog-mini-list">
            @foreach ($sectionPosts->take(3) as $post)
                @continue($page->is($post))
                <li>
                    <a href="{{ $post->getUrl() }}" class="voodbuilder-blog-mini-link">{{ $post->title }}</a>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
