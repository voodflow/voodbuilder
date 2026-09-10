<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing\Contracts;

/**
 * Remote licence transport. Implementations must never be called from public render paths.
 */
interface LicenceClient
{
    /**
     * @return array{
     *     edition: string,
     *     active: bool,
     *     capabilities: list<string>,
     *     identifier?: string|null,
     *     expires_at?: string|null,
     *     message?: string|null,
     *     catalog_credentials?: array<string, string>|null,
     * }
     *
     * @throws LicenceClientException
     */
    public function fetchEntitlements(string $licenceKey): array;
}
