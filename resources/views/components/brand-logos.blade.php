@props([
    'config' => [],
    'showLogo' => true,
    'showSiteName' => true,
    'brandName' => null,
    'siteTitle' => null,
    'href' => null,
    'linkClass' => 'inline-flex h-16 w-full items-center gap-2.5 text-base font-semibold text-vp-text-1 transition-colors hover:text-vp-brand-1',
    'desktopLogoClass' => null,
    'mobileLogoClass' => null,
    'nameClass' => 'whitespace-nowrap',
    'as' => 'a',
    'preview' => false,
])

@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\Editor\ChromeBrandLogos;
    use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

    $config = is_array($config) ? $config : [];
    $logos = ChromeBrandLogos::resolve($config);
    $logoSize = ChromeBrandLogos::normalizeSize($config['logo_size'] ?? null);
    $logoSizeMobile = ChromeBrandLogos::normalizeSize($config['logo_size_mobile'] ?? $logoSize);
    $logoFullWidth = (bool) ($config['logo_full_width'] ?? false);
    $logoShape = ChromeBrandLogos::normalizeShape($config['logo_shape'] ?? null);
    $desktopLogoClass = $desktopLogoClass ?? (
        $logoShape === 'circle' && ! $logoFullWidth
            ? ChromeBrandLogos::footerLogoClass($logoSize, false, true, false, false, 'circle')
            : ChromeBrandLogos::desktopLogoClass($logoSize, $logoFullWidth)
    );
    $mobileLogoClass = $mobileLogoClass ?? (
        $logoShape === 'circle' && ! $logoFullWidth
            ? ChromeBrandLogos::footerLogoClass($logoSizeMobile, false, false, false, false, 'circle')
            : ChromeBrandLogos::mobileLogoClass($logoSizeMobile, $logoFullWidth)
    );
    $brandName = $brandName ?? VoodbuilderSettings::brandName();
    $siteTitle = $siteTitle ?? VoodbuilderSettings::siteTitle();
    $href = $href ?? VoodbuilderUrls::home();
    $showLogoMaster = (bool) $showLogo;
    $showSiteNameMaster = (bool) $showSiteName;
    $showLogoDesktop = $showLogoMaster && (bool) ($config['show_logo_desktop'] ?? true);
    $showLogoMobile = $showLogoMaster && (bool) ($config['show_logo_mobile'] ?? true);
    $showNameDesktop = $showSiteNameMaster && (bool) ($config['show_site_name_desktop'] ?? true);
    $showNameMobile = $showSiteNameMaster && (bool) ($config['show_site_name_mobile'] ?? true);
    $showLogo = ($showLogoDesktop || $showLogoMobile) && $logos['has_any'];
    $showSiteName = $showNameDesktop || $showNameMobile;
    $preview = (bool) $preview;
    // Editor canvas mounts both parts so toggles stay independent without remount.
    $mountLogo = $preview ? $logos['has_any'] : $showLogo;
    $mountName = $preview || $showSiteName || $showLogo;
    $tag = $as === 'div' ? 'div' : 'a';
@endphp

<{{ $tag }}
    @if ($tag === 'a') href="{{ $href }}" @endif
    {{ $attributes->class([
        $linkClass,
        'w-full min-w-0' => $logoFullWidth,
    ]) }}
    @if ($tag === 'a') aria-label="{{ $siteTitle }}" @endif
    data-voodbuilder-logo-size="{{ $logoSize }}"
    data-voodbuilder-logo-size-mobile="{{ $logoSizeMobile }}"
    data-voodbuilder-brand-logo-full="{{ $logoFullWidth ? '1' : '0' }}"
    data-voodbuilder-logo-shape="{{ $logoShape }}"
    data-vb-show-logo-desktop="{{ $showLogoDesktop ? '1' : '0' }}"
    data-vb-show-logo-mobile="{{ $showLogoMobile ? '1' : '0' }}"
    data-vb-show-name-desktop="{{ $showNameDesktop ? '1' : '0' }}"
    data-vb-show-name-mobile="{{ $showNameMobile ? '1' : '0' }}"
>
    @if ($mountLogo)
        <span
            data-voodbuilder-chrome-part="logo"
            @class([
                'contents' => $preview ? $showLogoMaster : true,
                'hidden' => $preview ? ! $showLogoMaster : false,
                'w-full' => $logoFullWidth,
            ])
            @if ($preview && ! $showLogoMaster) data-voodbuilder-chrome-hidden @endif
        >
            @if ($logos['mobile_light'])
                <img
                    src="{{ $logos['mobile_light'] }}"
                    alt=""
                    @class([$mobileLogoClass, 'vb-brand-logo', 'vb-brand-logo--mobile', 'vb-brand-logo--light'])
                >
            @endif
            @if ($logos['mobile_dark'])
                <img
                    src="{{ $logos['mobile_dark'] }}"
                    alt=""
                    @class([$mobileLogoClass, 'vb-brand-logo', 'vb-brand-logo--mobile', 'vb-brand-logo--dark'])
                >
            @endif
            @if ($logos['desktop_light'])
                <img
                    src="{{ $logos['desktop_light'] }}"
                    alt=""
                    @class([$desktopLogoClass, 'vb-brand-logo', 'vb-brand-logo--desktop', 'vb-brand-logo--light'])
                >
            @endif
            @if ($logos['desktop_dark'])
                <img
                    src="{{ $logos['desktop_dark'] }}"
                    alt=""
                    @class([$desktopLogoClass, 'vb-brand-logo', 'vb-brand-logo--desktop', 'vb-brand-logo--dark'])
                >
            @endif
        </span>
    @endif

    @if ($mountName)
        <span
            data-voodbuilder-chrome-part="site-name"
            @class([
                $nameClass => $showSiteName,
                'sr-only' => ! $showSiteName && $showLogo,
                'hidden' => ! $showSiteName && ! $showLogo,
            ])
        >{{ $brandName }}</span>
    @endif
</{{ $tag }}>
