@php
    /** @var array<string, mixed> $grapesJsConfig */
@endphp

<div class="vpress-grapesjs-frontend" data-vpress-grapesjs-root>
    <div class="vpress-grapesjs-frontend__toolbar">
        <div class="vpress-grapesjs-frontend__toolbar-inner">
            <span class="vpress-grapesjs-frontend__title">{{ __('vpress::pro.frontend.toolbar_title') }}</span>
            <div class="vpress-grapesjs-frontend__actions">
                <span class="vpress-grapesjs-frontend__status" data-vpress-grapesjs-saved hidden>
                    {{ __('vpress::pro.frontend.saved') }}
                </span>
                <button
                    type="button"
                    class="vpress-grapesjs-frontend__button vpress-grapesjs-frontend__button--primary"
                    data-vpress-grapesjs-save
                >
                    <span data-vpress-grapesjs-save-label>{{ __('vpress::pro.frontend.save') }}</span>
                </button>
            </div>
        </div>
    </div>

    <div class="vpress-grapesjs-frontend__canvas" data-vpress-grapesjs-canvas></div>
</div>

<script type="application/json" data-vpress-grapesjs-config>
    {!! json_encode($grapesJsConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
