@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $chromeKind = $chromeKind ?? 'tagline';
    $taglineChrome = SiteFooterConfig::chromeAttributes($config, $chromeKind, $preview);
    $taglineClass = $taglineClass ?? 'text-sm text-vp-text-2';
@endphp

<p
    @class([$taglineClass, $taglineChrome['class']])
    data-voodbuilder-footer-tagline
    data-voodbuilder-chrome="{{ $chromeKind }}"
    {!! $taglineChrome['attr'] !!}
>
    {{ __('voodbuilder::pro.grapesjs.blocks.footer_default_tagline') }}
</p>
