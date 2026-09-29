<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a public visitor lands on (or navigates to) a site path.
 *
 * Prefer {@see \Voodflow\Voodbuilder\Support\SiteVisit\SiteVisitWorkflowRunner}
 * for path/page/menu matching; this event is the audit/extension hook.
 *
 * @phpstan-type VisitPayload array{
 *     path: string,
 *     url: string,
 *     page_id: string|null,
 *     menu_item_id: string|null,
 *     visitor_key: string,
 *     user_id: string|null,
 *     locale: string|null,
 * }
 */
final class SitePathVisited
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  VisitPayload  $visit
     */
    public function __construct(
        public readonly array $visit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->visit;
    }
}
