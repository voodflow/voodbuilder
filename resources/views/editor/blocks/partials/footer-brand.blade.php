@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\BrandMarkAssets;
    use Voodflow\Voodbuilder\Support\Editor\ChromeBrandLogos;
    use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

    $config = is_array($config ?? null) ? $config : [];
    $brandName = $brandName ?? VoodbuilderSettings::brandName();
    $logos = ChromeBrandLogos::resolve($config);
    $logoSize = ChromeBrandLogos::normalizeSize($config['logo_size'] ?? null);
    $logoSizeMobile = ChromeBrandLogos::normalizeSize($config['logo_size_mobile'] ?? $logoSize);
    $logoFullWidth = (bool) ($config['logo_full_width'] ?? false);
    $homeUrl = VoodbuilderUrls::home();
    $preview = (bool) ($preview ?? false);
    $showBrand = (bool) ($showBrand ?? true);
    $showSiteName = (bool) ($showSiteName ?? ($config['show_site_name'] ?? true));
    // Same as nav brand-logos: empty uploads resolve to the animated package mark.
    $showLogo = $showBrand && $logos['has_any'];
    $logoOnly = $showLogo && ! $showSiteName;
    $wideLogo = $logoOnly || $logoFullWidth;
    $packageMark = $logos['has_any']
        && str_contains((string) ($logos['desktop_light'] ?? ''), BrandMarkAssets::PUBLIC_RELATIVE);
    // Editor preview always mounts both parts so toggles stay independent without remount.
    $mountLogo = $preview ? $logos['has_any'] : $showLogo;
    $mountName = $preview || $showSiteName || $showLogo;
@endphp

<a
    href="{{ $homeUrl }}"
    @class([
        'inline-flex items-center title-font font-medium text-vp-text-1',
        'w-full min-w-0' => $wideLogo,
    ])
    data-voodbuilder-footer-brand-link
    data-voodbuilder-brand-logo-only="{{ $logoOnly ? '1' : '0' }}"
    data-voodbuilder-brand-logo-full="{{ $logoFullWidth ? '1' : '0' }}"
    data-voodbuilder-logo-size="{{ $logoSize }}"
    data-voodbuilder-logo-size-mobile="{{ $logoSizeMobile }}"
>
    @if ($mountLogo)
        <span
            data-voodbuilder-chrome-part="logo"
            @class([
                'contents' => $preview ? $showBrand : true,
                'hidden' => $preview ? ! $showBrand : false,
                'w-full' => $wideLogo,
            ])
            @if ($preview && ! $showBrand) data-voodbuilder-chrome-hidden @endif
        >
            @if ($logos['mobile_light'])
                <img
                    src="{{ $logos['mobile_light'] }}"
                    alt="{{ $brandName }}"
                    @class([
                        'vb-brand-logo', 'vb-brand-logo--mobile', 'vb-brand-logo--light',
                        ChromeBrandLogos::footerLogoClass($logoSizeMobile, $logoOnly, false, $logoFullWidth, $packageMark),
                    ])
                >
            @endif
            @if ($logos['mobile_dark'])
                <img
                    src="{{ $logos['mobile_dark'] }}"
                    alt="{{ $brandName }}"
                    @class([
                        'vb-brand-logo', 'vb-brand-logo--mobile', 'vb-brand-logo--dark',
                        ChromeBrandLogos::footerLogoClass($logoSizeMobile, $logoOnly, false, $logoFullWidth, $packageMark),
                    ])
                >
            @endif
            @if ($logos['desktop_light'])
                <img
                    src="{{ $logos['desktop_light'] }}"
                    alt="{{ $brandName }}"
                    @class([
                        'vb-brand-logo', 'vb-brand-logo--desktop', 'vb-brand-logo--light',
                        ChromeBrandLogos::footerLogoClass($logoSize, $logoOnly, true, $logoFullWidth, $packageMark),
                    ])
                >
            @endif
            @if ($logos['desktop_dark'])
                <img
                    src="{{ $logos['desktop_dark'] }}"
                    alt="{{ $brandName }}"
                    @class([
                        'vb-brand-logo', 'vb-brand-logo--desktop', 'vb-brand-logo--dark',
                        ChromeBrandLogos::footerLogoClass($logoSize, $logoOnly, true, $logoFullWidth, $packageMark),
                    ])
                >
            @endif
        </span>
    @endif

    @if ($mountName)
        <span
            data-voodbuilder-chrome-part="site-name"
            @class([
                'ml-3 text-xl' => $showSiteName,
                'sr-only' => ! $showSiteName && $showLogo,
                'hidden' => ! $showSiteName && ! $showLogo,
            ])
        >{{ $brandName }}</span>
    @endif
</a>
