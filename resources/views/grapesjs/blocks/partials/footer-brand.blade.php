@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\GrapesJs\ChromeBrandLogos;
    use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

    $config = is_array($config ?? null) ? $config : [];
    $brandName = $brandName ?? VoodbuilderSettings::brandName();
    $logos = ChromeBrandLogos::resolve($config);
    $logoSize = ChromeBrandLogos::normalizeSize($config['logo_size'] ?? null);
    $homeUrl = VoodbuilderUrls::home();
    $preview = (bool) ($preview ?? false);
    $showBrand = (bool) ($showBrand ?? true);
    $showSiteName = (bool) ($showSiteName ?? ($config['show_site_name'] ?? true));
    $showLogo = $showBrand && $logos['has_any'];
    $showPlaceholder = $showBrand && ! $logos['has_any'];
    $logoOnly = $showLogo && ! $showSiteName;
    // Editor preview always mounts both parts so toggles stay independent without remount.
    $mountLogo = $preview ? ($logos['has_any'] || true) : ($showLogo || $showPlaceholder);
    $mountName = $preview || $showSiteName || $showLogo;
@endphp

<a
    href="{{ $homeUrl }}"
    @class([
        'inline-flex items-center title-font font-medium text-vp-text-1',
        'w-full min-w-0' => $logoOnly,
    ])
    data-voodbuilder-footer-brand-link
    data-voodbuilder-brand-logo-only="{{ $logoOnly ? '1' : '0' }}"
    data-voodbuilder-logo-size="{{ $logoSize }}"
>
    @if ($mountLogo)
        <span
            data-voodbuilder-chrome-part="logo"
            @class([
                'contents' => $preview ? $showBrand : ($showLogo || $showPlaceholder),
                'hidden' => $preview ? ! $showBrand : ! ($showLogo || $showPlaceholder),
            ])
            @if ($preview && ! $showBrand) data-voodbuilder-chrome-hidden @endif
        >
            @if ($logos['has_any'])
                @if ($logos['mobile_light'])
                    <img
                        src="{{ $logos['mobile_light'] }}"
                        alt="{{ $brandName }}"
                        @class([
                            'vb-brand-logo', 'vb-brand-logo--mobile', 'vb-brand-logo--light',
                            ChromeBrandLogos::footerLogoClass($logoSize, $logoOnly, false),
                        ])
                    >
                @endif
                @if ($logos['mobile_dark'])
                    <img
                        src="{{ $logos['mobile_dark'] }}"
                        alt="{{ $brandName }}"
                        @class([
                            'vb-brand-logo', 'vb-brand-logo--mobile', 'vb-brand-logo--dark',
                            ChromeBrandLogos::footerLogoClass($logoSize, $logoOnly, false),
                        ])
                    >
                @endif
                @if ($logos['desktop_light'])
                    <img
                        src="{{ $logos['desktop_light'] }}"
                        alt="{{ $brandName }}"
                        @class([
                            'vb-brand-logo', 'vb-brand-logo--desktop', 'vb-brand-logo--light',
                            ChromeBrandLogos::footerLogoClass($logoSize, $logoOnly, true),
                        ])
                    >
                @endif
                @if ($logos['desktop_dark'])
                    <img
                        src="{{ $logos['desktop_dark'] }}"
                        alt="{{ $brandName }}"
                        @class([
                            'vb-brand-logo', 'vb-brand-logo--desktop', 'vb-brand-logo--dark',
                            ChromeBrandLogos::footerLogoClass($logoSize, $logoOnly, true),
                        ])
                    >
                @endif
            @else
                <span @class([
                    'inline-flex shrink-0 items-center justify-center rounded-full bg-vp-brand-1 p-2 text-white',
                    ChromeBrandLogos::squareClass($logoSize),
                ]) data-voodbuilder-brand-placeholder>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-1/2 w-1/2" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </span>
            @endif
        </span>
    @endif

    @if ($mountName)
        <span
            data-voodbuilder-chrome-part="site-name"
            @class([
                'ml-3 text-xl' => $showSiteName,
                'hidden' => ! $showSiteName,
            ])
        >
            @if ($showSiteName)
                {{ $brandName }}
            @else
                <span class="sr-only">{{ $brandName }}</span>
            @endif
        </span>
    @endif
</a>
