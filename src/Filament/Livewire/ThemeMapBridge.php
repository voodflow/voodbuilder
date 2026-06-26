<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Livewire;

use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\SubThemeResolver;
use Voodflow\Vpress\Support\ThemeBindings;
use Voodflow\Vpress\Support\ThemeMapAssets;
use Voodflow\Vpress\Support\ThemeMapPayload;

class ThemeMapBridge extends Component
{
    public string $subTheme = 'site';

    /** @var array<string, string> */
    public array $channelThemes = [];

    /** @var array<string, mixed> */
    public array $payload = [];

    /**
     * @param  array<string, string>  $channelThemes
     */
    public function mount(string $subTheme = 'site', array $channelThemes = []): void
    {
        $this->subTheme = SubThemeResolver::resolveId($subTheme) ?? SubThemeResolver::SITE;
        $this->channelThemes = ThemeBindings::expandChannelThemesForForm($channelThemes);
        $this->refreshPayload();

        $this->dispatch('vpress-theme-map-refresh', payload: $this->payload);
        $this->js(ThemeMapAssets::mountJs());
    }

    #[On('vpress-themes-changed')]
    public function refreshPayload(): void
    {
        $this->payload = ThemeMapPayload::build($this->subTheme, $this->channelThemes);
        $this->dispatch('vpress-theme-map-refresh', payload: $this->payload);
        $this->js(ThemeMapAssets::mountJs());
    }

    /**
     * @param  array<string, string|null>  $channelOverrides
     */
    public function applyAssignments(string $subTheme, array $channelOverrides): void
    {
        $resolvedSiteTheme = SubThemeResolver::resolveId($subTheme) ?? SubThemeResolver::SITE;

        if (! app(SubThemeRegistry::class)->exists($resolvedSiteTheme)) {
            Notification::make()
                ->title(__('vpress::settings.theme_map_invalid_binding'))
                ->danger()
                ->send();

            return;
        }

        $normalized = [];

        foreach ($channelOverrides as $channelId => $themeId) {
            if (! is_string($channelId) || ! is_string($themeId) || ! filled($themeId)) {
                continue;
            }

            if (! ThemeBindings::isValidChannelBinding($channelId, $themeId)) {
                continue;
            }

            $override = ThemeMapPayload::normalizeOverride($channelId, $themeId, $resolvedSiteTheme);

            if ($override !== null) {
                $normalized[$channelId] = $override;
            }
        }

        $this->subTheme = $resolvedSiteTheme;
        $this->channelThemes = $normalized;

        $this->dispatch(
            'vpress-theme-map-sync',
            subTheme: $this->subTheme,
            channelThemes: $this->channelThemes,
        );
    }

    public function selectThemeInEditor(string $themeId): void
    {
        $this->dispatch('vpress-select-theme', id: $themeId);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('vpress::filament.theme-map-bridge');
    }
}
