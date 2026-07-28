@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;
    use Voodflow\Voodbuilder\Support\Navigation;
    use Voodflow\Voodbuilder\Support\SiteFooterColumnPlacements;

    $brandName = VoodbuilderSettings::brandName();
    $hasColumnMenus = collect(SiteFooterColumnPlacements::columnSlugs())
        ->contains(fn (string $slug): bool => Navigation::items($slug)->isNotEmpty());
    $legacyFooterItems = Navigation::items('footer');
    $tagline = SiteFooterConfig::resolveTagline();
@endphp

<footer class="border-t border-vp-divider bg-vp-bg text-vp-text-2" role="contentinfo">
    @if ($hasColumnMenus || $canvasPreview)
        <div class="mx-auto max-w-[var(--width-vp-layout,80rem)] px-6 py-12 md:px-8">
            <div class="flex flex-col flex-wrap gap-10 md:flex-row md:items-start">
                <div class="shrink-0 md:w-64">
                    <x-voodbuilder::nav-title />
                    <p class="mt-3 text-sm text-vp-text-2">
                        {{ $tagline }}
                    </p>
                </div>

                <div class="grid w-full grow gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (SiteFooterColumnPlacements::columnSlugs() as $menuSlug)
                        <x-voodbuilder::footer-menu-column
                            :menu="$menuSlug"
                            :canvas-preview="$canvasPreview"
                        />
                    @endforeach
                </div>
            </div>

            <p class="mt-10 border-t border-vp-divider pt-6 text-center text-sm">
                {{ SiteFooterConfig::resolveCopyright(null, $brandName) }}
            </p>
        </div>
    @else
        <div class="px-6 py-8 text-center text-sm md:px-8">
            @if ($legacyFooterItems->isNotEmpty())
                <nav class="mb-3 flex flex-wrap justify-center gap-4" aria-label="{{ __('Footer') }}">
                    @foreach ($legacyFooterItems as $item)
                        @if ($item->hasChildren())
                            @foreach ($item->navigationChildren() as $child)
                                <a
                                    href="{{ $child->resolveUrl() }}"
                                    class="transition-colors hover:text-vp-brand-1"
                                    @if ($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                >
                                    {{ __($child->label) }}
                                </a>
                            @endforeach
                        @else
                            <a
                                href="{{ $item->resolveUrl() }}"
                                class="transition-colors hover:text-vp-brand-1"
                                @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                            >
                                {{ __($item->label) }}
                            </a>
                        @endif
                    @endforeach
                </nav>
            @endif
            <p>{{ SiteFooterConfig::resolveCopyright(null, $brandName) }}</p>
        </div>
    @endif
</footer>
