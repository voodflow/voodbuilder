<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Contracts;

use RuntimeException;
use Voodflow\Voodbuilder\Contracts\RegistersConditions;
use Voodflow\Voodbuilder\Contracts\VoodBuilderModule;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class ModuleRegistryTest extends TestCase
{
    public function test_registry_is_bound_and_includes_history_pilot_by_default(): void
    {
        $registry = Voodbuilder::modules();

        $this->assertInstanceOf(ModuleRegistry::class, $registry);
        $this->assertTrue($registry->has('history'));
        $this->assertTrue($registry->isEnabled('history'));
        $this->assertTrue($registry->booted());
    }

    public function test_register_enable_disable_and_boot_lifecycle(): void
    {
        $calls = [];
        $registry = new ModuleRegistry($this->app);

        $module = new class($calls) extends AbstractVoodBuilderModule
        {
            /** @param array<int, string> $calls */
            public function __construct(private array &$calls) {}

            public function id(): string
            {
                return 'history';
            }

            public function name(): string
            {
                return 'History';
            }

            public function capabilities(): array
            {
                return ['editor.history'];
            }

            public function register(ModuleContext $context): void
            {
                $this->calls[] = 'register';
            }

            public function boot(ModuleContext $context): void
            {
                $this->calls[] = 'boot';
            }
        };

        $registry->register($module);
        $this->assertTrue($registry->has('history'));
        $this->assertTrue($registry->isEnabled('history'));

        $registry->disable('history');
        $registry->boot();
        $this->assertSame([], $calls);
        $this->assertTrue($registry->booted());

        $calls = [];
        $registry = new ModuleRegistry($this->app);
        $module = new class($calls) extends AbstractVoodBuilderModule
        {
            /** @param array<int, string> $calls */
            public function __construct(private array &$calls) {}

            public function id(): string
            {
                return 'history';
            }

            public function name(): string
            {
                return 'History';
            }

            public function register(ModuleContext $context): void
            {
                $this->calls[] = 'register';
            }

            public function boot(ModuleContext $context): void
            {
                $this->calls[] = 'boot';
            }
        };
        $registry->register($module);
        $registry->boot();

        $this->assertSame(['register', 'boot'], $calls);
    }

    public function test_boot_fails_when_dependency_disabled(): void
    {
        $registry = new ModuleRegistry($this->app);

        $registry->register(new class extends AbstractVoodBuilderModule
        {
            public function id(): string
            {
                return 'core-pages';
            }

            public function name(): string
            {
                return 'Pages';
            }
        }, enabled: false);

        $registry->register(new class extends AbstractVoodBuilderModule
        {
            public function id(): string
            {
                return 'history';
            }

            public function name(): string
            {
                return 'History';
            }

            public function dependencies(): array
            {
                return ['core-pages'];
            }
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires enabled dependency [core-pages]');

        $registry->boot();
    }

    public function test_contributors_filters_enabled_modules_by_interface(): void
    {
        $registry = new ModuleRegistry($this->app);

        $registry->register(new class extends AbstractVoodBuilderModule implements RegistersConditions
        {
            public function id(): string
            {
                return 'conditions';
            }

            public function name(): string
            {
                return 'Conditions';
            }

            public function conditionDefinitions(ModuleContext $context): array
            {
                return [['id' => 'locale', 'label' => 'Locale']];
            }
        });

        $registry->register(new class extends AbstractVoodBuilderModule
        {
            public function id(): string
            {
                return 'history';
            }

            public function name(): string
            {
                return 'History';
            }
        });

        $contributors = $registry->contributors(RegistersConditions::class);

        $this->assertCount(1, $contributors);
        $this->assertInstanceOf(VoodBuilderModule::class, $contributors->first());
        $this->assertSame('conditions', $contributors->first()->id());
    }
}
