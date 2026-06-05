<section class="VPPartner">
    @if(filled($config['title'] ?? null))
        <h2 class="VPPartnerTitle">{{ $config['title'] }}</h2>
    @endif
    @if(filled($config['text'] ?? null))
        <p class="VPPartnerText">{{ $config['text'] }}</p>
    @endif
    @if(filled($config['email'] ?? null))
        <a href="mailto:{{ $config['email'] }}" class="VPPartnerLink">{{ $config['email'] }}</a>
    @endif
</section>
