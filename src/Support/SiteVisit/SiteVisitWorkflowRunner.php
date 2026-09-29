<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\SiteVisit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Voodflow\Voodbuilder\Events\SitePathVisited;
use Voodflow\Voodbuilder\Support\VoodflowIntegration;

/**
 * Queue Voodflow executions for Site Visit trigger nodes that match a landing.
 */
final class SiteVisitWorkflowRunner
{
    public const VISITOR_COOKIE = 'vpopups_vid';

    private const DEDUPE_SECONDS = 45;

    /**
     * @param  array{
     *     path?: string,
     *     url?: string,
     *     page_id?: string|null,
     *     menu_item_id?: string|null,
     *     visitor_key?: string,
     *     user_id?: string|null,
     *     locale?: string|null,
     * }  $visit
     * @return list<array{workflow_id: string|int, execution_id: string|int, node_id: string}>
     */
    public function handle(array $visit): array
    {
        $normalized = $this->normalizeVisit($visit);

        if ($normalized['visitor_key'] === '' || $normalized['path'] === '') {
            return [];
        }

        event(new SitePathVisited($normalized));

        if (! VoodflowIntegration::enabled()) {
            return [];
        }

        $workflowClass = 'Voodflow\\Voodflow\\Models\\Workflow';
        $nodeClass = 'Voodflow\\Voodflow\\Models\\Node';
        $executionClass = 'Voodflow\\Voodflow\\Models\\Execution';
        $jobClass = 'Voodflow\\Voodflow\\Jobs\\ExecuteWorkflowJob';

        if (! class_exists($workflowClass) || ! class_exists($nodeClass) || ! class_exists($executionClass) || ! class_exists($jobClass)) {
            return [];
        }

        $queued = [];

        try {
            $activeWorkflowIds = $workflowClass::query()
                ->where('status', 'active')
                ->pluck('id');

            if ($activeWorkflowIds->isEmpty()) {
                Log::debug('Voodbuilder: site visit — no active workflows');

                return [];
            }

            $nodes = $nodeClass::query()
                ->where('type', 'site_visit_trigger_node')
                ->whereIn('workflow_id', $activeWorkflowIds)
                ->orderBy('id')
                ->get();

            if ($nodes->isEmpty()) {
                Log::debug('Voodbuilder: site visit — no site_visit_trigger_node on active workflows', [
                    'path' => $normalized['path'],
                ]);

                return [];
            }
            foreach ($nodes as $node) {
                if (! $node instanceof Model) {
                    continue;
                }

                $config = is_array($node->getAttribute('config')) ? $node->getAttribute('config') : [];

                if (! SiteVisitMatcher::matches($config, $normalized)) {
                    continue;
                }

                $workflowId = $node->getAttribute('workflow_id');
                $nodeId = (string) $node->getAttribute('node_id');
                $dedupeKey = sprintf(
                    'voodbuilder:site_visit:%s:%s:%s:%s',
                    $normalized['visitor_key'],
                    $normalized['path'],
                    (string) $workflowId,
                    $nodeId,
                );

                if (! Cache::add($dedupeKey, 1, self::DEDUPE_SECONDS)) {
                    continue;
                }

                $executionId = $this->queueExecution(
                    $workflowClass,
                    $executionClass,
                    $jobClass,
                    $workflowId,
                    $nodeId,
                    $normalized,
                );

                if ($executionId === null) {
                    continue;
                }

                $queued[] = [
                    'workflow_id' => $workflowId,
                    'execution_id' => $executionId,
                    'node_id' => $nodeId,
                ];
            }
        } catch (Throwable $e) {
            Log::error('Voodbuilder: site visit runner failed', [
                'path' => $normalized['path'],
                'error' => $e->getMessage(),
            ]);
        }

        return $queued;
    }

    /**
     * @param  array<string, mixed>  $visit
     * @return array{
     *     path: string,
     *     url: string,
     *     page_id: string|null,
     *     menu_item_id: string|null,
     *     visitor_key: string,
     *     user_id: string|null,
     *     locale: string|null,
     * }
     */
    private function normalizeVisit(array $visit): array
    {
        $path = SiteVisitPath::normalize((string) ($visit['path'] ?? ''));
        $pageId = trim((string) ($visit['page_id'] ?? ''));
        $menuItemId = trim((string) ($visit['menu_item_id'] ?? ''));
        $visitorKey = trim((string) ($visit['visitor_key'] ?? ''));
        $userId = trim((string) ($visit['user_id'] ?? ''));
        $locale = trim((string) ($visit['locale'] ?? ''));
        $url = trim((string) ($visit['url'] ?? ''));

        return [
            'path' => $path,
            'url' => $url !== '' ? $url : $path,
            'page_id' => $pageId !== '' ? $pageId : null,
            'menu_item_id' => $menuItemId !== '' ? $menuItemId : null,
            'visitor_key' => $visitorKey,
            'user_id' => $userId !== '' ? $userId : null,
            'locale' => $locale !== '' ? $locale : null,
        ];
    }

    /**
     * @param  class-string  $workflowClass
     * @param  class-string  $executionClass
     * @param  class-string  $jobClass
     * @param  array<string, mixed>  $visit
     */
    private function queueExecution(
        string $workflowClass,
        string $executionClass,
        string $jobClass,
        mixed $workflowId,
        string $nodeId,
        array $visit,
    ): string|int|null {
        try {
            /** @var Model|null $workflow */
            $workflow = $workflowClass::query()
                ->whereKey($workflowId)
                ->where('status', 'active')
                ->first();

            if ($workflow === null) {
                return null;
            }

            $context = array_merge($visit, [
                '_start_node_id' => $nodeId,
                'trigger' => 'site_visit',
                'data' => $visit,
            ]);

            $executionTable = config('voodflow.table_names.executions', 'voodflow_executions');
            $executionData = [
                'workflow_id' => $workflow->getKey(),
                'trigger_type' => SitePathVisited::class,
                'status' => 'pending',
                'input_context' => $context,
            ];

            if (
                (bool) config('voodflow.tenancy.enabled')
                && Schema::hasColumn($executionTable, 'tenant_id')
                && filled($workflow->getAttribute('tenant_id'))
            ) {
                $executionData['tenant_id'] = (string) $workflow->getAttribute('tenant_id');
            }

            if (method_exists($workflow, 'getCurrentVersion')) {
                $executionData['workflow_version'] = $workflow->getCurrentVersion();
            }

            if (method_exists($workflow, 'versions')) {
                $latestVersion = $workflow->versions()->orderByDesc('version')->first();
                if ($latestVersion) {
                    $executionData['workflow_version_id'] = $latestVersion->getKey();
                }
            }

            $execution = $executionClass::query()->create($executionData);
            $jobClass::dispatch($execution->getKey(), $nodeId);

            Log::info('Voodbuilder: site visit workflow queued', [
                'workflow_id' => $workflow->getKey(),
                'execution_id' => $execution->getKey(),
                'node_id' => $nodeId,
                'path' => $visit['path'] ?? null,
            ]);

            return $execution->getKey();
        } catch (Throwable $e) {
            Log::error('Voodbuilder: site visit queue failed', [
                'workflow_id' => $workflowId,
                'node_id' => $nodeId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
