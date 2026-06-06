@php
    use Voodflow\Vpress\Models\VpressSettings;

    $siteTitle = VpressSettings::siteTitle();
    $brandName = VpressSettings::brandName();
    $logoUrl = VpressSettings::logoUrl();
    $logoMobileUrl = VpressSettings::logoMobileUrl();
    $showBrandName = (bool) VpressSettings::get('show_site_title', true);
@endphp

<a
    href="{{ \Voodflow\Vpress\Support\VpressUrls::home() }}"
    class="inline-flex h-16 w-full items-center gap-2.5 text-base font-semibold text-vp-text-1 transition-colors hover:text-vp-brand-1"
    aria-label="{{ $siteTitle }}"
>
    @if ($logoUrl)
        @if ($logoMobileUrl)
            <img
                src="{{ $logoMobileUrl }}"
                alt=""
                class="h-8 w-auto max-w-[120px] object-contain object-left md:hidden"
            >
            <img
                src="{{ $logoUrl }}"
                alt=""
                class="hidden h-10 w-auto max-w-[220px] object-contain object-left md:block"
            >
        @else
            <img
                src="{{ $logoUrl }}"
                alt=""
                class="h-8 w-auto max-w-[160px] object-contain object-left md:h-10 md:max-w-[220px]"
            >
        @endif
    @endif
    @if ($showBrandName && ! $logoUrl)
        <span class="whitespace-nowrap">{{ $brandName }}</span>
    @elseif ($showBrandName && $logoUrl)
        <span class="sr-only">{{ $brandName }}</span>
    @endif
</a>
