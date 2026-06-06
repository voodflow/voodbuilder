@php
    use Voodflow\Vdocs\Support\DocNavigation;

    $sections = class_exists(DocNavigation::class) && DocNavigation::enabled()
        ? DocNavigation::sections()
        : collect();
@endphp

@if ($sections->isNotEmpty() && Route::has('vdocs.index'))
    <div
        class="relative hidden vp:block"
        x-data="{ open: false }"
        @click.outside="open = false"
        @keydown.escape.window="open = false"
    >
        <button
            type="button"
            @class([
                'inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium transition-colors',
                'text-vp-brand-1' => DocNavigation::isActive(),
                'text-vp-text-1 hover:text-vp-brand-1' => ! DocNavigation::isActive(),
            ])
            aria-haspopup="menu"
            :aria-expanded="open"
            @click="open = ! open"
        >
            <span>{{ __('vdocs::nav.label') }}</span>
            <svg class="h-4 w-4 text-vp-text-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            role="menu"
            class="absolute top-[calc(100%+0.5rem)] left-0 z-50 min-w-[14rem] overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-elv py-2 shadow-lg"
        >
            <a
                href="{{ DocNavigation::indexUrl() }}"
                role="menuitem"
                @class([
                    'block px-3 py-2 text-sm font-medium transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1',
                    'text-vp-brand-1' => request()->routeIs('vdocs.index'),
                    'text-vp-text-1' => ! request()->routeIs('vdocs.index'),
                ])
            >
                {{ __('vdocs::nav.overview') }}
            </a>

            <div class="my-1 h-px bg-vp-divider" aria-hidden="true"></div>

            @foreach ($sections as $section)
                @php
                    $isActiveSection = request()->routeIs('vdocs.show', 'vdocs.segment')
                        && in_array($section->slug, [
                            (string) request()->route('section'),
                            (string) request()->route('segment'),
                        ], true);
                @endphp
                <a
                    href="{{ DocNavigation::sectionUrl($section) }}"
                    role="menuitem"
                    @class([
                        'block px-3 py-2 text-sm transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1',
                        'font-medium text-vp-brand-1' => $isActiveSection,
                        'text-vp-text-2' => ! $isActiveSection,
                    ])
                >
                    {{ $section->title }}
                </a>
            @endforeach
        </div>
    </div>
@endif
