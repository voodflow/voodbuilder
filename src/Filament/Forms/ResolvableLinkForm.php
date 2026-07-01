<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Voodflow\Voodbuilder\Enums\ResolvableLinkType;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\MenuRouteCatalog;
use Voodflow\Voodbuilder\Support\MenuRouteParameterField;
use Voodflow\Voodbuilder\Support\ResolvableLinkSupport;
use Voodflow\Voodbuilder\Support\SitePageResolver;

final class ResolvableLinkForm
{
    /**
     * @param  array{
     *     required?: bool,
     *     type_label?: string,
     *     extra_types?: array<string, string>,
     *     visible?: callable(Get): bool,
     * }  $options
     * @return array<int, mixed>
     */
    public static function fields(string $prefix, array $options = []): array
    {
        $required = (bool) ($options['required'] ?? false);
        $typeField = ResolvableLinkSupport::typeField($prefix);
        $targetField = ResolvableLinkSupport::targetField($prefix);
        $typeOptions = self::typeOptions($options['extra_types'] ?? []);
        $visible = $options['visible'] ?? null;

        $fields = [
            Select::make($typeField)
                ->label($options['type_label'] ?? __('voodbuilder::landing.link.type'))
                ->options($typeOptions)
                ->default(ResolvableLinkType::Url->value)
                ->live()
                ->required($required)
                ->afterStateUpdated(function (callable $set) use ($prefix): void {
                    $set(ResolvableLinkSupport::targetField($prefix), null);
                    $set(ResolvableLinkSupport::routeParametersKey($prefix), null);

                    foreach (MenuRouteParameterField::parameterNames() as $parameterName) {
                        $set(ResolvableLinkSupport::routeParameterFlatKey($prefix, $parameterName), null);
                    }
                }),
            Select::make($targetField)
                ->key("{$prefix}_link_page")
                ->label(__('Page'))
                ->options(fn (): array => self::sitePageOptions())
                ->searchable()
                ->preload()
                ->visible(fn (Get $get): bool => self::isType($get, $prefix, ResolvableLinkType::Page))
                ->required(fn (Get $get): bool => $required && self::isType($get, $prefix, ResolvableLinkType::Page)),
            Select::make($targetField)
                ->key("{$prefix}_link_route")
                ->label(__('voodbuilder::admin.fields.menu_route'))
                ->options(fn (): array => MenuRouteCatalog::options())
                ->searchable()
                ->preload()
                ->live()
                ->visible(fn (Get $get): bool => self::isType($get, $prefix, ResolvableLinkType::Route))
                ->required(fn (Get $get): bool => $required && self::isType($get, $prefix, ResolvableLinkType::Route))
                ->helperText(__('voodbuilder::admin.helpers.menu_route'))
                ->afterStateUpdated(function (callable $set, ?string $state, Get $get) use ($prefix): void {
                    $requiredParameters = filled($state)
                        ? MenuRouteCatalog::requiredParameterNames($state)
                        : [];

                    foreach (MenuRouteParameterField::parameterNames() as $parameterName) {
                        $flatKey = ResolvableLinkSupport::routeParameterFlatKey($prefix, $parameterName);

                        if (! in_array($parameterName, $requiredParameters, true)) {
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
                }),
            TextInput::make($targetField)
                ->key("{$prefix}_link_url")
                ->label(__('URL'))
                ->helperText(__('Absolute URL (https://…) or site path (/pages/example)'))
                ->visible(fn (Get $get): bool => self::isType($get, $prefix, ResolvableLinkType::Url))
                ->required(fn (Get $get): bool => $required && self::isType($get, $prefix, ResolvableLinkType::Url)),
            TextInput::make($targetField)
                ->key("{$prefix}_link_mail")
                ->label(__('Email'))
                ->email()
                ->visible(fn (Get $get): bool => self::isType($get, $prefix, ResolvableLinkType::Mail))
                ->required(fn (Get $get): bool => $required && self::isType($get, $prefix, ResolvableLinkType::Mail)),
            ...self::routeParameterFields($prefix),
        ];

        if ($visible !== null) {
            foreach ($fields as $field) {
                $field->visible($visible);
            }
        }

        return $fields;
    }

    /**
     * @param  list<string>  $prefixes
     */
    public static function configureAction(Action $action, array $prefixes): Action
    {
        return $action
            ->fillForm(function (array $arguments) use ($prefixes): array {
                $config = $arguments['config'] ?? [];

                if (! is_array($config)) {
                    return [];
                }

                return ResolvableLinkSupport::expandPrefixes($config, $prefixes);
            })
            ->mutateFormDataUsing(fn (array $data): array => ResolvableLinkSupport::compressPrefixes($data, $prefixes));
    }

    /** @return array<string, string> */
    protected static function sitePageOptions(): array
    {
        return SitePage::query()
            ->orderByDesc('is_home')
            ->orderBy('locale')
            ->orderBy('title')
            ->get()
            ->mapWithKeys(function (SitePage $page): array {
                $label = $page->title;

                if (SitePageResolver::localizationEnabled()) {
                    $label .= ' ('.strtoupper((string) $page->locale).')';
                }

                if ($page->is_home) {
                    $label .= ' ('.__('Home').')';
                } elseif (! $page->published) {
                    $label .= ' ('.__('Draft').')';
                }

                return [$page->slug => $label];
            })
            ->all();
    }

    /**
     * @param  array<string, string>  $extraTypes
     * @return array<string, string>
     */
    protected static function typeOptions(array $extraTypes): array
    {
        $options = collect(ResolvableLinkType::cases())
            ->mapWithKeys(fn (ResolvableLinkType $type): array => [$type->value => $type->getLabel()])
            ->all();

        return [...$options, ...$extraTypes];
    }

    protected static function isType(Get $get, string $prefix, ResolvableLinkType $expected): bool
    {
        $type = $get(ResolvableLinkSupport::typeField($prefix));

        if ($type instanceof ResolvableLinkType) {
            return $type === $expected;
        }

        return is_string($type) && ResolvableLinkType::tryFrom($type) === $expected;
    }

    /** @return array<int, mixed> */
    protected static function routeParameterFields(string $prefix): array
    {
        $fields = [];
        $targetField = ResolvableLinkSupport::targetField($prefix);
        $typeField = ResolvableLinkSupport::typeField($prefix);

        foreach (MenuRouteParameterField::parameterNames() as $parameterName) {
            $flatKey = ResolvableLinkSupport::routeParameterFlatKey($prefix, $parameterName);

            $fields[] = Select::make($flatKey)
                ->key("{$prefix}_{$flatKey}_select")
                ->label(fn (Get $get): string => MenuRouteParameterField::label($get($targetField), $parameterName))
                ->options(fn (Get $get): array => MenuRouteParameterField::options($get($targetField), $parameterName, $get))
                ->searchable()
                ->preload()
                ->live()
                ->hidden(fn (Get $get): bool => ! self::routeParameterVisible($get, $prefix, $parameterName) || MenuRouteParameterField::options($get($targetField), $parameterName, $get) === [])
                ->required(fn (Get $get): bool => self::routeParameterVisible($get, $prefix, $parameterName) && MenuRouteParameterField::options($get($targetField), $parameterName, $get) !== []);

            $fields[] = TextInput::make($flatKey)
                ->key("{$prefix}_{$flatKey}_text")
                ->label(fn (Get $get): string => MenuRouteParameterField::label($get($targetField), $parameterName))
                ->hidden(fn (Get $get): bool => ! self::routeParameterVisible($get, $prefix, $parameterName) || MenuRouteParameterField::options($get($targetField), $parameterName, $get) !== [])
                ->required(fn (Get $get): bool => self::routeParameterVisible($get, $prefix, $parameterName) && MenuRouteParameterField::options($get($targetField), $parameterName, $get) === []);
        }

        return $fields;
    }

    protected static function routeParameterVisible(Get $get, string $prefix, string $parameterName): bool
    {
        if (! self::isType($get, $prefix, ResolvableLinkType::Route)) {
            return false;
        }

        $routeName = $get(ResolvableLinkSupport::targetField($prefix));

        if (! is_string($routeName) || $routeName === '') {
            return false;
        }

        return in_array($parameterName, MenuRouteCatalog::requiredParameterNames($routeName), true);
    }
}
