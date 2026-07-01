@php
    $brandName = $organizer['brand_name'] ?? $config['brand_name'] ?? null;
    $logoUrl = $organizer['logo_url'] ?? null;
@endphp

<footer class="text-gray-600 body-font vp-landing-footer-layout vp-landing-footer-layout--d">
    <div class="container px-5 py-8 mx-auto flex items-center sm:flex-row flex-col">
        <a href="{{ url('/') }}" class="flex title-font font-medium items-center md:justify-start justify-center text-gray-900">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $brandName ?? '' }}" class="h-10 w-10 rounded-full object-cover">
            @else
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-vp-brand-1 p-2 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-full w-full" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </span>
            @endif

            @if ($brandName)
                <span class="ml-3 text-xl">{{ $brandName }}</span>
            @endif
        </a>

        <div class="text-sm text-gray-500 sm:ml-4 sm:pl-4 sm:border-l-2 sm:border-gray-200 sm:py-2 sm:mt-0 mt-4">
            @include('voodbuilder::blocks.landing.partials.layout-copyright')
        </div>
    </div>
</footer>
