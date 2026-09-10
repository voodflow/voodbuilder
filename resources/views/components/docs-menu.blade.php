@php
    use Voodflow\Vdocs\Support\DocNavigation;

    $navTopics = class_exists(DocNavigation::class) && DocNavigation::shouldAutoInject()
        ? DocNavigation::navTopics()
        : collect();

    $sections = $navTopics->isEmpty() && class_exists(DocNavigation::class) && DocNavigation::shouldAutoInject()
        ? DocNavigation::sections()
        : collect();
@endphp

@if (($navTopics->isNotEmpty() || $sections->isNotEmpty()) && Route::has('vdocs.index'))
    <div
        class="relative hidden vp:block"
        data-voodbuilder-nav-dropdown
        data-voodbuilder-nav-dropdown-trigger="click"
    >
        <button
            type="button"
            data-voodbuilder-nav-dropdown-toggle
            @class([
                'inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium transition-colors',
                'text-vp-brand-1' => DocNavigation::isActive(),
                'text-vp-text-1 hover:text-vp-brand-1' => ! DocNavigation::isActive(),
            ])
            aria-haspopup="menu"
            aria-expanded="false"
        >
            <span>{{ __('vdocs::nav.label') }}</span>
            <svg class="h-4 w-4 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div
            data-voodbuilder-nav-dropdown-panel
            hidden
            role="menu"
            class="voodbuilder-dropdown-panel absolute top-[calc(100%+0.5rem)] left-0 z-50"
        >
            @if ($navTopics->isNotEmpty())
                @if ($navTopics->count() > 1)
                    <a
                        href="{{ DocNavigation::indexUrl() }}"
                        role="menuitem"
                        @class([
                            'text-vp-brand-1' => request()->routeIs('vdocs.index'),
                        ])
                    >
                        {{ __('vdocs::nav.overview') }}
                    </a>

                    <div class="voodbuilder-dropdown-separator" aria-hidden="true"></div>
                @endif

                @foreach ($navTopics as $topic)
                    <a
                        href="{{ DocNavigation::topicUrl($topic) }}"
                        role="menuitem"
                        @class([
                            'text-vp-brand-1' => request()->route('topic') === $topic->slug,
                        ])
                    >
                        {{ $topic->title }}
                    </a>
                @endforeach
            @else
                <a
                    href="{{ DocNavigation::indexUrl() }}"
                    role="menuitem"
                    @class([
                        'text-vp-brand-1' => request()->routeIs('vdocs.index'),
                    ])
                >
                    {{ __('vdocs::nav.overview') }}
                </a>

                <div class="voodbuilder-dropdown-separator" aria-hidden="true"></div>

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
                            'text-vp-brand-1' => $isActiveSection,
                        ])
                    >
                        {{ $section->title }}
                    </a>
                @endforeach
            @endif
        </div>
    </div>
@endif
