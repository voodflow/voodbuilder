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

    public function test_it_migrates_legacy_voodbuilder_layouts_and_fallback_url(): void
    {
        File::ensureDirectoryExists(config_path());

        File::put(config_path('vtuts.php'), <<<'PHP'
<?php

return [
    'layout' => 'voodbuilder::layouts.page',
    'doc_layout' => 'voodbuilder::layouts.doc',
    'localization' => [
        'fallback_url' => null,
    ],
];
PHP);

        $this->assertTrue(ConfigureVtutsForVoodbuilder::apply());

        $contents = File::get(config_path('vtuts.php'));

        $this->assertStringContainsString("'layout' => 'vtuts::layouts.voodbuilder-page'", $contents);
        $this->assertStringContainsString("'doc_layout' => 'vtuts::layouts.voodbuilder'", $contents);
        $this->assertStringContainsString('LocaleSwitcher::currentPageUrlWithLocale', $contents);
    }
}
