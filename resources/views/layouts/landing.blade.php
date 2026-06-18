@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    @yield('body_class_extra')
@endsection

@section('content')
    <div class="vpress-landing-shell">
        @yield('landing')
    </div>
@endsection
