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
use Filament\Schemas\Components\Group;
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
use Voodflow\Vpress\Support\Navigation;

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

    /** @return array<int, Select|TextInput> */
    protected static function menuItemLinkFields(Get $get): array
    {
        return match (static::resolveMenuItemType($get)) {
            MenuItemType::Page => [
                Select::make('link')
                    ->label(__('Page'))
                    ->options(fn (): array => static::sitePageOptions())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->afterStateUpdated(function (callable $set, ?string $state): void {
                        if (blank($state)) {
                            $set('route_match', null);

                            return;
                        }

                        $page = SitePage::query()->where('slug', $state)->first();

                        $set('route_match', $page?->is_home ? 'home' : null);
                    }),
            ],
            MenuItemType::Route => [
                TextInput::make('link')
                    ->label(__('Route name'))
                    ->helperText(__('e.g. vtuts.index, blog.index, home'))
                    ->required(),
            ],
            MenuItemType::Url => [
                TextInput::make('link')
                    ->label(__('URL'))
                    ->helperText(__('Absolute URL (https://…) or site path (/docs/)'))
                    ->required(),
            ],
            default => [],
        };
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

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Menus';

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
                            ->schema([
                                TextInput::make('label')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->options(MenuItemType::class)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, mixed $state, mixed $old): void {
                                        if ($state !== $old) {
                                            $set('link', null);
                                            $set('route_match', null);
                                        }
                                    }),
                                Group::make()
                                    ->schema(fn (Get $get): array => static::menuItemLinkFields($get))
                                    ->columnSpanFull(),
                                TextInput::make('route_match')
                                    ->label(__('Active route pattern'))
                                    ->helperText(__('Optional. e.g. tutorials.* — not needed for site pages'))
                                    ->visible(fn (Get $get): bool => ! static::isMenuItemType($get, MenuItemType::Page)),
                                Toggle::make('open_in_new_tab')
                                    ->label(__('Open in new tab')),
                            ])
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->collapsible()
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
