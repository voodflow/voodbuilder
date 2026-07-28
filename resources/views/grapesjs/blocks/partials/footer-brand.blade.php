@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\GrapesJs\ChromeBrandLogos;
    use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

    $config = is_array($config ?? null) ? $config : [];
    $brandName = $brandName ?? VoodbuilderSettings::brandName();
    $logos = ChromeBrandLogos::resolve($config);
    $homeUrl = VoodbuilderUrls::home();
    $showBrand = (bool) ($showBrand ?? true);
    $showSiteName = (bool) ($showSiteName ?? ($config['show_site_name'] ?? true));
    $showLogo = $showBrand && $logos['has_any'];
@endphp

<a href="{{ $homeUrl }}" class="inline-flex items-center title-font font-medium text-vp-text-1">
    @if ($showLogo)
        @if ($logos['mobile_light'])
            <img
                src="{{ $logos['mobile_light'] }}"
                alt="{{ $brandName }}"
                class="vb-brand-logo vb-brand-logo--mobile vb-brand-logo--light h-10 w-10 rounded-full object-cover"
            >
        @endif
        @if ($logos['mobile_dark'])
            <img
                src="{{ $logos['mobile_dark'] }}"
                alt="{{ $brandName }}"
                class="vb-brand-logo vb-brand-logo--mobile vb-brand-logo--dark h-10 w-10 rounded-full object-cover"
            >
        @endif
        @if ($logos['desktop_light'])
            <img
                src="{{ $logos['desktop_light'] }}"
                alt="{{ $brandName }}"
                class="vb-brand-logo vb-brand-logo--desktop vb-brand-logo--light h-10 w-10 rounded-full object-cover"
            >
        @endif
        @if ($logos['desktop_dark'])
            <img
                src="{{ $logos['desktop_dark'] }}"
                alt="{{ $brandName }}"
                class="vb-brand-logo vb-brand-logo--desktop vb-brand-logo--dark h-10 w-10 rounded-full object-cover"
            >
        @endif
    @elseif ($showBrand && $showSiteName)
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-vp-brand-1 p-2 text-white">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-6 w-6" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
            </svg>
        </span>
    @endif
    @if ($showSiteName)
        <span class="ml-3 text-xl">{{ $brandName }}</span>
    @elseif ($showLogo)
        <span class="sr-only">{{ $brandName }}</span>
    @endif
</a>
