@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('content')
    <div class="VPPage">
        @yield('page')
    </div>
@endsection
