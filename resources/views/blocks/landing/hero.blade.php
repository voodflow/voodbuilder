@php
    use Voodflow\Vpress\Support\LandingBlockSupport;

    $appearance = LandingBlockSupport::sectionAppearance($config, 'brand');
    $align = LandingBlockSupport::textAlignClass((string) ($config['text_align'] ?? 'center'));
    $onDark = LandingBlockSupport::onDarkBackground($config);
    $minHeight = ($config['tall'] ?? false) ? 'min-h-[28rem]' : 'min-h-[20rem]';
    $shell = LandingBlockSupport::sectionShellClass($config, [
        'section_width' => 'bleed',
        'section_padding' => 'large',
    ]);
@endphp

<section class="vp-landing-hero {{ $shell }} {{ $appearance['class'] }} {{ $minHeight }} flex flex-col justify-center px-6 md:px-10" @if ($appearance['style'] !== '') style="{{ $appearance['style'] }}" @endif>
    <div class="mx-auto flex w-full {{ LandingBlockSupport::innerWidthClass($config) }} flex-col gap-6 {{ $align }}">
        @if (! empty($config['eyebrow']))
            <p class="text-sm font-semibold uppercase tracking-[0.2em] opacity-80">{{ $config['eyebrow'] }}</p>
        @endif

        @if (! empty($config['heading']))
            <h1 class="max-w-4xl text-4xl font-bold leading-tight md:text-5xl lg:text-6xl">{{ $config['heading'] }}</h1>
        @endif

        @if (! empty($config['subheading']))
            <p class="max-w-3xl text-lg opacity-90 md:text-xl">{{ $config['subheading'] }}</p>
        @endif

        @if (! empty($config['primary_button_url']) || ! empty($config['secondary_button_url']))
            <div class="mt-2 flex flex-wrap gap-3 {{ $config['text_align'] === 'left' ? 'justify-start' : 'justify-center' }}">
                @include('vpress::blocks.landing.partials.button', [
                    'label' => $config['primary_button_label'] ?? null,
                    'url' => $config['primary_button_url'] ?? null,
                    'class' => LandingBlockSupport::primaryButtonClass((string) ($config['primary_button_style'] ?? 'solid'), $onDark),
                ])
                @include('vpress::blocks.landing.partials.button', [
                    'label' => $config['secondary_button_label'] ?? null,
                    'url' => $config['secondary_button_url'] ?? null,
                    'class' => LandingBlockSupport::secondaryButtonClass($onDark),
                ])
            </div>
        @endif
    </div>
</section>
