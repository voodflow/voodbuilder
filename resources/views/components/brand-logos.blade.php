@props([
    'config' => [],
    'showLogo' => true,
    'showSiteName' => true,
    'brandName' => null,
    'siteTitle' => null,
    'href' => null,
    'linkClass' => 'inline-flex h-16 w-full items-center gap-2.5 text-base font-semibold text-vp-text-1 transition-colors hover:text-vp-brand-1',
    'desktopLogoClass' => 'h-10 w-auto max-w-[220px] object-contain object-left',
    'mobileLogoClass' => 'h-8 w-auto max-w-[120px] object-contain object-left',
    'nameClass' => 'whitespace-nowrap',
    'as' => 'a',
])

@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\GrapesJs\ChromeBrandLogos;
    use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

    $logos = ChromeBrandLogos::resolve(is_array($config) ? $config : []);
    $brandName = $brandName ?? VoodbuilderSettings::brandName();
    $siteTitle = $siteTitle ?? VoodbuilderSettings::siteTitle();
    $href = $href ?? VoodbuilderUrls::home();
    $showLogo = (bool) $showLogo && $logos['has_any'];
    $showSiteName = (bool) $showSiteName;
    $tag = $as === 'div' ? 'div' : 'a';
@endphp

<{{ $tag }}
    @if ($tag === 'a') href="{{ $href }}" @endif
    {{ $attributes->class([$linkClass]) }}
    @if ($tag === 'a') aria-label="{{ $siteTitle }}" @endif
>
    @if ($showLogo)
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
    @endif

    @if ($showSiteName)
        <span @class([$nameClass])>{{ $brandName }}</span>
    @elseif ($showLogo)
        <span class="sr-only">{{ $brandName }}</span>
    @endif
</{{ $tag }}>
