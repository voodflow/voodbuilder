<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Concerns;

use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

trait ConfiguresTranslatableLocaleField
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  class-string<Resource>  $resourceClass
     */
    protected static function translatableLocaleSelect(Select $field, string $modelClass, string $resourceClass): Select
    {
        return $field
            ->live()
            ->afterStateUpdated(function (?string $state, ?Model $record, mixed $livewire) use ($modelClass, $resourceClass): void {
                if ($record === null || blank($state) || blank($record->getAttribute('translation_group_id'))) {
                    return;
                }

                if ($state === $record->getAttribute('locale')) {
                    return;
                }

                $sibling = $modelClass::query()
                    ->where('translation_group_id', $record->getAttribute('translation_group_id'))
                    ->where('locale', $state)
                    ->first();

                if ($sibling === null || ! is_object($livewire) || ! method_exists($livewire, 'redirect')) {
                    return;
                }

                $livewire->redirect($resourceClass::getUrl('edit', ['record' => $sibling]));
            });
    }
}
