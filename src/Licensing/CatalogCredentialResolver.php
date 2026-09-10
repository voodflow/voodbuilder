<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

use Illuminate\Support\Facades\Cache;
use Voodflow\Voodbuilder\Licensing\AnyStack\AnyStackEntitlementProvider;
use Voodflow\Voodbuilder\Support\Editor\EditorCommunityBlockCatalog;

/**
 * Resolves CDN catalog credentials for Elements / page-templates.
 *
 * Today: shared secret from .env (VOODBUILDER_CATALOG_TOKEN).
 * Tomorrow: prefer short-lived / per-licence credential from AnyStack snapshot
 * (`catalog_credentials.elements` in POST /entitlements response).
 */
final class CatalogCredentialResolver
{
    public static function elementsCredential(): ?string
    {
        $fromLicence = self::credentialFromAnyStackSnapshot('elements');

        if ($fromLicence !== null && $fromLicence !== '') {
            return $fromLicence;
        }

        $token = trim((string) config('voodbuilder-elements.catalog_token', ''));

        if ($token !== '') {
            return $token;
        }

        $token = trim((string) config('voodbuilder.page_templates.catalog_token', ''));

        return $token !== '' ? $token : null;
    }

    public static function pageTemplatesCredential(): ?string
    {
        $fromLicence = self::credentialFromAnyStackSnapshot('page_templates')
            ?? self::credentialFromAnyStackSnapshot('templates');

        if ($fromLicence !== null && $fromLicence !== '') {
            return $fromLicence;
        }

        $token = trim((string) (
            config('voodbuilder.page_templates.catalog_token')
            ?: config('voodbuilder-elements.catalog_token')
            ?: ''
        ));

        return $token !== '' ? $token : null;
    }

    public static function elementsReady(): bool
    {
        if (! EditorCommunityBlockCatalog::elementsLibraryActive()) {
            return false;
        }

        $url = trim((string) config('voodbuilder-elements.catalog_url', ''));

        return $url !== '' && self::elementsCredential() !== null;
    }

    public static function pageTemplatesReady(): bool
    {
        $url = trim((string) config('voodbuilder.page_templates.catalog_url', ''));

        return $url !== '' && self::pageTemplatesCredential() !== null;
    }

    /**
     * @return array{companion_active: bool, url_configured: bool, token_configured: bool, credential_source: string|null, ready: bool}
     */
    public static function elementsStatus(): array
    {
        $source = self::credentialSource('elements');

        return [
            'companion_active' => EditorCommunityBlockCatalog::elementsLibraryActive(),
            'url_configured' => trim((string) config('voodbuilder-elements.catalog_url', '')) !== '',
            'token_configured' => self::elementsCredential() !== null,
            'credential_source' => $source,
            'ready' => self::elementsReady(),
        ];
    }

    /**
     * @return array{url_configured: bool, token_configured: bool, credential_source: string|null, ready: bool}
     */
    public static function pageTemplatesStatus(): array
    {
        $source = self::credentialSource('page_templates');

        return [
            'url_configured' => trim((string) config('voodbuilder.page_templates.catalog_url', '')) !== '',
            'token_configured' => self::pageTemplatesCredential() !== null,
            'credential_source' => $source,
            'ready' => self::pageTemplatesReady(),
        ];
    }

    private static function credentialFromAnyStackSnapshot(string $product): ?string
    {
        /** @var array<string, mixed>|null $snapshot */
        $snapshot = Cache::get(AnyStackEntitlementProvider::SNAPSHOT_CACHE_KEY);

        if (! is_array($snapshot)) {
            return null;
        }

        $credentials = $snapshot['catalog_credentials'] ?? null;

        if (! is_array($credentials)) {
            return null;
        }

        $value = $credentials[$product] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function credentialSource(string $product): ?string
    {
        if ($product === 'elements' && self::credentialFromAnyStackSnapshot('elements') !== null) {
            return 'anystack';
        }

        if ($product === 'page_templates'
            && (self::credentialFromAnyStackSnapshot('page_templates') !== null
                || self::credentialFromAnyStackSnapshot('templates') !== null)
        ) {
            return 'anystack';
        }

        if ($product === 'elements' && trim((string) config('voodbuilder-elements.catalog_token', '')) !== '') {
            return 'env';
        }

        if ($product === 'page_templates' && trim((string) (
            config('voodbuilder.page_templates.catalog_token')
            ?: config('voodbuilder-elements.catalog_token')
            ?: ''
        )) !== '') {
            return 'env';
        }

        return null;
    }
}
