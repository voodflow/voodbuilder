@php
    use Voodflow\Vdocs\Support\DocNavigation;
    use Voodflow\Voodbuilder\Enums\MenuDropdownLayout;
    use Voodflow\Voodbuilder\Models\NavigationMenuItem;
    use Voodflow\Voodbuilder\Enums\MenuItemType;

    $navTopics = class_exists(DocNavigation::class) && DocNavigation::shouldAutoInject()
        ? DocNavigation::productNavTopics()
        : collect();

    $sections = $navTopics->isEmpty() && class_exists(DocNavigation::class) && DocNavigation::shouldAutoInject()
        ? DocNavigation::sections()
        : collect();

    $showOverview = $navTopics->count() > 1;
    $topicItems = $navTopics->map(function ($topic): NavigationMenuItem {
        return new NavigationMenuItem([
            'label' => $topic->title,
            'description' => filled($topic->description) ? (string) $topic->description : null,
            'type' => MenuItemType::Url,
            'link' => DocNavigation::topicUrl($topic),
        ]);
    });

    $usesMega = $topicItems->count() >= 5
        || $topicItems->contains(fn (NavigationMenuItem $item): bool => $item->hasRichPresentation());
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
            data-layout="{{ $usesMega ? MenuDropdownLayout::Mega->value : MenuDropdownLayout::List->value }}"
            hidden
            role="menu"
            @class([
                'voodbuilder-dropdown-panel absolute top-[calc(100%+0.5rem)] left-0 z-50',
                'voodbuilder-dropdown-panel--mega' => $usesMega,
            ])
        >
            @if ($navTopics->isNotEmpty())
                @if ($showOverview)
                    <a
                        href="{{ DocNavigation::indexUrl() }}"
                        role="menuitem"
                        @class([
                            'voodbuilder-nav-menu-item',
                            'text-vp-brand-1' => request()->routeIs('vdocs.index'),
                        ])
                    >
                        <span class="voodbuilder-nav-menu-item__text">
                            <span class="voodbuilder-nav-menu-item__label">{{ __('vdocs::nav.overview') }}</span>
                            <span class="voodbuilder-nav-menu-item__description">{{ __('vdocs::nav.overview_description') }}</span>
                        </span>
                    </a>

                    <div class="voodbuilder-dropdown-separator" aria-hidden="true"></div>
                @endif

                @foreach ($topicItems as $index => $item)
                    @php
                        $topic = $navTopics[$index];
                    @endphp
                    <a
                        href="{{ $item->resolveUrl() }}"
                        role="menuitem"
                        @class([
                            'voodbuilder-nav-menu-item',
                            'text-vp-brand-1' => request()->route('topic') === $topic->slug,
                        ])
                    >
                        <x-voodbuilder::menu-nav-item-content :item="$item" />
                    </a>
                @endforeach
            @else
                <a
                    href="{{ DocNavigation::indexUrl() }}"
                    role="menuitem"
                    @class([
                        'voodbuilder-nav-menu-item',
                        'text-vp-brand-1' => request()->routeIs('vdocs.index'),
                    ])
                >
                    <span class="voodbuilder-nav-menu-item__text">
                        <span class="voodbuilder-nav-menu-item__label">{{ __('vdocs::nav.overview') }}</span>
                    </span>
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
                            'voodbuilder-nav-menu-item',
                            'text-vp-brand-1' => $isActiveSection,
                        ])
                    >
                        <span class="voodbuilder-nav-menu-item__text">
                            <span class="voodbuilder-nav-menu-item__label">{{ $section->title }}</span>
                        </span>
                    </a>
                @endforeach
            @endif
        </div>
    </div>
@endif
