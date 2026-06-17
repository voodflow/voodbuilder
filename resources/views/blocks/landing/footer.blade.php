<footer class="vp-landing-footer {{ \Voodflow\Vpress\Support\LandingBlockSupport::sectionShellClass($config, ['section_width' => 'bleed', 'section_padding' => 'none']) }} bg-vp-text-1 text-vp-text-3">
    <div class="mx-auto max-w-[var(--width-vp-layout)] px-6 py-12 md:px-8 md:py-16">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-1">
                @if (! empty($organizer['logo_url']))
                    <img
                        src="{{ $organizer['logo_url'] }}"
                        alt="{{ $organizer['brand_name'] ?? '' }}"
                        class="mb-6 h-10 w-auto max-w-[12rem] object-contain brightness-0 invert"
                    >
                @elseif (! empty($organizer['brand_name']))
                    <p class="mb-6 text-2xl font-bold tracking-tight text-white">
                        {{ $organizer['brand_name'] }}
                    </p>
                @endif

                @if ($organizer['lines'] !== [])
                    <div class="space-y-2 text-sm leading-relaxed">
                        @foreach ($organizer['lines'] as $line)
                            @if (filled($line['url']))
                                <a href="{{ $line['url'] }}" class="block transition hover:text-white">
                                    {{ $line['label'] }}
                                </a>
                            @else
                                <p>{{ $line['label'] }}</p>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            @foreach ($menuColumns as $column)
                <div>
                    <h3 class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-white">
                        {{ $column['title'] }}
                    </h3>

                    @if ($column['links'] !== [])
                        <ul class="space-y-2 text-sm">
                            @foreach ($column['links'] as $link)
                                <li>
                                    <a
                                        href="{{ $link['url'] }}"
                                        class="transition hover:text-vp-brand-1"
                                        @if ($link['open_in_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                                    >
                                        {{ $link['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($copyrightSegments !== [])
            <div class="mt-10 border-t border-white/10 pt-6">
                <p class="flex flex-wrap items-center gap-x-3 gap-y-2 text-xs font-semibold uppercase tracking-[0.18em]">
                    @foreach ($copyrightSegments as $index => $segment)
                        @if ($index > 0)
                            <span aria-hidden="true" class="text-white/30">•</span>
                        @endif

                        @if ($segment['type'] === 'link' && filled($segment['url']))
                            <a
                                href="{{ $segment['url'] }}"
                                @class([
                                    'transition hover:text-white',
                                    'text-vp-brand-1' => $segment['highlight'],
                                ])
                            >
                                {{ $segment['label'] }}
                            </a>
                        @else
                            <span @class(['text-vp-brand-1' => $segment['highlight']])>
                                {{ $segment['label'] }}
                            </span>
                        @endif
                    @endforeach
                </p>
            </div>
        @endif
    </div>
</footer>
