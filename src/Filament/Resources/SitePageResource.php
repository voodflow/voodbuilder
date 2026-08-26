<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Enums\PageVisibility;
use Voodflow\Voodbuilder\Filament\Actions\CreateSitePageTranslationAction;
use Voodflow\Voodbuilder\Filament\Actions\DeleteSitePageTranslationsAction;
use Voodflow\Voodbuilder\Filament\Columns\TranslationLocaleColumn;
use Voodflow\Voodbuilder\Filament\Concerns\ConfiguresTranslatableLocaleField;
use Voodflow\Voodbuilder\Filament\Concerns\ListsCanonicalTranslationGroups;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource\Pages\CreateSitePage;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource\Pages\EditSitePage;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource\Pages\ListSitePages;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRegistry;
use Voodflow\Voodbuilder\Support\RichContentBlockRegistry;
use Voodflow\Voodbuilder\Support\SitePageForm;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Support\ThemeBindings;
use Voodflow\Vtuts\Support\Locales;

/**
 * Filament resource: Site Page.
 */
class SitePageResource extends Resource
{
    use ConfiguresTranslatableLocaleField;
    use ListsCanonicalTranslationGroups;

    protected static ?string $model = SitePage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-window';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::admin.navigation.pages');
    }

    protected static ?string $modelLabel = 'Page';

    protected static ?string $slug = 'voodbuilder/pages';

    public const LAYOUT_AUTO = SitePage::LAYOUT_AUTO;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->schema([
                        Tabs::make('page_tabs')
                            ->tabs([
                                Tab::make(__('voodbuilder::admin.sections.content'))
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (?string $state, Set $set, Get $get, ?SitePage $record): void {
                                                if ($record !== null || filled($get('slug'))) {
                                                    return;
                                                }

                                                $set('slug', Str::slug((string) $state));
                                            }),

                                        TextInput::make('slug')
                                            ->maxLength(255)
                                            ->unique(
                                                ignoreRecord: true,
                                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where(
                                                    'locale',
                                                    $get('locale') ?? (class_exists(Locales::class) ? Locales::default() : 'en'),
                                                ),
                                            )
                                            ->disabled(fn (?SitePage $record): bool => (bool) $record?->is_home),

                                        Textarea::make('excerpt')
                                            ->label(__('voodbuilder::admin.fields.excerpt'))
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->helperText(__('voodbuilder::admin.helpers.excerpt'))
                                            ->columnSpanFull(),

                                        Hidden::make('builder')
                                            ->default(PageBuilder::Visual->value)
                                            ->dehydrated(),

                                        // Legacy rich-editor pages remain editable until converted to Visual.
                                        RichEditor::make('content')
                                            ->label(__('Page content'))
                                            ->customBlocks(app(RichContentBlockRegistry::class)->editorGroups())
                                            ->toolbarButtons([
                                                ['bold', 'italic', 'strike', 'link'],
                                                ['h2', 'h3', 'blockquote', 'bulletList', 'orderedList'],
                                                ['customBlocks'],
                                            ])
                                            ->visible(fn (?SitePage $record): bool => $record !== null && ! $record->usesEditorBuilder())
                                            ->columnSpanFull(),

                                        Placeholder::make('editor_frontend_hint')
                                            ->label(__('voodbuilder::pro.fields.editor_edit'))
                                            ->content(function (?SitePage $record): HtmlString {
                                                if ($record === null) {
                                                    return new HtmlString(e(__('voodbuilder::pro.helpers.editor_save_first')));
                                                }

                                                $url = $record->getUrl();

                                                return new HtmlString(
                                                    __('voodbuilder::pro.helpers.editor_frontend', ['url' => $url])
                                                    .' <a class="text-primary-600 underline" href="'.e($url).'" target="_blank" rel="noopener">'
                                                    .e(__('voodbuilder::pro.actions.open_visual_editor'))
                                                    .'</a>'
                                                );
                                            })
                                            ->visible(fn (?SitePage $record): bool => SitePageForm::showEditorHint($record))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Tab::make(__('voodbuilder::admin.sections.access'))
                                    ->schema([
                                        Select::make('visibility')
                                            ->label(__('voodbuilder::admin.fields.visibility'))
                                            ->options(PageVisibility::class)
                                            ->default(PageVisibility::Public->value)
                                            ->native(false)
                                            ->helperText(__('voodbuilder::admin.helpers.visibility'))
                                            ->columnSpanFull(),

                                        Toggle::make('password_protected')
                                            ->label(__('voodbuilder::admin.fields.password_protected'))
                                            ->helperText(__('voodbuilder::admin.helpers.password_protected'))
                                            ->live()
                                            ->columnSpanFull(),

                                        Repeater::make('credentials')
                                            ->relationship()
                                            ->label(__('voodbuilder::admin.fields.password_credentials'))
                                            ->helperText(__('voodbuilder::admin.helpers.password_credentials'))
                                            ->schema([
                                                TextInput::make('email')
                                                    ->label(__('voodbuilder::admin.fields.credential_email'))
                                                    ->email()
                                                    ->maxLength(255),
                                                TextInput::make('password')
                                                    ->label(__('voodbuilder::admin.fields.credential_password'))
                                                    ->password()
                                                    ->revealable()
                                                    ->required(fn ($record): bool => $record === null)
                                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                                                    ->formatStateUsing(fn (): ?string => null)
                                                    ->helperText(__('voodbuilder::admin.helpers.credential_password')),
                                            ])
                                            ->columns(1)
                                            ->defaultItems(0)
                                            ->addActionLabel(__('voodbuilder::admin.actions.add_credential'))
                                            ->collapsible()
                                            ->visible(fn (Get $get): bool => (bool) $get('password_protected'))
                                            ->columnSpanFull(),
                                    ]),

                                Tab::make(__('voodbuilder::admin.sections.dynamic'))
                                    ->schema([
                                        Toggle::make('is_dynamic')
                                            ->label(__('voodbuilder::admin.fields.is_dynamic'))
                                            ->helperText(__('voodbuilder::admin.helpers.is_dynamic'))
                                            ->live()
                                            ->columnSpanFull(),

                                        Select::make('dynamic_channel')
                                            ->label(__('voodbuilder::admin.fields.dynamic_channel'))
                                            ->options(fn (): array => app(DynamicPageRegistry::class)->channelOptions())
                                            ->native(false)
                                            ->searchable()
                                            ->required(fn (Get $get): bool => (bool) $get('is_dynamic'))
                                            ->visible(fn (Get $get): bool => (bool) $get('is_dynamic'))
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set) => $set('dynamic_routes', []))
                                            ->helperText(__('voodbuilder::admin.helpers.dynamic_channel'))
                                            ->columnSpanFull(),

                                        CheckboxList::make('dynamic_routes')
                                            ->label(__('voodbuilder::admin.fields.dynamic_routes'))
                                            ->options(fn (Get $get): array => app(DynamicPageRegistry::class)
                                                ->routeOptions((string) ($get('dynamic_channel') ?? '')))
                                            ->required(fn (Get $get): bool => (bool) $get('is_dynamic'))
                                            ->visible(fn (Get $get): bool => (bool) $get('is_dynamic') && filled($get('dynamic_channel')))
                                            ->helperText(__('voodbuilder::admin.helpers.dynamic_routes'))
                                            ->columns(1)
                                            ->columnSpanFull(),

                                        TextInput::make('dynamic_priority')
                                            ->label(__('voodbuilder::admin.fields.dynamic_priority'))
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(999)
                                            ->visible(fn (Get $get): bool => (bool) $get('is_dynamic'))
                                            ->helperText(__('voodbuilder::admin.helpers.dynamic_priority')),

                                        Placeholder::make('dynamic_preview_url')
                                            ->label(__('voodbuilder::admin.fields.dynamic_preview_url'))
                                            ->content(function (Get $get, ?SitePage $record): HtmlString {
                                                $channel = (string) ($get('dynamic_channel') ?? $record?->dynamic_channel ?? '');
                                                $url = filled($channel)
                                                    ? app(DynamicPageRegistry::class)->get($channel)?->previewUrl($record)
                                                    : null;

                                                if (blank($url)) {
                                                    return new HtmlString('<span class="text-gray-500">—</span>');
                                                }

                                                return new HtmlString(
                                                    '<a class="text-primary-600 underline" href="'.e($url).'" target="_blank" rel="noopener">'
                                                    .e($url)
                                                    .'</a>'
                                                );
                                            })
                                            ->visible(fn (Get $get): bool => (bool) $get('is_dynamic'))
                                            ->helperText(__('voodbuilder::admin.helpers.dynamic_preview_url'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Publish')
                            ->afterHeader([
                                Action::make('view')
                                    ->icon('heroicon-o-eye')
                                    ->color('gray')
                                    ->url(fn (SitePage $record): string => $record->getUrl())
                                    ->openUrlInNewTab()
                                    ->visible(fn (?SitePage $record): bool => $record?->published ?? false),
                            ])
                            ->schema([
                                Toggle::make('published')
                                    ->label(__('Published'))
                                    ->default(false),

                                DateTimePicker::make('published_at'),

                                Toggle::make('is_home')
                                    ->label(__('Home page'))
                                    ->helperText(__('voodbuilder::admin.helpers.home_page_locale'))
                                    ->live(),

                                static::translatableLocaleSelect(
                                    Select::make('locale')
                                        ->label(__('voodbuilder::admin.fields.language'))
                                        ->options(fn (): array => class_exists(Locales::class) ? Locales::options() : ['en' => 'English'])
                                        ->default(fn (): string => class_exists(Locales::class) ? Locales::default() : 'en')
                                        ->required()
                                        ->native(false)
                                        ->visible(fn (): bool => SitePageResolver::localizationEnabled()),
                                    SitePage::class,
                                    static::class,
                                ),

                                Placeholder::make('translation_links')
                                    ->label(__('voodbuilder::admin.fields.translations'))
                                    ->content(function (?SitePage $record): HtmlString|string {
                                        if ($record === null || blank($record->translation_group_id)) {
                                            return __('voodbuilder::admin.translation.none_yet');
                                        }

                                        $siblings = SitePage::query()
                                            ->where('translation_group_id', $record->translation_group_id)
                                            ->whereKeyNot($record->getKey())
                                            ->orderBy('locale')
                                            ->get();

                                        if ($siblings->isEmpty()) {
                                            return __('voodbuilder::admin.translation.none_yet');
                                        }

                                        $links = $siblings
                                            ->map(function (SitePage $page): string {
                                                $label = class_exists(Locales::class)
                                                    ? (Locales::options()[$page->locale] ?? $page->locale)
                                                    : $page->locale;
                                                $url = static::getUrl('edit', ['record' => $page]);

                                                return '<a href="'.e($url).'" class="text-primary-600 hover:underline">'.e($label).'</a>';
                                            })
                                            ->implode(' · ');

                                        return new HtmlString($links);
                                    })
                                    ->visibleOn('edit')
                                    ->visible(fn (): bool => SitePageResolver::localizationEnabled()),
                            ]),

                        // Chrome layout is inherited from the channel/theme (Admin → Layouts).
                        Hidden::make('chrome_layout_id')
                            ->dehydrated()
                            ->dehydrateStateUsing(fn (): ?string => null),

                        Section::make(__('voodbuilder::admin.sections.appearance'))
                            ->collapsed(false)
                            ->visible(fn (): bool => ! SitePageForm::chromeLayoutManagesShell()
                                || SitePageForm::allowsSubThemeOverride())
                            ->schema([
                                Select::make('layout')
                                    ->label(__('voodbuilder::admin.fields.canvas_width'))
                                    ->options([
                                        self::LAYOUT_AUTO => __('voodbuilder::admin.fields.layout_auto'),
                                        'page' => __('voodbuilder::admin.fields.layout_standard'),
                                        'full_width' => __('voodbuilder::admin.fields.layout_full_width'),
                                    ])
                                    ->default(self::LAYOUT_AUTO)
                                    ->native(false)
                                    ->helperText(fn (Get $get, ?SitePage $record): ?string => match (true) {
                                        ($get('layout') === self::LAYOUT_AUTO || blank($get('layout')))
                                            && ($record?->is_home || (bool) $get('is_home')) => __('voodbuilder::admin.helpers.layout_auto_home'),
                                        $get('layout') === self::LAYOUT_AUTO || blank($get('layout')) => __('voodbuilder::admin.helpers.layout_auto_page'),
                                        static::formUsesFullWidthLayout($get, $record) => __('voodbuilder::landing.layouts.full_width_help'),
                                        default => null,
                                    })
                                    // Content width lives on the chrome layout when layouts manage the shell.
                                    ->visible(fn (): bool => ! SitePageForm::chromeLayoutManagesShell())
                                    ->afterStateHydrated(function (Select $component, ?SitePage $record): void {
                                        if ($record === null) {
                                            return;
                                        }

                                        if (in_array($record->layout, ['home', 'landing', 'full_width'], true)) {
                                            $component->state('full_width');

                                            return;
                                        }

                                        if ($record->usesAutomaticLayout()) {
                                            $component->state(self::LAYOUT_AUTO);

                                            return;
                                        }
                                    })
                                    ->dehydrateStateUsing(function (?string $state, Get $get, ?SitePage $record): string {
                                        if ($state === self::LAYOUT_AUTO || ! filled($state)) {
                                            return self::LAYOUT_AUTO;
                                        }

                                        $isHome = $record?->is_home || (bool) $get('is_home');

                                        if ($state !== 'full_width') {
                                            return $state;
                                        }

                                        return $isHome ? 'home' : 'full_width';
                                    })
                                    ->live(),

                                Toggle::make('hide_site_footer')
                                    ->label(__('voodbuilder::landing.layouts.hide_site_footer'))
                                    ->helperText(__('voodbuilder::landing.layouts.hide_site_footer_help'))
                                    ->visible(fn (Get $get, ?SitePage $record): bool => ! SitePageForm::chromeLayoutManagesShell()
                                        && static::formUsesFullWidthLayout($get, $record)),

                                Toggle::make('hide_site_nav')
                                    ->label(__('voodbuilder::landing.layouts.hide_site_nav'))
                                    ->helperText(__('voodbuilder::landing.layouts.hide_site_nav_help'))
                                    ->visible(fn (Get $get, ?SitePage $record): bool => ! SitePageForm::chromeLayoutManagesShell()
                                        && static::formUsesFullWidthLayout($get, $record)),

                                Select::make('sub_theme')
                                    ->label(__('voodbuilder::admin.fields.sub_theme'))
                                    ->options(fn (?SitePage $record): array => [
                                        '' => __('voodbuilder::admin.fields.sub_theme_inherit'),
                                        ...ThemeBindings::sitePagesSelectOptions($record?->sub_theme),
                                    ])
                                    ->default(null)
                                    ->nullable()
                                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->native(false)
                                    ->visible(fn (): bool => SitePageForm::allowsSubThemeOverride())
                                    ->helperText(function (Get $get, ?SitePage $record): string {
                                        $siteTheme = ThemeBindings::siteThemeLabel();
                                        $message = __('voodbuilder::admin.helpers.sub_theme_page', ['theme' => $siteTheme]);

                                        $subTheme = filled($get('sub_theme'))
                                            ? (string) $get('sub_theme')
                                            : SubThemeResolver::siteDefault();

                                        if (
                                            static::formUsesFullWidthLayout($get, $record)
                                            && $subTheme === SubThemeResolver::DEFAULT
                                        ) {
                                            return $message.' '.__('voodbuilder::admin.helpers.sub_theme_marketing_recommended');
                                        }

                                        return $message;
                                    }),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TranslationLocaleColumn::make(static::class),
                TextColumn::make('builder')
                    ->label(__('voodbuilder::pro.fields.builder'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (PageBuilder|string|null $state): string => $state instanceof PageBuilder
                        ? $state->label()
                        : PageBuilder::normalize((string) $state)?->label() ?? (string) $state),
                TextColumn::make('layout')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        SitePage::LAYOUT_AUTO => __('voodbuilder::admin.fields.layout_auto'),
                        'home', 'landing', 'full_width' => __('voodbuilder::admin.fields.layout_full_width'),
                        default => __('voodbuilder::admin.fields.layout_standard'),
                    }),
                TextColumn::make('sub_theme')
                    ->label(__('voodbuilder::admin.fields.sub_theme'))
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? app(SubThemeRegistry::class)->label($state)
                        : __('voodbuilder::admin.fields.sub_theme_inherit'))
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'info' : 'gray'),
                IconColumn::make('is_home')->label(__('Home'))->boolean(),
                IconColumn::make('published')->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (SitePage $record): string => static::getUrl('edit', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('openVisualEditor')
                        ->label(__('voodbuilder::pro.actions.open_visual_editor'))
                        ->icon('heroicon-o-paint-brush')
                        ->color('gray')
                        ->url(fn (SitePage $record): string => $record->getUrl().(str_contains($record->getUrl(), '?') ? '&' : '?').'edit=1')
                        ->openUrlInNewTab()
                        ->visible(fn (SitePage $record): bool => $record->usesEditorBuilder()),
                    CreateSitePageTranslationAction::make(),
                    DeleteSitePageTranslationsAction::make(fromTable: true),
                    DeleteAction::make()->hidden(fn (SitePage $record): bool => $record->is_home),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->iconButton()
                    ->tooltip(__('voodbuilder::admin.actions.actions')),
            ])
            ->recordActionsColumnLabel(null)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->filters([
                TernaryFilter::make('is_home')
                    ->label(__('voodbuilder::admin.filters.is_home'))
                    ->trueLabel(__('voodbuilder::admin.filters.yes'))
                    ->falseLabel(__('voodbuilder::admin.filters.no'))
                    ->placeholder(__('voodbuilder::admin.filters.any')),
                TernaryFilter::make('published')
                    ->label(__('voodbuilder::admin.filters.published'))
                    ->trueLabel(__('voodbuilder::admin.filters.yes'))
                    ->falseLabel(__('voodbuilder::admin.filters.no'))
                    ->placeholder(__('voodbuilder::admin.filters.any')),
                SelectFilter::make('builder')
                    ->label(__('voodbuilder::admin.filters.builder'))
                    ->options(PageBuilder::options())
                    ->visible(false),
                SelectFilter::make('layout')
                    ->label(__('voodbuilder::admin.filters.layout'))
                    ->options([
                        'page' => __('Standard page'),
                        'full_width' => __('voodbuilder::landing.layouts.full_width'),
                        'home' => __('Home'),
                        'landing' => __('voodbuilder::landing.layouts.landing'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        if ($value === 'full_width') {
                            return $query->whereIn('layout', ['home', 'landing', 'full_width']);
                        }

                        return $query->where('layout', $value);
                    }),
                SelectFilter::make('sub_theme')
                    ->label(__('voodbuilder::admin.filters.sub_theme'))
                    ->options(fn (): array => [
                        '__inherit__' => __('voodbuilder::admin.filters.sub_theme_inherit'),
                        ...app(SubThemeRegistry::class)->options(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        if ($value === '__inherit__') {
                            return $query->whereNull('sub_theme');
                        }

                        return $query->where('sub_theme', $value);
                    }),
                static::translationLocaleFilter(),
            ])
            ->filtersFormColumns(2)
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSitePages::route('/'),
            'create' => CreateSitePage::route('/create'),
            'edit' => EditSitePage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    protected static function formUsesFullWidthLayout(Get $get, ?SitePage $record): bool
    {
        $layout = $get('layout');

        if ($layout === 'full_width') {
            return true;
        }

        if ($layout === self::LAYOUT_AUTO || blank($layout)) {
            return ($record?->is_home || (bool) $get('is_home')) && $layout !== 'page';
        }

        return $record?->usesFullWidthLayout() ?? false;
    }
}
