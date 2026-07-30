<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Support\ThemeBindings;
use Voodflow\Voodbuilder\Support\ThemeMapAssets;
use Voodflow\Voodbuilder\Support\ThemeMapPayload;

/**
 * Theme Map Bridge.
 */
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

        $this->dispatch('voodbuilder-theme-map-refresh', payload: $this->payload);
        $this->js(ThemeMapAssets::mountJs());
    }

    #[On('voodbuilder-themes-changed')]
    public function reloadFromSettings(): void
    {
        $data = VoodbuilderSettings::data();

        $this->subTheme = SubThemeResolver::resolveId((string) ($data['sub_theme'] ?? SubThemeResolver::SITE))
            ?? SubThemeResolver::SITE;
        $this->channelThemes = ThemeBindings::expandChannelThemesForForm(
            is_array($data['content_channel_sub_themes'] ?? null) ? $data['content_channel_sub_themes'] : [],
        );

        $this->refreshPayload();

        $this->dispatch(
            'voodbuilder-theme-map-sync',
            subTheme: $this->subTheme,
            channelThemes: $this->channelThemes,
        );
    }

    public function refreshPayload(): void
    {
        $this->payload = ThemeMapPayload::build($this->subTheme, $this->channelThemes);
        $this->dispatch('voodbuilder-theme-map-refresh', payload: $this->payload);
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
                ->title(__('voodbuilder::settings.theme_map_invalid_binding'))
                ->danger()
                ->send();

            return;
        }

        $normalized = [];
        $rejected = [];

        foreach ($channelOverrides as $channelId => $themeId) {
            if (! is_string($channelId) || ! is_string($themeId) || ! filled($themeId)) {
                continue;
            }

            if (! ThemeBindings::isValidChannelBinding($channelId, $themeId)) {
                $rejected[] = $channelId;

                continue;
            }

            $override = ThemeMapPayload::normalizeOverride($channelId, $themeId, $resolvedSiteTheme);

            if ($override !== null) {
                $normalized[$channelId] = $override;
            }
        }

        if ($rejected !== []) {
            Notification::make()
                ->title(__('voodbuilder::settings.theme_map_invalid_binding'))
                ->body(__('voodbuilder::settings.theme_map_rejected_bindings'))
                ->warning()
                ->send();
        }

        $this->subTheme = $resolvedSiteTheme;
        $this->channelThemes = $normalized;

        $this->refreshPayload();

        $this->dispatch(
            'voodbuilder-theme-map-sync',
            subTheme: $this->subTheme,
            channelThemes: $this->channelThemes,
        );
    }

    /**
     * @param  array<string, string>  $channelThemes
     */
    #[On('voodbuilder-theme-map-settings-saved')]
    public function syncFromSaved(string $subTheme, array $channelThemes): void
    {
        $this->subTheme = SubThemeResolver::resolveId($subTheme) ?? SubThemeResolver::SITE;
        $this->channelThemes = ThemeBindings::expandChannelThemesForForm($channelThemes);
        $this->refreshPayload();
    }

    public function selectThemeInEditor(string $themeId): void
    {
        $this->dispatch('voodbuilder-select-theme', id: $themeId);
    }

    public function render(): View
    {
        return view('voodbuilder::filament.theme-map-bridge');
    }
}
