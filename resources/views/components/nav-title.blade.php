@props([
    'config' => [],
    'preview' => false,
])

@php
    $config = is_array($config) ? $config : [];
    $showLogo = array_key_exists('show_logo', $config)
        ? (bool) $config['show_logo']
        : true;
    $showSiteName = array_key_exists('show_site_name', $config)
        ? (bool) $config['show_site_name']
        : true;
@endphp

<x-voodbuilder::brand-logos
    :config="$config"
    :show-logo="$showLogo"
    :show-site-name="$showSiteName"
    :preview="$preview"
    {{ $attributes }}
/>
