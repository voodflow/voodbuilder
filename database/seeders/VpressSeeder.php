<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\CookieConsent\Settings\CookieConsentSettings;
use Voodflow\Vpress\Enums\MenuItemType;
use Voodflow\Vpress\Models\NavigationMenu;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\DefaultHomeContent;
use Voodflow\Vpress\Support\DemoSubThemeContent;
use Voodflow\Vpress\Support\Navigation;
use Voodflow\Vpress\Support\SitePageSection;

class VpressSeeder extends Seeder
{
    public function run(): void
    {
        VpressSettings::query()->firstOrCreate(
            ['id' => 1],
            ['data' => VpressSettings::defaults()],
        );

        SitePage::query()->updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Privacy Policy',
                'layout' => 'page',
                'published' => true,
                'published_at' => now(),
                'is_home' => false,
            ],
        );

        SitePage::query()->updateOrCreate(
            ['slug' => 'cookie-policy'],
            [
                'title' => 'Cookie Policy',
                'layout' => 'page',
                'published' => true,
                'published_at' => now(),
                'is_home' => false,
            ],
        );

        $this->seedHomePage();
        $this->seedThemeDemoPages();

        $this->seedMenus();
        $this->seedCookieConsentSettings();
    }

    protected function seedHomePage(): void
    {
        SitePage::query()
            ->where('is_home', true)
            ->where('slug', '!=', 'home')
            ->update(['is_home' => false]);

        SitePage::query()->updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => __('vpress::home.page_title'),
                'layout' => 'home',
                'sub_theme' => 'default',
                'content' => DefaultHomeContent::content(),
                'published' => true,
                'published_at' => now(),
                'is_home' => true,
            ],
        );
    }

    protected function seedThemeDemoPages(): void
    {
        if (! Route::has('blog.index')) {
            $this->seedBlogSection();
        }

        $this->seedNewsSection();
    }

    protected function seedBlogSection(): void
    {
        $index = DemoSubThemeContent::blogIndexMeta();

        SitePage::query()->updateOrCreate(
            ['slug' => 'blog'],
            [
                'title' => $index['title'],
                'excerpt' => $index['excerpt'],
                'layout' => 'page',
                'sub_theme' => SitePageSection::BLOG,
                'section' => SitePageSection::BLOG,
                'section_home' => true,
                'content' => null,
                'published' => true,
                'published_at' => now(),
                'is_home' => false,
            ],
        );

        foreach (DemoSubThemeContent::blogPostDefinitions() as $definition) {
            $post = DemoSubThemeContent::blogPost($definition['key']);

            SitePage::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'layout' => 'page',
                    'sub_theme' => SitePageSection::BLOG,
                    'section' => SitePageSection::BLOG,
                    'section_home' => false,
                    'content' => $post['content'],
                    'published' => true,
                    'published_at' => now()->subDays($definition['days_ago']),
                    'is_home' => false,
                ],
            );
        }
    }

    protected function seedNewsSection(): void
    {
        $index = DemoSubThemeContent::newsIndexMeta();

        SitePage::query()->updateOrCreate(
            ['slug' => 'news'],
            [
                'title' => $index['title'],
                'excerpt' => $index['excerpt'],
                'layout' => 'page',
                'sub_theme' => SitePageSection::NEWS,
                'section' => SitePageSection::NEWS,
                'section_home' => true,
                'content' => null,
                'published' => true,
                'published_at' => now(),
                'is_home' => false,
            ],
        );

        foreach (DemoSubThemeContent::newsArticleDefinitions() as $definition) {
            $article = DemoSubThemeContent::newsArticle($definition['key']);

            SitePage::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'layout' => 'page',
                    'sub_theme' => SitePageSection::NEWS,
                    'section' => SitePageSection::NEWS,
                    'section_home' => false,
                    'content' => $article['content'],
                    'published' => true,
                    'published_at' => now()->subDays($definition['days_ago']),
                    'is_home' => false,
                ],
            );
        }
    }

    protected function seedMenus(): void
    {
        $main = NavigationMenu::query()->updateOrCreate(
            ['slug' => 'main'],
            ['name' => 'Main navigation'],
        );

        $main->items()->delete();
        $main->items()->createMany([
            [
                'label' => 'Home',
                'type' => MenuItemType::Route,
                'link' => 'home',
                'route_match' => 'home',
                'sort_order' => 0,
            ],
            ...$this->tutorialMenuItems(),
            ...$this->themeDemoMenuItems(),
        ]);

        $footer = NavigationMenu::query()->updateOrCreate(
            ['slug' => 'footer'],
            ['name' => 'Footer'],
        );

        $footer->items()->delete();
        $footer->items()->createMany([
            [
                'label' => 'Privacy Policy',
                'type' => MenuItemType::Page,
                'link' => 'privacy-policy',
                'sort_order' => 0,
            ],
            [
                'label' => 'Cookie Policy',
                'type' => MenuItemType::Page,
                'link' => 'cookie-policy',
                'sort_order' => 1,
            ],
        ]);

        Navigation::clearCache();
    }

    /** @return list<array<string, mixed>> */
    protected function tutorialMenuItems(): array
    {
        if (! Route::has('vtuts.index')) {
            return [];
        }

        return [
            [
                'label' => 'Tutorials',
                'type' => MenuItemType::Route,
                'link' => 'vtuts.index',
                'route_match' => 'vtuts.*',
                'sort_order' => 10,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    protected function themeDemoMenuItems(): array
    {
        $blogItem = Route::has('blog.index')
            ? [
                'label' => __('vpress::demo.blog.title'),
                'type' => MenuItemType::Route,
                'link' => 'blog.index',
                'route_match' => 'blog.*',
                'sort_order' => 20,
            ]
            : [
                'label' => __('vpress::demo.blog.title'),
                'type' => MenuItemType::Page,
                'link' => 'blog',
                'sort_order' => 20,
            ];

        return [
            $blogItem,
            [
                'label' => __('vpress::demo.news.title'),
                'type' => MenuItemType::Page,
                'link' => 'news',
                'sort_order' => 30,
            ],
        ];
    }

    protected function seedCookieConsentSettings(): void
    {
        if (! class_exists(CookieConsentSettings::class)) {
            return;
        }

        $settings = app(CookieConsentSettings::class);

        $settings->content_message = __('We use cookies to improve your experience. By continuing, you accept our cookie policy.');
        $settings->content_allow = __('Accept');
        $settings->content_deny = __('Decline');
        $settings->content_link = __('Learn more');
        $cookiePolicy = SitePage::query()->where('slug', 'cookie-policy')->first();
        $settings->content_href = $cookiePolicy?->getUrl();
        $settings->position = 'bottom';
        $settings->theme = 'block';

        $settings->save();
    }
}
