<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DataSources\Contracts;

interface SuggestsMetaKeys
{
    /**
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    public function suggestMetaKeys(array $config = []): array;
}
