<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Models\PageTemplate;
use Voodflow\Voodbuilder\Support\Editor\EditorPageTemplateBundle;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorPageTemplateBundleTest extends TestCase
{
    public function test_build_export_payload_includes_generator_metadata(): void
    {
        $payload = EditorPageTemplateBundle::buildExportPayload([]);

        $this->assertSame(EditorPageTemplateBundle::FORMAT_VERSION, $payload['format_version']);
        $this->assertSame('voodbuilder-page-templates', $payload['format']);
        $this->assertSame('voodbuilder', $payload['generator']['name']);
        $this->assertSame(VoodbuilderPackageVersion::current(), $payload['generator']['version']);
        $this->assertSame([], $payload['templates']);
    }

    public function test_serialize_template_omits_null_description(): void
    {
        $template = new PageTemplate([
            'name' => 'Landing',
            'category' => 'Hero',
            'description' => null,
            'html' => '<section>Landing</section>',
            'css' => null,
            'js' => null,
        ]);

        $entry = EditorPageTemplateBundle::serializeTemplate($template);

        $this->assertArrayNotHasKey('description', $entry);
        $this->assertSame('Landing', $entry['name']);
        $this->assertSame('Hero', $entry['category']);
    }

    public function test_extract_templates_accepts_single_entry_payload(): void
    {
        $entries = EditorPageTemplateBundle::extractTemplates([
            'name' => 'Home',
            'html' => '<section>Home</section>',
            'css' => '.home { color: red; }',
        ]);

        $this->assertCount(1, $entries);
        $this->assertSame('Home', $entries[0]['name']);
    }

    public function test_rejects_import_from_unsupported_format(): void
    {
        $this->expectException(ValidationException::class);

        EditorPageTemplateBundle::assertImportable([
            'format' => 'foreign-format',
            'format_version' => 1,
        ]);
    }

    public function test_rejects_import_from_newer_format_version(): void
    {
        $this->expectException(ValidationException::class);

        EditorPageTemplateBundle::assertImportable([
            'format' => EditorPageTemplateBundle::FORMAT,
            'format_version' => EditorPageTemplateBundle::FORMAT_VERSION + 1,
        ]);
    }
}
