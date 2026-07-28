@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $config = is_array($config ?? null) ? $config : [];
    $chromeKind = $chromeKind ?? 'tagline';
    $taglineChrome = SiteFooterConfig::chromeAttributes($config, $chromeKind, $preview);
    $taglineClass = $taglineClass ?? 'text-sm text-vp-text-2';
    $taglineText = SiteFooterConfig::resolveTagline(
        is_string($config['tagline'] ?? null) ? $config['tagline'] : null,
    );
@endphp

<p
    @class([$taglineClass, $taglineChrome['class']])
    data-voodbuilder-footer-tagline
    data-voodbuilder-chrome="{{ $chromeKind }}"
    {!! $taglineChrome['attr'] !!}
>{{ $taglineText }}</p>
