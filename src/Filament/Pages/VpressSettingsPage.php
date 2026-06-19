<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\ContentChannelRegistry;
use Voodflow\Vpress\Support\ContentChannelThemes;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\ThemeBindings;
use Voodflow\Vpress\Support\ThemePresetManager;
use Voodflow\Vtuts\Support\Locales;
use Voodflow\Vtuts\Support\LocaleSwitcher;

/**
 * @property-read Schema $form
 */
class VpressSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('vpress::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('vpress::admin.navigation.settings');
    }

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'vpress/settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $data = VpressSettings::data();

        if (blank($data['primary_locale'] ?? null) && class_exists(Locales::class)) {
            $data['primary_locale'] = VpressSettings::primaryLocale();
        }

        $this->form->fill($data);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();
            $data['active_theme_preset_id'] = null;

            VpressSettings::saveData($data);

            $this->commitDatabaseTransaction();

            Notification::make()
                ->title(__('Settings saved'))
                ->success()
                ->send();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();
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
        $uploadDisk = config('vpress.uploads.disk', 'public');
        $uploadDirectory = config('vpress.uploads.directory', 'vpress');
        $imageTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
        $faviconTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];

        return $schema
            ->components([
                Tabs::make('SettingsTabs')
                    ->tabs([
                        Tab::make(__('vpress::settings.tabs.site'))
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Section::make(__('Site'))
                                    ->schema([
                                        TextInput::make('site_title')
                                            ->label(__('Site title'))
                                            ->helperText(__('Shown in the browser tab, page titles, and social sharing previews.'))
                                            ->maxLength(255),
                                        TextInput::make('brand_name')
                                            ->label(__('Brand name'))
                                            ->helperText(__('Short name shown next to the logo in the header. Leave empty to reuse the site title.'))
                                            ->maxLength(255),
                                        Toggle::make('show_site_title')
                                            ->label(__('Show brand name next to logo'))
                                            ->helperText(__('Disable to show only the logo in the header.'))
                                            ->default(true),
                                        FileUpload::make('logo')
                                            ->label(__('Logo'))
                                            ->disk($uploadDisk)
                                            ->directory($uploadDirectory)
                                            ->visibility('public')
                                            ->acceptedFileTypes($imageTypes)
                                            ->maxSize((int) config('vpress.uploads.max_size', 2048))
                                            ->imagePreviewHeight('64')
                                            ->helperText(__('vpress::settings.logo_help'))
                                            ->nullable(),
                                        FileUpload::make('logo_mobile')
                                            ->label(__('vpress::settings.logo_mobile'))
                                            ->disk($uploadDisk)
                                            ->directory($uploadDirectory.'/mobile')
                                            ->visibility('public')
                                            ->acceptedFileTypes($imageTypes)
                                            ->maxSize((int) config('vpress.uploads.max_size', 2048))
                                            ->imagePreviewHeight('48')
                                            ->helperText(__('vpress::settings.logo_mobile_help'))
                                            ->nullable(),
                                        FileUpload::make('favicon')
                                            ->label(__('Favicon'))
                                            ->disk($uploadDisk)
                                            ->directory($uploadDirectory.'/favicons')
                                            ->visibility('public')
                                            ->acceptedFileTypes($faviconTypes)
                                            ->maxSize(512)
                                            ->imagePreviewHeight('32')
                                            ->helperText(__('Used when pages do not define their own favicon.'))
                                            ->nullable(),
                                    ]),
                            ]),
                        Tab::make(__('vpress::settings.tabs.appearance'))
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Section::make(__('Header & navigation'))
                                    ->schema([
                                        Toggle::make('show_notification_bell')
                                            ->label(__('Show notification bell'))
                                            ->helperText(__('Visible to logged-in users when comment notifications are enabled.'))
                                            ->default(true),
                                        Toggle::make('show_theme_toggle')
                                            ->label(__('Show dark / light toggle'))
                                            ->default(true)
                                            ->live(),
                                        Select::make('theme_mode')
                                            ->label(fn (Get $get): string => $get('show_theme_toggle')
                                                ? __('Default theme')
                                                : __('Site theme'))
                                            ->options(fn (Get $get): array => $get('show_theme_toggle')
                                                ? [
                                                    'system' => __('Follow system preference'),
                                                    'light' => __('Always light'),
                                                    'dark' => __('Always dark'),
                                                ]
                                                : [
                                                    'light' => __('Always light'),
                                                    'dark' => __('Always dark'),
                                                ])
                                            ->default('system')
                                            ->helperText(fn (Get $get): string => $get('show_theme_toggle')
                                                ? __('Used on first visit and in private browsing when the visitor has not chosen a theme yet. “Follow system” uses the device setting.')
                                                : __('Applied to all visitors; the theme toggle is hidden.')),
                                        Toggle::make('show_account_link')
                                            ->label(__('Show account link for logged-in users'))
                                            ->default(true),
                                        Toggle::make('sticky_nav')
                                            ->label(__('Sticky navigation on standard pages'))
                                            ->helperText(__('Keeps the header visible while scrolling on home, CMS pages, and auth screens. Documentation pages with a sidebar always use a fixed header.'))
                                            ->default(false),
                                        Toggle::make('show_language_switcher')
                                            ->label(__('Show language switcher'))
                                            ->helperText(__('Hidden automatically when only one content locale is configured.'))
                                            ->default(true)
                                            ->live()
                                            ->visible(fn (): bool => class_exists(LocaleSwitcher::class)
                                                && LocaleSwitcher::enabled()),
                                        Select::make('primary_locale')
                                            ->label(__('vpress::settings.primary_locale'))
                                            ->options(fn (): array => class_exists(Locales::class) ? Locales::options() : [])
                                            ->default(fn (): string => VpressSettings::primaryLocale())
                                            ->helperText(__('vpress::settings.primary_locale_help'))
                                            ->visible(fn (): bool => class_exists(Locales::class)
                                                && class_exists(LocaleSwitcher::class)
                                                && LocaleSwitcher::enabled()),
                                    ]),
                            ]),
                        Tab::make(__('vpress::settings.tabs.theme'))
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Placeholder::make('theme_scope_info')
                                    ->hiddenLabel()
                                    ->content(new HtmlString(__('vpress::settings.theme_scope_info'))),
                                Section::make(__('vpress::theme_bindings.section_title'))
                                    ->description(__('vpress::theme_bindings.section_help'))
                                    ->schema([
                                        Select::make('sub_theme')
                                            ->label(__('vpress::theme_bindings.site_pages'))
                                            ->options(fn (): array => ThemeBindings::sitePagesSelectOptions(
                                                VpressSettings::get('sub_theme'),
                                            ))
                                            ->default('events')
                                            ->native(false)
                                            ->helperText(__('vpress::theme_bindings.site_pages_help')),
                                        ...$this->channelBindingFields(),
                                    ]),
                                Section::make(__('vpress::theme_presets.section_title'))
                                    ->description(__('vpress::theme_presets.section_help'))
                                    ->schema([
                                        Placeholder::make('active_theme_preset')
                                            ->label(__('vpress::theme_presets.active'))
                                            ->content(fn (): string => filled(VpressSettings::get('active_theme_preset_id'))
                                                ? (ThemePresetManager::find((string) VpressSettings::get('active_theme_preset_id'))?->label
                                                    ?? (string) VpressSettings::get('active_theme_preset_id'))
                                                : __('vpress::theme_presets.none')),
                                        Placeholder::make('bundled_theme_presets')
                                            ->label(__('vpress::theme_presets.bundled'))
                                            ->content(fn (): string => ThemePresetManager::bundled()
                                                ->map(fn ($preset): string => $preset->label.($preset->description ? " — {$preset->description}" : ''))
                                                ->join("\n") ?: '—'),
                                    ]),
                                Section::make(__('vpress::settings.theme_colors_section'))
                                    ->description(__('vpress::settings.theme_colors_help'))
                                    ->schema([
                                        Tabs::make('SubThemeColors')
                                            ->tabs($this->subThemeColorTabs())
                                            ->contained(false)
                                            ->visible(fn (): bool => $this->subThemeColorTabs() !== []),
                                    ]),
                            ]),
                        Tab::make(__('vpress::settings.tabs.seo'))
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make(__('SEO defaults'))
                                    ->description(__('Fallback metadata for pages without custom SEO (Open Graph, Twitter, search engines).'))
                                    ->schema([
                                        Textarea::make('seo_default_description')
                                            ->label(__('Default meta description'))
                                            ->rows(3)
                                            ->maxLength(320),
                                        FileUpload::make('seo_default_image')
                                            ->label(__('Default social sharing image'))
                                            ->disk($uploadDisk)
                                            ->directory($uploadDirectory.'/social')
                                            ->visibility('public')
                                            ->acceptedFileTypes($imageTypes)
                                            ->maxSize((int) config('vpress.uploads.social_max_size', 4096))
                                            ->imagePreviewHeight('120')
                                            ->helperText(__('Recommended 1200×630 px for Open Graph and Twitter cards.'))
                                            ->nullable(),
                                        TextInput::make('seo_site_name')
                                            ->label(__('Open Graph site name'))
                                            ->maxLength(255),
                                        TextInput::make('seo_title_suffix')
                                            ->label(__('Title suffix'))
                                            ->placeholder(' | My Site')
                                            ->maxLength(64),
                                        TextInput::make('seo_default_author')
                                            ->label(__('Default author'))
                                            ->maxLength(255),
                                        TextInput::make('seo_twitter_username')
                                            ->label(__('Twitter / X username'))
                                            ->placeholder('myaccount')
                                            ->helperText(__('Without the @ symbol.'))
                                            ->maxLength(64),
                                        TextInput::make('seo_robots')
                                            ->label(__('Default robots directive'))
                                            ->default('max-snippet:-1,max-image-preview:large,max-video-preview:-1')
                                            ->maxLength(255),
                                        Toggle::make('seo_canonical_enabled')
                                            ->label(__('Output canonical link tags'))
                                            ->helperText(__('Self-referencing canonical URLs on public pages.'))
                                            ->default(true),
                                    ]),
                            ]),
                        Tab::make(__('vpress::settings.tabs.geo_ai'))
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Section::make(__('GEO & AI'))
                                    ->description(__('Organization context and summaries for geographic and AI-oriented crawlers.'))
                                    ->schema([
                                        TextInput::make('geo_organization_name')
                                            ->label(__('Organization name'))
                                            ->maxLength(255),
                                        FileUpload::make('geo_organization_logo')
                                            ->label(__('Organization logo'))
                                            ->disk($uploadDisk)
                                            ->directory($uploadDirectory.'/organization')
                                            ->visibility('public')
                                            ->acceptedFileTypes($imageTypes)
                                            ->maxSize((int) config('vpress.uploads.max_size', 2048))
                                            ->imagePreviewHeight('64')
                                            ->nullable(),
                                        TextInput::make('geo_region')
                                            ->label(__('GEO region'))
                                            ->placeholder('IT-62')
                                            ->maxLength(32),
                                        TextInput::make('geo_placename')
                                            ->label(__('GEO place name'))
                                            ->placeholder('Rome')
                                            ->maxLength(128),
                                        Textarea::make('geo_site_summary')
                                            ->label(__('Site summary for AI / abstract'))
                                            ->helperText(__('Short factual summary of what this site offers. Used in schema.org and AI-oriented meta tags.'))
                                            ->rows(4)
                                            ->maxLength(500),
                                    ]),
                            ]),
                        Tab::make(__('vpress::settings.tabs.analytics'))
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make(__('Analytics & monitoring'))
                                    ->description(__('Tracking scripts load on the public site only after cookie consent is accepted. Configure the banner under Settings → Cookie consent.'))
                                    ->schema([
                                        TextInput::make('facebook_pixel_id')
                                            ->label(__('Facebook Pixel ID'))
                                            ->placeholder('123456789012345')
                                            ->maxLength(64),
                                        TextInput::make('google_tag_manager_id')
                                            ->label(__('Google Tag Manager ID'))
                                            ->placeholder('GTM-XXXXXXX')
                                            ->maxLength(32),
                                        TextInput::make('google_analytics_id')
                                            ->label(__('Google Analytics 4 ID'))
                                            ->placeholder('G-XXXXXXXXXX')
                                            ->maxLength(32),
                                        Textarea::make('monitoring_head_code')
                                            ->label(__('Custom code (head)'))
                                            ->helperText(__('HTML/JS injected in <head> after consent (e.g. Hotjar, Clarity).'))
                                            ->rows(4),
                                        Textarea::make('monitoring_body_code')
                                            ->label(__('Custom code (body)'))
                                            ->helperText(__('HTML/JS injected before </body> after consent.'))
                                            ->rows(4),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString('settings-tab'),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected function channelBindingFields(): array
    {
        $channels = app(ContentChannelRegistry::class)->all();

        if ($channels === []) {
            return [
                Placeholder::make('no_content_channels')
                    ->hiddenLabel()
                    ->content(new HtmlString(__('vpress::theme_bindings.no_channels'))),
            ];
        }

        return collect($channels)
            ->map(fn (PublicContentChannel $channel): Select => Select::make("content_channel_sub_themes.{$channel->id()}")
                ->label($channel->label())
                ->options(fn (): array => ThemeBindings::selectOptionsForChannel($channel->id()))
                ->native(false)
                ->helperText(__('vpress::theme_bindings.channel_help', [
                    'capability' => ThemeBindings::requiredCapabilityForChannel($channel)->label(),
                    'default' => ContentChannelThemes::configuredDefaultFor($channel->id())
                        ? app(SubThemeRegistry::class)->label((string) ContentChannelThemes::configuredDefaultFor($channel->id()))
                        : __('vpress::theme_bindings.no_package_default'),
                ])))
            ->all();
    }

    public function applyThemePreset(string $presetId): void
    {
        $preset = ThemePresetManager::find($presetId);

        if ($preset === null) {
            Notification::make()
                ->title(__('vpress::theme_presets.not_found'))
                ->danger()
                ->send();

            return;
        }

        $preset->apply();
        $this->form->fill(VpressSettings::data());

        Notification::make()
            ->title(__('vpress::theme_presets.applied', ['label' => $preset->label]))
            ->success()
            ->send();
    }

    public function exportCurrentThemePreset(): StreamedResponse
    {
        $preset = ThemePresetManager::snapshotFromSettings(
            'export-'.now()->format('Y-m-d-His'),
            'Exported '.now()->toDateTimeString(),
        );

        $json = json_encode(ThemePresetManager::export($preset), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(
            static function () use ($json): void {
                echo $json;
            },
            "{$preset->id}.json",
            ['Content-Type' => 'application/json'],
        );
    }

    /**
     * @return array<Tab>
     */
    protected function subThemeColorTabs(): array
    {
        $registry = app(SubThemeRegistry::class);
        $tabs = [];

        foreach ($registry->ids() as $id) {
            $description = $registry->description($id);
            $tabs[] = Tab::make($registry->label($id))
                ->schema([
                    ...($description !== null ? [
                        Placeholder::make("sub_theme_colors_{$id}_description")
                            ->hiddenLabel()
                            ->content(new HtmlString('<p class="text-sm text-gray-500 dark:text-gray-400">'.e($description).'</p>')),
                    ] : []),
                    Toggle::make("sub_theme_colors.{$id}.custom")
                        ->label(__('vpress::settings.theme_customize'))
                        ->helperText(__('vpress::settings.theme_customize_help'))
                        ->live(),
                    Section::make(__('vpress::settings.theme_light_mode'))
                        ->schema([
                            ColorPicker::make("sub_theme_colors.{$id}.light.primary")
                                ->label(__('vpress::settings.theme_primary'))
                                ->helperText(__('vpress::settings.theme_primary_help')),
                            ColorPicker::make("sub_theme_colors.{$id}.light.secondary")
                                ->label(__('vpress::settings.theme_secondary'))
                                ->helperText(__('vpress::settings.theme_secondary_help')),
                        ])
                        ->columns(2)
                        ->visible(fn (Get $get): bool => (bool) $get("sub_theme_colors.{$id}.custom")),
                    Section::make(__('vpress::settings.theme_dark_mode'))
                        ->schema([
                            ColorPicker::make("sub_theme_colors.{$id}.dark.primary")
                                ->label(__('vpress::settings.theme_primary'))
                                ->helperText(__('vpress::settings.theme_dark_primary_help')),
                            ColorPicker::make("sub_theme_colors.{$id}.dark.secondary")
                                ->label(__('vpress::settings.theme_secondary'))
                                ->helperText(__('vpress::settings.theme_dark_secondary_help')),
                        ])
                        ->columns(2)
                        ->visible(fn (Get $get): bool => (bool) $get("sub_theme_colors.{$id}.custom")),
                ]);
        }

        return $tabs;
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
            ->id('settings-form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('applyThemePreset')
                        ->label(__('vpress::theme_presets.apply'))
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Select::make('preset_id')
                                ->label(__('vpress::theme_presets.select'))
                                ->options(fn (): array => ThemePresetManager::all()
                                    ->mapWithKeys(fn ($preset): array => [$preset->id => $preset->label])
                                    ->all())
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (array $data): void {
                            $this->applyThemePreset((string) $data['preset_id']);
                        }),
                    Action::make('exportThemePreset')
                        ->label(__('vpress::theme_presets.export_current'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (): StreamedResponse => $this->exportCurrentThemePreset()),
                    Action::make('importThemePreset')
                        ->label(__('vpress::theme_presets.import'))
                        ->icon('heroicon-o-arrow-up-tray')
                        ->schema([
                            FileUpload::make('preset_file')
                                ->label(__('vpress::theme_presets.file'))
                                ->acceptedFileTypes(['application/json'])
                                ->required(),
                            Toggle::make('apply')
                                ->label(__('vpress::theme_presets.import_apply'))
                                ->default(true),
                        ])
                        ->action(function (array $data): void {
                            $disk = config('vpress.uploads.disk', 'public');
                            $relative = is_array($data['preset_file'] ?? null)
                                ? ($data['preset_file'][0] ?? null)
                                : ($data['preset_file'] ?? null);

                            if (! is_string($relative) || ! Storage::disk($disk)->exists($relative)) {
                                Notification::make()
                                    ->title(__('vpress::theme_presets.import_failed'))
                                    ->danger()
                                    ->send();

                                return;
                            }

                            try {
                                ThemePresetManager::importFromFile(
                                    Storage::disk($disk)->path($relative),
                                    apply: (bool) ($data['apply'] ?? true),
                                );
                                $this->form->fill(VpressSettings::data());

                                Notification::make()
                                    ->title(__('vpress::theme_presets.imported'))
                                    ->success()
                                    ->send();
                            } catch (\Throwable) {
                                Notification::make()
                                    ->title(__('vpress::theme_presets.import_failed'))
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('save')
                        ->label(__('Save settings'))
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('Settings');
    }
}
