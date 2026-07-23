@extends('voodbuilder::layouts.full-width')

@section('body_class_extra', 'voodbuilder-grapesjs-editing voodbuilder-popup-editor')

@php
    $popupDisplayWidth = $grapesJsConfig['popupDisplayWidth'] ?? '32rem';
@endphp

@push('head')
    <style id="voodbuilder-grapesjs-host-chrome-critical">{!! \Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsHostChrome::criticalHideCss() !!}</style>
@endpush

@section('content')
    <div class="voodbuilder-popup-editor-shell voodbuilder-grapesjs-mode" style="--voodbuilder-popup-editor-width: {{ $popupDisplayWidth }}">
        <div class="voodbuilder-popup-editor-preview" aria-hidden="true">
            <div class="voodbuilder-popup-editor-preview__overlay"></div>
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
            background: rgb(15 23 42 / 0.5);
        }

        .voodbuilder-popup-editor-shell .voodbuilder-grapesjs-frontend {
            position: relative;
            z-index: 1;
        }

        /* Match popups-runtime.js panel: max-width + rounded-xl + shadow-2xl + bg-vp-bg */
        .voodbuilder-popup-editor-shell .gjs-editor {
            position: relative;
            max-width: min(var(--voodbuilder-popup-editor-width, 32rem), calc(100% - 2rem));
            margin: 2rem auto;
            border-radius: 0.75rem;
            overflow: hidden;
            background: var(--color-vp-bg, #ffffff);
            box-shadow: 0 25px 50px -12px rgb(15 23 42 / 0.35);
        }

        .voodbuilder-popup-editor-shell .gjs-editor::after {
            content: '×';
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            border: 1px solid color-mix(in srgb, var(--color-vp-brand-1, #0d9488) 28%, var(--color-vp-divider, #e2e8f0));
            background: color-mix(in srgb, var(--color-vp-brand-1, #0d9488) 12%, var(--color-vp-bg-elv, #fff));
            color: var(--color-vp-brand-1, #0d9488);
            font-size: 1.35rem;
            font-weight: 600;
            line-height: 1;
            pointer-events: none;
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.08);
        }

        .voodbuilder-popup-editor-shell .gjs-cv-canvas,
        .voodbuilder-popup-editor-shell .gjs-frame-wrapper {
            background: transparent !important;
        }
    </style>
@endpush
