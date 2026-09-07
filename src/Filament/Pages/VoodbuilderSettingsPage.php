<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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
use Throwable;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\Popups\PopupsOrphanStatus;
use Voodflow\Vtuts\Support\Locales;
use Voodflow\Vtuts\Support\LocaleSwitcher;

/**
 * @property-read Schema $form
 */
class VoodbuilderSettingsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::admin.navigation.settings');
    }

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'voodbuilder/settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $data = VoodbuilderSettings::data();

        if (blank($data['primary_locale'] ?? null) && class_exists(Locales::class)) {
            $data['primary_locale'] = VoodbuilderSettings::primaryLocale();
        }

        $this->data = $data;
        $this->form->fill($data);

        if (PopupsOrphanStatus::detected()) {
            Notification::make()
                ->warning()
                ->title(PopupsOrphanStatus::adminTitle())
                ->body(PopupsOrphanStatus::adminBody())
                ->persistent()
                ->send();
        }
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            VoodbuilderSettings::saveData($data);

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
        $uploadDisk = config('voodbuilder.uploads.disk', 'public');
        $uploadDirectory = config('voodbuilder.uploads.directory', 'voodbuilder');
        $imageTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
        $faviconTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];

        return $schema
            ->components([
                Tabs::make('SettingsTabs')
                    ->tabs([
                        Tab::make(__('voodbuilder::settings.tabs.site'))
                            ->icon('heroicon-o-building-office-2')

                            ->schema([
                                Section::make(__('Site'))
                                    ->contained(false)
                                    ->schema([
                                        TextInput::make('site_title')
                                            ->label(__('Site title'))
                                            ->helperText(__('Shown in the browser tab, page titles, and social sharing previews.'))
                                            ->maxLength(255),
                                        TextInput::make('brand_name')
                                            ->label(__('Brand name'))
                                            ->helperText(__('Short name shown next to the logo in the header. Leave empty to reuse the site title.'))
                                            ->maxLength(255),
                                        $this->configurePublicBrandingUpload(
                                            FileUpload::make('favicon')
                                                ->label(__('Favicon'))
                                                ->disk($uploadDisk)
                                                ->directory($uploadDirectory.'/favicons')
                                                ->visibility('public')
                                                ->acceptedFileTypes($faviconTypes)
                                                ->maxSize(512)
                                                ->helperText(__('Used when pages do not define their own favicon. Leave empty to use the Voodflow mark.')),
                                        ),
                                    ]),
                            ]),
                        Tab::make(__('voodbuilder::settings.tabs.appearance'))
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Section::make(__('voodbuilder::settings.header_nav.section'))
                                    ->description(__('voodbuilder::settings.header_nav.section_description'))
                                    ->contained(false)
                                    ->schema([
                                        Section::make(__('voodbuilder::settings.header_nav.navigation_fieldset'))
                                            ->description(__('voodbuilder::settings.header_nav.navigation_fieldset_help'))
                                            ->compact()
                                            ->schema([
                                                Toggle::make('sticky_nav')
                                                    ->label(__('voodbuilder::settings.header_nav.sticky_nav'))
                                                    ->helperText(__('voodbuilder::settings.header_nav.sticky_nav_help'))
                                                    ->default(false),
                                                Toggle::make('show_notification_bell')
                                                    ->label(__('voodbuilder::settings.header_nav.notification_bell'))
                                                    ->helperText(__('voodbuilder::settings.header_nav.notification_bell_help'))
                                                    ->default(true),
                                                Toggle::make('show_account_link')
                                                    ->label(__('voodbuilder::settings.header_nav.account_link'))
                                                    ->helperText(__('voodbuilder::settings.header_nav.account_link_help'))
                                                    ->default(true),
                                            ]),
                                        Section::make(__('voodbuilder::settings.header_nav.theme_fieldset'))
                                            ->description(__('voodbuilder::settings.header_nav.theme_fieldset_help'))
                                            ->compact()
                                            ->schema([
                                                Toggle::make('show_theme_toggle')
                                                    ->label(__('voodbuilder::settings.header_nav.theme_toggle'))
                                                    ->helperText(__('voodbuilder::settings.header_nav.theme_toggle_help'))
                                                    ->default(true)
                                                    ->live(),
                                                Select::make('theme_mode')
                                                    ->label(fn (Get $get): string => $get('show_theme_toggle')
                                                        ? __('voodbuilder::settings.header_nav.default_theme')
                                                        : __('voodbuilder::settings.header_nav.site_theme'))
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
                                                        ? __('voodbuilder::settings.header_nav.default_theme_help')
                                                        : __('voodbuilder::settings.header_nav.site_theme_help')),
                                            ]),
                                        Section::make(__('voodbuilder::settings.header_nav.language_fieldset'))
                                            ->compact()
                                            ->schema([
                                                Toggle::make('show_language_switcher')
                                                    ->label(__('voodbuilder::settings.header_nav.language_switcher'))
                                                    ->helperText(__('voodbuilder::settings.header_nav.language_switcher_help'))
                                                    ->default(true)
                                                    ->live()
                                                    ->visible(fn (): bool => class_exists(LocaleSwitcher::class)
                                                        && LocaleSwitcher::enabled()),
                                                Select::make('primary_locale')
                                                    ->label(__('voodbuilder::settings.primary_locale'))
                                                    ->options(fn (): array => class_exists(Locales::class) ? Locales::options() : [])
                                                    ->default(fn (): string => VoodbuilderSettings::primaryLocale())
                                                    ->helperText(__('voodbuilder::settings.primary_locale_help'))
                                                    ->visible(fn (): bool => class_exists(Locales::class)
                                                        && class_exists(LocaleSwitcher::class)
                                                        && LocaleSwitcher::enabled()),
                                            ]),
                                    ]),
                            ]),
                        Tab::make(__('voodbuilder::settings.tabs.seo'))
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
                                            ->maxSize((int) config('voodbuilder.uploads.social_max_size', 4096))
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
                        Tab::make(__('voodbuilder::settings.tabs.geo_ai'))
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
                                                ->maxSize((int) config('voodbuilder.uploads.max_size', 2048)),
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
                        Tab::make(__('voodbuilder::settings.tabs.analytics'))
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make(__('Analytics & monitoring'))
                                    ->description(__('Tracking scripts load on the public site only after analytics cookie consent is accepted (via Vcookiebar when installed).'))
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
            ->getUploadedFileUsing(function (FileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                $uploaded = $component->getUploadedFile($file, $storedFileNames);

                if ($uploaded === null || $component->getDiskName() !== 'public') {
                    return $uploaded;
                }

                $uploaded['url'] = '/storage/'.ltrim($file, '/');

                return $uploaded;
            });
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
