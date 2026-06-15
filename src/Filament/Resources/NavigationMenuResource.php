<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Voodflow\Vpress\Enums\MenuItemType;
use Voodflow\Vpress\Filament\Resources\NavigationMenuResource\Pages\CreateNavigationMenu;
use Voodflow\Vpress\Filament\Resources\NavigationMenuResource\Pages\EditNavigationMenu;
use Voodflow\Vpress\Filament\Resources\NavigationMenuResource\Pages\ListNavigationMenus;
use Voodflow\Vpress\Models\NavigationMenu;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\MenuRouteCatalog;
use Voodflow\Vpress\Support\MenuRouteParameterField;

class NavigationMenuResource extends Resource
{
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
    protected static function sitePageOptions(): array
    {
        return SitePage::query()
            ->orderByDesc('is_home')
            ->orderBy('title')
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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('vpress::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('vpress::admin.navigation.menus');
    }

    protected static ?string $modelLabel = 'Menu';

    protected static ?string $slug = 'vpress/navigation-menus';

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
                            ->options([
                                'main' => __('Main navigation — center of the header'),
                                'header_extra' => __('Header extras — right side (before language / theme / account)'),
                                'footer' => __('Footer links'),
                            ])
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->native(false)
                            ->helperText(__('Use header_extra for Shop, Blog, or other links on the right side of the navbar.')),
                    ]),
                Section::make(__('Menu items'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->mutateRelationshipDataBeforeFillUsing(
                                fn (array $data): array => MenuRouteParameterField::expandForFill($data),
                            )
                            ->mutateRelationshipDataBeforeCreateUsing(
                                fn (array $data): array => MenuRouteParameterField::compressForSave($data),
                            )
                            ->mutateRelationshipDataBeforeSaveUsing(
                                fn (array $data): array => MenuRouteParameterField::compressForSave($data),
                            )
                            ->schema([
                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->options(MenuItemType::class)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, mixed $state, mixed $old, Get $get): void {
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
                                    ->key('menu_item_link_page')
                                    ->label(__('Page'))
                                    ->options(fn (): array => static::sitePageOptions())
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Page))
                                    ->required(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Page))
                                    ->afterStateUpdated(function (callable $set, ?string $state): void {
                                        if (blank($state)) {
                                            $set('route_match', null);

                                            return;
                                        }

                                        $page = SitePage::query()->where('slug', $state)->first();

                                        $set('route_match', $page?->is_home ? 'home' : null);
                                    }),
                                Select::make('link')
                                    ->key('menu_item_link_route')
                                    ->label(__('vpress::admin.fields.menu_route'))
                                    ->options(fn (): array => MenuRouteCatalog::options())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Route))
                                    ->required(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Route))
                                    ->helperText(__('vpress::admin.helpers.menu_route'))
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
                                    ->key('menu_item_link_url')
                                    ->label(__('URL'))
                                    ->helperText(__('Absolute URL (https://…) or site path (/docs/)'))
                                    ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Url))
                                    ->required(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Url)),
                                ...MenuRouteParameterField::components(),
                                TextInput::make('route_match')
                                    ->label(__('vpress::admin.fields.menu_route_match'))
                                    ->helperText(__('vpress::admin.helpers.menu_route_match'))
                                    ->visible(fn (Get $get): bool => static::isMenuItemType($get, MenuItemType::Url)),
                                Toggle::make('open_in_new_tab')
                                    ->label(__('Open in new tab')),
                            ])
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->badge(),
                TextColumn::make('items_count')->counts('items')->label(__('Items')),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
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
