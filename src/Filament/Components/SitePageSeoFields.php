<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-page SEO overrides stored on the morph `seo` relation (ralphjsmit/laravel-seo).
 */
final class SitePageSeoFields
{
    /**
     * @return list<Group>
     */
    public static function make(): array
    {
        $only = ['title', 'description', 'robots', 'canonical_url'];

        return [
            Group::make([
                TextInput::make('title')
                    ->label(__('voodbuilder::admin.fields.seo_title'))
                    ->helperText(__('voodbuilder::admin.helpers.seo_title'))
                    ->maxLength(70)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label(__('voodbuilder::admin.fields.seo_description'))
                    ->helperText(__('voodbuilder::admin.helpers.seo_description'))
                    ->rows(3)
                    ->maxLength(200)
                    ->columnSpanFull(),

                Select::make('robots')
                    ->label(__('voodbuilder::admin.fields.seo_robots'))
                    ->options([
                        'index, follow' => 'Index, Follow',
                        'index, nofollow' => 'Index, Nofollow',
                        'noindex, follow' => 'Noindex, Follow',
                        'noindex, nofollow' => 'Noindex, Nofollow',
                    ])
                    ->native(false)
                    ->helperText(__('voodbuilder::admin.helpers.seo_robots'))
                    ->columnSpanFull(),

                TextInput::make('canonical_url')
                    ->label(__('voodbuilder::admin.fields.seo_canonical'))
                    ->url()
                    ->helperText(__('voodbuilder::admin.helpers.seo_canonical'))
                    ->columnSpanFull(),
            ])
                ->afterStateHydrated(function (Group $component, ?Model $record) use ($only): void {
                    $component->getChildSchema()->fill(
                        $record?->seo?->only($only) ?: []
                    );
                })
                ->statePath('seo')
                ->dehydrated(false)
                ->saveRelationshipsUsing(function (Model $record, array $state) use ($only): void {
                    $state = collect($state)
                        ->only($only)
                        ->map(fn (mixed $value): mixed => filled($value) ? $value : null)
                        ->all();

                    if ($record->seo && $record->seo->exists) {
                        $record->seo->update($state);
                    } else {
                        $record->seo()->create($state);
                    }
                }),
        ];
    }
}
