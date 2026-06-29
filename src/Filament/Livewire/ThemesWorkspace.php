<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Livewire;

use Filament\Livewire\Notifications as FilamentNotifications;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\SubThemeCloner;
use Voodflow\Vpress\Support\SubThemeExporter;
use Voodflow\Vpress\Support\SubThemeImporter;
use Voodflow\Vpress\Support\SubThemeManager;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\SubThemeResolver;
use Voodflow\Vpress\Support\ThemeAssetCompiler;
use Voodflow\Vpress\Support\ThemeBindings;
use Voodflow\Vpress\Support\ThemePalette;
use Voodflow\Vpress\Support\ThemePaletteGenerator;
use Voodflow\Vpress\Support\ThemePresenter;

class ThemesWorkspace extends Component
{
    use WithFileUploads;

    public ?string $selectedId = null;

    public string $label = '';

    public string $themeSlug = '';

    public string $description = '';

    public bool $metaEditable = false;

    public bool $canEditColors = false;

    /** @var array<string, ?string> */
    public array $light = [];

    /** @var array<string, ?string> */
    public array $dark = [];

    public bool $showColorModal = false;

    public string $colorMode = 'light';

    public string $colorKey = 'primary';

    public string $colorValue = '#3451b2';

    public bool $showGenerateModal = false;

    public string $seedPrimary = '#3451b2';

    public string $seedSecondary = '';

    public string $seedHeaderBg = '';

    public bool $showDeleteModal = false;

    public string $deleteFallbackId = 'site';

    public bool $showCloneModal = false;

    public string $cloneSourceId = '';

    public string $cloneTargetId = '';

    public string $cloneLabel = '';

    /** @var TemporaryUploadedFile|null */
    public $importArchive = null;

    /** @var array{custom: list<array<string, mixed>>, plugin: list<array<string, mixed>>} */
    public array $groups = [
        'custom' => [],
        'plugin' => [],
    ];

    public function mount(): void
    {
        $this->syncThemeGroups();
    }

    #[On('vpress-themes-changed')]
    public function refreshThemeCatalog(): void
    {
        $this->syncThemeGroups();
    }

    public function closeEditor(): void
    {
        $this->selectedId = null;
    }

    public function selectTheme(string $id): void
    {
        if (! app(SubThemeRegistry::class)->exists($id)) {
            return;
        }

        $this->selectedId = $id;
        $card = ThemePresenter::card($id);
        $this->label = $card['label'];
        $this->themeSlug = $id;
        $this->description = $card['description'];
        $this->metaEditable = $card['can_edit_meta'];
        $this->canEditColors = $card['can_edit_colors'];
        $this->light = ThemePresenter::modeColors($id, 'light');
        $this->dark = ThemePresenter::modeColors($id, 'dark');
        $this->seedPrimary = $this->light['primary'] ?? '#3451b2';
        $this->seedSecondary = $this->light['secondary'] ?? '';
        $this->seedHeaderBg = $this->light['header_bg'] ?? '';
    }

    #[On('vpress-select-theme')]
    public function onSelectTheme(string $id): void
    {
        $this->selectTheme($id);
    }

    public function openColor(string $mode, string $key): void
    {
        if (! $this->canEditColors) {
            return;
        }

        $this->colorMode = $mode;
        $this->colorKey = $key;
        $palette = $mode === 'dark' ? $this->dark : $this->light;
        $this->colorValue = $palette[$key] ?? '#3451b2';
        $this->showColorModal = true;
    }

    public function updatedColorValue(?string $value): void
    {
        if (! $this->canEditColors || ! $this->showColorModal || $this->selectedId === null) {
            return;
        }

        $sanitized = ThemePalette::sanitizeColor($value ?? '');

        if ($sanitized === null) {
            return;
        }

        if ($this->colorMode === 'dark') {
            $this->dark[$this->colorKey] = $sanitized;
        } else {
            $this->light[$this->colorKey] = $sanitized;
        }

        $this->persistColors();
    }

    public function closeColorModal(): void
    {
        $this->showColorModal = false;
    }

    public function clearColor(string $mode, string $key): void
    {
        if (! $this->canEditColors) {
            return;
        }

        if ($mode === 'dark') {
            $this->dark[$key] = null;
        } else {
            $this->light[$key] = null;
        }

        $this->showColorModal = false;
        $this->persistColors();
    }

    public function updatedLabel(?string $value): void
    {
        if (! $this->metaEditable || $this->selectedId === null) {
            return;
        }

        $this->themeSlug = SubThemeManager::slugFromLabel((string) $value);
    }

    public function saveMetadata(): void
    {
        if ($this->selectedId === null || ! $this->metaEditable) {
            return;
        }

        $previousId = $this->selectedId;
        $result = SubThemeManager::saveAppThemeMetadata(
            $this->selectedId,
            $this->label,
            $this->description,
            $this->themeSlug,
        );

        if (! $result->success) {
            Notification::make()->title(__('vpress::settings.theme_workspace_save_failed'))->body($result->error)->danger()->send();

            return;
        }

        if ($result->id !== $previousId) {
            $this->selectedId = $result->id;
            $this->themeSlug = $result->id;
            $this->dispatch('vpress-themes-changed');
        }

        Notification::make()->title(__('vpress::settings.theme_workspace_meta_saved'))->success()->send();
    }

    public function persistColors(): void
    {
        if ($this->selectedId === null || ! $this->canEditColors) {
            return;
        }

        $colors = VpressSettings::get('sub_theme_colors', []);

        if (! is_array($colors)) {
            $colors = [];
        }

        $colors[$this->selectedId] = [
            'custom' => true,
            'light' => array_filter($this->light, static fn (?string $v): bool => filled($v)),
            'dark' => array_filter($this->dark, static fn (?string $v): bool => filled($v)),
        ];

        VpressSettings::saveData([
            'sub_theme_colors' => ThemePalette::normalize($colors),
        ]);

        $this->dispatch('vpress-theme-colors-updated');
    }

    public function openGenerateModal(): void
    {
        $this->seedPrimary = $this->light['primary'] ?? '#3451b2';
        $this->seedSecondary = $this->light['secondary'] ?? '';
        $this->seedHeaderBg = $this->light['header_bg'] ?? '';
        $this->showGenerateModal = true;
    }

    public function generatePalette(): void
    {
        if ($this->selectedId === null || ! $this->canEditColors) {
            return;
        }

        try {
            $palette = ThemePaletteGenerator::fromSeeds(
                $this->seedPrimary,
                filled($this->seedSecondary) ? $this->seedSecondary : null,
                filled($this->seedHeaderBg) ? $this->seedHeaderBg : null,
            );
        } catch (\InvalidArgumentException $exception) {
            Notification::make()->title(__('vpress::settings.generate_theme_palette_failed'))->body($exception->getMessage())->danger()->send();

            return;
        }

        $this->light = array_merge($this->light, $palette['light']);
        $this->dark = array_merge($this->dark, $palette['dark']);
        $this->showGenerateModal = false;
        $this->persistColors();

        Notification::make()->title(__('vpress::settings.generate_theme_palette_success'))->success()->send();
    }

    public function syncDarkFromLight(): void
    {
        if ($this->selectedId === null || ! $this->canEditColors) {
            return;
        }

        if (! filled($this->light['primary'] ?? null)) {
            Notification::make()
                ->title(__('vpress::settings.sync_dark_theme_colors_failed'))
                ->body(__('vpress::settings.sync_dark_theme_colors_missing_light'))
                ->warning()
                ->send();

            return;
        }

        $this->dark = ThemePaletteGenerator::darkFromLight(array_filter(
            $this->light,
            static fn (?string $value): bool => filled($value),
        ));

        $this->persistColors();

        Notification::make()->title(__('vpress::settings.sync_dark_theme_colors_success'))->success()->send();
    }

    public function resetColors(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        ThemePalette::resetForTheme($this->selectedId);
        $this->light = ThemePresenter::modeColors($this->selectedId, 'light');
        $this->dark = ThemePresenter::modeColors($this->selectedId, 'dark');

        Notification::make()->title(__('vpress::settings.reset_theme_colors_success'))->success()->send();
    }

    public function openCloneModal(string $sourceId): void
    {
        $this->cloneSourceId = $sourceId;
        $this->cloneTargetId = SubThemeCloner::suggestCloneId($sourceId);
        $this->cloneLabel = app(SubThemeRegistry::class)->label($sourceId).' copy';
        $this->showCloneModal = true;
    }

    public function executeClone(): void
    {
        $result = SubThemeCloner::clone(
            sourceId: $this->cloneSourceId,
            targetId: $this->cloneTargetId,
            label: $this->cloneLabel,
            importColors: true,
        );

        if (! $result->success) {
            Notification::make()->title(__('vpress::settings.clone_theme_failed'))->body($result->error)->danger()->send();

            return;
        }

        $this->showCloneModal = false;
        $this->selectTheme($result->id);
        $this->syncThemeGroups();

        ThemeAssetCompiler::scheduleCompile();

        $this->notify(
            Notification::make()
                ->title(__('vpress::settings.clone_theme_created'))
                ->body(__('vpress::settings.theme_assets_rebuilding'))
                ->success(),
        );

        $this->dispatch('vpress-themes-changed');
    }

    public function confirmDelete(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $this->showDeleteModal = true;

        $siteTheme = (string) (VpressSettings::get('sub_theme') ?: SubThemeResolver::SITE);
        $deleteId = $this->selectedId;

        if ($siteTheme === $deleteId) {
            $this->deleteFallbackId = collect(app(SubThemeRegistry::class)->ids())
                ->first(static fn (string $id): bool => $id !== $deleteId) ?? SubThemeResolver::SITE;
        } else {
            $this->deleteFallbackId = $siteTheme;
        }
    }

    public function deleteTheme(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $id = $this->selectedId;
        $result = SubThemeManager::delete($id, $this->deleteFallbackId);

        if (! $result->success) {
            Notification::make()->title(__('vpress::settings.delete_theme_failed'))->body($result->error)->danger()->send();

            return;
        }

        $this->showDeleteModal = false;
        $this->selectedId = null;
        $this->syncThemeGroups();

        $this->notify(
            Notification::make()->title(__('vpress::settings.delete_theme_success'))->success(),
        );

        $saved = VpressSettings::data();
        $this->dispatch(
            'vpress-theme-map-settings-saved',
            subTheme: (string) ($saved['sub_theme'] ?? SubThemeResolver::SITE),
            channelThemes: ThemeBindings::expandChannelThemesForForm(
                is_array($saved['content_channel_sub_themes'] ?? null) ? $saved['content_channel_sub_themes'] : [],
            ),
        );
        $this->dispatch('vpress-themes-changed');
    }

    public function exportTheme(string $id): BinaryFileResponse
    {
        $archivePath = SubThemeExporter::export($id, SubThemeExporter::defaultArchivePath($id));

        return response()->download($archivePath)->deleteFileAfterSend();
    }

    public function importTheme(): void
    {
        if ($this->importArchive === null) {
            $this->notify(
                Notification::make()
                    ->title(__('vpress::settings.import_theme_failed'))
                    ->body(__('vpress::settings.import_theme_missing_archive'))
                    ->danger(),
            );

            return;
        }

        $path = SubThemeImporter::resolveArchiveUploadPath($this->importArchive);

        if ($path === null) {
            $this->notify(
                Notification::make()
                    ->title(__('vpress::settings.import_theme_failed'))
                    ->body(__('vpress::settings.import_theme_missing_archive'))
                    ->danger(),
            );
            $this->importArchive = null;

            return;
        }

        try {
            $this->validate([
                'importArchive' => ['required', 'file', 'mimes:zip', 'max:51200'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->notify(
                Notification::make()
                    ->title(__('vpress::settings.import_theme_failed'))
                    ->body($exception->validator->errors()->first('importArchive'))
                    ->danger(),
            );
            $this->importArchive = null;

            return;
        }

        $result = SubThemeImporter::import($path);

        if (! $result->success) {
            $this->notify(
                Notification::make()
                    ->title(__('vpress::settings.import_theme_failed'))
                    ->body($result->error)
                    ->danger(),
            );
            $this->importArchive = null;

            return;
        }

        $this->importArchive = null;
        $this->syncThemeGroups();
        $this->selectTheme($result->id);

        ThemeAssetCompiler::scheduleCompile();

        $body = filled($result->renamedFrom)
            ? __('vpress::settings.import_theme_renamed', ['from' => $result->renamedFrom, 'id' => $result->id])
            : __('vpress::settings.import_theme_success', ['id' => $result->id]);

        $this->notify(
            Notification::make()
                ->title(__('vpress::settings.import_theme_imported'))
                ->body(trim($body.' '.__('vpress::settings.theme_assets_rebuilding')))
                ->success(),
        );

        $this->dispatch('vpress-themes-changed');
    }

    public function updatedImportArchive(): void
    {
        if ($this->importArchive === null) {
            return;
        }

        if (SubThemeImporter::resolveArchiveUploadPath($this->importArchive) === null) {
            return;
        }

        $this->importTheme();
    }

    public function prepareDelete(string $id): void
    {
        $this->selectTheme($id);
        $this->confirmDelete();
    }

    public function render(): View
    {
        return view('vpress::filament.themes-workspace', [
            'groups' => $this->groups,
            'colorKeys' => ThemePresenter::COLOR_KEYS,
            'fallbackOptions' => collect(app(SubThemeRegistry::class)->options())
                ->reject(fn (string $label, string $id): bool => $this->showDeleteModal && $id === $this->selectedId)
                ->all(),
            'colorKeyLabel' => ThemePresenter::colorLabel($this->colorKey),
        ]);
    }

    private function syncThemeGroups(): void
    {
        $this->groups = ThemePresenter::groupedCards();
    }

    private function notify(Notification $notification): void
    {
        $notification->send();

        $payload = $notification->toArray();

        $this->dispatch('notificationSent', notification: $payload)
            ->to(FilamentNotifications::class);

        $this->dispatch('notificationsSent')
            ->to(FilamentNotifications::class);
    }
}
