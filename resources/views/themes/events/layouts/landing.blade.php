@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-events
@endsection

@section('content')
    <div class="vpress-events-shell">
        <div class="vpress-landing-shell">
            @yield('landing')
        </div>
    </div>
@endsection
