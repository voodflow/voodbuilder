<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\File;
use Voodflow\Voodbuilder\Support\ConfigureVtutsForVoodbuilder;
use Voodflow\Voodbuilder\Tests\TestCase;

class ConfigureVtutsForVoodbuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(config_path('vtuts.php'));

        parent::tearDown();
    }

    public function test_it_patches_vtuts_layouts_and_fallback_url(): void
    {
        File::ensureDirectoryExists(config_path());

        File::put(config_path('vtuts.php'), <<<'PHP'
<?php

return [
    'layout' => 'vtuts::layouts.page',
    'doc_layout' => 'vtuts::layouts.doc',
    'localization' => [
        'fallback_url' => null,
    ],
];
PHP);

        $this->assertTrue(ConfigureVtutsForVoodbuilder::apply());

        $contents = File::get(config_path('vtuts.php'));

        $this->assertStringContainsString("'layout' => 'voodbuilder::layouts.page'", $contents);
        $this->assertStringContainsString("'doc_layout' => 'voodbuilder::layouts.doc'", $contents);
        $this->assertStringContainsString('LocaleSwitcher::currentPageUrlWithLocale', $contents);
    }
}
