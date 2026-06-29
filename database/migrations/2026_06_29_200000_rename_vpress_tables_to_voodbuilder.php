<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $tables = [
        'vpress_menu_items' => 'voodbuilder_menu_items',
        'vpress_menus' => 'voodbuilder_menus',
        'vpress_settings' => 'voodbuilder_settings',
        'vpress_model_integrations' => 'voodbuilder_model_integrations',
    ];

    public function up(): void
    {
        foreach ($this->tables as $from => $to) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables, true) as $from => $to) {
            if (Schema::hasTable($to) && ! Schema::hasTable($from)) {
                Schema::rename($to, $from);
            }
        }
    }
};
