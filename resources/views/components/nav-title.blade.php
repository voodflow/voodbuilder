@props([
    'config' => [],
])

@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $config = is_array($config) ? $config : [];
    $showLogo = array_key_exists('show_logo', $config)
        ? (bool) $config['show_logo']
        : true;
    $showSiteName = array_key_exists('show_site_name', $config)
        ? (bool) $config['show_site_name']
        : (bool) VoodbuilderSettings::get('show_site_title', true);
@endphp

<x-voodbuilder::brand-logos
    :config="$config"
    :show-logo="$showLogo"
    :show-site-name="$showSiteName"
    {{ $attributes }}
/>
