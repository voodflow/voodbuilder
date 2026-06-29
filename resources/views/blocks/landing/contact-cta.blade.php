@php
    use Voodflow\Voodbuilder\Support\LandingBlockContent;

    $section = LandingBlockContent::section($config, [
        'section_width' => 'bleed',
        'section_padding' => 'large',
    ], 'dark');
    $highlight = $config['highlight_phrase'] ?? null;
    $intro = $config['intro'] ?? null;
    $displayEmail = ($config['display_style'] ?? 'email') === 'email';
@endphp

<section
    class="vp-landing-contact-cta {{ $section['shell'] }} {{ $section['corners'] }} {{ $section['appearance']['class'] }} text-center"
    @if ($section['appearance']['style'] !== '') style="{{ $section['appearance']['style'] }}" @endif
>
    <div class="mx-auto max-w-3xl">
        @if (filled($intro))
            <p @class([
                'text-base leading-relaxed md:text-lg',
                'text-white/75' => $section['onDark'],
                'text-vp-text-2' => ! $section['onDark'],
            ])>
                @if (filled($highlight) && is_string($intro) && str_contains($intro, (string) $highlight))
                    {!! str_replace(
                        e($highlight),
                        '<span class="font-semibold text-vp-brand-1">'.e($highlight).'</span>',
                        e($intro),
                    ) !!}
                @else
                    {{ $intro }}
                    @if (filled($highlight))
                        <span class="font-semibold text-vp-brand-1">{{ $highlight }}</span>
                    @endif
                @endif
            </p>
        @endif

        @if (filled($actionUrl) && filled($actionLabel))
            <div class="mt-8">
                <a
                    href="{{ $actionUrl }}"
                    @class([
                        'inline-block border-b-4 border-vp-brand-1 pb-2 font-extrabold uppercase tracking-wide transition hover:text-vp-brand-1',
                        'text-white' => $section['onDark'],
                        'text-vp-text-1' => ! $section['onDark'],
                        'text-3xl md:text-5xl' => $displayEmail,
                        'rounded-lg px-6 py-3 text-sm border-b-0 bg-vp-brand-1 text-white hover:bg-vp-brand-1/90' => ! $displayEmail,
                    ])
                >
                    {{ $actionLabel }}
                </a>
            </div>
        @endif
    </div>
</section>
