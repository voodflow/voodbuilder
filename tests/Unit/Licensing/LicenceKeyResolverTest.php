<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit\Licensing;

use Voodflow\Voodbuilder\Licensing\ComposerAnystackCredentialsReader;
use Voodflow\Voodbuilder\Licensing\LicenceKeyResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

final class LicenceKeyResolverTest extends TestCase
{
    private ?string $tempAuthPath = null;

    private string|false|null $previousComposerAuth = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousComposerAuth = getenv('COMPOSER_AUTH');
        putenv('COMPOSER_AUTH');
        unset($_ENV['COMPOSER_AUTH'], $_SERVER['COMPOSER_AUTH']);

        config([
            'voodbuilder.license.key' => '',
            'voodbuilder.license.composer_auth_extra_paths' => [],
            'voodbuilder.license.composer_repository_hosts' => [],
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->tempAuthPath !== null && is_file($this->tempAuthPath)) {
            unlink($this->tempAuthPath);
        }

        if ($this->previousComposerAuth === false || $this->previousComposerAuth === null) {
            putenv('COMPOSER_AUTH');
            unset($_ENV['COMPOSER_AUTH'], $_SERVER['COMPOSER_AUTH']);
        } else {
            putenv('COMPOSER_AUTH='.$this->previousComposerAuth);
            $_ENV['COMPOSER_AUTH'] = $this->previousComposerAuth;
            $_SERVER['COMPOSER_AUTH'] = $this->previousComposerAuth;
        }

        parent::tearDown();
    }

    public function test_prefers_explicit_config_key_over_auth_json(): void
    {
        $this->writeAuthJson([
            'http-basic' => [
                'voodbuilder-developer.composer.sh' => [
                    'username' => 'license',
                    'password' => 'from-auth-json',
                ],
            ],
        ]);

        config(['voodbuilder.license.key' => 'from-config']);

        $this->assertSame('from-config', LicenceKeyResolver::resolve());
    }

    public function test_reads_license_key_from_auth_json_via_voodbuilder_reader(): void
    {
        $this->writeAuthJson([
            'http-basic' => [
                'voodbuilder-developer.composer.sh' => [
                    'username' => 'license',
                    'password' => 'dev-license-key',
                ],
            ],
        ]);

        $this->assertSame('dev-license-key', LicenceKeyResolver::resolve());
        $this->assertSame(
            'dev-license-key',
            ComposerAnystackCredentialsReader::read()['key'] ?? null,
        );
    }

    public function test_strips_fingerprint_suffix_from_password(): void
    {
        $this->writeAuthJson([
            'http-basic' => [
                'voodbuilder-developer.composer.sh' => [
                    'username' => 'license',
                    'password' => 'the-key:machine-fingerprint',
                ],
            ],
        ]);

        $this->assertSame('the-key', LicenceKeyResolver::resolve());
    }

    public function test_prefers_agency_host_over_developer_when_both_present(): void
    {
        $this->writeAuthJson([
            'http-basic' => [
                'voodbuilder-developer.composer.sh' => [
                    'username' => 'license',
                    'password' => 'developer-key',
                ],
                'voodbuilder-agency.composer.sh' => [
                    'username' => 'license',
                    'password' => 'agency-key',
                ],
            ],
        ]);

        $creds = ComposerAnystackCredentialsReader::read();

        $this->assertNotNull($creds);
        $this->assertSame('agency-key', $creds['key']);
        $this->assertSame('agency-key', LicenceKeyResolver::resolve());
    }

    public function test_returns_empty_string_when_no_key_is_available(): void
    {
        $this->assertSame('', LicenceKeyResolver::resolve());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeAuthJson(array $payload): void
    {
        $this->tempAuthPath = tempnam(sys_get_temp_dir(), 'vb-auth-');
        $this->assertNotFalse($this->tempAuthPath);

        file_put_contents(
            $this->tempAuthPath,
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );

        config([
            'voodbuilder.license.composer_auth_extra_paths' => [$this->tempAuthPath],
        ]);
    }
}
