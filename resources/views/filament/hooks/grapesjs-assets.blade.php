@if (config('vpress.grapesjs.enabled', true))
    @vite([
        config('vpress.grapesjs.vite'),
        'packages/voodflow/vpress/resources/css/grapesjs/editor.css',
    ])
@endif
