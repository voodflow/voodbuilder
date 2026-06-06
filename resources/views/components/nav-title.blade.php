@php
    use Voodflow\Vpress\Models\VpressSettings;

    $siteTitle = VpressSettings::siteTitle();
    $brandName = VpressSettings::brandName();
    $logoUrl = VpressSettings::logoUrl();
    $showBrandName = (bool) VpressSettings::get('show_site_title', true);
@endphp

<a
    href="{{ \Voodflow\Vpress\Support\VpressUrls::home() }}"
    class="inline-flex h-16 w-full items-center gap-2.5 text-base font-semibold text-vp-text-1 transition-colors hover:text-vp-brand-1"
    aria-label="{{ $siteTitle }}"
>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="" class="h-6 w-auto max-w-[120px] object-contain">
    @endif
    @if ($showBrandName)
        <span class="whitespace-nowrap">{{ $brandName }}</span>
    @endif
</a>
