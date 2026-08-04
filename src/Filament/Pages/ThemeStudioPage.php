<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;
use Throwable;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Modules\Themes\ThemesModule;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Support\ThemeBindings;

/**
 * Theme Studio — first-class visual theme catalog + channel assignment map.
 *
 * Clone/customize themes and orchestrate which theme powers each site area
 * (Landing, Docs, Tutorials, Blog, …). Not a Settings tab: product surface.
 *
 * @property-read Schema $form
 */
class ThemeStudioPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'voodbuilder/theme-studio';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Right stage: channel map (`map`) or theme customize desk (`edit`). */
    public string $studioMode = 'map';

    /** Theme currently open in the customize desk (shared across catalog ↔ editor). */
    public ?string $editingThemeId = null;

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::admin.navigation.theme_studio');
    }

    public static function canAccess(): bool
    {
        return ThemesModule::isEnabled();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $data = VoodbuilderSettings::data();

        $channelThemes = is_array($data['content_channel_sub_themes'] ?? null)
            ? $data['content_channel_sub_themes']
            : [];

        $this->data = [
            'sub_theme' => SubThemeResolver::resolveId((string) ($data['sub_theme'] ?? SubThemeResolver::SITE))
                ?? SubThemeResolver::SITE,
            'content_channel_sub_themes' => ThemeBindings::expandChannelThemesForForm($channelThemes),
        ];

        $this->form->fill($this->data);
    }

    #[On('voodbuilder-theme-studio-edit')]
    public function onThemeStudioEdit(string $id): void
    {
        $this->editingThemeId = $id;
        $this->studioMode = 'edit';
    }

    #[On('theme-studio-open-editor')]
    public function openEditorMode(?string $id = null): void
    {
        if (filled($id)) {
            $this->editingThemeId = $id;
        }

        $this->studioMode = 'edit';
    }

    #[On('theme-studio-close-editor')]
    public function closeEditorMode(): void
    {
        $this->studioMode = 'map';
        $this->editingThemeId = null;
    }

    /**
     * @param  array<string, string>  $channelThemes
     */
    #[On('voodbuilder-theme-map-sync')]
    public function syncThemeMap(string $subTheme, array $channelThemes): void
    {
        $this->data['sub_theme'] = $subTheme;
        $this->data['content_channel_sub_themes'] = $channelThemes;
        $this->form->fill($this->data);
    }

    #[On('voodbuilder-themes-changed')]
    public function reloadThemeMapFromSettings(): void
    {
        $data = VoodbuilderSettings::data();

        $this->data['sub_theme'] = SubThemeResolver::resolveId((string) ($data['sub_theme'] ?? SubThemeResolver::SITE))
            ?? SubThemeResolver::SITE;
        $this->data['content_channel_sub_themes'] = ThemeBindings::expandChannelThemesForForm(
            is_array($data['content_channel_sub_themes'] ?? null) ? $data['content_channel_sub_themes'] : [],
        );
        $this->form->fill($this->data);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            if (isset($this->data['sub_theme'])) {
                $data['sub_theme'] = $this->data['sub_theme'];
            }

            if (isset($this->data['content_channel_sub_themes']) && is_array($this->data['content_channel_sub_themes'])) {
                $data['content_channel_sub_themes'] = $this->data['content_channel_sub_themes'];
            }

            // Merge only theme assignment keys — never wipe Site/SEO from Settings.
            VoodbuilderSettings::saveData([
                'sub_theme' => $data['sub_theme'] ?? SubThemeResolver::SITE,
                'content_channel_sub_themes' => is_array($data['content_channel_sub_themes'] ?? null)
                    ? $data['content_channel_sub_themes']
                    : [],
            ]);

            $this->commitDatabaseTransaction();

            $saved = VoodbuilderSettings::data();
            $this->data['sub_theme'] = (string) ($saved['sub_theme'] ?? SubThemeResolver::SITE);
            $this->data['content_channel_sub_themes'] = ThemeBindings::expandChannelThemesForForm(
                is_array($saved['content_channel_sub_themes'] ?? null) ? $saved['content_channel_sub_themes'] : [],
            );
            $this->form->fill($this->data);

            $this->dispatch(
                'voodbuilder-theme-map-settings-saved',
                subTheme: $this->data['sub_theme'],
                channelThemes: $this->data['content_channel_sub_themes'],
            );

            Notification::make()
                ->title(__('voodbuilder::settings.theme_studio_saved'))
                ->success()
                ->send();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('voodbuilder::filament.theme-studio-layout')
                    ->viewData(fn (): array => [
                        'subTheme' => (string) ($this->data['sub_theme'] ?? 'site'),
                        'channelThemes' => is_array($this->data['content_channel_sub_themes'] ?? null)
                            ? $this->data['content_channel_sub_themes']
                            : [],
                    ]),
                Hidden::make('sub_theme'),
                Hidden::make('content_channel_sub_themes'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('theme-studio-form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label(__('voodbuilder::settings.theme_studio_save'))
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('voodbuilder::admin.navigation.theme_studio');
    }

    public function getHeading(): string|Htmlable
    {
        return __('voodbuilder::admin.navigation.theme_studio');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('voodbuilder::settings.theme_studio_subheading');
    }
}
