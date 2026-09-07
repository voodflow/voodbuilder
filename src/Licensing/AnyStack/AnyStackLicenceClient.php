<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing\AnyStack;

use Illuminate\Support\Facades\Http;
use Voodflow\Voodbuilder\Licensing\Contracts\LicenceClient;
use Voodflow\Voodbuilder\Licensing\Contracts\LicenceClientException;
use Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix;

/**
 * HTTP client for AnyStack-style entitlement endpoints.
 * Isolated behind LicenceClient — swap provider without touching Core callers.
 */
final class AnyStackLicenceClient implements LicenceClient
{
    public function __construct(
        private readonly string $endpoint,
        private readonly int $timeoutSeconds = 5,
    ) {}

    public function fetchEntitlements(string $licenceKey): array
    {
        if ($this->endpoint === '') {
            throw LicenceClientException::unreachable('AnyStack endpoint is not configured.');
        }

        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->acceptJson()
                ->post(rtrim($this->endpoint, '/').'/entitlements', [
                    'licence_key' => $licenceKey,
                    'product' => 'voodbuilder',
                ]);
        } catch (\Throwable $e) {
            throw LicenceClientException::unreachable('AnyStack request failed: '.$e->getMessage(), $e);
        }

        if (! $response->successful()) {
            throw LicenceClientException::unreachable(
                'AnyStack returned HTTP '.$response->status(),
            );
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        $edition = strtolower((string) ($payload['edition'] ?? EditionCapabilityMatrix::EDITION_COMMUNITY));

        if (! in_array($edition, [
            EditionCapabilityMatrix::EDITION_COMMUNITY,
            EditionCapabilityMatrix::EDITION_PROFESSIONAL,
            EditionCapabilityMatrix::EDITION_AGENCY,
        ], true)) {
            $edition = EditionCapabilityMatrix::EDITION_COMMUNITY;
        }

        $capabilities = $payload['capabilities'] ?? EditionCapabilityMatrix::forEdition($edition);

        if (! is_array($capabilities)) {
            $capabilities = EditionCapabilityMatrix::forEdition($edition);
        }

        return [
            'edition' => $edition,
            'active' => (bool) ($payload['active'] ?? true),
            'capabilities' => array_values(array_map('strval', $capabilities)),
            'identifier' => isset($payload['identifier']) ? (string) $payload['identifier'] : $licenceKey,
            'expires_at' => isset($payload['expires_at']) ? (string) $payload['expires_at'] : null,
            'message' => isset($payload['message']) ? (string) $payload['message'] : null,
        ];
    }
}
