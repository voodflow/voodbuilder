@extends(\Voodflow\Voodbuilder\Support\PluginLayout::appShell())

@section('body_class')
    @yield('body_class_extra')
@endsection

@section('content')
    <div class="voodbuilder-landing-shell voodbuilder-full-width-shell">
        @yield('landing')
    </div>
@endsection
