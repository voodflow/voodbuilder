@extends('voodbuilder::layouts.full-width')

@section('body_class_extra', 'voodbuilder-editor-editing voodbuilder-chrome-layout-editor')

@push('head')
    <style id="voodbuilder-editor-host-chrome-critical">{!! \Voodflow\Voodbuilder\Support\Editor\EditorHostChrome::criticalHideCss() !!}</style>
@endpush

@section('content')
    <div class="voodbuilder-chrome-layout-editor-shell voodbuilder-editor-mode">
        @include('voodbuilder::partials.editor-frontend-editor', [
            'editorConfig' => $editorConfig,
        ])
    </div>
@endsection

@push('scripts-before-livewire')
    <style>
        .voodbuilder-chrome-layout-editor .voodbuilder-editor-frontend {
            min-height: 100vh;
        }

        .voodbuilder-chrome-layout-editor-shell {
            min-height: 100vh;
            background: var(--color-vp-bg, #f8fafc);
        }
    </style>
@endpush
