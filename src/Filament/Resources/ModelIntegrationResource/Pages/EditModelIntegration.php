<?php

namespace Voodflow\Vpress\Filament\Resources\ModelIntegrationResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Voodflow\Vpress\Filament\Resources\ModelIntegrationResource;

class EditModelIntegration extends EditRecord
{
    protected static string $resource = ModelIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $essential = $data['fields']['essential'] ?? [];
        if (is_array($essential)) {
            $normalizedEssential = [];
            foreach ($essential as $item) {
                if (is_array($item) && isset($item['field'])) {
                    $normalizedEssential[] = $item['field'];
                } elseif (is_string($item)) {
                    $normalizedEssential[] = $item;
                }
            }
            $data['fields']['essential'] = $normalizedEssential;
        }

        $relations = $data['fields']['relations'] ?? [];
        foreach ($relations as $i => $relation) {
            $relationFields = $relation['fields'] ?? [];
            if (is_array($relationFields)) {
                $normalizedFields = [];
                foreach ($relationFields as $item) {
                    if (is_array($item) && isset($item['field'])) {
                        $normalizedFields[] = $item['field'];
                    } elseif (is_string($item)) {
                        $normalizedFields[] = $item;
                    }
                }
                $data['fields']['relations'][$i]['fields'] = $normalizedFields;
            }

            if (empty($relation['nested_relations']) && ! empty($relation['expand'])) {
                $items = [];
                foreach ($relation['expand'] as $rel) {
                    $fields = [];
                    foreach ($relation['expand_fields'] ?? [] as $ef) {
                        if (($ef['relation'] ?? null) === $rel) {
                            $fields = $ef['fields'] ?? [];

                            break;
                        }
                    }
                    $items[] = ['relation' => $rel, 'fields' => $fields];
                }
                $data['fields']['relations'][$i]['nested_relations'] = $items;
            }
            unset(
                $data['fields']['relations'][$i]['expand'],
                $data['fields']['relations'][$i]['expand_fields']
            );
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $relations = $data['fields']['relations'] ?? [];
        foreach ($relations as $i => $relation) {
            unset(
                $data['fields']['relations'][$i]['expand'],
                $data['fields']['relations'][$i]['expand_fields']
            );
        }

        return $data;
    }
}
