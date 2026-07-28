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
    use Voodflow\Voodbuilder\Support\GrapesJs\ChromeBrandLogos;
    use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

    $config = is_array($config) ? $config : [];
    $logos = ChromeBrandLogos::resolve($config);
    $logoSize = ChromeBrandLogos::normalizeSize($config['logo_size'] ?? null);
    $desktopLogoClass = $desktopLogoClass ?? ChromeBrandLogos::desktopLogoClass($logoSize);
    $mobileLogoClass = $mobileLogoClass ?? ChromeBrandLogos::mobileLogoClass($logoSize);
    $brandName = $brandName ?? VoodbuilderSettings::brandName();
    $siteTitle = $siteTitle ?? VoodbuilderSettings::siteTitle();
    $href = $href ?? VoodbuilderUrls::home();
    $showLogo = (bool) $showLogo && $logos['has_any'];
    $showSiteName = (bool) $showSiteName;
    $preview = (bool) $preview;
    // Editor canvas mounts both parts so toggles stay independent without remount.
    $mountLogo = $preview ? $logos['has_any'] : $showLogo;
    $mountName = $preview || $showSiteName || $showLogo;
    $tag = $as === 'div' ? 'div' : 'a';
@endphp

<{{ $tag }}
    @if ($tag === 'a') href="{{ $href }}" @endif
    {{ $attributes->class([$linkClass]) }}
    @if ($tag === 'a') aria-label="{{ $siteTitle }}" @endif
    data-voodbuilder-logo-size="{{ $logoSize }}"
>
    @if ($mountLogo)
        <span
            data-voodbuilder-chrome-part="logo"
            @class([
                'contents' => $preview ? $showLogo : true,
                'hidden' => $preview ? ! $showLogo : false,
            ])
            @if ($preview && ! $showLogo) data-voodbuilder-chrome-hidden @endif
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
