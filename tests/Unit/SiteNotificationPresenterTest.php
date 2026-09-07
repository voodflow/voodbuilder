<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\SiteNotificationPresenter;
use Voodflow\Voodbuilder\Tests\TestCase;

class SiteNotificationPresenterTest extends TestCase
{
    #[Test]
    public function it_presents_filament_database_notifications_with_title_and_body(): void
    {
        $notification = new DatabaseNotification([
            'id' => (string) Str::uuid(),
            'type' => FilamentDatabaseNotification::class,
            'data' => [
                'actions' => [],
                'body' => 'Il tutorial «Installazione» (IT) è stato tradotto automaticamente.',
                'color' => null,
                'duration' => 'persistent',
                'icon' => 'heroicon-o-bell',
                'iconColor' => 'info',
                'status' => 'info',
                'title' => 'Traduzione pronta per revisione',
                'view' => null,
                'viewData' => [],
                'format' => 'filament',
            ],
        ]);

        $presented = SiteNotificationPresenter::present($notification);

        $this->assertSame('Traduzione pronta per revisione', $presented['title']);
        $this->assertStringContainsString('Installazione', $presented['body']);
        $this->assertStringNotContainsString('"actions"', $presented['body']);
    }
}
