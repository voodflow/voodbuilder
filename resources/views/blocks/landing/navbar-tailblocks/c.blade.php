<header class="text-gray-600 body-font vp-landing-navbar-tailblocks vp-landing-navbar-tailblocks--c">
    <div class="container mx-auto flex flex-wrap p-5 flex-col md:flex-row items-center">
        @include('voodbuilder::blocks.landing.partials.tailblocks-nav-links', [
            'navbar' => $navbar,
            'navClass' => 'flex lg:w-2/5 flex-wrap items-center text-base md:ml-auto',
            'linkClass' => 'mr-5 hover:text-gray-900',
            'ctaClass' => 'inline-flex items-center bg-gray-100 border-0 py-1 px-3 focus:outline-none hover:bg-gray-200 rounded text-base mt-4 md:mt-0 lg:justify-end ml-5 lg:ml-0',
        ])

        <a href="{{ url('/') }}" class="flex order-first lg:order-none lg:w-1/5 title-font font-medium items-center text-gray-900 lg:items-center lg:justify-center mb-4 md:mb-0">
            @if (! empty($navbar['logo_url']))
                <img src="{{ $navbar['logo_url'] }}" alt="{{ $navbar['brand_name'] ?? '' }}" class="h-10 w-10 rounded-full object-cover">
            @else
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-vp-brand-1 p-2 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-full w-full" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </span>
            @endif

            @if (! empty($navbar['brand_name']))
                <span class="ml-3 text-xl">{{ $navbar['brand_name'] }}</span>
            @endif
        </a>

        @if (! empty($navbar['cta_label']) && ! empty($navbar['cta_url']))
            <div class="lg:w-2/5 inline-flex lg:justify-end ml-5 lg:ml-0">
                <a href="{{ $navbar['cta_url'] }}" class="inline-flex items-center bg-gray-100 border-0 py-1 px-3 focus:outline-none hover:bg-gray-200 rounded text-base mt-4 md:mt-0">
                    {{ $navbar['cta_label'] }}
                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="w-4 h-4 ml-1" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        @endif
    </div>
</header>
