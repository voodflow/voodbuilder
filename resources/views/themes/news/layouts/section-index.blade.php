@extends(config('voodbuilder.layouts.app', 'voodbuilder::layouts.app'))

@section('body_class')
    voodbuilder-sub-theme-news voodbuilder-has-reading-progress
@endsection

@section('content')
    <div class="voodbuilder-news-shell" data-voodbuilder-article>
        <div class="voodbuilder-news-layout">
            @include('voodbuilder::themes.news.partials.sidebar-left', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? $page,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])

            <div class="voodbuilder-news-main">
                @yield('section_index')
            </div>

            @include('voodbuilder::themes.news.partials.sidebar-right', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? $page,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])
        </div>
    </div>
@endsection
