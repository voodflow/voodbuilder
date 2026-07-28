<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Tests\TestCase;

class GlobalTextTagsTest extends TestCase
{
    #[Test]
    public function it_replaces_current_year(): void
    {
        $this->assertSame(
            '© '.date('Y').' VoodBuilder',
            GlobalTextTags::replace('© {current_year} VoodBuilder'),
        );
    }

    #[Test]
    public function it_replaces_brand_and_site_tags_with_overrides(): void
    {
        $resolved = GlobalTextTags::replace(
            '{brand_name} · {site_name} · {site_url}',
            [
                'brand_name' => 'Acme CMS',
                'site_name' => 'Acme Site',
                'site_url' => 'https://example.test',
            ],
        );

        $this->assertSame('Acme CMS · Acme Site · https://example.test', $resolved);
    }

    #[Test]
    public function it_lists_known_keys(): void
    {
        $keys = GlobalTextTags::keys();

        $this->assertContains('current_year', $keys);
        $this->assertContains('brand_name', $keys);
        $this->assertContains('site_name', $keys);
        $this->assertContains('site_url', $keys);
        $this->assertContains('logged_username', $keys);
    }

    #[Test]
    public function replace_in_html_swaps_tokens_in_markup(): void
    {
        $html = '<p>© {current_year} {brand_name}</p>';

        $this->assertSame(
            '<p>© '.date('Y').' Studio</p>',
            GlobalTextTags::replaceInHtml($html, ['brand_name' => 'Studio']),
        );
    }

    #[Test]
    public function logged_username_is_empty_for_guests(): void
    {
        Auth::logout();

        $this->assertSame(
            'Ciao , il '.date('Y').' è il tuo anno!',
            GlobalTextTags::replace('Ciao {logged_username}, il {current_year} è il tuo anno!'),
        );
    }

    #[Test]
    public function logged_username_uses_authenticated_user_name(): void
    {
        $user = new GlobalTextTagsAuthUser([
            'id' => 1,
            'name' => 'Paolo',
            'email' => 'paolo@example.test',
        ]);

        Auth::login($user);

        $this->assertSame(
            'Ciao Paolo, il '.date('Y').' è il tuo anno!',
            GlobalTextTags::replace('Ciao {logged_username}, il {current_year} è il tuo anno!'),
        );
    }

    #[Test]
    public function logged_username_falls_back_to_username_attribute(): void
    {
        $user = new GlobalTextTagsAuthUser([
            'id' => 2,
            'name' => '',
            'username' => 'paolo_u',
            'email' => 'paolo@example.test',
        ]);

        Auth::login($user);

        $this->assertSame('paolo_u', GlobalTextTags::replace('{logged_username}'));
    }

    #[Test]
    public function logged_username_is_html_escaped(): void
    {
        Auth::logout();

        $this->assertSame(
            'Ciao &lt;b&gt;X&lt;/b&gt;',
            GlobalTextTags::replace('Ciao {logged_username}', [
                'logged_username' => '<b>X</b>',
            ]),
        );
    }

    #[Test]
    public function replace_in_html_works_on_rich_text_markup(): void
    {
        $html = '<div class="rich"><p>Ciao {logged_username}, il {current_year}!</p></div>';

        $this->assertSame(
            '<div class="rich"><p>Ciao Anna, il '.date('Y').'!</p></div>',
            GlobalTextTags::replaceInHtml($html, ['logged_username' => 'Anna']),
        );
    }
}

class GlobalTextTagsAuthUser extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $guarded = [];

    public $timestamps = false;
}
