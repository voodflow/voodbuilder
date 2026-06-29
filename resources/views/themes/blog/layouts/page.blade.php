@extends(config('voodbuilder.layouts.app', 'voodbuilder::layouts.app'))

@section('body_class')
    voodbuilder-sub-theme-blog voodbuilder-has-reading-progress
@endsection

@section('content')
    <div class="voodbuilder-blog-shell" data-voodbuilder-article>
        <div class="voodbuilder-blog-content">
            @yield('page')
        </div>
    </div>
@endsection
