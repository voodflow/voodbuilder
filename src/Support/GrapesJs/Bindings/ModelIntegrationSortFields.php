<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Bindings;

use Voodflow\Voodbuilder\Models\ModelIntegration;

final class ModelIntegrationSortFields
{
    private const SYSTEM_FIELDS = [
        'id' => 'sort_id',
        'created_at' => 'sort_created_at',
        'updated_at' => 'sort_updated_at',
    ];

    /**
     * @return list<array{id: string, label: string}>
     */
    public function forIntegration(ModelIntegration $integration): array
    {
        $fields = [];
        $seen = [];

        foreach (self::SYSTEM_FIELDS as $fieldId => $labelKey) {
            $fields[] = [
                'id' => $fieldId,
                'label' => __('voodbuilder::pro.bindings.'.$labelKey),
            ];
            $seen[$fieldId] = true;
        }

        foreach ($integration->getNormalizedFields()['essential'] as $key => $label) {
            if (is_int($key)) {
                $fieldId = (string) $label;
                $fieldLabel = str_replace('_', ' ', ucfirst($fieldId));
            } else {
                $fieldId = (string) $key;
                $fieldLabel = (string) $label;
            }

            if (isset($seen[$fieldId])) {
                continue;
            }

            $fields[] = [
                'id' => $fieldId,
                'label' => $fieldLabel,
            ];
            $seen[$fieldId] = true;
        }

        return $fields;
    }

    public function resolveColumn(ModelIntegration $integration, ?string $sort): string
    {
        $requested = trim((string) $sort);

        if ($requested === '') {
            return 'id';
        }

        foreach ($this->forIntegration($integration) as $field) {
            if ($field['id'] === $requested) {
                return $requested;
            }
        }

        return 'id';
    }

    public function resolveDirection(?string $direction): string
    {
        return strtolower(trim((string) $direction)) === 'asc' ? 'asc' : 'desc';
    }
}
