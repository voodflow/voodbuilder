<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Licensing;

/**
 * Reads the Anystack Composer HTTP Basic password from auth.json / COMPOSER_AUTH.
 *
 * Password format: "license-key" or "license-key:fingerprint" (Anystack docs).
 * Self-contained in VoodBuilder so licensing works without voodflow/voodflow.
 */
final class ComposerAnystackCredentialsReader
{
    /**
     * Preferred *.composer.sh hosts (Agency before Developer before Core).
     *
     * @var list<string>
     */
    public const DEFAULT_REPOSITORY_HOSTS = [
        'voodbuilder-agency.composer.sh',
        'voodbuilder-developer.composer.sh',
        'voodbuilder.composer.sh',
    ];

    /**
     * @return array{key: string, fingerprint: ?string}|null
     */
    public static function read(): ?array
    {
        $fromEnv = self::parseComposerAuthJson(getenv('COMPOSER_AUTH') ?: '');
        $hosts = self::preferredRepositoryHosts();

        if ($hosts !== []) {
            $parsed = self::extractForHosts($fromEnv, $hosts);
            if ($parsed !== null) {
                return $parsed;
            }

            foreach (self::candidateAuthFilePaths() as $path) {
                if (! is_readable($path)) {
                    continue;
                }

                $json = json_decode((string) file_get_contents($path), true);
                if (! is_array($json)) {
                    continue;
                }

                $parsed = self::extractForHosts($json, $hosts);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        $parsed = self::discoverAnyComposerShHost($fromEnv);
        if ($parsed !== null) {
            return $parsed;
        }

        foreach (self::candidateAuthFilePaths() as $path) {
            if (! is_readable($path)) {
                continue;
            }

            $json = json_decode((string) file_get_contents($path), true);
            if (! is_array($json)) {
                continue;
            }

            $parsed = self::discoverAnyComposerShHost($json);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function preferredRepositoryHosts(): array
    {
        $fromConfig = config('voodbuilder.license.composer_repository_hosts', []);
        $fromConfig = is_array($fromConfig) ? $fromConfig : [];

        $merged = array_merge(
            self::DEFAULT_REPOSITORY_HOSTS,
            array_values(array_filter(array_map('strval', $fromConfig))),
        );

        return array_values(array_unique($merged));
    }

    /**
     * @param  list<string>  $hosts
     * @return array{key: string, fingerprint: ?string}|null
     */
    public static function extractForHosts(array $composerAuthJson, array $hosts): ?array
    {
        $basic = $composerAuthJson['http-basic'] ?? [];
        if (! is_array($basic)) {
            return null;
        }

        foreach ($hosts as $host) {
            $password = $basic[$host]['password'] ?? null;
            if (! is_string($password) || $password === '') {
                continue;
            }

            return self::parsePassword($password);
        }

        return null;
    }

    /**
     * @return array{key: string, fingerprint: ?string}|null
     */
    public static function discoverAnyComposerShHost(array $composerAuthJson): ?array
    {
        $basic = $composerAuthJson['http-basic'] ?? [];
        if (! is_array($basic)) {
            return null;
        }

        foreach ($basic as $host => $creds) {
            if (! is_string($host) || ! str_ends_with(strtolower($host), '.composer.sh')) {
                continue;
            }

            if (! is_array($creds)) {
                continue;
            }

            $password = $creds['password'] ?? null;
            if (! is_string($password) || $password === '') {
                continue;
            }

            $parsed = self::parsePassword($password);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @return array{key: string, fingerprint: ?string}|null
     */
    private static function parsePassword(string $password): ?array
    {
        $parts = explode(':', $password, 2);
        $key = trim($parts[0]);

        if ($key === '') {
            return null;
        }

        $fingerprint = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : null;

        return ['key' => $key, 'fingerprint' => $fingerprint];
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseComposerAuthJson(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string>
     */
    public static function candidateAuthFilePaths(): array
    {
        $extra = config('voodbuilder.license.composer_auth_extra_paths', []);
        $paths = [];

        foreach (is_array($extra) ? $extra : [] as $p) {
            if (! is_string($p) || $p === '') {
                continue;
            }

            $paths[] = str_starts_with($p, DIRECTORY_SEPARATOR) ? $p : base_path($p);
        }

        $paths[] = base_path('auth.json');
        $paths[] = dirname(base_path()).DIRECTORY_SEPARATOR.'auth.json';

        $composerHome = getenv('COMPOSER_HOME') ?: '';
        if (is_string($composerHome) && $composerHome !== '') {
            $paths[] = rtrim($composerHome, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'auth.json';
        }

        $home = getenv('HOME') ?: '';
        if (is_string($home) && $home !== '') {
            $paths[] = $home.DIRECTORY_SEPARATOR.'.composer'.DIRECTORY_SEPARATOR.'auth.json';
            $paths[] = $home.DIRECTORY_SEPARATOR.'.config'.DIRECTORY_SEPARATOR.'composer'.DIRECTORY_SEPARATOR.'auth.json';
        }

        return array_values(array_unique($paths));
    }
}
