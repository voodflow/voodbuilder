@php
    $primaryUrl = $config['primary_url'] ?? config('cosmolab.docs_url');
    $secondaryUrl = $config['secondary_url'] ?? config('cosmolab.shop_url');
@endphp

<div class="VPHero">
    <p class="name">{{ $config['name'] ?? config('app.name') }}</p>
    <p class="text">{{ $config['headline'] ?? '' }}</p>
    @if(filled($config['tagline'] ?? null))
        <p class="tagline">{{ $config['tagline'] }}</p>
    @endif
    <div class="actions">
        @if(filled($config['primary_label'] ?? null) && filled($primaryUrl))
            <a href="{{ $primaryUrl }}" @if(str_starts_with($primaryUrl, 'http')) target="_blank" rel="noopener" @endif class="VPButton brand">
                {{ $config['primary_label'] }}
            </a>
        @endif
        @if(filled($config['secondary_label'] ?? null) && filled($secondaryUrl))
            <a href="{{ $secondaryUrl }}" target="_blank" rel="noopener" class="VPButton alt">
                {{ $config['secondary_label'] }}
            </a>
        @endif
    </div>
</div>
