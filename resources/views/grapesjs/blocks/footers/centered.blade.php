@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $brandName = VoodbuilderSettings::brandName();
@endphp

<div class="container mx-auto px-5 py-16 text-center">
    <div class="mx-auto max-w-lg">
        <div data-voodbuilder-brand></div>
        <p class="mt-4 text-sm text-vp-text-2" data-voodbuilder-footer-tagline>
            {{ __('voodbuilder::pro.grapesjs.blocks.footer_default_tagline') }}
        </p>
        <nav class="mt-6 flex flex-wrap justify-center gap-x-6 gap-y-2" data-voodbuilder-menu="footer" data-voodbuilder-chrome="footer-menu" aria-label="{{ __('Footer') }}"></nav>
        <p class="mt-8 text-xs text-vp-text-3" data-voodbuilder-footer-copyright data-voodbuilder-chrome="copyright">
            &copy; {{ date('Y') }} {{ $brandName }}
        </p>
    </div>
</div>
