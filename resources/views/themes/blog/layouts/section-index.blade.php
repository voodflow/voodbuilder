@extends(config('voodbuilder.layouts.app', 'voodbuilder::layouts.app'))

@section('body_class')
    voodbuilder-sub-theme-blog voodbuilder-has-reading-progress
@endsection

@section('content')
    <div class="voodbuilder-blog-shell" data-voodbuilder-article>
        <div class="voodbuilder-blog-layout">
            @include('voodbuilder::themes.blog.partials.sidebar-left', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? $page,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])

            <div class="voodbuilder-blog-main">
                @yield('section_index')
            </div>

            @include('voodbuilder::themes.blog.partials.sidebar-right', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? $page,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])
        </div>
    </div>
@endsection
