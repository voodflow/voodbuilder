<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;
use Voodflow\Vtuts\Support\Locales;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\SubThemeRegistry;

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
                Section::make(__('Header & appearance'))
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
                        Select::make('sub_theme')
                            ->label(__('vpress::admin.fields.sub_theme'))
                            ->options(fn (): array => app(SubThemeRegistry::class)->options())
                            ->default('default')
                            ->native(false)
                            ->helperText(__('vpress::admin.helpers.sub_theme_site')),
                        Toggle::make('sticky_nav')
                            ->label(__('Sticky navigation on standard pages'))
                            ->helperText(__('Keeps the header visible while scrolling on home, CMS pages, and auth screens. Documentation pages with a sidebar always use a fixed header.'))
                            ->default(false),
                        Toggle::make('show_language_switcher')
                            ->label(__('Show language switcher'))
                            ->helperText(__('Hidden automatically when only one content locale is configured.'))
                            ->default(true)
                            ->live()
                            ->visible(fn (): bool => class_exists(\Voodflow\Vtuts\Support\LocaleSwitcher::class)
                                && \Voodflow\Vtuts\Support\LocaleSwitcher::enabled()),
                        Select::make('primary_locale')
                            ->label(__('vpress::settings.primary_locale'))
                            ->options(fn (): array => class_exists(Locales::class) ? Locales::options() : [])
                            ->default(fn (): string => VpressSettings::primaryLocale())
                            ->helperText(__('vpress::settings.primary_locale_help'))
                            ->visible(fn (): bool => class_exists(Locales::class)
                                && class_exists(\Voodflow\Vtuts\Support\LocaleSwitcher::class)
                                && \Voodflow\Vtuts\Support\LocaleSwitcher::enabled()),
                    ]),
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
