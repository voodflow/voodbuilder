@props([
    'topics' => null,
    'sections' => null,
    'active' => false,
])

@php
    use Voodflow\Vdocs\Support\DocNavigation;
    use Voodflow\Voodbuilder\Enums\MenuItemType;
    use Voodflow\Voodbuilder\Models\NavigationMenuItem;

    $topics = collect($topics ?? []);
    $sections = collect($sections ?? []);
    $showOverview = $topics->count() > 1;
@endphp

<li data-voodbuilder-nav-mobile-item @class(['is-open' => $active])>
    <button
        type="button"
        data-voodbuilder-nav-mobile-toggle
        @class([
            'voodbuilder-mobile-nav__link w-full',
            'is-active' => $active,
        ])
        aria-expanded="{{ $active ? 'true' : 'false' }}"
    >
        <span class="voodbuilder-nav-menu-item__text">
            <span class="voodbuilder-nav-menu-item__label">{{ __('vdocs::nav.label') }}</span>
        </span>
        <svg
            data-voodbuilder-nav-mobile-chevron
            @class([
                'h-4 w-4 shrink-0 transition-transform duration-300 ease-out',
                'rotate-180' => $active,
            ])
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div
        data-voodbuilder-nav-mobile-panel
        @class(['is-open' => $active])
        aria-hidden="{{ $active ? 'false' : 'true' }}"
    >
        <ul class="voodbuilder-mobile-nav__sublinks">
            @if ($topics->isNotEmpty())
                @if ($showOverview)
                    @php
                        $overview = new NavigationMenuItem([
                            'label' => __('vdocs::nav.overview'),
                            'description' => __('vdocs::nav.overview_description'),
                            'icon' => 'list-details',
                            'type' => MenuItemType::Url,
                            'link' => DocNavigation::indexUrl(),
                        ]);
                    @endphp
                    <li>
                        <a
                            href="{{ DocNavigation::indexUrl() }}"
                            @class([
                                'voodbuilder-mobile-nav__link voodbuilder-mobile-nav__link--secondary voodbuilder-mobile-nav__link--rich',
                                'is-active' => request()->routeIs('vdocs.index'),
                            ])
                            data-mobile-nav-close
                        >
                            <x-voodbuilder::menu-nav-item-content :item="$overview" />
                        </a>
                    </li>
                @endif
                @foreach ($topics as $topic)
                    @php
                        $topicItem = new NavigationMenuItem([
                            'label' => $topic->title,
                            'description' => filled($topic->description) ? (string) $topic->description : null,
                            'icon' => 'book-2',
                            'type' => MenuItemType::Url,
                            'link' => DocNavigation::topicUrl($topic),
                        ]);
                    @endphp
                    <li>
                        <a
                            href="{{ DocNavigation::topicUrl($topic) }}"
                            @class([
                                'voodbuilder-mobile-nav__link voodbuilder-mobile-nav__link--secondary voodbuilder-mobile-nav__link--rich',
                                'is-active' => request()->route('topic') === $topic->slug,
                            ])
                            data-mobile-nav-close
                        >
                            <x-voodbuilder::menu-nav-item-content :item="$topicItem" />
                        </a>
                    </li>
                @endforeach
            @else
                @php
                    $overview = new NavigationMenuItem([
                        'label' => __('vdocs::nav.overview'),
                        'description' => __('vdocs::nav.overview_description'),
                        'icon' => 'list-details',
                        'type' => MenuItemType::Url,
                        'link' => DocNavigation::indexUrl(),
                    ]);
                @endphp
                <li>
                    <a
                        href="{{ DocNavigation::indexUrl() }}"
                        @class([
                            'voodbuilder-mobile-nav__link voodbuilder-mobile-nav__link--secondary voodbuilder-mobile-nav__link--rich',
                            'is-active' => request()->routeIs('vdocs.index'),
                        ])
                        data-mobile-nav-close
                    >
                        <x-voodbuilder::menu-nav-item-content :item="$overview" />
                    </a>
                </li>
                @foreach ($sections as $section)
                    @php
                        $isActiveSection = request()->routeIs('vdocs.show', 'vdocs.segment')
                            && in_array($section->slug, [
                                (string) request()->route('section'),
                                (string) request()->route('segment'),
                            ], true);
                        $sectionItem = new NavigationMenuItem([
                            'label' => $section->title,
                            'icon' => 'news',
                            'type' => MenuItemType::Url,
                            'link' => DocNavigation::sectionUrl($section),
                        ]);
                    @endphp
                    <li>
                        <a
                            href="{{ DocNavigation::sectionUrl($section) }}"
                            @class([
                                'voodbuilder-mobile-nav__link voodbuilder-mobile-nav__link--secondary voodbuilder-mobile-nav__link--rich',
                                'is-active' => $isActiveSection,
                            ])
                            data-mobile-nav-close
                        >
                            <x-voodbuilder::menu-nav-item-content :item="$sectionItem" />
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</li>
