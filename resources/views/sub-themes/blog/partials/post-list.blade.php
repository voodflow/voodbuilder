@php
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Vpress\Models\SitePage> $posts */
@endphp

<div class="vpress-blog-post-list">
    @foreach ($posts as $post)
        <article class="vpress-blog-post-card">
            <time class="vpress-blog-post-date" datetime="{{ $post->published_at?->toDateString() }}">
                {{ $post->published_at?->translatedFormat('M j, Y') }}
            </time>
            <h2 class="vpress-blog-post-title">
                <a href="{{ $post->getUrl() }}">{{ $post->title }}</a>
            </h2>
            <p class="vpress-blog-post-excerpt">{{ $post->displayExcerpt() }}</p>
            <a href="{{ $post->getUrl() }}" class="vpress-blog-post-read-more">
                {{ __('vpress::demo.blog.read_more') }}
            </a>
        </article>
    @endforeach
</div>
