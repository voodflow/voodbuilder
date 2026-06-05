@if(filled($config['features'] ?? null))
    <section class="VPFeatures">
        @if(filled($config['title'] ?? null))
            <h2 class="VPFeaturesTitle">{{ $config['title'] }}</h2>
        @endif
        <div class="VPFeaturesGrid">
            @foreach($config['features'] as $feature)
                <div class="VPFeature">
                    @if(filled($feature['icon'] ?? null))
                        <div class="VPFeatureIcon">{{ $feature['icon'] }}</div>
                    @endif
                    <h3 class="VPFeatureTitle">{{ $feature['title'] ?? '' }}</h3>
                    <p class="VPFeatureText">{{ $feature['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
