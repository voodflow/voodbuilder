@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;

    $config = is_array($config ?? null) ? $config : [];
    $brandName = VoodbuilderSettings::brandName();
    $copyrightChrome = SiteFooterConfig::chromeAttributes($config, 'copyright', $preview);
    $copyrightClass = $copyrightClass ?? 'text-xs text-vp-text-3';
    $copyrightText = SiteFooterConfig::resolveCopyright(
        is_string($config['copyright'] ?? null) ? $config['copyright'] : null,
        $brandName,
    );
@endphp

<p
    @class([$copyrightClass, $copyrightChrome['class']])
    data-voodbuilder-footer-copyright
    data-voodbuilder-chrome="copyright"
    {!! $copyrightChrome['attr'] !!}
>{{ $copyrightText }}</p>
