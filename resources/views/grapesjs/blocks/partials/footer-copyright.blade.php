@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $brandName = VoodbuilderSettings::brandName();
    $copyrightChrome = SiteFooterConfig::chromeAttributes($config, 'copyright', $preview);
    $copyrightClass = $copyrightClass ?? 'text-xs text-vp-text-3';
@endphp

<p
    @class([$copyrightClass, $copyrightChrome['class']])
    data-voodbuilder-footer-copyright
    data-voodbuilder-chrome="copyright"
    {!! $copyrightChrome['attr'] !!}
>
    &copy; {{ date('Y') }} {{ $brandName }}
</p>
