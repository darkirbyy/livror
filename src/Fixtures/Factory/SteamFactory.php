<?php

namespace App\Fixtures\Factory;

use App\Entity\Steam;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class SteamFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Steam::class;
    }

    protected function defaults(): array
    {
        return [
            'id' => 1,
            'name' => mb_ucfirst(self::faker()->words(self::faker()->numberBetween(1, 5), true)),
        ];
    }
}
