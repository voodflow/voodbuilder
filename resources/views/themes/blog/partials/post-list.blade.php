@php
    /** @var \Illuminate\Support\Collection<int, \Voodflow\Voodbuilder\Models\SitePage> $posts */
@endphp

<div class="voodbuilder-blog-post-list">
    @foreach ($posts as $post)
        <article class="voodbuilder-blog-post-card">
            <time class="voodbuilder-blog-post-date" datetime="{{ $post->published_at?->toDateString() }}">
                {{ $post->published_at?->translatedFormat('M j, Y') }}
            </time>
            <h2 class="voodbuilder-blog-post-title">
                <a href="{{ $post->getUrl() }}">{{ $post->title }}</a>
            </h2>
            <p class="voodbuilder-blog-post-excerpt">{{ $post->displayExcerpt() }}</p>
            <a href="{{ $post->getUrl() }}" class="voodbuilder-blog-post-read-more">
                {{ __('voodbuilder::demo.blog.read_more') }}
            </a>
        </article>
    @endforeach
</div>
