@php
    use Voodflow\Vpress\Support\LandingBlockSupport;

    $appearance = LandingBlockSupport::sectionAppearance($config, (string) ($config['background_tone'] ?? 'brand'));
    $align = LandingBlockSupport::textAlignClass((string) ($config['text_align'] ?? 'center'));
    $onDark = LandingBlockSupport::onDarkBackground($config);
    $shell = LandingBlockSupport::sectionShellClass($config, ['section_width' => 'contained']);
@endphp

<section class="vp-landing-banner-cta {{ $shell }} rounded-2xl {{ $appearance['class'] }}" @if ($appearance['style'] !== '') style="{{ $appearance['style'] }}" @endif>
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-4 px-6 py-12 md:px-10 md:py-14 {{ $align }}">
        @if (! empty($config['heading']))
            <h2 class="text-3xl font-bold md:text-4xl">{{ $config['heading'] }}</h2>
        @endif

        @if (! empty($config['subheading']))
            <p class="max-w-2xl text-lg opacity-90">{{ $config['subheading'] }}</p>
        @endif

        @if (! empty($config['button_url']))
            <div class="mt-2 flex flex-wrap gap-3 {{ $config['text_align'] === 'left' ? 'justify-start' : 'justify-center' }}">
                @include('vpress::blocks.landing.partials.button', [
                    'label' => $config['button_label'] ?? __('vpress::landing.learn_more'),
                    'url' => $config['button_url'],
                    'class' => LandingBlockSupport::primaryButtonClass((string) ($config['button_style'] ?? 'solid'), $onDark),
                ])
            </div>
        @endif
    </div>
</section>
