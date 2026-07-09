<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPageTemplateRemoteImporter;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsPageTemplateRemoteImporterTest extends TestCase
{
    public function test_rejects_non_https_urls(): void
    {
        $this->expectException(ValidationException::class);

        GrapesJsPageTemplateRemoteImporter::normalizeUrl('http://example.com/template.json');
    }

    public function test_rejects_localhost_urls(): void
    {
        $this->expectException(ValidationException::class);

        GrapesJsPageTemplateRemoteImporter::normalizeUrl('https://localhost/template.json');
    }

    public function test_accepts_public_https_urls(): void
    {
        $url = GrapesJsPageTemplateRemoteImporter::normalizeUrl('https://cdn.example.com/templates/landing.json');

        $this->assertSame('https://cdn.example.com/templates/landing.json', $url);
    }
}
