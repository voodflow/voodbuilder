@php
    use Voodflow\Vpress\Support\LandingBlockContent;
    use Voodflow\Vpress\Support\LandingBlockSupport;
    use Voodflow\Vpress\Support\ResolvableLinkSupport;

    $section = LandingBlockContent::section($config, [
        'section_width' => 'bleed',
        'section_padding' => 'large',
    ]);
    $minHeight = ($config['tall'] ?? false) ? 'min-h-[28rem]' : 'min-h-[20rem]';
    $primaryUrl = ResolvableLinkSupport::resolve($config, 'primary_button', 'primary_button_url');
    $secondaryUrl = ResolvableLinkSupport::resolve($config, 'secondary_button', 'secondary_button_url');
@endphp

<section
    class="vp-landing-hero {{ $section['shell'] }} {{ $section['appearance']['class'] }} {{ $minHeight }} flex flex-col justify-center px-6 md:px-10"
    @if ($section['appearance']['style'] !== '') style="{{ $section['appearance']['style'] }}" @endif
>
    <div class="mx-auto flex w-full {{ LandingBlockSupport::innerWidthClass($config) }} flex-col gap-6 {{ $section['align'] }}">
        @if (! empty($config['eyebrow']))
            <p class="text-sm font-semibold uppercase tracking-[0.2em] opacity-80">{{ $config['eyebrow'] }}</p>
        @endif

        @if (! empty($config['heading']))
            <h1 class="max-w-4xl text-4xl font-bold leading-tight md:text-5xl lg:text-6xl">{{ $config['heading'] }}</h1>
        @endif

        @if (! empty($config['subheading']))
            <p class="max-w-3xl text-lg opacity-90 md:text-xl">{{ $config['subheading'] }}</p>
        @endif

        @if (filled($primaryUrl) || filled($secondaryUrl))
            <div class="mt-2 flex flex-wrap gap-3 {{ $config['text_align'] === 'left' ? 'justify-start' : 'justify-center' }}">
                @include('vpress::blocks.landing.partials.button', [
                    'label' => $config['primary_button_label'] ?? null,
                    'url' => $primaryUrl,
                    'class' => LandingBlockSupport::primaryButtonClass((string) ($config['primary_button_style'] ?? 'solid'), $section['onDark']),
                    'open_in_new_tab' => ResolvableLinkSupport::opensInNewTab($config, 'primary_button'),
                ])
                @include('vpress::blocks.landing.partials.button', [
                    'label' => $config['secondary_button_label'] ?? null,
                    'url' => $secondaryUrl,
                    'class' => LandingBlockSupport::secondaryButtonClass($section['onDark']),
                    'open_in_new_tab' => ResolvableLinkSupport::opensInNewTab($config, 'secondary_button'),
                ])
            </div>
        @endif
    </div>
</section>
