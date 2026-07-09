@extends('voodbuilder::layouts.full-width')

@section('body_class_extra', 'voodbuilder-grapesjs-editing voodbuilder-popup-editor')

@section('content')
    <div class="voodbuilder-popup-editor-shell voodbuilder-grapesjs-mode">
        <div class="voodbuilder-popup-editor-preview" aria-hidden="true">
            <div class="voodbuilder-popup-editor-preview__overlay"></div>
            <div class="voodbuilder-popup-editor-preview__frame"></div>
        </div>
        @include('voodbuilder::partials.grapesjs-frontend-editor', [
            'grapesJsConfig' => $grapesJsConfig,
        ])
    </div>
@endsection

@push('scripts-before-livewire')
    <style>
        .voodbuilder-popup-editor .voodbuilder-grapesjs-frontend {
            min-height: 100vh;
        }

        .voodbuilder-popup-editor-shell {
            position: relative;
            min-height: 100vh;
            background: var(--color-vp-bg, #f8fafc);
        }

        .voodbuilder-popup-editor-preview {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        .voodbuilder-popup-editor-preview__overlay {
            position: absolute;
            inset: 0;
            background: rgb(15 23 42 / 0.45);
        }

        .voodbuilder-popup-editor-preview__frame {
            position: absolute;
            top: 50%;
            left: 50%;
            width: min(42rem, calc(100% - 2rem));
            height: min(70vh, 40rem);
            transform: translate(-50%, -50%);
            border-radius: 0.75rem;
            border: 2px dashed rgb(148 163 184 / 0.8);
            box-shadow: 0 25px 50px -12px rgb(15 23 42 / 0.35);
        }

        .voodbuilder-popup-editor-shell .voodbuilder-grapesjs-frontend {
            position: relative;
            z-index: 1;
        }

        .voodbuilder-popup-editor-shell .gjs-editor {
            max-width: min(42rem, calc(100% - 2rem));
            margin: 2rem auto;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgb(15 23 42 / 0.35);
        }
    </style>
@endpush
