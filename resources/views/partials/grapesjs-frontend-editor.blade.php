@php
    /** @var array<string, mixed> $grapesJsConfig */
@endphp

<div
    class="vpress-grapesjs-frontend"
    x-data="vpressGrapesJsFrontendEditor(@js($grapesJsConfig))"
    x-cloak
>
    <div class="vpress-grapesjs-frontend__toolbar">
        <div class="vpress-grapesjs-frontend__toolbar-inner">
            <span class="vpress-grapesjs-frontend__title">{{ __('vpress::pro.frontend.toolbar_title') }}</span>
            <div class="vpress-grapesjs-frontend__actions">
                <span
                    class="vpress-grapesjs-frontend__status"
                    x-show="saved"
                    x-transition
                >{{ __('vpress::pro.frontend.saved') }}</span>
                <button
                    type="button"
                    class="vpress-grapesjs-frontend__button vpress-grapesjs-frontend__button--primary"
                    x-on:click="save"
                    x-bind:disabled="saving"
                >
                    <span x-show="! saving">{{ __('vpress::pro.frontend.save') }}</span>
                    <span x-show="saving">{{ __('vpress::pro.frontend.saving') }}</span>
                </button>
            </div>
        </div>
    </div>

    <div x-ref="editor" class="vpress-grapesjs-frontend__canvas"></div>
</div>

@push('head')
    @vite([
        config('vpress.grapesjs.vite'),
        'packages/voodflow/vpress/resources/css/grapesjs/editor.css',
    ])
@endpush
