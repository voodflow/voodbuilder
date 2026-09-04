<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Livewire;

use Filament\Livewire\Notifications as FilamentNotifications;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\ActiveThemeMap;
use Voodflow\Voodbuilder\Support\SubThemeCloner;
use Voodflow\Voodbuilder\Support\SubThemeExporter;
use Voodflow\Voodbuilder\Support\SubThemeImporter;
use Voodflow\Voodbuilder\Support\SubThemeManager;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Support\ThemeAssetCompiler;
use Voodflow\Voodbuilder\Support\ThemeBindings;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Support\ThemePaletteGenerator;
use Voodflow\Voodbuilder\Support\ThemePresenter;

/**
 * Themes Workspace.
 */
class ThemesWorkspace extends Component
{
    use WithFileUploads;

    public ?string $selectedId = null;

    public string $label = '';

    public string $themeSlug = '';

    public string $description = '';

    public bool $metaEditable = false;

    public bool $canEditColors = false;

    public bool $canDelete = false;

    /** @var array<string, string|int|null> */
    public array $light = [];

    /** @var array<string, string|int|null> */
    public array $dark = [];

    public bool $showColorModal = false;

    public string $colorMode = 'light';

    public string $colorKey = 'primary';

    public string $colorValue = '#3451b2';

    public bool $headerBgTransparent = false;

    public int $headerBgOpacity = 80;

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

    /**
     * Color scheme copied for paste onto another custom theme.
     *
     * @var array{version: int, type: string, source_id: string, light: array<string, ?string>, dark: array<string, ?string>}|null
     */
    public ?array $copiedColorScheme = null;

    /** @var array{custom: list<array<string, mixed>>, plugin: list<array<string, mixed>>} */
    public array $groups = [
        'custom' => [],
        'plugin' => [],
    ];

    /**
     * Theme Studio layout role:
     * - full: legacy single column (catalog + editor)
     * - catalog: sidebar list only
     * - editor: customize panel only
     */
    public string $studioRole = 'full';

    /** Brand tab: colors-first desk (no close / lighter chrome). */
    public bool $brandDesk = false;

    /** Theme Studio desk: denser edit UI + live preview. */
    public bool $studioDesk = false;

    /** Which palette drives the live preview swatches. */
    public string $previewMode = 'light';

    /** Catalog filter tab when studioDesk: plugin | custom. */
    public string $catalogTab = 'custom';

    public function mount(
        string $studioRole = 'full',
        ?string $initialThemeId = null,
        bool $brandDesk = false,
        bool $studioDesk = false,
    ): void {
        $this->studioRole = match ($studioRole) {
            'catalog', 'editor', 'full' => $studioRole,
            default => 'full',
        };
        $this->brandDesk = $brandDesk;
        $this->studioDesk = $studioDesk;
        $this->syncThemeGroups();

        if ($this->studioDesk && $this->groups['custom'] === []) {
            $this->catalogTab = 'plugin';
        }

        if ($this->studioRole === 'editor' && filled($initialThemeId) && app(SubThemeRegistry::class)->exists($initialThemeId)) {
            $this->hydrateEditorFor($initialThemeId);
        }
    }

    public function setPreviewMode(string $mode): void
    {
        if (in_array($mode, ['light', 'dark'], true)) {
            $this->previewMode = $mode;
        }
    }

    public function setCatalogTab(string $tab): void
    {
        if (in_array($tab, ['plugin', 'custom'], true)) {
            $this->catalogTab = $tab;
        }
    }

    #[On('voodbuilder-studio-clone-editing')]
    public function cloneEditingTheme(): void
    {
        if ($this->studioRole !== 'editor' || $this->selectedId === null) {
            return;
        }

        $this->openCloneModal($this->selectedId);
    }

    #[On('voodbuilder-studio-export-editing')]
    public function exportEditingTheme(): void
    {
        if ($this->studioRole !== 'editor' || $this->selectedId === null) {
            return;
        }

        $this->exportTheme($this->selectedId);
    }

    /**
     * Load editor fields without switching studio mode (used on mount / cross-component sync).
     */
    protected function hydrateEditorFor(string $id): void
    {
        $this->selectedId = $id;
        $card = ThemePresenter::card($id);
        $this->label = $card['label'];
        $this->themeSlug = $id;
        $this->description = $card['description'];
        $this->metaEditable = $card['can_edit_meta'];
        $this->canEditColors = $card['can_edit_colors'];
        $this->canDelete = $card['can_delete'];
        $this->light = ThemePresenter::modeColors($id, 'light');
        $this->dark = ThemePresenter::modeColors($id, 'dark');
        $this->seedPrimary = $this->light['primary'] ?? '#3451b2';
        $this->seedSecondary = $this->light['secondary'] ?? '';
        $this->seedHeaderBg = $this->light['header_bg'] ?? '';
    }

    #[On('voodbuilder-themes-changed')]
    public function refreshThemeCatalog(): void
    {
        $this->syncThemeGroups();
    }

    #[On('voodbuilder-theme-colors-updated')]
    public function refreshThemeCatalogColors(): void
    {
        if ($this->studioRole === 'editor') {
            return;
        }

        $this->syncThemeGroups();
    }

    public function closeEditor(): void
    {
        $this->selectedId = null;
        $this->dispatch('theme-studio-close-editor');
        $this->dispatch('voodbuilder-theme-studio-highlight', id: null);
    }

    public function selectTheme(string $id): void
    {
        if (! app(SubThemeRegistry::class)->exists($id)) {
            return;
        }

        if ($this->studioRole === 'catalog') {
            $this->selectedId = $id;
            $this->dispatch('voodbuilder-theme-studio-edit', id: $id);

            return;
        }

        $this->hydrateEditorFor($id);

        if ($this->studioRole === 'full') {
            $this->dispatch('theme-studio-open-editor', id: $id);
        }
    }

    #[On('voodbuilder-select-theme')]
    public function onSelectTheme(string $id): void
    {
        if ($this->studioRole === 'catalog') {
            $this->selectedId = app(SubThemeRegistry::class)->exists($id) ? $id : null;
            $this->dispatch('voodbuilder-theme-studio-edit', id: $id);

            return;
        }

        $this->selectTheme($id);
    }

    #[On('voodbuilder-theme-studio-edit')]
    public function onStudioEdit(string $id): void
    {
        // Brand desk stays locked to the Site pages layout via hydrate only.
        if ($this->brandDesk) {
            return;
        }

        if ($this->studioRole === 'catalog') {
            $this->selectedId = app(SubThemeRegistry::class)->exists($id) ? $id : null;

            return;
        }

        if ($this->studioRole === 'editor' || $this->studioRole === 'full') {
            if (! app(SubThemeRegistry::class)->exists($id)) {
                return;
            }

            $this->hydrateEditorFor($id);
        }
    }

    /**
     * Load theme into the editor without asking Theme Studio to switch tabs.
     */
    #[On('voodbuilder-theme-studio-hydrate')]
    public function onStudioHydrate(string $id): void
    {
        if ($this->studioRole !== 'editor' && $this->studioRole !== 'full') {
            return;
        }

        // Brand desk always follows Site pages layout; layout editor ignores these.
        if (! $this->brandDesk) {
            return;
        }

        if (! app(SubThemeRegistry::class)->exists($id)) {
            return;
        }

        $this->hydrateEditorFor($id);
    }

    #[On('voodbuilder-theme-studio-highlight')]
    public function onStudioHighlight(?string $id): void
    {
        if ($this->studioRole !== 'catalog') {
            return;
        }

        $this->selectedId = filled($id) && app(SubThemeRegistry::class)->exists($id) ? $id : null;
    }

    #[On('theme-studio-close-editor')]
    public function onStudioCloseEditor(): void
    {
        if ($this->studioRole === 'editor') {
            $this->selectedId = null;
        }
    }

    public function openColor(string $mode, string $key): void
    {
        if (! $this->canEditColors) {
            return;
        }

        $this->colorMode = $mode;
        $this->colorKey = $key;
        $palette = $mode === 'dark' ? $this->dark : $this->light;
        $this->colorValue = is_string($palette[$key] ?? null) ? $palette[$key] : '#3451b2';

        if ($key === 'header_bg') {
            $opacity = ThemePalette::sanitizeOpacity($palette['header_bg_opacity'] ?? null);
            $this->headerBgTransparent = $opacity !== null && $opacity < 100;
            $this->headerBgOpacity = $opacity ?? 80;
        } else {
            $this->headerBgTransparent = false;
            $this->headerBgOpacity = 80;
        }

        $this->showColorModal = true;
    }

    public function updatedHeaderBgTransparent(bool $value): void
    {
        if (! $this->canEditColors || ! $this->showColorModal || $this->colorKey !== 'header_bg') {
            return;
        }

        if ($value) {
            if ($this->headerBgOpacity >= 100) {
                $this->headerBgOpacity = 80;
            }

            $this->writeHeaderBgOpacity($this->headerBgOpacity);
        } else {
            $this->writeHeaderBgOpacity(null);
        }

        $this->persistColors();
    }

    public function updatedHeaderBgOpacity(int|string|null $value): void
    {
        if (! $this->canEditColors || ! $this->showColorModal || $this->colorKey !== 'header_bg') {
            return;
        }

        if (! $this->headerBgTransparent) {
            return;
        }

        $opacity = ThemePalette::sanitizeOpacity($value) ?? 80;
        $this->headerBgOpacity = max(0, min(100, $opacity));
        $this->writeHeaderBgOpacity($this->headerBgOpacity);
        $this->persistColors();
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

            if ($key === 'header_bg') {
                unset($this->dark['header_bg_opacity']);
            }
        } else {
            $this->light[$key] = null;

            if ($key === 'header_bg') {
                unset($this->light['header_bg_opacity']);
            }
        }

        $this->headerBgTransparent = false;
        $this->headerBgOpacity = 80;
        $this->showColorModal = false;
        $this->persistColors();
    }

    public function updatedLabel(?string $value): void
    {
        if (! $this->metaEditable || $this->selectedId === null) {
            return;
        }

        $this->themeSlug = SubThemeManager::slugFromLabel((string) $value);

        if ($this->studioDesk) {
            $this->persistMetadata(notify: false);
        }
    }

    public function updatedThemeSlug(?string $value): void
    {
        if ($this->studioDesk) {
            $this->persistMetadata(notify: false);
        }
    }

    public function updatedDescription(?string $value): void
    {
        if ($this->studioDesk) {
            $this->persistMetadata(notify: false);
        }
    }

    public function saveMetadata(): void
    {
        $this->persistMetadata(notify: true);
    }

    protected function persistMetadata(bool $notify = true): void
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
            Notification::make()->title(__('voodbuilder::settings.theme_workspace_save_failed'))->body($result->error)->danger()->send();

            return;
        }

        if ($result->id !== $previousId) {
            $this->selectedId = $result->id;
            $this->themeSlug = $result->id;
            $this->dispatch('voodbuilder-themes-changed');
        }

        if ($notify) {
            Notification::make()->title(__('voodbuilder::settings.theme_workspace_meta_saved'))->success()->send();
        }
    }

    public function persistColors(): void
    {
        if ($this->selectedId === null || ! $this->canEditColors) {
            return;
        }

        $this->writePaletteToThemes([$this->selectedId]);
        $this->dispatch('voodbuilder-theme-colors-updated');
    }

    /**
     * Copy the current desk palette onto every layout currently assigned in Areas.
     */
    public function applyColorsToAssignedLayouts(): void
    {
        if ($this->selectedId === null || ! $this->canEditColors || ! $this->brandDesk) {
            return;
        }

        $targets = $this->assignedThemeIds();

        if ($targets === []) {
            $targets = [$this->selectedId];
        }

        $this->writePaletteToThemes($targets);

        Notification::make()
            ->title(__('voodbuilder::settings.theme_studio_colors_applied_all'))
            ->success()
            ->send();

        $this->dispatch('voodbuilder-theme-colors-updated');
    }

    /**
     * @param  list<string>  $themeIds
     */
    private function writePaletteToThemes(array $themeIds): void
    {
        $colors = VoodbuilderSettings::get('sub_theme_colors', []);

        if (! is_array($colors)) {
            $colors = [];
        }

        $palette = [
            'custom' => true,
            'light' => $this->filterModeForPersist($this->light),
            'dark' => $this->filterModeForPersist($this->dark),
        ];

        foreach ($themeIds as $themeId) {
            if ($themeId === '') {
                continue;
            }

            $colors[$themeId] = $palette;
        }

        VoodbuilderSettings::saveData([
            'sub_theme_colors' => ThemePalette::normalize($colors),
        ]);
    }

    /**
     * Theme ids currently used by Site pages + content channels.
     *
     * @return list<string>
     */
    private function assignedThemeIds(): array
    {
        $data = [
            'sub_theme' => VoodbuilderSettings::get('sub_theme'),
            'content_channel_sub_themes' => VoodbuilderSettings::get('content_channel_sub_themes', []),
        ];

        $ids = [];

        foreach (ActiveThemeMap::assignments($data) as $row) {
            $themeId = (string) ($row['theme_id'] ?? '');

            if ($themeId !== '') {
                $ids[$themeId] = $themeId;
            }
        }

        return array_values($ids);
    }

    /**
     * Options for the site-colors layout picker (assigned layouts first, then others).
     *
     * @return array<string, string>
     */
    public function brandThemeOptions(): array
    {
        $registry = app(SubThemeRegistry::class);
        $assigned = $this->assignedThemeIds();
        $options = [];

        foreach ($assigned as $themeId) {
            $options[$themeId] = $registry->label($themeId);
        }

        foreach ($registry->ids() as $themeId) {
            if (! isset($options[$themeId])) {
                $options[$themeId] = $registry->label($themeId);
            }
        }

        return $options;
    }

    public function selectBrandTheme(string $id): void
    {
        if (! $this->brandDesk || ! app(SubThemeRegistry::class)->exists($id)) {
            return;
        }

        $this->hydrateEditorFor($id);
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
            Notification::make()->title(__('voodbuilder::settings.generate_theme_palette_failed'))->body($exception->getMessage())->danger()->send();

            return;
        }

        $this->light = array_merge($this->light, $palette['light']);
        $this->dark = array_merge($this->dark, $palette['dark']);
        $this->showGenerateModal = false;
        $this->persistColors();

        Notification::make()->title(__('voodbuilder::settings.generate_theme_palette_success'))->success()->send();
    }

    public function syncDarkFromLight(): void
    {
        if ($this->selectedId === null || ! $this->canEditColors) {
            return;
        }

        if (! filled($this->light['primary'] ?? null)) {
            Notification::make()
                ->title(__('voodbuilder::settings.sync_dark_theme_colors_failed'))
                ->body(__('voodbuilder::settings.sync_dark_theme_colors_missing_light'))
                ->warning()
                ->send();

            return;
        }

        $this->dark = ThemePaletteGenerator::darkFromLight(array_filter(
            $this->light,
            static fn (?string $value): bool => filled($value),
        ));

        $this->persistColors();

        Notification::make()->title(__('voodbuilder::settings.sync_dark_theme_colors_success'))->success()->send();
    }

    public function resetColors(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        ThemePalette::resetForTheme($this->selectedId);
        $this->light = ThemePresenter::modeColors($this->selectedId, 'light');
        $this->dark = ThemePresenter::modeColors($this->selectedId, 'dark');

        Notification::make()->title(__('voodbuilder::settings.reset_theme_colors_success'))->success()->send();
    }

    public function copyColorScheme(): void
    {
        if ($this->selectedId === null) {
            return;
        }

        $payload = [
            'version' => 1,
            'type' => 'voodbuilder-color-scheme',
            'source_id' => $this->selectedId,
            'light' => $this->normalizeModeColors($this->light),
            'dark' => $this->normalizeModeColors($this->dark),
        ];

        $this->copiedColorScheme = $payload;

        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $this->js('void navigator.clipboard.writeText('.json_encode($json).').catch(() => {})');
        } catch (\JsonException) {
            // Livewire state still holds the scheme for paste.
        }

        Notification::make()
            ->title(__('voodbuilder::settings.copy_color_scheme_success'))
            ->body(__('voodbuilder::settings.copy_color_scheme_success_body'))
            ->success()
            ->send();
    }

    public function pasteColorScheme(?string $clipboardJson = null): void
    {
        if ($this->selectedId === null || ! $this->canEditColors) {
            return;
        }

        $scheme = $this->resolveColorSchemePayload($clipboardJson);

        if ($scheme === null) {
            Notification::make()
                ->title(__('voodbuilder::settings.paste_color_scheme_failed'))
                ->body(__('voodbuilder::settings.paste_color_scheme_empty'))
                ->warning()
                ->send();

            return;
        }

        if (($scheme['source_id'] ?? null) === $this->selectedId) {
            Notification::make()
                ->title(__('voodbuilder::settings.paste_color_scheme_failed'))
                ->body(__('voodbuilder::settings.paste_color_scheme_same_theme'))
                ->warning()
                ->send();

            return;
        }

        $this->light = $this->normalizeModeColors($scheme['light'] ?? []);
        $this->dark = $this->normalizeModeColors($scheme['dark'] ?? []);
        $this->persistColors();

        Notification::make()
            ->title(__('voodbuilder::settings.paste_color_scheme_success'))
            ->success()
            ->send();
    }

    public function canPasteColorScheme(): bool
    {
        if (! $this->canEditColors || $this->selectedId === null || $this->copiedColorScheme === null) {
            return false;
        }

        return ($this->copiedColorScheme['source_id'] ?? null) !== $this->selectedId;
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
            Notification::make()->title(__('voodbuilder::settings.clone_theme_failed'))->body($result->error)->danger()->send();

            return;
        }

        $this->showCloneModal = false;
        $this->syncThemeGroups();

        ThemeAssetCompiler::scheduleCompile();

        $this->notify(
            Notification::make()
                ->title(__('voodbuilder::settings.clone_theme_created'))
                ->body(__('voodbuilder::settings.clone_theme_ready'))
                ->success(),
        );

        $this->dispatch('voodbuilder-themes-changed');
        $this->dispatch('voodbuilder-theme-studio-edit', id: $result->id);
        $this->dispatch('theme-studio-open-editor', id: $result->id);

        if ($this->studioRole !== 'catalog') {
            $this->hydrateEditorFor($result->id);
        } else {
            $this->selectedId = $result->id;
        }
    }

    public function confirmDelete(): void
    {
        if ($this->selectedId === null || ! $this->canDelete) {
            return;
        }

        $this->showDeleteModal = true;

        $siteTheme = (string) (VoodbuilderSettings::get('sub_theme') ?: SubThemeResolver::SITE);
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
        if ($this->selectedId === null || ! $this->canDelete) {
            return;
        }

        $id = $this->selectedId;
        $result = SubThemeManager::delete($id, $this->deleteFallbackId);

        if (! $result->success) {
            Notification::make()->title(__('voodbuilder::settings.delete_theme_failed'))->body($result->error)->danger()->send();

            return;
        }

        $this->showDeleteModal = false;
        $this->selectedId = null;
        $this->syncThemeGroups();

        $this->notify(
            Notification::make()->title(__('voodbuilder::settings.delete_theme_success'))->success(),
        );

        $this->dispatch('theme-studio-close-editor');
        $this->dispatch('voodbuilder-theme-studio-highlight', id: null);

        $saved = VoodbuilderSettings::data();
        $this->dispatch(
            'voodbuilder-theme-map-settings-saved',
            subTheme: (string) ($saved['sub_theme'] ?? SubThemeResolver::SITE),
            channelThemes: ThemeBindings::expandChannelThemesForForm(
                is_array($saved['content_channel_sub_themes'] ?? null) ? $saved['content_channel_sub_themes'] : [],
            ),
        );
        $this->dispatch('voodbuilder-themes-changed');
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
                    ->title(__('voodbuilder::settings.import_theme_failed'))
                    ->body(__('voodbuilder::settings.import_theme_missing_archive'))
                    ->danger(),
            );

            return;
        }

        $path = SubThemeImporter::resolveArchiveUploadPath($this->importArchive);

        if ($path === null) {
            $this->notify(
                Notification::make()
                    ->title(__('voodbuilder::settings.import_theme_failed'))
                    ->body(__('voodbuilder::settings.import_theme_missing_archive'))
                    ->danger(),
            );
            $this->importArchive = null;

            return;
        }

        try {
            $this->validate([
                'importArchive' => ['required', 'file', 'mimes:zip', 'max:51200'],
            ]);
        } catch (ValidationException $exception) {
            $this->notify(
                Notification::make()
                    ->title(__('voodbuilder::settings.import_theme_failed'))
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
                    ->title(__('voodbuilder::settings.import_theme_failed'))
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
            ? __('voodbuilder::settings.import_theme_renamed', ['from' => $result->renamedFrom, 'id' => $result->id])
            : __('voodbuilder::settings.import_theme_success', ['id' => $result->id]);

        $this->notify(
            Notification::make()
                ->title(__('voodbuilder::settings.import_theme_imported'))
                ->body($body)
                ->success(),
        );

        $this->dispatch('voodbuilder-themes-changed');
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
        return view('voodbuilder::filament.themes-workspace', [
            'groups' => $this->groups,
            'colorKeys' => ThemePresenter::COLOR_KEYS,
            'fallbackOptions' => collect(app(SubThemeRegistry::class)->options())
                ->reject(fn (string $label, string $id): bool => $this->showDeleteModal && $id === $this->selectedId)
                ->all(),
            'colorKeyLabel' => ThemePresenter::colorLabel($this->colorKey),
            'canPasteColorScheme' => $this->canPasteColorScheme(),
            'brandThemeOptions' => $this->brandDesk ? $this->brandThemeOptions() : [],
        ]);
    }

    private function syncThemeGroups(): void
    {
        $this->groups = ThemePresenter::groupedCards();
    }

    /**
     * @param  array<string, mixed>  $colors
     * @return array<string, string|int|null>
     */
    private function normalizeModeColors(array $colors): array
    {
        $resolved = [];

        foreach (ThemePresenter::COLOR_KEYS as $key) {
            $value = $colors[$key] ?? null;
            $resolved[$key] = ThemePalette::sanitizeColor(is_string($value) ? $value : null);
        }

        $opacity = ThemePalette::sanitizeOpacity($colors['header_bg_opacity'] ?? null);

        if ($opacity !== null) {
            $resolved['header_bg_opacity'] = $opacity;
        }

        return $resolved;
    }

    /**
     * @param  array<string, string|int|null>  $mode
     * @return array<string, string|int>
     */
    private function filterModeForPersist(array $mode): array
    {
        $out = [];

        foreach ($mode as $key => $value) {
            if ($key === 'header_bg_opacity') {
                $opacity = ThemePalette::sanitizeOpacity($value);

                if ($opacity !== null && $opacity < 100) {
                    $out[$key] = $opacity;
                }

                continue;
            }

            if (is_string($value) && filled($value)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function writeHeaderBgOpacity(?int $opacity): void
    {
        $opacity = ThemePalette::sanitizeOpacity($opacity);

        if ($this->colorMode === 'dark') {
            if ($opacity === null || $opacity >= 100) {
                unset($this->dark['header_bg_opacity']);
            } else {
                $this->dark['header_bg_opacity'] = $opacity;
            }

            return;
        }

        if ($opacity === null || $opacity >= 100) {
            unset($this->light['header_bg_opacity']);
        } else {
            $this->light['header_bg_opacity'] = $opacity;
        }
    }

    /**
     * @return array{version?: int, type?: string, source_id?: string, light?: array<string, mixed>, dark?: array<string, mixed>}|null
     */
    private function resolveColorSchemePayload(?string $clipboardJson): ?array
    {
        if (filled($clipboardJson)) {
            try {
                $decoded = json_decode($clipboardJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $decoded = null;
            }

            if (is_array($decoded) && ($decoded['type'] ?? null) === 'voodbuilder-color-scheme') {
                return $decoded;
            }
        }

        return $this->copiedColorScheme;
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
