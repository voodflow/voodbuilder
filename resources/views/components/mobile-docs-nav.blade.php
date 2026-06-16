@props([
    'sections',
    'active' => false,
])

@php
    use Voodflow\Vdocs\Support\DocNavigation;
@endphp

<li x-data="{ open: {{ $active ? 'true' : 'false' }} }">
    <button
        type="button"
        @class([
            'vpress-mobile-nav__link w-full',
            'is-active' => $active,
        ])
        @click="open = ! open"
        :aria-expanded="open"
    >
        <span>{{ __('vdocs::nav.label') }}</span>
        <svg
            class="h-4 w-4 shrink-0 transition-transform duration-300 ease-out"
            :class="{ 'rotate-180': open }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-collapse.duration.300ms x-cloak>
        <ul class="mt-1 space-y-1 pl-3">
            <li>
                <a
                    href="{{ DocNavigation::indexUrl() }}"
                    @class([
                        'vpress-mobile-nav__link vpress-mobile-nav__link--secondary',
                        'is-active' => request()->routeIs('vdocs.index'),
                    ])
                    data-mobile-nav-close
                >
                    {{ __('vdocs::nav.overview') }}
                </a>
            </li>
            @foreach ($sections as $section)
                @php
                    $isActiveSection = request()->routeIs('vdocs.show', 'vdocs.segment')
                        && in_array($section->slug, [
                            (string) request()->route('section'),
                            (string) request()->route('segment'),
                        ], true);
                @endphp
                <li>
                    <a
                        href="{{ DocNavigation::sectionUrl($section) }}"
                        @class([
                            'vpress-mobile-nav__link vpress-mobile-nav__link--secondary',
                            'is-active' => $isActiveSection,
                        ])
                        data-mobile-nav-close
                    >
                        {{ $section->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</li>
