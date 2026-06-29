@extends(config('voodbuilder.layouts.app', 'voodbuilder::layouts.app'))

@section('body_class')
    voodbuilder-sub-theme-news voodbuilder-has-reading-progress
@endsection

@section('content')
    <div class="voodbuilder-news-shell" data-voodbuilder-article>
        <div class="voodbuilder-news-content">
            <div class="voodbuilder-news-content-inner">
                @yield('home')
            </div>
        </div>
    </div>
@endsection
