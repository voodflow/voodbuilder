@extends('voodbuilder::layouts.full-width')

@section('body_class_extra', 'voodbuilder-grapesjs-editing voodbuilder-chrome-layout-editor')

@push('head')
    <style id="voodbuilder-grapesjs-host-chrome-critical">{!! \Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsHostChrome::criticalHideCss() !!}</style>
@endpush

@section('content')
    <div class="voodbuilder-chrome-layout-editor-shell voodbuilder-grapesjs-mode">
        @include('voodbuilder::partials.grapesjs-frontend-editor', [
            'grapesJsConfig' => $grapesJsConfig,
        ])
    </div>
@endsection

@push('scripts-before-livewire')
    <style>
        .voodbuilder-chrome-layout-editor .voodbuilder-grapesjs-frontend {
            min-height: 100vh;
        }

        .voodbuilder-chrome-layout-editor-shell {
            min-height: 100vh;
            background: var(--color-vp-bg, #f8fafc);
        }
    </style>
@endpush
