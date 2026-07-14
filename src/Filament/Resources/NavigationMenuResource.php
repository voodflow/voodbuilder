<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Enums\MenuLinkDisplay;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Filament\Actions\CloneNavigationMenuAction;
use Voodflow\Voodbuilder\Filament\Actions\CreateNavigationMenuTranslationAction;
use Voodflow\Voodbuilder\Filament\Actions\DeleteNavigationMenuTranslationsAction;
use Voodflow\Voodbuilder\Filament\Columns\TranslationLocaleColumn;
use Voodflow\Voodbuilder\Filament\Concerns\ConfiguresTranslatableLocaleField;
use Voodflow\Voodbuilder\Filament\Concerns\ListsCanonicalTranslationGroups;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource\Pages\CreateNavigationMenu;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource\Pages\EditNavigationMenu;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource\Pages\ListNavigationMenus;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\MenuRouteCatalog;
use Voodflow\Voodbuilder\Support\MenuRouteParameterField;
use Voodflow\Voodbuilder\Support\MenuTablerIcons;
use Voodflow\Voodbuilder\Support\NavigationMenuPlacements;
use Voodflow\Voodbuilder\Support\NavigationMenuResolver;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Vtuts\Support\Locales;

class NavigationMenuResource extends Resource
{
    use ConfiguresTranslatableLocaleField;
    use ListsCanonicalTranslationGroups;

    protected static ?string $model = NavigationMenu::class;

    protected static function resolveMenuItemType(Get $get): ?MenuItemType
    {
        $type = $get('type');

        if ($type instanceof MenuItemType) {
            return $type;
        }

        return is_string($type) ? MenuItemType::tryFrom($type) : null;
    }

    protected static function isMenuItemType(Get $get, MenuItemType $expected): bool
    {
        return static::resolveMenuItemType($get) === $expected;
    }

    /** @return array<string, string> */
    protected static function sitePageOptions(?string $locale = null): array
    {
        $query = SitePage::query()
            ->where('builder', PageBuilder::GrapesJs)
            ->orderByDesc('is_home')
            ->orderBy('title');

        if ($locale !== null && SitePageResolver::localizationEnabled()) {
            $query->where('locale', $locale);
        }

        return $query
            ->get()
            ->mapWithKeys(function (SitePage $page): array {
                $label = $page->title;

                if ($page->is_home) {
                    $label .= ' ('.__('Home').')';
                } elseif (! $page->published) {
                    $label .= ' ('.__('Draft').')';
                }

                return [$page->slug => $label];
            })
            ->all();
    }

    /** @return array<string, string> */
    protected static function menuItemTypeOptions(bool $isChild): array
    {
        $types = collect(MenuItemType::cases());

        if ($isChild) {
            $types = $types->reject(fn (MenuItemType $type): bool => $type === MenuItemType::Group);
        }

        return $types
            ->mapWithKeys(fn (MenuItemType $type): array => [$type->value => $type->getLabel()])
            ->all();
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::admin.navigation.menus');
    }

    protected static ?string $modelLabel = 'Menu';

    protected static ?string $slug = 'voodbuilder/navigation-menus';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('slug')
                            ->label(__('Menu placement'))
                            ->options(fn (?NavigationMenu $record): array => NavigationMenuPlacements::formOptions($record))
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where(
                                    'locale',
                                    $get('locale') ?? (class_exists(Locales::class) ? Locales::default() : 'en'),
                                ),
                            )
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (callable $set, ?string $state): void {
                                if ($state === 'social') {
                                    $set('link_display', MenuLinkDisplay::IconOnly->value);
                                }
                            })
                            ->helperText(__('voodbuilder::admin.menu_placements.helper')),
                        Select::make('link_display')
                            ->label(__('voodbuilder::admin.navigation.link_display'))
                            ->options(MenuLinkDisplay::class)
                            ->default(MenuLinkDisplay::TextOnly)
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('slug') === 'social')
                            ->required(fn (Get $get): bool => $get('slug') === 'social')
                            ->helperText(__('voodbuilder::admin.navigation.link_display_help')),
                        static::translatableLocaleSelect(
                            Select::make('locale')
                                ->label(__('voodbuilder::admin.fields.language'))
                                ->options(fn (): array => class_exists(Locales::class) ? Locales::options() : ['en' => 'English'])
                                ->default(fn (): string => class_exists(Locales::class) ? Locales::default() : 'en')
                                ->required()
                                ->native(false)
                                ->visible(fn (): bool => NavigationMenuResolver::localizationEnabled()),
                            NavigationMenu::class,
                            static::class,
                        ),
                        Placeholder::make('translation_links')
                            ->label(__('voodbuilder::admin.fields.translations'))
                            ->content(function (?NavigationMenu $record): HtmlString|string {
                                if ($record === null || blank($record->translation_group_id)) {
                                    return __('voodbuilder::admin.menu_translation.none_yet');
                                }

                                $siblings = NavigationMenu::query()
                                    ->where('translation_group_id', $record->translation_group_id)
                                    ->whereKeyNot($record->getKey())
                                    ->orderBy('locale')
                                    ->get();

                                if ($siblings->isEmpty()) {
                                    return __('voodbuilder::admin.menu_translation.none_yet');
                                }

                                $links = $siblings
                                    ->map(function (NavigationMenu $menu): string {
                                        $label = class_exists(Locales::class)
                                            ? (Locales::options()[$menu->locale] ?? $menu->locale)
                                            : $menu->locale;
                                        $url = static::getUrl('edit', ['record' => $menu]);

                                        return '<a href="'.e($url).'" class="text-primary-600 hover:underline">'.e($label).'</a>';
                                    })
                                    ->implode(' · ');

                                return new HtmlString($links);
                            })
                            ->visibleOn('edit')
                            ->visible(fn (): bool => NavigationMenuResolver::localizationEnabled()),
                    ]),
            ]);
    }

    /** @return array<int, Component> */
    public static function menuItemFormSchema(bool $isChild, ?string $menuSlug = null, ?string $menuLocale = null): array
    {
        return static::menuItemFields($isChild, $menuSlug, $menuLocale);
    }

    /** @return array<int, Component> */
    protected static function menuItemFields(bool $isChild, ?string $menuSlug = null, ?string $menuLocale = null): array
    {
        $isSocialMenu = $menuSlug === 'social';

        return [
            TextInput::make('label')
                ->required()
                ->maxLength(255),
            Select::make('icon')
                ->label(__('voodbuilder::admin.fields.menu_icon'))
                ->options(MenuTablerIcons::options())
                ->searchable()
                ->native(false)
                ->visible($isSocialMenu)
                ->required($isSocialMenu)
                ->helperText(__('voodbuilder::admin.helpers.menu_icon')),
            Select::make('type')
                ->options(static::menuItemTypeOptions($isChild))
                ->required()
                ->live()
                ->afterStateUpdated(function (callable $set, mixed $state, mixed $old): void {
                    if ($state !== $old) {
                        $set('link', null);
                        $set('route_parameters', null);
                        $set('route_match', null);

                        foreach (MenuRouteParameterField::parameterNames() as $parameterName) {
                            $set(MenuRouteParameterField::flatKey($parameterName), null);
                        }
                    }
                }),
            Select::make('link')
                ->key($isChild ? 'menu_child_link_page' : 'menu_item_link_page')
                ->label(__('Page'))
                ->options(fn (): array => static::sitePageOptions($menuLocale))
                ->searchable()
                ->preload()
                ->live()
                ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Page))
                ->required(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Page))
                ->helperText(__('voodbuilder::admin.helpers.menu_grapes_pages_only'))
                ->afterStateUpdated(function (callable $set, ?string $state): void {
                    if (blank($state)) {
                        $set('route_match', null);

                        return;
                    }

                    $page = SitePage::query()->where('slug', $state)->first();

                    $set('route_match', $page?->is_home ? 'home' : null);
                }),
            Placeholder::make('page_visual_editor_link')
                ->label('')
                ->content(function (Get $get): ?HtmlString {
                    if (! static::isMenuItemType($get, MenuItemType::Page)) {
                        return null;
                    }

                    $slug = $get('link');

                    if (blank($slug)) {
                        return null;
                    }

                    $page = SitePage::query()->where('slug', $slug)->first();

                    if (! $page instanceof SitePage || ! $page->usesGrapesJsBuilder()) {
                        return null;
                    }

                    $url = $page->getUrl().'?edit=1';

                    return new HtmlString(
                        '<a href="'.e($url).'" target="_blank" rel="noopener" class="text-sm text-primary-600 hover:underline">'
                        .e(__('voodbuilder::pro.actions.open_visual_editor'))
                        .'</a>'
                    );
                })
                ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Page)),
            Select::make('link')
                ->key($isChild ? 'menu_child_link_route' : 'menu_item_link_route')
                ->label(__('voodbuilder::admin.fields.menu_route'))
                ->options(fn (): array => MenuRouteCatalog::options())
                ->searchable()
                ->preload()
                ->live()
                ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Route))
                ->required(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Route))
                ->helperText(__('voodbuilder::admin.helpers.menu_route'))
                ->afterStateUpdated(function (callable $set, ?string $state, Get $get): void {
                    $required = filled($state)
                        ? MenuRouteCatalog::requiredParameterNames($state)
                        : [];

                    foreach (MenuRouteParameterField::parameterNames() as $parameterName) {
                        $flatKey = MenuRouteParameterField::flatKey($parameterName);

                        if (! in_array($parameterName, $required, true)) {
                            $set($flatKey, null);

                            continue;
                        }

                        if (blank($get($flatKey))) {
                            $default = MenuRouteParameterField::defaultValue($state, $parameterName);

                            if (filled($default)) {
                                $set($flatKey, $default);
                            }
                        }
                    }

                    $set('route_match', filled($state) ? MenuRouteCatalog::activePattern($state) : null);
                }),
            TextInput::make('link')
                ->key($isChild ? 'menu_child_link_url' : 'menu_item_link_url')
                ->label(__('URL'))
                ->helperText(__('Absolute URL (https://…) or site path (/docs/)'))
                ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Url))
                ->required(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Url)),
            TextInput::make('link')
                ->key($isChild ? 'menu_child_link_mail' : 'menu_item_link_mail')
                ->label(__('Email'))
                ->email()
                ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Mail))
                ->required(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Mail)),
            ...MenuRouteParameterField::components(),
            TextInput::make('route_match')
                ->label(__('voodbuilder::admin.fields.menu_route_match'))
                ->helperText(__('voodbuilder::admin.helpers.menu_route_match'))
                ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Url)),
            Toggle::make('open_in_new_tab')
                ->label(__('Open in new tab'))
                ->visible(fn (Get $get): bool => ! static::isMenuItemType($get, MenuItemType::Group)),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => NavigationMenuPlacements::label($state)),
                TranslationLocaleColumn::make(static::class),
                TextColumn::make('root_items_count')->counts('rootItems')->label(__('Items')),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordUrl(fn (NavigationMenu $record): string => static::getUrl('edit', ['record' => $record]))
            ->filters([
                static::translationLocaleFilter(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    CreateNavigationMenuTranslationAction::make(),
                    DeleteNavigationMenuTranslationsAction::make(fromTable: true),
                    CloneNavigationMenuAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->iconButton()
                    ->tooltip(__('voodbuilder::admin.actions.actions')),
            ])
            ->recordActionsColumnLabel(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNavigationMenus::route('/'),
            'create' => CreateNavigationMenu::route('/create'),
            'edit' => EditNavigationMenu::route('/{record}/edit'),
        ];
    }
}
