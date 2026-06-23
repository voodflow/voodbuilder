<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Throwable;
use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\ContentChannelRegistry;
use Voodflow\Vpress\Support\SubThemeCloner;
use Voodflow\Vpress\Support\SubThemeExporter;
use Voodflow\Vpress\Support\SubThemeImporter;
use Voodflow\Vpress\Support\SubThemeLocator;
use Voodflow\Vpress\Support\SubThemeManager;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\SubThemeScaffolder;
use Voodflow\Vpress\Support\ThemeBindings;
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

        $channelThemes = is_array($data['content_channel_sub_themes'] ?? null)
            ? $data['content_channel_sub_themes']
            : [];
        $data['content_channel_sub_themes'] = ThemeBindings::expandChannelThemesForForm($channelThemes);

        $registry = app(SubThemeRegistry::class);
        $defaultEditingTheme = (string) ($data['sub_theme'] ?? 'site');

        if (! $registry->exists($defaultEditingTheme)) {
            $defaultEditingTheme = $registry->ids()[0] ?? 'site';
        }

        $data['editing_theme_id'] = $defaultEditingTheme;

        $this->form->fill($data);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();
            unset($data['editing_theme_id']);

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
                                        $this->configurePublicBrandingUpload(
                                            FileUpload::make('logo')
                                                ->label(__('Logo'))
                                                ->disk($uploadDisk)
                                                ->directory($uploadDirectory)
                                                ->visibility('public')
                                                ->acceptedFileTypes($imageTypes)
                                                ->maxSize((int) config('vpress.uploads.max_size', 2048))
                                                ->helperText(__('vpress::settings.logo_help')),
                                        ),
                                        $this->configurePublicBrandingUpload(
                                            FileUpload::make('logo_mobile')
                                                ->label(__('vpress::settings.logo_mobile'))
                                                ->disk($uploadDisk)
                                                ->directory($uploadDirectory.'/mobile')
                                                ->visibility('public')
                                                ->acceptedFileTypes($imageTypes)
                                                ->maxSize((int) config('vpress.uploads.max_size', 2048))
                                                ->helperText(__('vpress::settings.logo_mobile_help')),
                                        ),
                                        $this->configurePublicBrandingUpload(
                                            FileUpload::make('favicon')
                                                ->label(__('Favicon'))
                                                ->disk($uploadDisk)
                                                ->directory($uploadDirectory.'/favicons')
                                                ->visibility('public')
                                                ->acceptedFileTypes($faviconTypes)
                                                ->maxSize(512)
                                                ->helperText(__('Used when pages do not define their own favicon.')),
                                        ),
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
                        Tab::make('themes')
                            ->label(__('vpress::settings.tabs.themes'))
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                $this->themesWorkspaceSection(),
                                $this->layoutBindingsSection(),
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
                                        $this->configurePublicBrandingUpload(
                                            FileUpload::make('geo_organization_logo')
                                                ->label(__('Organization logo'))
                                                ->disk($uploadDisk)
                                                ->directory($uploadDirectory.'/organization')
                                                ->visibility('public')
                                                ->acceptedFileTypes($imageTypes)
                                                ->maxSize((int) config('vpress.uploads.max_size', 2048)),
                                        ),
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
     * Logo/favicon uploads accept SVG; FilePond image preview hangs on "Waiting for size".
     * Show the filename with an open link instead, and use a relative /storage URL so Docker port
     * mapping does not break when APP_URL differs from the browser URL.
     */
    protected function configurePublicBrandingUpload(FileUpload $field): FileUpload
    {
        return $field
            ->nullable()
            ->previewable(false)
            ->openable()
            ->getUploadedFileUsing(function (FileUpload $component, string $file, string | array | null $storedFileNames): ?array {
                $uploaded = $component->getUploadedFile($file, $storedFileNames);

                if ($uploaded === null || $component->getDiskName() !== 'public') {
                    return $uploaded;
                }

                $uploaded['url'] = '/storage/'.ltrim($file, '/');

                return $uploaded;
            });
    }

    protected function themesWorkspaceSection(): Section
    {
        return Section::make(__('vpress::settings.themes_workspace_section'))
            ->description(__('vpress::settings.themes_workspace_help'))
            ->schema([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->schema([
                        Select::make('editing_theme_id')
                            ->label(__('vpress::settings.editing_theme'))
                            ->helperText(__('vpress::settings.editing_theme_help'))
                            ->options(fn (): array => $this->themeSelectOptions())
                            ->required()
                            ->live()
                            ->native(false)
                            ->searchable()
                            ->columnSpan(['default' => 1, 'lg' => 2]),
                        Actions::make($this->themeManagementActions())
                            ->verticalAlignment(VerticalAlignment::Start)
                            ->columnSpan(1),
                    ]),
                Placeholder::make('theme_workspace_card')
                    ->hiddenLabel()
                    ->visible(fn (Get $get): bool => filled($get('editing_theme_id')))
                    ->content(fn (Get $get): HtmlString => new HtmlString(
                        $this->themeWorkspaceCardHtml((string) $get('editing_theme_id')),
                    ))
                    ->columnSpanFull(),
                Placeholder::make('theme_workspace_overview')
                    ->hiddenLabel()
                    ->content(fn (): HtmlString => new HtmlString($this->themesOverviewChipsHtml()))
                    ->columnSpanFull(),
                Section::make(__('vpress::settings.theme_colors_section'))
                    ->description(__('vpress::settings.theme_colors_help'))
                    ->visible(fn (Get $get): bool => filled($get('editing_theme_id')))
                    ->schema([
                        Section::make(__('vpress::settings.theme_light_mode'))
                            ->schema(fn (Get $get): array => $this->themeColorFields((string) $get('editing_theme_id'), 'light'))
                            ->columns(3)
                            ->compact(),
                        Section::make(__('vpress::settings.theme_dark_mode'))
                            ->schema(fn (Get $get): array => $this->themeColorFields((string) $get('editing_theme_id'), 'dark'))
                            ->columns(3)
                            ->compact(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected function themeSelectOptions(): array
    {
        $registry = app(SubThemeRegistry::class);
        $options = [];

        foreach ($registry->ids() as $id) {
            $options[$id] = $registry->label($id).' ('.$id.')';
        }

        return $options;
    }

    /**
     * @return array<int, Action|ActionGroup>
     */
    protected function themeManagementActions(): array
    {
        $selectedThemeId = fn (): ?string => filled($this->data['editing_theme_id'] ?? null)
            ? (string) $this->data['editing_theme_id']
            : null;

        return [
            ActionGroup::make([
                $this->createSubThemeAction(),
                $this->cloneSubThemeAction(),
                $this->importSubThemeAction(),
            ])
                ->label(__('vpress::settings.theme_actions_add'))
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->button(),
            ActionGroup::make([
                $this->exportSubThemeAction()
                    ->fillForm(fn (): array => [
                        'theme_id' => $selectedThemeId(),
                    ]),
                $this->renameSubThemeAction()
                    ->fillForm(fn (): array => [
                        'theme_id' => $selectedThemeId(),
                        'label' => filled($selectedThemeId())
                            ? app(SubThemeRegistry::class)->label((string) $selectedThemeId())
                            : null,
                    ]),
            ])
                ->label(__('vpress::settings.theme_actions_manage'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('gray')
                ->button(),
            $this->deleteSubThemeAction()
                ->fillForm(fn (): array => [
                    'theme_id' => $selectedThemeId(),
                ]),
        ];
    }

    protected function themeWorkspaceCardHtml(string $themeId): string
    {
        $registry = app(SubThemeRegistry::class);
        $location = SubThemeLocator::resolve($themeId);
        $label = e($registry->label($themeId));
        $id = e($themeId);
        $description = $registry->description($themeId);
        $origin = $location !== null
            ? __("vpress::settings.theme_origin_{$location->origin}")
            : __('vpress::settings.theme_origin_unknown');
        $originKey = $location?->origin ?? 'unknown';
        $originClass = $originKey === 'app'
            ? 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-300 dark:ring-primary-400/30'
            : 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10';
        $descriptionHtml = $description !== null
            ? '<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">'.e($description).'</p>'
            : '';

        return '<div class="rounded-xl border border-gray-200 bg-gray-50/80 p-4 dark:border-white/10 dark:bg-white/5">'
            .'<div class="flex flex-wrap items-start justify-between gap-3">'
            .'<div><h3 class="text-base font-semibold text-gray-950 dark:text-white">'.$label.'</h3>'
            .'<p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">'.$id.'</p>'
            .$descriptionHtml
            .'</div>'
            .'<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset '.e($originClass).'">'
            .e($origin)
            .'</span></div></div>';
    }

    protected function themesOverviewChipsHtml(): string
    {
        $registry = app(SubThemeRegistry::class);
        $selectedId = (string) ($this->data['editing_theme_id'] ?? '');
        $chips = '';

        foreach (SubThemeLocator::exportable() as $location) {
            $id = $location->id;
            $label = e($registry->label($id));
            $isSelected = $id === $selectedId;
            $chipClass = $isSelected
                ? 'bg-primary-600 text-white ring-primary-600 dark:bg-primary-500 dark:ring-primary-500'
                : 'bg-white text-gray-700 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10';

            $chips .= '<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset '.$chipClass.'" title="'.e($id).'">'
                .$label
                .'</span>';
        }

        if ($chips === '') {
            return '';
        }

        return '<div class="flex flex-wrap gap-2 pt-1">'
            .'<span class="w-full text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">'
            .e(__('vpress::settings.themes_overview_label'))
            .'</span>'
            .$chips
            .'</div>';
    }

    protected function layoutBindingsSection(): Section
    {
        $schema = [
            Placeholder::make('layout_bindings_columns')
                ->hiddenLabel()
                ->content(new HtmlString(
                    '<div class="hidden md:grid md:grid-cols-2 gap-x-6 gap-y-1 pb-2 mb-1 border-b border-gray-200 dark:border-white/10">'
                    .'<span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">'.e(__('Area')).'</span>'
                    .'<span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">'.e(__('vpress::theme_bindings.layout_column')).'</span>'
                    .'</div>',
                )),
            $this->layoutBindingRow(
                key: 'site_pages',
                title: __('vpress::theme_bindings.site_pages'),
                description: __('vpress::theme_bindings.site_pages_description'),
                field: Select::make('sub_theme')
                    ->hiddenLabel()
                    ->options(fn (): array => ThemeBindings::sitePagesSelectOptions(
                        VpressSettings::get('sub_theme'),
                    ))
                    ->default('site')
                    ->native(false)
                    ->required(),
            ),
            ...$this->channelLayoutBindingRows(),
            Placeholder::make('layout_bindings_fallback')
                ->hiddenLabel()
                ->content(new HtmlString(
                    '<p class="text-sm text-gray-600 dark:text-gray-400">'.e(__('vpress::theme_bindings.unregistered_fallback')).'</p>',
                )),
        ];

        return Section::make(__('vpress::theme_bindings.section_title'))
            ->description(__('vpress::theme_bindings.section_help'))
            ->schema($schema);
    }

    protected function layoutBindingRow(string $key, string $title, string $description, Select $field): Grid
    {
        return Grid::make(['default' => 1, 'md' => 2])
            ->schema([
                Placeholder::make("layout_binding_{$key}_area")
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<div class="py-1">'
                        .'<p class="text-sm font-medium text-gray-950 dark:text-white">'.e($title).'</p>'
                        .'<p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">'.e($description).'</p>'
                        .'</div>',
                    )),
                $field,
            ]);
    }

    /**
     * @return array<int, Grid>
     */
    protected function channelLayoutBindingRows(): array
    {
        $channels = collect(app(ContentChannelRegistry::class)->all())
            ->filter(fn (PublicContentChannel $channel): bool => ThemeBindings::shouldShowChannelBinding($channel));

        if ($channels->isEmpty()) {
            return [
                Placeholder::make('no_content_channels')
                    ->hiddenLabel()
                    ->content(new HtmlString(__('vpress::theme_bindings.no_channels'))),
            ];
        }

        return $channels
            ->map(fn (PublicContentChannel $channel): Grid => $this->layoutBindingRow(
                key: 'channel_'.$channel->id(),
                title: $channel->label(),
                description: ThemeBindings::channelAreaDescription($channel),
                field: Select::make("content_channel_sub_themes.{$channel->id()}")
                    ->hiddenLabel()
                    ->options(fn (Get $get): array => ThemeBindings::selectOptionsForChannel(
                        $channel->id(),
                        $get("content_channel_sub_themes.{$channel->id()}"),
                    ))
                    ->native(false)
                    ->required(),
            ))
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function renameSubThemeAction(): Action
    {
        return Action::make('renameSubTheme')
            ->label(__('vpress::settings.rename_theme'))
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->modalHeading(__('vpress::settings.rename_theme'))
            ->modalDescription(__('vpress::settings.rename_theme_help'))
            ->form([
                Select::make('theme_id')
                    ->label(__('vpress::settings.rename_theme_id'))
                    ->options(fn (): array => collect(SubThemeManager::manageableAppThemes())
                        ->mapWithKeys(fn ($location): array => [
                            $location->id => app(SubThemeRegistry::class)->label($location->id),
                        ])
                        ->all())
                    ->required()
                    ->live()
                    ->native(false),
                TextInput::make('label')
                    ->label(__('vpress::settings.rename_theme_label'))
                    ->required()
                    ->maxLength(100)
                    ->default(fn (Get $get): ?string => filled($get('theme_id'))
                        ? app(SubThemeRegistry::class)->label((string) $get('theme_id'))
                        : null),
            ])
            ->action(function (array $data): void {
                $result = SubThemeManager::updateLabel(
                    (string) $data['theme_id'],
                    (string) $data['label'],
                );

                if (! $result->success) {
                    Notification::make()
                        ->title(__('vpress::settings.rename_theme_failed'))
                        ->body($result->error)
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('vpress::settings.rename_theme_success'))
                    ->body(__('vpress::settings.rename_theme_success_body', [
                        'label' => $data['label'],
                        'id' => $result->id,
                    ]))
                    ->success()
                    ->send();

                $this->mount();
            });
    }

    public function deleteSubThemeAction(): Action
    {
        return Action::make('deleteSubTheme')
            ->label(__('vpress::settings.delete_theme'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->button()
            ->requiresConfirmation()
            ->modalHeading(__('vpress::settings.delete_theme'))
            ->modalDescription(__('vpress::settings.delete_theme_help'))
            ->form([
                Select::make('theme_id')
                    ->label(__('vpress::settings.delete_theme_id'))
                    ->options(fn (): array => collect(SubThemeManager::manageableAppThemes())
                        ->mapWithKeys(fn ($location): array => [
                            $location->id => app(SubThemeRegistry::class)->label($location->id),
                        ])
                        ->all())
                    ->required()
                    ->native(false),
                Select::make('fallback_id')
                    ->label(__('vpress::settings.delete_theme_fallback'))
                    ->options(fn (): array => app(SubThemeRegistry::class)->options())
                    ->default('site')
                    ->required()
                    ->native(false)
                    ->helperText(__('vpress::settings.delete_theme_fallback_help')),
            ])
            ->action(function (array $data): void {
                $result = SubThemeManager::delete(
                    (string) $data['theme_id'],
                    (string) $data['fallback_id'],
                );

                if (! $result->success) {
                    Notification::make()
                        ->title(__('vpress::settings.delete_theme_failed'))
                        ->body($result->error)
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('vpress::settings.delete_theme_success'))
                    ->body(__('vpress::settings.delete_theme_success_body', ['id' => $result->id]))
                    ->success()
                    ->send();

                $this->mount();
            });
    }

    public function createSubThemeAction(): Action
    {
        return Action::make('createSubTheme')
            ->label(__('vpress::settings.create_theme'))
            ->icon('heroicon-o-plus')
            ->modalHeading(__('vpress::settings.create_theme'))
            ->modalDescription(__('vpress::settings.create_theme_help'))
            ->form([
                TextInput::make('theme_id')
                    ->label(__('vpress::settings.create_theme_id'))
                    ->required()
                    ->maxLength(48)
                    ->regex('/^[a-z][a-z0-9-]*$/')
                    ->helperText(__('vpress::settings.create_theme_id_help')),
                TextInput::make('theme_label')
                    ->label(__('vpress::settings.create_theme_label'))
                    ->required()
                    ->maxLength(100),
            ])
            ->action(function (array $data): void {
                $result = SubThemeScaffolder::create(
                    (string) $data['theme_id'],
                    (string) $data['theme_label'],
                );

                if (! $result->success) {
                    Notification::make()
                        ->title(__('vpress::settings.create_theme_failed'))
                        ->body($result->error)
                        ->danger()
                        ->send();

                    return;
                }

                $body = __('vpress::settings.create_theme_success', ['label' => $data['theme_label']]);

                if (! $result->importAppended) {
                    $body .= ' '.__('vpress::settings.create_theme_build_hint');
                }

                Notification::make()
                    ->title(__('vpress::settings.create_theme_created'))
                    ->body($body)
                    ->success()
                    ->send();

                $this->mount();
            });
    }

    public function cloneSubThemeAction(): Action
    {
        return Action::make('cloneSubTheme')
            ->label(__('vpress::settings.clone_theme'))
            ->icon('heroicon-o-document-duplicate')
            ->modalHeading(__('vpress::settings.clone_theme'))
            ->modalDescription(__('vpress::settings.clone_theme_help'))
            ->form([
                Select::make('source_id')
                    ->label(__('vpress::settings.clone_theme_source'))
                    ->options(fn (): array => SubThemeLocator::exportable()
                        ->mapWithKeys(fn ($location): array => [
                            $location->id => app(SubThemeRegistry::class)->label($location->id),
                        ])
                        ->all())
                    ->required()
                    ->live()
                    ->native(false),
                TextInput::make('target_id')
                    ->label(__('vpress::settings.clone_theme_target_id'))
                    ->required()
                    ->maxLength(48)
                    ->regex('/^[a-z][a-z0-9-]*$/')
                    ->helperText(__('vpress::settings.create_theme_id_help'))
                    ->default(fn (Get $get): ?string => filled($get('source_id'))
                        ? SubThemeCloner::suggestCloneId((string) $get('source_id'))
                        : null),
                TextInput::make('target_label')
                    ->label(__('vpress::settings.clone_theme_target_label'))
                    ->maxLength(100),
                Toggle::make('import_colors')
                    ->label(__('vpress::settings.import_theme_colors'))
                    ->default(true),
            ])
            ->action(function (array $data): void {
                $result = SubThemeCloner::clone(
                    sourceId: (string) $data['source_id'],
                    targetId: (string) $data['target_id'],
                    label: filled($data['target_label'] ?? null) ? (string) $data['target_label'] : null,
                    importColors: (bool) ($data['import_colors'] ?? true),
                );

                if (! $result->success) {
                    Notification::make()
                        ->title(__('vpress::settings.clone_theme_failed'))
                        ->body($result->error)
                        ->danger()
                        ->send();

                    return;
                }

                $body = __('vpress::settings.clone_theme_success', [
                    'source' => $data['source_id'],
                    'id' => $result->id,
                ]);

                if (! $result->importAppended) {
                    $body .= ' '.__('vpress::settings.create_theme_build_hint');
                }

                Notification::make()
                    ->title(__('vpress::settings.clone_theme_created'))
                    ->body($body)
                    ->success()
                    ->send();

                $this->mount();
            });
    }

    public function exportSubThemeAction(): Action
    {
        return Action::make('exportSubTheme')
            ->label(__('vpress::settings.export_theme'))
            ->icon('heroicon-o-arrow-down-tray')
            ->modalHeading(__('vpress::settings.export_theme'))
            ->modalDescription(__('vpress::settings.export_theme_help'))
            ->form([
                Select::make('theme_id')
                    ->label(__('vpress::settings.export_theme_id'))
                    ->options(fn (): array => SubThemeLocator::exportable()
                        ->mapWithKeys(fn ($location): array => [
                            $location->id => app(SubThemeRegistry::class)->label($location->id),
                        ])
                        ->all())
                    ->required()
                    ->native(false)
                    ->default(fn (): ?string => filled($this->data['editing_theme_id'] ?? null)
                        ? (string) $this->data['editing_theme_id']
                        : null),
            ])
            ->action(function (array $data) {
                $themeId = (string) $data['theme_id'];
                $archivePath = SubThemeExporter::export(
                    $themeId,
                    SubThemeExporter::defaultArchivePath($themeId),
                );

                return response()->download($archivePath)->deleteFileAfterSend();
            });
    }

    public function importSubThemeAction(): Action
    {
        return Action::make('importSubTheme')
            ->label(__('vpress::settings.import_theme'))
            ->icon('heroicon-o-arrow-up-tray')
            ->modalHeading(__('vpress::settings.import_theme'))
            ->modalDescription(__('vpress::settings.import_theme_help'))
            ->form([
                FileUpload::make('archive')
                    ->label(__('vpress::settings.import_theme_archive'))
                    ->disk('local')
                    ->directory('vpress-theme-imports')
                    ->storeFiles(false)
                    ->acceptedFileTypes([
                        'application/zip',
                        'application/x-zip-compressed',
                        'multipart/x-zip',
                    ])
                    ->required(),
                TextInput::make('target_id')
                    ->label(__('vpress::settings.import_theme_target_id'))
                    ->maxLength(48)
                    ->regex('/^[a-z][a-z0-9-]*$/')
                    ->helperText(__('vpress::settings.import_theme_target_id_help')),
                Toggle::make('force')
                    ->label(__('vpress::settings.import_theme_force'))
                    ->helperText(__('vpress::settings.import_theme_force_help')),
                Toggle::make('import_colors')
                    ->label(__('vpress::settings.import_theme_colors'))
                    ->default(true),
            ])
            ->action(function (array $data): void {
                $archivePath = SubThemeImporter::resolveArchiveUploadPath($data['archive'] ?? null);

                if ($archivePath === null) {
                    Notification::make()
                        ->title(__('vpress::settings.import_theme_failed'))
                        ->body(__('vpress::settings.import_theme_missing_archive'))
                        ->danger()
                        ->send();

                    return;
                }

                $result = SubThemeImporter::import(
                    archivePath: $archivePath,
                    force: (bool) ($data['force'] ?? false),
                    importColors: (bool) ($data['import_colors'] ?? true),
                    targetId: filled($data['target_id'] ?? null) ? (string) $data['target_id'] : null,
                    renameOnConflict: ! ($data['force'] ?? false),
                );

                if (! $result->success) {
                    Notification::make()
                        ->title(__('vpress::settings.import_theme_failed'))
                        ->body($result->error)
                        ->danger()
                        ->send();

                    return;
                }

                $body = $result->renamedFrom !== null
                    ? __('vpress::settings.import_theme_renamed', [
                        'from' => $result->renamedFrom,
                        'id' => $result->id,
                    ])
                    : __('vpress::settings.import_theme_success', ['id' => $result->id]);

                if (! $result->importAppended) {
                    $body .= ' '.__('vpress::settings.create_theme_build_hint');
                }

                Notification::make()
                    ->title(__('vpress::settings.import_theme_imported'))
                    ->body($body)
                    ->success()
                    ->send();

                $this->mount();
            });
    }

    /**
     * @return array<int, ColorPicker>
     */
    protected function themeColorFields(string $themeId, string $mode): array
    {
        $prefix = "sub_theme_colors.{$themeId}.{$mode}";

        return [
            ColorPicker::make("{$prefix}.primary")
                ->label(__('vpress::settings.theme_primary'))
                ->hex()
                ->helperText(__('vpress::settings.theme_primary_help')),
            ColorPicker::make("{$prefix}.secondary")
                ->label(__('vpress::settings.theme_secondary'))
                ->hex()
                ->helperText(__('vpress::settings.theme_secondary_help')),
            ColorPicker::make("{$prefix}.header_bg")
                ->label(__('vpress::settings.theme_header_bg'))
                ->hex()
                ->helperText(__('vpress::settings.theme_header_bg_help')),
            ColorPicker::make("{$prefix}.header_text")
                ->label(__('vpress::settings.theme_header_text'))
                ->hex()
                ->helperText(__('vpress::settings.theme_header_text_help')),
            ColorPicker::make("{$prefix}.body_bg")
                ->label(__('vpress::settings.theme_body_bg'))
                ->hex()
                ->helperText(__('vpress::settings.theme_body_bg_help')),
            ColorPicker::make("{$prefix}.text")
                ->label(__('vpress::settings.theme_body_text'))
                ->hex()
                ->helperText(__('vpress::settings.theme_body_text_help')),
        ];
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
