<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Nodes\SiteVisitTriggerNode;

use Voodflow\Voodflow\Execution\ExecutionContext;
use Voodflow\Voodflow\Execution\ExecutionResult;
use Voodflow\Voodflow\Nodes\AbstractNode;
use Voodflow\Voodflow\Support\NodeDefinitionBuilder;
use Voodflow\Voodbuilder\Support\SiteVisit\SiteVisitTargetOptions;

/**
 * Start a workflow when a visitor lands on a site page, menu URL, or custom path.
 */
final class SiteVisitTriggerNode extends AbstractNode
{
    public static function type(): string
    {
        return 'site_visit_trigger_node';
    }

    public static function name(): string
    {
        return 'Site Visit';
    }

    public static function defaultConfig(): array
    {
        return [
            'label' => 'Site Visit',
            'description' => '',
            'match_type' => 'page',
            'page_id' => '',
            'menu_item_id' => '',
            'path' => '',
        ];
    }

    public static function metadata(): array
    {
        return array_merge(parent::metadata(), [
            'category' => 'integration',
            'function' => 'trigger',
            'group' => 'VoodBuilder',
            'color' => 'sky',
            'icon' => 'heroicon-o-map-pin',
            'description' => 'Start when a visitor lands on a page, menu link, or path.',
            'positioning' => [
                'input' => false,
                'output' => true,
            ],
        ]);
    }

    public function execute(ExecutionContext $context): ExecutionResult
    {
        return ExecutionResult::success($context->input);
    }

    public function validate(array $config): array
    {
        $matchType = strtolower(trim((string) ($config['match_type'] ?? 'any')));

        return match ($matchType) {
            'page' => trim((string) ($config['page_id'] ?? '')) === ''
                ? ['Select a site page']
                : [],
            'menu_item' => trim((string) ($config['menu_item_id'] ?? '')) === ''
                ? ['Select a menu item']
                : [],
            'path' => trim((string) ($config['path'] ?? '')) === ''
                ? ['Enter a path (e.g. /courses/laravel)']
                : [],
            'any', '' => [],
            default => ['Select a valid match type'],
        };
    }

    /**
     * @return array<string, array{label: string, type: string}>
     */
    public function getAvailableFields(array $config): array
    {
        return [
            'visitor_key' => ['label' => 'Visitor key', 'type' => 'string'],
            'path' => ['label' => 'Path', 'type' => 'string'],
            'url' => ['label' => 'URL', 'type' => 'string'],
            'page_id' => ['label' => 'Page id', 'type' => 'string'],
            'menu_item_id' => ['label' => 'Menu item id', 'type' => 'string'],
            'user_id' => ['label' => 'User id', 'type' => 'string'],
            'locale' => ['label' => 'Locale', 'type' => 'string'],
        ];
    }

    public static function definition(): array
    {
        return NodeDefinitionBuilder::create()
            ->addSelectField(
                'match_type',
                'Match',
                SiteVisitTargetOptions::matchTypes(),
                default: 'page',
                required: true,
            )
            ->addSelectField(
                'page_id',
                'Site page',
                SiteVisitTargetOptions::pages(),
                required: false,
                placeholder: '— Select a page —',
            )
            ->withVisibleWhen('match_type', 'page')
            ->addSelectField(
                'menu_item_id',
                'Menu item',
                SiteVisitTargetOptions::menuItems(),
                required: false,
                placeholder: '— Select a menu item —',
            )
            ->withVisibleWhen('match_type', 'menu_item')
            ->addTextField(
                'path',
                'Path',
                placeholder: '/courses/laravel',
                description: 'Exact public path (leading slash). Use for non-VoodBuilder routes.',
            )
            ->withVisibleWhen('match_type', 'path')
            ->setOutputSchema([
                'type' => 'object',
                'properties' => [
                    'visitor_key' => ['type' => 'string'],
                    'path' => ['type' => 'string'],
                    'url' => ['type' => 'string'],
                    'page_id' => ['type' => ['string', 'null']],
                    'menu_item_id' => ['type' => ['string', 'null']],
                    'user_id' => ['type' => ['string', 'null']],
                    'locale' => ['type' => ['string', 'null']],
                ],
            ])
            ->build();
    }
}
